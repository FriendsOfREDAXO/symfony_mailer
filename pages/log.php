<?php

use FriendsOfRedaxo\SymfonyMailer\RexSymfonyMailer;

$addon = rex_addon::get('symfony_mailer');
$func = rex_request('func', 'string');
$success = '';
$error = '';

// Delete log if requested
if ('delete_log' == $func) {
    $logFile = RexSymfonyMailer::getLogFile();
    if (rex_log_file::delete($logFile)) {
        $success = rex_i18n::msg('symfony_mailer_log_deleted');
    } else {
        $error = rex_i18n::msg('symfony_mailer_log_delete_error');
    }
}

// Show messages if any
if ($success != '') {
    echo rex_view::success($success); 
}
if ($error != '') {
    echo rex_view::error($error);
}

// Build table output
$content = '<table class="table table-hover">
    <thead>
        <tr>
            <th>' . rex_i18n::msg('symfony_mailer_log_status') . '</th>
            <th>' . rex_i18n::msg('symfony_mailer_log_date') . '</th>
            <th>' . rex_i18n::msg('symfony_mailer_log_from') . '</th>
            <th>' . rex_i18n::msg('symfony_mailer_log_to') . '</th>
            <th>' . rex_i18n::msg('symfony_mailer_log_subject') . '</th>
            <th>' . rex_i18n::msg('symfony_mailer_log_message') . '</th>
        </tr>
    </thead>
    <tbody>';

// Read and display log entries
$logFile = rex_log_file::factory(RexSymfonyMailer::getLogFile());
foreach (new LimitIterator($logFile, 0, 100) as $entry) {
    $data = $entry->getData();
    $status = trim($data[0]);
    $class = 'ERROR' == $status ? 'rex-state-error' : 'rex-mailer-log-ok';
    
    $content .= '
    <tr class="' . $class . '">
        <td data-title="' . rex_i18n::msg('symfony_mailer_log_status') . '"><strong>' . rex_escape($status) . '</strong></td>
        <td data-title="' . rex_i18n::msg('symfony_mailer_log_date') . '" class="rex-table-tabular-nums">' . 
            rex_formatter::intlDateTime($entry->getTimestamp(), [IntlDateFormatter::SHORT, IntlDateFormatter::MEDIUM]) . '</td>
        <td data-title="' . rex_i18n::msg('symfony_mailer_log_from') . '">' . rex_escape($data[1]) . '</td>
        <td data-title="' . rex_i18n::msg('symfony_mailer_log_to') . '">' . rex_escape($data[2]) . '</td>
        <td data-title="' . rex_i18n::msg('symfony_mailer_log_subject') . '">' . rex_escape($data[3]) . '</td>
        <td data-title="' . rex_i18n::msg('symfony_mailer_log_message') . '">' . nl2br(rex_escape($data[4])) . '</td>
    </tr>';
}

$content .= '
    </tbody>
</table>';

// Add delete button
$formElements = [];
$n = [];
$n['field'] = '<button class="btn btn-delete" type="submit" name="del_btn" data-confirm="' . 
    rex_i18n::msg('symfony_mailer_log_delete_confirm') . '">' . rex_i18n::msg('symfony_mailer_log_delete') . '</button>';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$buttons = $fragment->parse('core/form/submit.php');

// Build and show the complete page
$logFile = RexSymfonyMailer::getLogFile();
$fragment = new rex_fragment();
$fragment->setVar('title', rex_i18n::msg('symfony_mailer_log_title', $logFile), false);
$fragment->setVar('content', $content, false);
$fragment->setVar('buttons', $buttons, false);
$content = $fragment->parse('core/page/section.php');

echo '
<form action="' . rex_url::currentBackendPage() . '" method="post">
    <input type="hidden" name="func" value="delete_log" />
    ' . $content . '
</form>';
