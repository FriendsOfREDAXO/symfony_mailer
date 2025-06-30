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
            $endpoint = 'https://graph.microsoft.com/v1.0/me';
            
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
                $result['details']['user_info'] = [
                    'displayName' => $userData['displayName'] ?? 'Unknown',
                    'userPrincipalName' => $userData['userPrincipalName'] ?? 'Unknown',
                    'id' => $userData['id'] ?? 'Unknown'
                ];
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

        // Schritt 2: Token-Endpoint testen
        $result['steps']['token_request'] = [
            'status' => 'running',
            'message' => 'Requesting access token...'
        ];

        $endpoint = "https://login.microsoftonline.com/$tenantId/oauth2/v2.0/token";
        
        // Sicherstellen, dass alle Werte trimmed und nicht leer sind
        $trimmedClientId = trim($clientId);
        $trimmedClientSecret = trim($clientSecret);
        $trimmedTenantId = trim($tenantId);
        
        if (empty($trimmedClientId) || empty($trimmedClientSecret) || empty($trimmedTenantId)) {
            $result['steps']['token_request'] = [
                'status' => 'failed',
                'message' => '❌ Empty credentials after trimming'
            ];
            $result['message'] = 'Credentials contain only whitespace or are empty';
            return $result;
        }
        
        $postData = [
            'grant_type' => 'client_credentials',
            'client_id' => $trimmedClientId,
            'client_secret' => $trimmedClientSecret,
            'scope' => 'https://graph.microsoft.com/.default'
        ];

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
            'User-Agent: REDAXO-SymfonyMailer/1.1.0'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlInfo = curl_getinfo($ch);
        curl_close($ch);

        if ($curlError) {
            $result['steps']['token_request'] = [
                'status' => 'failed',
                'message' => '❌ Network error: ' . $curlError,
                'curl_info' => $curlInfo
            ];
            $result['message'] = 'Network connectivity issue';
            return $result;
        }

        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMsg = 'Unknown error';
            
            if (isset($errorData['error_description'])) {
                $errorMsg = $errorData['error_description'];
            } elseif (isset($errorData['error'])) {
                $errorMsg = $errorData['error'];
            }
            
            $result['steps']['token_request'] = [
                'status' => 'failed',
                'message' => "❌ HTTP $httpCode: $errorMsg",
                'http_code' => $httpCode,
                'response' => $errorData,
                'request_data' => [
                    'endpoint' => $endpoint,
                    'client_id' => $trimmedClientId,
                    'client_secret_length' => strlen($trimmedClientSecret),
                    'tenant_id' => $trimmedTenantId
                ]
            ];

            // Spezifische Hilfetexte für AADSTS Fehler
            if (strpos($errorMsg, 'AADSTS7000216') !== false) {
                $result['message'] = 'Client Secret wird nicht akzeptiert - prüfen Sie das Secret in Azure';
            } elseif (strpos($errorMsg, 'AADSTS70011') !== false) {
                $result['message'] = 'Invalid scope - sollte normalerweise nicht auftreten';
            } elseif (strpos($errorMsg, 'AADSTS90002') !== false) {
                $result['message'] = 'Tenant not found - prüfen Sie die Tenant ID';
            } elseif (strpos($errorMsg, 'AADSTS7000215') !== false) {
                $result['message'] = 'Invalid client secret - das Secret ist falsch oder abgelaufen';
            } elseif ($httpCode === 400) {
                if (strpos($errorMsg, 'invalid_client') !== false) {
                    $result['message'] = 'Invalid Client ID or Client Secret';
                } elseif (strpos($errorMsg, 'invalid_request') !== false) {
                    $result['message'] = 'Invalid request format or Tenant ID';
                } else {
                    $result['message'] = 'Bad request - check all credentials';
                }
            } elseif ($httpCode === 401) {
                $result['message'] = 'Authentication failed - verify credentials';
            } else {
                $result['message'] = "Token request failed (HTTP $httpCode)";
            }
            return $result;
        }

        $tokenData = json_decode($response, true);
        $result['steps']['token_request'] = [
            'status' => 'passed',
            'message' => '✅ Access token received',
            'token_type' => $tokenData['token_type'],
            'expires_in' => $tokenData['expires_in']
        ];

        // Schritt 3: Graph API testen
        $result['steps']['graph_api'] = [
            'status' => 'running',
            'message' => 'Testing Graph API access...'
        ];

        $accessToken = $tokenData['access_token'];
        $graphEndpoint = 'https://graph.microsoft.com/v1.0/me';

        $ch = curl_init($graphEndpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            $result['steps']['graph_api'] = [
                'status' => 'failed',
                'message' => '❌ Graph API network error: ' . $curlError
            ];
            $result['message'] = 'Graph API connectivity issue';
            return $result;
        }

        if ($httpCode === 200) {
            $userData = json_decode($response, true);
            $result['steps']['graph_api'] = [
                'status' => 'passed',
                'message' => '✅ Graph API accessible',
                'user_info' => [
                    'displayName' => $userData['displayName'] ?? 'N/A',
                    'userPrincipalName' => $userData['userPrincipalName'] ?? 'N/A'
                ]
            ];
            $result['success'] = true;
            $result['message'] = 'Microsoft Graph connection fully functional';
        } else {
            $errorData = json_decode($response, true);
            $result['steps']['graph_api'] = [
                'status' => 'failed',
                'message' => "❌ Graph API error (HTTP $httpCode)",
                'http_code' => $httpCode,
                'response' => $errorData
            ];

            if ($httpCode === 403) {
                $result['message'] = 'Insufficient permissions - Admin consent required for Mail.Send';
            } else {
                $result['message'] = "Graph API access failed (HTTP $httpCode)";
            }
        }

        return $result;
    }

    public function __toString(): string
    {
        return 'microsoft-graph';
    }
}
