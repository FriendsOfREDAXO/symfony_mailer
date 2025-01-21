<?php

use FriendsOfRedaxo\SymfonyMailer\RexSymfonyMailer;
use Symfony\Component\Mime\Email;

$addon = rex_addon::get('symfony_mailer');

$form = rex_config_form_enhanced::factory('symfony_mailer');

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

// Add test connection button
$form->addButton(
    'test_connection',
    $addon->i18n('test_connection'),
    ['class' => 'btn btn-primary'],
    function() use ($addon) {
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
        return true;
    }
);

// Add send test mail button
$form->addButton(
    'send_test',
    $addon->i18n('test_mail_send'),
    ['class' => 'btn btn-primary'],
    function() use ($addon) {
        if ('' == $addon->getConfig('from') || '' == $addon->getConfig('test_address')) {
            echo rex_view::error($addon->i18n('test_mail_no_addresses'));
            return true;
        }
        
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
        return true;
    }
);

// Output form
$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', $addon->i18n('configuration'), false);
$fragment->setVar('body', $form->get(), false);

echo $fragment->parse('core/page/section.php');
?>

