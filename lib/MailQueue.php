<?php

namespace FriendsOfRedaxo\SymfonyMailer;

use rex;
use rex_sql;
use rex_addon;
use rex_logger;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Part\DataPart;
use DateTime;

/**
 * Mail queue management class for centralized email processing
 */
class MailQueue
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    public const PRIORITY_LOW = 1;
    public const PRIORITY_NORMAL = 3;
    public const PRIORITY_HIGH = 5;

    private string $tableName;

    public function __construct()
    {
        $this->tableName = rex::getTable('symfony_mailer_queue');
    }

    /**
     * Add an email to the queue
     *
     * @param Email $email The email to queue
     * @param array $options Queue options (priority, scheduled_at, max_attempts)
     * @return int The queue ID
     */
    public function addEmail(Email $email, array $options = []): int
    {
        $sql = rex_sql::factory();
        
        // Prepare email data
        $fromAddresses = [];
        foreach ($email->getFrom() as $address) {
            $fromAddresses[] = $address->toString();
        }
        
        $toAddresses = [];
        foreach ($email->getTo() as $address) {
            $toAddresses[] = $address->toString();
        }
        
        $ccAddresses = [];
        foreach ($email->getCc() as $address) {
            $ccAddresses[] = $address->toString();
        }
        
        $bccAddresses = [];
        foreach ($email->getBcc() as $address) {
            $bccAddresses[] = $address->toString();
        }
        
        $replyTo = [];
        foreach ($email->getReplyTo() as $address) {
            $replyTo[] = $address->toString();
        }
        
        // Serialize attachments
        $attachments = [];
        foreach ($email->getAttachments() as $attachment) {
            $attachments[] = [
                'filename' => $attachment->getFilename(),
                'content_type' => $attachment->getContentType(),
                'body' => base64_encode($attachment->getBody())
            ];
        }
        
        // Serialize headers
        $headers = [];
        foreach ($email->getHeaders()->all() as $header) {
            $headers[$header->getName()] = $header->getBodyAsString();
        }
        
        $now = date('Y-m-d H:i:s');
        $scheduledAt = $options['scheduled_at'] ?? null;
        if ($scheduledAt instanceof DateTime) {
            $scheduledAt = $scheduledAt->format('Y-m-d H:i:s');
        }
        
        $sql->setTable($this->tableName);
        $sql->setValue('from_address', $fromAddresses[0] ?? '');
        $sql->setValue('from_name', $email->getFrom()[0]->getName() ?? '');
        $sql->setValue('to_addresses', json_encode($toAddresses));
        $sql->setValue('cc_addresses', json_encode($ccAddresses));
        $sql->setValue('bcc_addresses', json_encode($bccAddresses));
        $sql->setValue('reply_to', implode(', ', $replyTo));
        $sql->setValue('subject', $email->getSubject() ?? '');
        $sql->setValue('body_text', $email->getTextBody() ?? '');
        $sql->setValue('body_html', $email->getHtmlBody() ?? '');
        $sql->setValue('attachments', json_encode($attachments));
        $sql->setValue('headers', json_encode($headers));
        $sql->setValue('priority', $options['priority'] ?? self::PRIORITY_NORMAL);
        $sql->setValue('scheduled_at', $scheduledAt);
        $sql->setValue('status', self::STATUS_PENDING);
        $sql->setValue('attempts', 0);
        $sql->setValue('max_attempts', $options['max_attempts'] ?? 3);
        $sql->setValue('created_at', $now);
        $sql->setValue('updated_at', $now);
        
        $sql->insert();
        
        return (int) $sql->getLastId();
    }

    /**
     * Get pending emails from the queue
     *
     * @param int $limit Maximum number of emails to retrieve
     * @param int $priority Minimum priority (optional)
     * @return array Array of queue items
     */
    public function getPendingEmails(int $limit = 10, int $priority = null): array
    {
        $sql = rex_sql::factory();
        
        $where = "status = 'pending' AND (scheduled_at IS NULL OR scheduled_at <= NOW())";
        $params = [];
        
        if ($priority !== null) {
            $where .= " AND priority >= ?";
            $params[] = $priority;
        }
        
        $query = "SELECT * FROM {$this->tableName} WHERE {$where} ORDER BY priority DESC, created_at ASC LIMIT ?";
        $params[] = $limit;
        
        $sql->setQuery($query, $params);
        
        $emails = [];
        while ($sql->hasNext()) {
            $emails[] = $sql->getRow();
            $sql->next();
        }
        
        return $emails;
    }

    /**
     * Process a single email from the queue
     *
     * @param int $queueId The queue item ID
     * @return bool True if successful, false otherwise
     */
    public function processEmail(int $queueId): bool
    {
        $sql = rex_sql::factory();
        
        // Get the email data
        $sql->setQuery("SELECT * FROM {$this->tableName} WHERE id = ?", [$queueId]);
        if (!$sql->getRows()) {
            return false;
        }
        
        $row = $sql->getRow();
        
        // Update status to processing
        $this->updateStatus($queueId, self::STATUS_PROCESSING);
        
        try {
            // Create email object
            $email = $this->createEmailFromQueueData($row);
            
            // Send email using the mailer
            $mailer = new RexSymfonyMailer();
            $success = $mailer->send($email);
            
            if ($success) {
                $this->updateStatus($queueId, self::STATUS_SENT, null, date('Y-m-d H:i:s'));
                return true;
            } else {
                $this->handleFailure($queueId, 'Failed to send email');
                return false;
            }
            
        } catch (\Exception $e) {
            $this->handleFailure($queueId, $e->getMessage());
            return false;
        }
    }

    /**
     * Process multiple emails from the queue
     *
     * @param int $batchSize Number of emails to process
     * @return array Processing results
     */
    public function processBatch(int $batchSize = 10): array
    {
        $addon = rex_addon::get('symfony_mailer');
        $queueEnabled = $addon->getConfig('queue_enabled', false);
        
        if (!$queueEnabled) {
            return ['processed' => 0, 'errors' => ['Queue processing is disabled']];
        }
        
        $emails = $this->getPendingEmails($batchSize);
        $processed = 0;
        $errors = [];
        
        foreach ($emails as $emailData) {
            try {
                if ($this->processEmail($emailData['id'])) {
                    $processed++;
                } else {
                    $errors[] = "Failed to process email ID {$emailData['id']}";
                }
            } catch (\Exception $e) {
                $errors[] = "Error processing email ID {$emailData['id']}: " . $e->getMessage();
            }
        }
        
        return ['processed' => $processed, 'errors' => $errors];
    }

    /**
     * Get queue statistics
     *
     * @return array Queue statistics
     */
    public function getStats(): array
    {
        $sql = rex_sql::factory();
        
        $stats = [];
        
        // Count by status
        $sql->setQuery("SELECT status, COUNT(*) as count FROM {$this->tableName} GROUP BY status");
        while ($sql->hasNext()) {
            $stats[$sql->getValue('status')] = (int) $sql->getValue('count');
            $sql->next();
        }
        
        // Total count
        $sql->setQuery("SELECT COUNT(*) as total FROM {$this->tableName}");
        $stats['total'] = (int) $sql->getValue('total');
        
        // Scheduled for future
        $sql->setQuery("SELECT COUNT(*) as scheduled FROM {$this->tableName} WHERE scheduled_at > NOW()");
        $stats['scheduled'] = (int) $sql->getValue('scheduled');
        
        return $stats;
    }

    /**
     * Cancel a queued email
     *
     * @param int $queueId Queue item ID
     * @return bool Success status
     */
    public function cancelEmail(int $queueId): bool
    {
        return $this->updateStatus($queueId, self::STATUS_CANCELLED);
    }

    /**
     * Retry a failed email
     *
     * @param int $queueId Queue item ID
     * @return bool Success status
     */
    public function retryEmail(int $queueId): bool
    {
        $sql = rex_sql::factory();
        
        $sql->setTable($this->tableName);
        $sql->setWhere('id = ?', [$queueId]);
        $sql->setValue('status', self::STATUS_PENDING);
        $sql->setValue('error_message', null);
        $sql->setValue('updated_at', date('Y-m-d H:i:s'));
        
        return $sql->update() > 0;
    }

    /**
     * Clean up old processed emails
     *
     * @param int $olderThanDays Remove emails older than this many days
     * @return int Number of emails removed
     */
    public function cleanup(int $olderThanDays = 30): int
    {
        $sql = rex_sql::factory();
        
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$olderThanDays} days"));
        
        $sql->setQuery(
            "DELETE FROM {$this->tableName} WHERE status IN ('sent', 'cancelled') AND updated_at < ?",
            [$cutoffDate]
        );
        
        return $sql->getRows();
    }

    /**
     * Create an Email object from queue data
     *
     * @param array $queueData Queue item data
     * @return Email Email object
     */
    private function createEmailFromQueueData(array $queueData): Email
    {
        $email = new Email();
        
        // Set sender
        if ($queueData['from_name']) {
            $email->from(new Address($queueData['from_address'], $queueData['from_name']));
        } else {
            $email->from($queueData['from_address']);
        }
        
        // Set recipients
        $toAddresses = json_decode($queueData['to_addresses'], true) ?? [];
        foreach ($toAddresses as $address) {
            $email->addTo($address);
        }
        
        $ccAddresses = json_decode($queueData['cc_addresses'], true) ?? [];
        foreach ($ccAddresses as $address) {
            $email->addCc($address);
        }
        
        $bccAddresses = json_decode($queueData['bcc_addresses'], true) ?? [];
        foreach ($bccAddresses as $address) {
            $email->addBcc($address);
        }
        
        // Set reply-to
        if ($queueData['reply_to']) {
            $email->replyTo($queueData['reply_to']);
        }
        
        // Set subject and body
        $email->subject($queueData['subject'] ?? '');
        
        if ($queueData['body_text']) {
            $email->text($queueData['body_text']);
        }
        
        if ($queueData['body_html']) {
            $email->html($queueData['body_html']);
        }
        
        // Add attachments
        $attachments = json_decode($queueData['attachments'], true) ?? [];
        foreach ($attachments as $attachment) {
            $datapart = new DataPart(
                base64_decode($attachment['body']),
                $attachment['filename'],
                $attachment['content_type']
            );
            $email->addPart($datapart);
        }
        
        // Add custom headers
        $headers = json_decode($queueData['headers'], true) ?? [];
        foreach ($headers as $name => $value) {
            if (!in_array(strtolower($name), ['from', 'to', 'cc', 'bcc', 'subject', 'reply-to'])) {
                $email->getHeaders()->addTextHeader($name, $value);
            }
        }
        
        return $email;
    }

    /**
     * Update email status
     *
     * @param int $queueId Queue item ID
     * @param string $status New status
     * @param string|null $errorMessage Error message (optional)
     * @param string|null $processedAt Processed timestamp (optional)
     * @return bool Success status
     */
    private function updateStatus(int $queueId, string $status, ?string $errorMessage = null, ?string $processedAt = null): bool
    {
        $sql = rex_sql::factory();
        
        $sql->setTable($this->tableName);
        $sql->setWhere('id = ?', [$queueId]);
        $sql->setValue('status', $status);
        $sql->setValue('updated_at', date('Y-m-d H:i:s'));
        
        if ($errorMessage !== null) {
            $sql->setValue('error_message', $errorMessage);
        }
        
        if ($processedAt !== null) {
            $sql->setValue('processed_at', $processedAt);
        }
        
        return $sql->update() > 0;
    }

    /**
     * Handle email sending failure
     *
     * @param int $queueId Queue item ID
     * @param string $errorMessage Error message
     */
    private function handleFailure(int $queueId, string $errorMessage): void
    {
        $sql = rex_sql::factory();
        
        // Get current attempts
        $sql->setQuery("SELECT attempts, max_attempts FROM {$this->tableName} WHERE id = ?", [$queueId]);
        $attempts = (int) $sql->getValue('attempts');
        $maxAttempts = (int) $sql->getValue('max_attempts');
        
        $attempts++;
        
        $sql->setTable($this->tableName);
        $sql->setWhere('id = ?', [$queueId]);
        $sql->setValue('attempts', $attempts);
        $sql->setValue('error_message', $errorMessage);
        $sql->setValue('updated_at', date('Y-m-d H:i:s'));
        
        if ($attempts >= $maxAttempts) {
            $sql->setValue('status', self::STATUS_FAILED);
        } else {
            $sql->setValue('status', self::STATUS_PENDING);
        }
        
        $sql->update();
        
        // Log the error
        rex_logger::log('symfony_mailer', 'error', "Queue email {$queueId} failed (attempt {$attempts}/{$maxAttempts}): {$errorMessage}");
    }
}