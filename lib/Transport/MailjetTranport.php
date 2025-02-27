<?php

namespace FriendsOfRedaxo\SymfonyMailer\Helper;

use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Exception\TransportException;

class MailjetTransport extends AbstractTransport
{
    private string $apiKey;
    private string $apiSecret;

    public function __construct(string $apiKey, string $apiSecret)
    {
        parent::__construct();
        
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        
        try {
            $response = $this->callMailjetApi($email);
            if (!isset($response['Messages'][0]['Status']) || $response['Messages'][0]['Status'] !== 'success') {
                throw new TransportException(
                    sprintf('Unable to send email via Mailjet: %s', 
                        $response['Messages'][0]['Errors'][0]['ErrorMessage'] ?? 'Unknown error')
                );
            }
        } catch (\Exception $e) {
            throw new TransportException(
                'Could not reach Mailjet API: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    private function callMailjetApi(Email $email): array
    {
        $endpoint = 'https://api.mailjet.com/v3.1/send';

        $message = [
            'Messages' => [
                [
                    'From' => [
                        'Email' => $email->getFrom()[0]->getAddress(),
                        'Name' => $email->getFrom()[0]->getName()
                    ],
                    'To' => array_map(function($address) {
                        return [
                            'Email' => $address->getAddress(),
                            'Name' => $address->getName()
                        ];
                    }, $email->getTo()),
                    'Subject' => $email->getSubject(),
                    'TextPart' => $email->getTextBody(),
                    'HTMLPart' => $email->getHtmlBody()
                ]
            ]
        ];

        // CC und BCC hinzufügen wenn vorhanden
        if ($cc = $email->getCc()) {
            $message['Messages'][0]['Cc'] = array_map(function($address) {
                return [
                    'Email' => $address->getAddress(),
                    'Name' => $address->getName()
                ];
            }, $cc);
        }

        if ($bcc = $email->getBcc()) {
            $message['Messages'][0]['Bcc'] = array_map(function($address) {
                return [
                    'Email' => $address->getAddress(),
                    'Name' => $address->getName()
                ];
            }, $bcc);
        }

        // Anhänge hinzufügen
        if ($attachments = $email->getAttachments()) {
            $message['Messages'][0]['Attachments'] = array_map(function($attachment) {
                return [
                    'ContentType' => $attachment->getContentType(),
                    'Filename' => $attachment->getFilename(),
                    'Base64Content' => base64_encode($attachment->getBody())
                ];
            }, $attachments);
        }

        // Reply-To hinzufügen wenn vorhanden
        if ($replyTo = $email->getReplyTo()) {
            $message['Messages'][0]['ReplyTo'] = [
                'Email' => $replyTo[0]->getAddress(),
                'Name' => $replyTo[0]->getName()
            ];
        }

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_USERPWD, $this->apiKey . ':' . $this->apiSecret);
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
                sprintf('Mailjet API returned HTTP code %d', $httpCode)
            );
        }

        return json_decode($response, true);
    }

    public function __toString(): string
    {
        return 'mailjet';
    }
}
