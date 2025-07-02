<?php

use FriendsOfRedaxo\SymfonyMailer\RexSymfonyMailer;
use Symfony\Component\Mime\Email;

$addon = rex_addon::get('symfony_mailer');
$customConfigPath = rex_path::addonData('symfony_mailer', 'custom_config.yml');
$externalConfig = file_exists($customConfigPath);

// --- Funktionen für Testausgaben ---
function outputTestResult($message, $success = true, $error = null)
{
    if ($success) {
        echo rex_view::success($message);
    } else {
        $output = '';
        
        // Microsoft Graph spezifische Diagnose-Ausgabe
        if (isset($error['steps'])) {
            $output .= '<div class="panel panel-default">
                <div class="panel-heading"><strong>Microsoft Graph Diagnose</strong></div>
                <div class="panel-body">';
            
            foreach ($error['steps'] as $stepName => $stepInfo) {
                $status = $stepInfo['status'];
                $statusIcon = match($status) {
                    'passed' => '✅',
                    'failed' => '❌', 
                    'running' => '⏳',
                    default => '❔'
                };
                
                $output .= '<div style="margin-bottom: 10px;">
                    <strong>' . ucfirst(str_replace('_', ' ', $stepName)) . ':</strong> 
                    ' . $statusIcon . ' ' . rex_escape($stepInfo['message']) . '</div>';
                
                // Zusätzliche Details anzeigen
                if (isset($stepInfo['user_info'])) {
                    $output .= '<div style="margin-left: 20px; color: #666;">
                        User: ' . rex_escape($stepInfo['user_info']['displayName'] ?? 'N/A') . ' 
                        (' . rex_escape($stepInfo['user_info']['userPrincipalName'] ?? 'N/A') . ')
                    </div>';
                }
                
                if (isset($stepInfo['request_data'])) {
                    $output .= '<div style="margin-left: 20px; color: #666; font-family: monospace;">
                        <details>
                            <summary>Request Details (Debug)</summary>
                            <div style="background: #f5f5f5; padding: 10px; margin: 5px 0;">
                                <strong>Endpoint:</strong> ' . rex_escape($stepInfo['request_data']['endpoint']) . '<br>
                                <strong>Client ID:</strong> ' . rex_escape($stepInfo['request_data']['client_id']) . '<br>
                                <strong>Client Secret Length:</strong> ' . $stepInfo['request_data']['client_secret_length'] . ' chars<br>
                                <strong>Tenant ID:</strong> ' . rex_escape($stepInfo['request_data']['tenant_id']) . '
                            </div>
                        </details>
                    </div>';
                }
            }
            
            $output .= '</div></div>';
        }
        
        // Wenn ein Hinweis vorhanden ist, zeigen wir diesen
        if (isset($error['hint'])) {
            $output .= '<div class="alert alert-info"><strong>Lösungshinweis:</strong><br>' . rex_escape($error['hint']) . '</div>';
        }
        
        // Debug-Informationen für SMTP oder wenn Debug aktiviert ist
        if (isset($error['message']) && (rex_addon::get('symfony_mailer')->getConfig('debug') || !isset($error['steps']))) {
            $output .= '<br><br><strong>' . rex_i18n::msg('symfony_mailer_debug_info') . ':</strong><br>';
            $output .= '<pre class="rex-debug">' . rex_escape($error['message']);
            if (isset($error['dsn'])) {
                $output .= "\n\nDSN: " . rex_escape($error['dsn']);
            }
            if (isset($error['transport'])) {
                $output .= "\n\nTransport: " . rex_escape($error['transport']);
            }
            $output .= '</pre>';
        }
        
        echo rex_view::error($output ?: $message);
    }
}

// Handle test connection
if (rex_post('test_connection', 'boolean')) {
    try {
        $mailer = new \FriendsOfRedaxo\SymfonyMailer\RexSymfonyMailer();
        $result = $mailer->testConnection();
        
        outputTestResult($result['message'], $result['success'], $result['error_details'] ?? null);
    } catch (\Exception $e) {
        outputTestResult($e->getMessage(), false);
    }
}

