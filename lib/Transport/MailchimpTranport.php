<?php

namespace FriendsOfRedaxo\SymfonyMailer\Helper;

use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Exception\TransportException;

class MailchimpTransport extends AbstractTransport
{
    private string $apiKey;
    private string $serverPrefix;

    public function __construct(string $apiKey)
    {
        parent::__construct();
        
        $this->apiKey = $apiKey;
        // Extract server prefix from API key (last characters after '-')
        $this->serverPrefix = substr(strrchr($apiKey, '-'), 1);
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        
        try {
            $response = $this->callMailchimpApi($email);
            if (!$response['status'] === 'sent') {
                throw new TransportException(
                    sprintf('Unable to send email via Mailchimp: %s', $response['message'] ?? 'Unknown error')
                );
            }
        } catch (\Exception $e) {
            throw new TransportException(
                'Could not reach Mailchimp API: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    private function callMailchimpApi(Email $email): array
    {
        $endpoint = sprintf(
            'https://%s.api.mailchimp.com/3.0/messages/send',
            $this->serverPrefix
        );

        $message = [
            'message' => [
                'html' => $email->getHtmlBody(),
                'text' => $email->getTextBody(),
                'subject' => $email->getSubject(),
                'from_email' => $email->getFrom()[0]->getAddress(),
                'from_name' => $email->getFrom()[0]->getName(),
                'to' => array_map(function($address) {
                    return [
                        'email' => $address->getAddress(),
                        'name' => $address->getName(),
                        'type' => 'to'
                    ];
                }, $email->getTo())
            ],
        ];

        // Füge CC und BCC hinzu wenn vorhanden
        if ($cc = $email->getCc()) {
            $message['message']['to'] = array_merge(
                $message['message']['to'],
                array_map(function($address) {
                    return [
                        'email' => $address->getAddress(),
                        'name' => $address->getName(),
                        'type' => 'cc'
                    ];
                }, $cc)
            );
        }

        if ($bcc = $email->getBcc()) {
            $message['message']['to'] = array_merge(
                $message['message']['to'],
                array_map(function($address) {
                    return [
                        'email' => $address->getAddress(),
                        'name' => $address->getName(),
                        'type' => 'bcc'
                    ];
                }, $bcc)
            );
        }

        // Füge Anhänge hinzu
        if ($attachments = $email->getAttachments()) {
            $message['message']['attachments'] = array_map(function($attachment) {
                return [
                    'type' => $attachment->getContentType(),
                    'name' => $attachment->getFilename(),
                    'content' => base64_encode($attachment->getBody())
                ];
            }, $attachments);
        }

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_USERPWD, 'user:' . $this->apiKey);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new TransportException(
                sprintf('Mailchimp API returned HTTP code %d', $httpCode)
            );
        }

        return json_decode($response, true);
    }

    public function __toString(): string
    {
        return 'mailchimp';
    }
}
