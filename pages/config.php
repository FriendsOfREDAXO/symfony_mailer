<?php

use FriendsOfRedaxo\SymfonyMailer\RexSymfonyMailer;
use Symfony\Component\Mime\Email;

$addon = rex_addon::get('symfony_mailer');

// Handle test connection
if (rex_post('test_connection', 'boolean')) {
    try {
        $mailer = new RexSymfonyMailer();
        $result = $mailer->testConnection();
        
        if ($result['success']) {
            echo rex_view::success($result['message']);
        } else {
            $error = $result['message'];
            if (isset($result['debug']) && !empty($result['debug'])) {
                $error .= '<br><br><strong>' . $addon->i18n('debug_info') . ':</strong><br>';
                $error .= nl2br(rex_escape($result['debug']));
            }
            echo rex_view::error($error);
        }
    } catch (\Exception $e) {
        echo rex_view::error($e->getMessage());
    }
}

// Handle test mail
if (rex_post('test_mail', 'boolean')) {
    if ('' == $addon->getConfig('from') || '' == $addon->getConfig('test_address')) {
        echo rex_view::error($addon->i18n('test_mail_no_addresses'));
    } else {
        try {
            $mailer = new RexSymfonyMailer();
            
            $email = $mailer->createEmail();
            $email->to($addon->getConfig('test_address'));
            $email->subject($addon->i18n('test_mail_default_subject'));
            
            // Build test mail body with debug info
            $body = $addon->i18n('test_mail_greeting') . "\n\n";
            $body .= $addon->i18n('test_mail_body', rex::getServerName()) . "\n\n";
            $body .= str_repeat('-', 50) . "\n\n";
            $body .= 'Server: ' . rex::getServerName() . "\n";
            $body .= 'Domain: ' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '-') . "\n";
            $body .= 'Mailer: Symfony Mailer' . "\n";
            $body .= 'Host: ' . $addon->getConfig('host') . "\n";
            $body .= 'Port: ' . $addon->getConfig('port') . "\n";
            $body .= 'Security: ' . ($addon->getConfig('security') ?: 'none') . "\n";
            
            $email->text($body);
            
            if ($mailer->send($email)) {
                echo rex_view::success($addon->i18n('test_mail_sent', rex_escape($addon->getConfig('test_address'))));
            } else {
                $debugInfo = $mailer->getDebugInfo();
                $error = $addon->i18n('test_mail_error');
                if (!empty($debugInfo)) {
                    $error .= '<br><br><strong>' . $addon->i18n('debug_info') . ':</strong><br>';
                    $error .= nl2br(rex_escape(print_r($debugInfo, true)));
                }
                echo rex_view::error($error);
            }
            
        } catch (\Exception $e) {
            echo rex_view::error($addon->i18n('test_mail_error') . '<br>' . $e->getMessage());
        }
    }
}

// Setup config form
$form = rex_config_form::factory('symfony_mailer');

// SMTP Settings Fieldset
$form->addFieldset('SMTP Settings');

$field = $form->addTextField('from');
$field->setLabel($addon->i18n('sender_email'));
$field->setNotice($addon->i18n('sender_email_notice'));

$field = $form->addTextField('test_address');
$field->setLabel($addon->i18n('test_address'));
$field->setNotice($addon->i18n('test_address_notice'));

$field = $form->addTextField('name');
$field->setLabel($addon->i18n('sender_name'));

$field = $form->addTextField('host');
$field->setLabel($addon->i18n('smtp_host'));

$field = $form->addTextField('port');
$field->setLabel($addon->i18n('smtp_port'));
$field->setNotice($addon->i18n('smtp_port_notice'));

$field = $form->addSelectField('security');
$field->setLabel($addon->i18n('smtp_security'));
$select = $field->getSelect();
$select->addOption($addon->i18n('smtp_security_none'), '');
$select->addOption('TLS', 'tls');
$select->addOption('SSL', 'ssl');