// Test IMAP connection
if (rex_post('test_imap', 'boolean')) {
   if (!extension_loaded('imap')) {
       outputTestResult($addon->i18n('imap_extension_missing'), false);
    } elseif (!$addon->getConfig('imap_archive')) {
        outputTestResult($addon->i18n('imap_not_enabled'), false);
    } else {
        try {
            $host = $addon->getConfig('imap_host');
            $port = $addon->getConfig('imap_port', 993);
            $username = $addon->getConfig('imap_username');
            $password = $addon->getConfig('imap_password');
            $folder = $addon->getConfig('imap_folder', 'Sent');

            $mailbox = sprintf('{%s:%d/imap/ssl}', $host, $port); // Verbindung ohne Ordner
            
            // Set timeout for the connection attempt
            imap_timeout(IMAP_OPENTIMEOUT, 10);

            // Try to connect without specific folder first
            if ($connection = @imap_open($mailbox, $username, $password)) {
                
                $folders = imap_list($connection, $mailbox, '*');
                $folderExists = false;
                foreach ($folders as $f) {
                    $folder = str_replace($mailbox, '', $f);
                    if (ltrim($folder, '/') ==  $addon->getConfig('imap_folder', 'Sent')) {
                        $folderExists = true;
                        break;
                    }
                }

               if (!$folderExists) {
                    imap_close($connection);
                    outputTestResult(
                        $addon->i18n('imap_folder_not_exist') . ' "' . $addon->getConfig('imap_folder', 'Sent'). '"',
                        false
                    );
                    return;
               }

                // If Folder exist then use the folder to try open it
                $mailbox .=  $addon->getConfig('imap_folder', 'Sent');
               if ($connection = @imap_open($mailbox, $username, $password)) {
                    // Get mailbox info for additional debug data
                    $check = imap_check($connection);
                    $status = imap_status($connection, $mailbox, SA_ALL);

                    $debug = [
                        'Connection' => 'Success',
                        'Mailbox' => $mailbox,
                        'Available folders' => $folders,
                        'Messages in folder' => $check->Nmsgs,
                        'Folder status' => [
                            'messages' => $status->messages,
                            'recent' => $status->recent,
                            'unseen' => $status->unseen
                        ]
                    ];

                    imap_close($connection);

                    outputTestResult(
                        $addon->i18n('imap_connection_success'),
                        true,
                        $debug
                    );
                 }
                else {
                    $error = $addon->i18n('imap_connection_error') . '<br>' . imap_last_error();
                    imap_close($connection);
                    outputTestResult($error, false);
                }
           
            }
            else {
                $error = $addon->i18n('imap_connection_error') . '<br>' . imap_last_error();
                outputTestResult($error, false);
            }

        } catch (\Exception $e) {
            outputTestResult($addon->i18n('imap_connection_error') . '<br>' . $e->getMessage(), false);
        }
    }
}

