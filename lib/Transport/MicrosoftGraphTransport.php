<?php

namespace FriendsOfRedaxo\SymfonyMailer\Helper;

use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Exception\TransportException;

class MicrosoftGraphTransport extends AbstractTransport
{
    private string $tenantId;
    private string $clientId;
    private string $clientSecret;
    private ?string $accessToken = null;
    private ?int $tokenExpiry = null;

    public function __construct(string $tenantId, string $clientId, string $clientSecret)
    {
        parent::__construct();
        
        $this->tenantId = $tenantId;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        
        try {
            $this->ensureValidAccessToken();
            $response = $this->sendEmailViaGraph($email);
            
            if (!isset($response['id'])) {
                throw new TransportException(
                    sprintf('Unable to send email via Microsoft Graph: %s', 
                        $response['error']['message'] ?? 'Unknown error')
                );
            }
        } catch (\Exception $e) {
            throw new TransportException(
                'Could not reach Microsoft Graph API: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    private function ensureValidAccessToken(): void
    {
        if ($this->accessToken && $this->tokenExpiry && time() < $this->tokenExpiry - 300) {
            return; // Token ist noch 5 Minuten gültig
        }

        $this->requestAccessToken();
    }

    private function requestAccessToken(): void
    {
        $endpoint = sprintf(
            'https://login.microsoftonline.com/%s/oauth2/v2.0/token',
            $this->tenantId
        );

        $postData = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'scope' => 'https://graph.microsoft.com/.default'
        ];

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new TransportException(
                sprintf('Microsoft Graph authentication failed with HTTP code %d', $httpCode)
            );
        }

        $data = json_decode($response, true);
        
        if (!isset($data['access_token'])) {
            throw new TransportException(
                'Failed to obtain access token from Microsoft Graph'
            );
        }

        $this->accessToken = $data['access_token'];
        $this->tokenExpiry = time() + $data['expires_in'];
    }

    private function sendEmailViaGraph(Email $email): array
    {
        // Bestimme den Sender (User Principal Name oder Email)
        $fromAddress = $email->getFrom()[0]->getAddress();
        
        $endpoint = sprintf(
            'https://graph.microsoft.com/v1.0/users/%s/sendMail',
            urlencode($fromAddress)
        );

        $message = [
            'message' => [
                'subject' => $email->getSubject(),
                'body' => [
                    'contentType' => $email->getHtmlBody() ? 'HTML' : 'Text',
                    'content' => $email->getHtmlBody() ?: $email->getTextBody()
                ],
                'from' => [
                    'emailAddress' => [
                        'address' => $email->getFrom()[0]->getAddress(),
                        'name' => $email->getFrom()[0]->getName() ?: $email->getFrom()[0]->getAddress()
                    ]
                ],
                'toRecipients' => array_map(function($address) {
                    return [
                        'emailAddress' => [
                            'address' => $address->getAddress(),
                            'name' => $address->getName() ?: $address->getAddress()
                        ]
                    ];
                }, $email->getTo())
            ]
        ];

        // CC-Empfänger hinzufügen
        if ($cc = $email->getCc()) {
            $message['message']['ccRecipients'] = array_map(function($address) {
                return [
                    'emailAddress' => [
                        'address' => $address->getAddress(),
                        'name' => $address->getName() ?: $address->getAddress()
                    ]
                ];
            }, $cc);
        }

        // BCC-Empfänger hinzufügen
        if ($bcc = $email->getBcc()) {
            $message['message']['bccRecipients'] = array_map(function($address) {
                return [
                    'emailAddress' => [
                        'address' => $address->getAddress(),
                        'name' => $address->getName() ?: $address->getAddress()
                    ]
                ];
            }, $bcc);
        }

        // Reply-To hinzufügen
        if ($replyTo = $email->getReplyTo()) {
            $message['message']['replyTo'] = array_map(function($address) {
                return [
                    'emailAddress' => [
                        'address' => $address->getAddress(),
                        'name' => $address->getName() ?: $address->getAddress()
                    ]
                ];
            }, $replyTo);
        }

        // Anhänge hinzufügen
        if ($attachments = $email->getAttachments()) {
            $message['message']['attachments'] = array_map(function($attachment) {
                return [
                    '@odata.type' => '#microsoft.graph.fileAttachment',
                    'name' => $attachment->getFilename() ?: 'attachment',
                    'contentType' => $attachment->getContentType(),
                    'contentBytes' => base64_encode($attachment->getBody())
                ];
            }, $attachments);
        }

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 202) { // Microsoft Graph returns 202 Accepted for successful send
            $errorData = json_decode($response, true);
            throw new TransportException(
                sprintf('Microsoft Graph API returned HTTP code %d: %s', 
                    $httpCode, 
                    $errorData['error']['message'] ?? $response)
            );
        }

        // Bei erfolgreichem Versand gibt Graph eine 202 Accepted zurück
        // Wir simulieren eine ID für Kompatibilität
        return ['id' => uniqid('graph_', true)];
    }

    public function testConnection(): bool
    {
        try {
            $this->ensureValidAccessToken();
            
            // Test API-Aufruf um die Verbindung zu testen
            $endpoint = 'https://graph.microsoft.com/v1.0/me';
            
            $ch = curl_init($endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->accessToken
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            return $httpCode === 200;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function __toString(): string
    {
        return 'microsoft-graph';
    }
}
