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

        // FIXED: Manueller POST-String statt http_build_query()
        // Grund: http_build_query() scheint auf diesem Server nicht zu funktionieren
        $postString = 'client_id=' . urlencode($this->clientId) . 
                     '&client_secret=' . urlencode($this->clientSecret) . 
                     '&scope=' . urlencode('https://graph.microsoft.com/.default') . 
                     '&grant_type=client_credentials';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postString);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch) || $httpCode >= 400) {
            $curlError = curl_error($ch);
            curl_close($ch);
            throw new TransportException("HTTP $httpCode: $response" . ($curlError ? " (CURL: $curlError)" : ""));
        }
        
        curl_close($ch);

        $data = json_decode($response, true);
        
        if (!isset($data['access_token'])) {
            throw new TransportException(
                'Failed to obtain access token: ' . ($response ?: 'Unknown error')
            );
        }

        $this->accessToken = $data['access_token'];
        $this->tokenExpiry = time() + ($data['expires_in'] ?? 3600);
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

    public function testConnection(): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'details' => []
        ];

        try {
            // Schritt 1: Token anfordern
            $result['details']['step1'] = 'Requesting access token...';
            $this->ensureValidAccessToken();
            $result['details']['step1'] = '✅ Access token obtained successfully';
            
            // Schritt 2: Graph API Test-Aufruf
            $result['details']['step2'] = 'Testing Graph API connection...';
            $endpoint = 'https://graph.microsoft.com/v1.0/users'; // Application Token geeignet
            
            $ch = curl_init($endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->accessToken
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            $result['details']['http_code'] = $httpCode;
            $result['details']['curl_error'] = $curlError;
            
            if ($curlError) {
                $result['message'] = 'Network error: ' . $curlError;
                return $result;
            }
            
            if ($httpCode === 200) {
                $userData = json_decode($response, true);
                $result['success'] = true;
                $result['message'] = 'Microsoft Graph connection successful';
                $result['details']['step2'] = '✅ Graph API accessible';
                $result['details']['users_count'] = isset($userData['value']) ? count($userData['value']) : 0;
            } else {
                $errorData = json_decode($response, true);
                $result['message'] = 'Graph API error (HTTP ' . $httpCode . ')';
                $result['details']['step2'] = '❌ Graph API error';
                $result['details']['error_response'] = $errorData;
                
                // Spezifische Fehlermeldungen
                if ($httpCode === 401) {
                    $result['message'] .= ': Authentication failed - check credentials';
                } elseif ($httpCode === 403) {
                    $result['message'] .= ': Insufficient permissions - admin consent required';
                } elseif (isset($errorData['error']['message'])) {
                    $result['message'] .= ': ' . $errorData['error']['message'];
                }
            }
            
        } catch (\Exception $e) {
            $result['message'] = 'Exception: ' . $e->getMessage();
            $result['details']['exception'] = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ];
        }

        return $result;
    }

    /**
     * Test connection with detailed diagnostics (static method for easy access)
     *
     * ACHTUNG: Diese Methode nutzt jetzt rex_socket statt cURL für alle HTTP-Requests.
     */
    public static function diagnosticTest(string $tenantId, string $clientId, string $clientSecret): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'steps' => []
        ];

        // Schritt 1: Parameter validieren
        $result['steps']['validation'] = [
            'status' => 'running',
            'message' => 'Validating parameters...'
        ];

        if (empty($tenantId) || empty($clientId) || empty($clientSecret)) {
            $result['steps']['validation'] = [
                'status' => 'failed',
                'message' => '❌ Missing required parameters (tenant_id, client_id, client_secret)'
            ];
            $result['message'] = 'Configuration incomplete';
            return $result;
        }

        $result['steps']['validation'] = [
            'status' => 'passed',
            'message' => '✅ All parameters provided'
        ];

        // Schritt 2: Token-Endpoint testen (rex_socket)
        $result['steps']['token_request'] = [
            'status' => 'running',
            'message' => 'Requesting access token...'
        ];

        $endpoint = "https://login.microsoftonline.com/$tenantId/oauth2/v2.0/token";
        $postString = 'client_id=' . urlencode(trim($clientId)) .
                     '&client_secret=' . urlencode(trim($clientSecret)) .
                     '&scope=' . urlencode('https://graph.microsoft.com/.default') .
                     '&grant_type=client_credentials';
        try {
            $socket = \rex_socket::factoryUrl($endpoint);
            $socket->addHeader('Content-Type', 'application/x-www-form-urlencoded');
            $socket->addHeader('Accept', 'application/json');
            $socket->addHeader('User-Agent', 'REDAXO-SymfonyMailer/1.1.0');
            $socket->setTimeout(30);
            $response = $socket->doPost($postString);
            $httpCode = $response->getStatusCode();
            $body = $response->getBody();
        } catch (\rex_socket_exception $e) {
            $result['steps']['token_request'] = [
                'status' => 'failed',
                'message' => '❌ rex_socket error: ' . $e->getMessage()
            ];
            $result['message'] = 'Network connectivity issue (rex_socket)';
            return $result;
        }

        if ($httpCode !== 200) {
            $errorData = json_decode($body, true);
            $errorMsg = $errorData['error_description'] ?? $errorData['error'] ?? 'Unknown error';
            $result['steps']['token_request'] = [
                'status' => 'failed',
                'message' => "❌ HTTP $httpCode: $errorMsg",
                'http_code' => $httpCode,
                'response' => $errorData,
                'request_data' => [
                    'endpoint' => $endpoint,
                    'client_id' => trim($clientId),
                    'client_secret_length' => strlen(trim($clientSecret)),
                    'tenant_id' => trim($tenantId)
                ]
            ];
            $result['message'] = 'Token request failed (rex_socket)';
            return $result;
        }

        $tokenData = json_decode($body, true);
        $result['steps']['token_request'] = [
            'status' => 'passed',
            'message' => '✅ Access token received',
            'token_type' => $tokenData['token_type'] ?? '',
            'expires_in' => $tokenData['expires_in'] ?? ''
        ];

        // Schritt 3: Graph API testen (rex_socket)
        $result['steps']['graph_api'] = [
            'status' => 'running',
            'message' => 'Testing Graph API access...'
        ];
        $accessToken = $tokenData['access_token'] ?? '';
        if (!$accessToken) {
            $result['steps']['graph_api'] = [
                'status' => 'failed',
                'message' => '❌ No access token received.'
            ];
            $result['message'] = 'No access token received.';
            return $result;
        }
        $graphEndpoint = 'https://graph.microsoft.com/v1.0/users';
        try {
            $socket = \rex_socket::factoryUrl($graphEndpoint);
            $socket->addHeader('Authorization', 'Bearer ' . $accessToken);
            $socket->addHeader('Accept', 'application/json');
            $socket->addHeader('User-Agent', 'REDAXO-SymfonyMailer/1.1.0');
            $socket->setTimeout(30);
            $response = $socket->doGet();
            $httpCode = $response->getStatusCode();
            $body = $response->getBody();
        } catch (\rex_socket_exception $e) {
            $result['steps']['graph_api'] = [
                'status' => 'failed',
                'message' => '❌ rex_socket error: ' . $e->getMessage()
            ];
            $result['message'] = 'Graph API connectivity issue (rex_socket)';
            return $result;
        }
        if ($httpCode === 200) {
            $userData = json_decode($body, true);
            $result['steps']['graph_api'] = [
                'status' => 'passed',
                'message' => '✅ Microsoft Graph API erreichbar und Token gültig',
                'users_count' => isset($userData['value']) ? count($userData['value']) : 0
            ];
            $result['success'] = true;
            $result['message'] = 'Microsoft Graph Verbindung erfolgreich.';
        } elseif ($httpCode === 403) {
            $result['steps']['graph_api'] = [
                'status' => 'warning',
                'message' => '⚠️ Microsoft Graph API erreichbar, aber keine User-Liste erlaubt. Mailversand ist trotzdem möglich.'
            ];
            $result['success'] = true;
            $result['message'] = 'Microsoft Graph Verbindung erfolgreich (User-Liste nicht erlaubt, aber Mailversand möglich).';
        } else {
            $errorData = json_decode($body, true);
            $result['steps']['graph_api'] = [
                'status' => 'failed',
                'message' => "❌ Graph API error (HTTP $httpCode)",
                'http_code' => $httpCode
            ];
            $result['message'] = "Graph API access failed (HTTP $httpCode)";
        }
        return $result;
    }

    public function __toString(): string
    {
        return 'microsoft-graph';
    }
}