// Handle test mail
if (rex_post('test_mail', 'boolean')) {
    if ('' == $addon->getConfig('from') || '' == $addon->getConfig('test_address')) {
        outputTestResult($addon->i18n('test_mail_no_addresses'), false);
    } else {
        try {
            $mailer = new RexSymfonyMailer();
            $email = $mailer->createEmail();
            $email->to($addon->getConfig('test_address'));
            $email->subject($addon->i18n('test_mail_default_subject'));

            // REDAXO Farben (angepasst)
            $mainColor = '#324050'; // dunkel
            $accentColor = '#4b9ad9'; // blau
            $bgColor = '#f8fafc';
            $borderColor = '#e0e7ef';
            $headlineColor = $mainColor;
            $infoColor = $accentColor;

            // HTML-Body
            $html = '<div style="background:' . $bgColor . ';border:1.5px solid ' . $borderColor . ';border-radius:8px;padding:32px 32px 24px 32px;font-family:sans-serif;max-width:600px;margin:0 auto;">';
            $html .= '<div style="font-size:1.5em;font-weight:700;color:' . $mainColor . ';margin-bottom:12px;">REDAXO Symfony Mailer – Testmail</div>';
            $html .= '<div style="color:' . $accentColor . ';font-size:1.15em;margin-bottom:18px;">Der Versand funktioniert! 🎉</div>';
            $html .= '<div style="margin-bottom:18px;color:' . $headlineColor . ';">'
                . 'Diese Testmail wurde erfolgreich über den REDAXO Symfony Mailer verschickt.'
                . '</div>';
            $html .= '<table style="width:100%;margin-bottom:18px;font-size:1em;color:' . $headlineColor . ';border-collapse:collapse;">';
            $html .= '<tr><td style="padding:4px 0;width:160px;">Mailer:</td><td><strong>Symfony Mailer</strong></td></tr>';
            $html .= '<tr><td style="padding:4px 0;">Transport:</td><td>' . rex_escape($addon->getConfig('transport_type', 'smtp')) . '</td></tr>';
            $html .= '<tr><td style="padding:4px 0;">Absender:</td><td>' . rex_escape($addon->getConfig('from')) . '</td></tr>';
            $html .= '<tr><td style="padding:4px 0;">Empfänger:</td><td>' . rex_escape($addon->getConfig('test_address')) . '</td></tr>';
            $html .= '<tr><td style="padding:4px 0;">Server:</td><td>' . rex_escape(rex::getServerName()) . '</td></tr>';
            $html .= '<tr><td style="padding:4px 0;">Domain:</td><td>' . rex_escape(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '-') . '</td></tr>';
            if ($addon->getConfig('transport_type') === 'microsoft_graph') {
                // Keine sensiblen Graph-Daten mehr anzeigen
            } else {
                $html .= '<tr><td style="padding:4px 0;">SMTP Host:</td><td>' . rex_escape($addon->getConfig('host')) . '</td></tr>';
                $html .= '<tr><td style="padding:4px 0;">SMTP Port:</td><td>' . rex_escape($addon->getConfig('port')) . '</td></tr>';
                $html .= '<tr><td style="padding:4px 0;">Verschlüsselung:</td><td>' . rex_escape($addon->getConfig('security') ?: 'none') . '</td></tr>';
            }
            $html .= '</table>';
            $html .= '<div style="margin-top:32px;font-size:0.95em;color:#888;">REDAXO Symfony Mailer Addon – ' . date('d.m.Y H:i') . '</div>';
            $html .= '</div>';

            // Text-Body (Fallback, schlicht)
            $body = "REDAXO Symfony Mailer – Testmail\n";
            $body .= "==============================\n\n";
            $body .= "Der Versand funktioniert!\n\n";
            $body .= "Mailer: Symfony Mailer\n";
            $body .= "Transport: " . $addon->getConfig('transport_type', 'smtp') . "\n";
            $body .= "Absender: " . $addon->getConfig('from') . "\n";
            $body .= "Empfänger: " . $addon->getConfig('test_address') . "\n";
            $body .= "Server: " . rex::getServerName() . "\n";
            $body .= "Domain: " . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '-') . "\n";
            if ($addon->getConfig('transport_type') === 'microsoft_graph') {
                // Keine sensiblen Graph-Daten mehr anzeigen
            } else {
                $body .= "SMTP Host: " . $addon->getConfig('host') . "\n";
                $body .= "SMTP Port: " . $addon->getConfig('port') . "\n";
                $body .= "Verschlüsselung: " . ($addon->getConfig('security') ?: 'none') . "\n";
            }
            $body .= "\nREDAXO Symfony Mailer Addon – " . date('d.m.Y H:i') . "\n";

            $email->html($html);
            $email->text($body);

            if ($mailer->send($email)) {
                outputTestResult($addon->i18n('test_mail_sent', rex_escape($addon->getConfig('test_address'))), true);
            } else {
                $errorInfo = $mailer->getErrorInfo();
                outputTestResult($addon->i18n('test_mail_error'), false, $errorInfo);
            }
        } catch (\Exception $e) {
            outputTestResult($addon->i18n('test_mail_error') . '<br>' . $e->getMessage(), false);
        }
    }
}

// Setup config form
$form = rex_config_form::factory('symfony_mailer');

// --- Config Seite Meldung wenn custom_config.yml vorhanden ist ---
if ($externalConfig) {
    echo rex_view::warning($addon->i18n('config_external'));
}

// Transport Type Selection
$form->addFieldset($addon->i18n('transport_settings'));

$field = $form->addSelectField('transport_type');
$field->setLabel($addon->i18n('transport_type'));
$select = $field->getSelect();
$select->addOption('SMTP', 'smtp');
$select->addOption('Microsoft Graph', 'microsoft_graph');
$field->setNotice($addon->i18n('transport_type_notice'));
if ($externalConfig) {
    $field->setAttribute('disabled', 'disabled');
}

// Common Settings Fieldset
$form->addFieldset($addon->i18n('common_settings'));

$field = $form->addTextField('from');
$field->setLabel($addon->i18n('sender_email'));
$field->setNotice($addon->i18n('sender_email_notice'));
if ($externalConfig) {
    $field->setAttribute('disabled', 'disabled');
}

$field = $form->addTextField('test_address');
$field->setLabel($addon->i18n('test_address'));
$field->setNotice($addon->i18n('test_address_notice'));
if ($externalConfig) {
    $field->setAttribute('disabled', 'disabled');
}

