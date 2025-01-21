<?php

use FriendsOfRedaxo\SymfonyMailer\RexSymfonyMailer;

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

$form = rex_config_form_enhanced::factory('symfony_mailer');

// SMTP Settings Fieldset
$form->addFieldset('SMTP Settings');

$field = $form->addTextField('from');
$field->setLabel($addon->i18n('sender_email'));
$field->setNotice($addon->i18n('sender_email_notice'));

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
    ['class' => 'btn btn-primary']
);

// Output form
$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', $addon->i18n('configuration'), false);
$fragment->setVar('body', $form->get(), false);

echo $fragment->parse('core/page/section.php');

// Add JavaScript for form field toggling
?>
<script nonce="<?= rex_response::getNonce() ?>">
    rex_ready(function() {
        // Show/hide auth fields based on checkbox
        function toggleAuthFields() {
            if ($('#rex-form-auth').is(':checked')) {
                $('#rex-form-username, #rex-form-password').closest('.form-group').show();
            } else {
                $('#rex-form-username, #rex-form-password').closest('.form-group').hide();
            }
        }

        // Show/hide IMAP fields based on checkbox
        function toggleImapFields() {
            if ($('#rex-form-imap-archive').is(':checked')) {
                $('#rex-form-imap-host, #rex-form-imap-port, #rex-form-imap-username, #rex-form-imap-password, #rex-form-imap-folder')
                    .closest('.form-group').show();
            } else {
                $('#rex-form-imap-host, #rex-form-imap-port, #rex-form-imap-username, #rex-form-imap-password, #rex-form-imap-folder')
                    .closest('.form-group').hide();
            }
        }

        // Initial state
        toggleAuthFields();
        toggleImapFields();

        // Bind change events
        $('#rex-form-auth').change(toggleAuthFields);
        $('#rex-form-imap-archive').change(toggleImapFields);
    });
</script>