$field = $form->addCheckboxField('auth');
$field->setLabel($addon->i18n('smtp_auth'));
$field->addOption($addon->i18n('smtp_auth_enabled'), 1);

$field = $form->addTextField('username');
$field->setLabel($addon->i18n('smtp_username'));

$field = $form->addTextField('password');
$field->setLabel($addon->i18n('smtp_password'));
$field->getAttributes()['type'] = 'password';

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

// IMAP Archive Settings Fieldset
$form->addFieldset('IMAP Archive');

$field = $form->addCheckboxField('imap_archive');
$field->setLabel($addon->i18n('imap_archive'));
$field->addOption($addon->i18n('imap_archive_enabled'), 1);

$field = $form->addTextField('imap_host');
$field->setLabel($addon->i18n('imap_host'));

$field = $form->addTextField('imap_port');
$field->setLabel($addon->i18n('imap_port'));
$field->setNotice($addon->i18n('imap_port_notice'));

$field = $form->addTextField('imap_username');
$field->setLabel($addon->i18n('imap_username'));

$field = $form->addTextField('imap_password');
$field->setLabel($addon->i18n('imap_password'));
$field->getAttributes()['type'] = 'password';

$field = $form->addTextField('imap_folder');
$field->setLabel($addon->i18n('imap_folder'));
$field->setNotice($addon->i18n('imap_folder_notice'));

// Output form
$fragment = new rex_fragment();
$fragment->setVar('class', 'col-lg-8', false);
$fragment->setVar('title', $addon->i18n('configuration'), false);
$fragment->setVar('body', $form->get(), false);
$content = $fragment->parse('core/page/section.php');

// Test panel
$testButtons = '
<form action="' . rex_url::currentBackendPage() . '" method="post">
    <button type="submit" name="test_connection" value="1" class="btn btn-primary">' . $addon->i18n('test_connection') . '</button>
    <button type="submit" name="test_mail" value="1" class="btn btn-primary">' . $addon->i18n('test_mail_send') . '</button>
</form>';

$fragment = new rex_fragment();
$fragment->setVar('class', 'col-lg-4', false);
$fragment->setVar('title', $addon->i18n('test_title'), false);
$fragment->setVar('body', $testButtons, false);
$sidebar = $fragment->parse('core/page/section.php');

// Output complete page
echo '<div class="row">' . $content . $sidebar . '</div>';
?>

<script nonce="<?= rex_response::getNonce() ?>">
    $(document).on('rex:ready', function() {
        // Show/hide auth fields based on checkbox
        function toggleAuthFields() {
            if ($('#rex-symfony_mailer-auth').is(':checked')) {
                $('#rex-symfony_mailer-username, #rex-symfony_mailer-password').closest('.form-group').show();
            } else {
                $('#rex-symfony_mailer-username, #rex-symfony_mailer-password').closest('.form-group').hide();
            }
        }

        // Show/hide IMAP fields based on checkbox
        function toggleImapFields() {
            if ($('#rex-symfony_mailer-imap_archive').is(':checked')) {
                $('#rex-symfony_mailer-imap_host, #rex-symfony_mailer-imap_port, #rex-symfony_mailer-imap_username, #rex-symfony_mailer-imap_password, #rex-symfony_mailer-imap_folder')
                    .closest('.form-group').show();
            } else {
                $('#rex-symfony_mailer-imap_host, #rex-symfony_mailer-imap_port, #rex-symfony_mailer-imap_username, #rex-symfony_mailer-imap_password, #rex-symfony_mailer-imap_folder')
                    .closest('.form-group').hide();
            }
        }

        // Initial state
        toggleAuthFields();
        toggleImapFields();

        // Bind change events
        $('#rex-symfony_mailer-auth').change(toggleAuthFields);
        $('#rex-symfony_mailer-imap_archive').change(toggleImapFields);
    });
</script>