$field = $form->addTextField('name');
$field->setLabel($addon->i18n('sender_name'));
if ($externalConfig) {
    $field->setAttribute('disabled', 'disabled');
}

// SMTP Settings Fieldset
$form->addFieldset($addon->i18n('smtp_settings'));

$field = $form->addTextField('host');
$field->setLabel($addon->i18n('smtp_host'));
if ($externalConfig) {
    $field->setAttribute('disabled', 'disabled');
}

$field = $form->addTextField('port');
$field->setLabel($addon->i18n('smtp_port'));
$field->setNotice($addon->i18n('smtp_port_notice'));
if ($externalConfig) {
    $field->setAttribute('disabled', 'disabled');
}

$field = $form->addSelectField('security');
$field->setLabel($addon->i18n('smtp_security'));
$select = $field->getSelect();
$select->addOption($addon->i18n('smtp_security_none'), '');
$select->addOption('TLS', 'tls');
$select->addOption('SSL', 'ssl');
if ($externalConfig) {
    $field->setAttribute('disabled', 'disabled');
}

$field = $form->addCheckboxField('auth');
$field->setLabel($addon->i18n('smtp_auth'));
$field->addOption($addon->i18n('smtp_auth_enabled'), 1);
if ($externalConfig) {
    $field->setAttribute('disabled', 'disabled');
}

$field = $form->addTextField('username');
$field->setLabel($addon->i18n('smtp_username'));
if ($externalConfig) {
    $field->setAttribute('disabled', 'disabled');
}

$field = $form->addTextField('password');
$field->setLabel($addon->i18n('smtp_password'));
$field->setAttribute('type', 'password');
if ($externalConfig) {
    $field->setAttribute('disabled', 'disabled');
}

// Microsoft Graph Settings Fieldset
$form->addFieldset($addon->i18n('graph_settings'));

$field = $form->addTextField('graph_tenant_id');
$field->setLabel($addon->i18n('graph_tenant_id'));
$field->setNotice($addon->i18n('graph_tenant_id_notice'));
if ($externalConfig) {
    $field->setAttribute('disabled', 'disabled');
}

$field = $form->addTextField('graph_client_id');
$field->setLabel($addon->i18n('graph_client_id'));
$field->setNotice($addon->i18n('graph_client_id_notice'));
if ($externalConfig) {
    $field->setAttribute('disabled', 'disabled');
}

$field = $form->addTextField('graph_client_secret');
$field->setLabel($addon->i18n('graph_client_secret'));
$field->setAttribute('type', 'password');
$field->setNotice($addon->i18n('graph_client_secret_notice'));
if ($externalConfig) {
    $field->setAttribute('disabled', 'disabled');
}

$field = $form->addCheckboxField('debug');
$field->setLabel($addon->i18n('smtp_debug'));
$field->addOption($addon->i18n('smtp_debug_enabled'), 1);
$field->setNotice($addon->i18n('smtp_debug_notice'));

// Log and Archive Settings Fieldset
$form->addFieldset('Log & Archive');

$field = $form->addSelectField('logging');
$field->setLabel($addon->i18n('logging'));
$select = $field->getSelect();
$select->addOption($addon->i18n('log_disabled'), 0);
$select->addOption($addon->i18n('log_errors'), 1);
$select->addOption($addon->i18n('log_all'), 2);

$field = $form->addCheckboxField('archive');
$field->setLabel($addon->i18n('archive_emails'));
$field->addOption($addon->i18n('archive_enabled'), 1);
if ($externalConfig) {
    $field->setAttribute('disabled', 'disabled');
}

// IMAP Archive Settings Fieldset
$imap_available = extension_loaded('imap');
if ($imap_available) {
    $form->addFieldset('IMAP Archive');

    $field = $form->addCheckboxField('imap_archive');
    $field->setLabel($addon->i18n('imap_archive'));
    $field->addOption($addon->i18n('imap_archive_enabled'), 1);
    if ($externalConfig) {
        $field->setAttribute('disabled', 'disabled');
    }

    $field = $form->addTextField('imap_host');
    $field->setLabel($addon->i18n('imap_host'));
    if ($externalConfig) {
        $field->setAttribute('disabled', 'disabled');
    }

    $field = $form->addTextField('imap_port');
    $field->setLabel($addon->i18n('imap_port'));
    $field->setNotice($addon->i18n('imap_port_notice'));
    if ($externalConfig) {
        $field->setAttribute('disabled', 'disabled');
    }

    $field = $form->addTextField('imap_username');
    $field->setLabel($addon->i18n('imap_username'));
    if ($externalConfig) {
        $field->setAttribute('disabled', 'disabled');
    }

    $field = $form->addTextField('imap_password');
    $field->setLabel($addon->i18n('imap_password'));
    $field->setAttribute('type', 'password');
    if ($externalConfig) {
        $field->setAttribute('disabled', 'disabled');
    }

    $field = $form->addTextField('imap_folder');
    $field->setLabel($addon->i18n('imap_folder'));
    $field->setNotice($addon->i18n('imap_folder_notice'));
    if ($externalConfig) {
        $field->setAttribute('disabled', 'disabled');
    }
}

// Detour Mode Settings Fieldset
$form->addFieldset('Detour Mode');

$field = $form->addCheckboxField('detour_mode');
$field->setLabel($addon->i18n('detour_mode'));
$field->addOption($addon->i18n('detour_mode_enabled'), 1);
$field->setNotice($addon->i18n('detour_mode_notice'));

// Mail Queue Settings Fieldset
$form->addFieldset($addon->i18n('queue'));

$field = $form->addCheckboxField('queue_enabled');
$field->setLabel($addon->i18n('queue_enabled'));
$field->addOption('1', 1);
$field->setNotice($addon->i18n('queue_enabled_notice'));
if ($externalConfig) {
    $field->setAttribute('disabled', 'disabled');
}

$field = $form->addTextField('queue_batch_size');
$field->setLabel($addon->i18n('queue_batch_size'));
$field->setNotice($addon->i18n('queue_batch_size_notice'));
if ($externalConfig) {
    $field->setAttribute('disabled', 'disabled');
}

$field = $form->addTextField('queue_max_attempts');
$field->setLabel($addon->i18n('queue_max_attempts'));
$field->setNotice($addon->i18n('queue_max_attempts_notice'));
if ($externalConfig) {
    $field->setAttribute('disabled', 'disabled');
}

// Output form
echo '<section class="rex-page-section">
    <div class="panel panel-edit">
        <header class="panel-heading"><div class="panel-title">' . $addon->i18n('configuration') . '</div></header>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-8">';

echo $form->get();

echo '</div>
                <div class="col-md-4">
                    <form action="' . rex_url::currentBackendPage() . '" method="post">
                        <div class="panel panel-default">
                            <header class="panel-heading"><div class="panel-title">' . $addon->i18n('test_title') . '</div></header>
                            <div class="panel-body">
                                <div class="alert alert-info">
                                    ' . $addon->i18n('test_info') . '
                                </div>
                                
                                <fieldset>
                                    <legend>' . $addon->i18n('transport_test') . '</legend>
                                    <div class="form-group">
                                        <button type="submit" name="test_connection" value="1" class="btn btn-block btn-primary">' . $addon->i18n('test_connection') . '</button>
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" name="test_mail" value="1" class="btn btn-block btn-primary">' . $addon->i18n('test_mail_send') . '</button>
                                    </div>
                                </fieldset>';

if ($imap_available) {
    echo '<fieldset>
        <legend>' . $addon->i18n('imap_test') . '</legend>
        <div class="form-group">
            <button type="submit" name="test_imap" value="1" class="btn btn-block btn-primary">' . $addon->i18n('test_imap_connection') . '</button>
        </div>
    </fieldset>';
} else {
      echo '<div class="alert alert-warning">' . $addon->i18n('imap_extension_missing_notice', '<a href="https://www.php.net/manual/en/imap.installation.php" target="_blank">PHP IMAP</a>') . '</div>';
}

echo '         
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>';

// Add JavaScript for dynamic form visibility
echo '<script>
document.addEventListener("DOMContentLoaded", function() {
    const transportSelect = document.querySelector("select[name=\'transport_type\']");
    const smtpFieldset = document.querySelector("fieldset:has(input[name=\'host\'])");
    const graphFieldset = document.querySelector("fieldset:has(input[name=\'graph_tenant_id\'])");
    
    function toggleTransportFields() {
        if (!transportSelect || !smtpFieldset || !graphFieldset) return;
        
        const selectedTransport = transportSelect.value;
        
        if (selectedTransport === "microsoft_graph") {
            smtpFieldset.style.display = "none";
            graphFieldset.style.display = "block";
        } else {
            smtpFieldset.style.display = "block";
            graphFieldset.style.display = "none";
        }
    }
    
    if (transportSelect) {
        transportSelect.addEventListener("change", toggleTransportFields);
        toggleTransportFields(); // Initial call
    }
});
</script>';
