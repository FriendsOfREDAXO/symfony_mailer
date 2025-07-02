<?php

use FriendsOfRedaxo\SymfonyMailer\MailQueue;

$addon = rex_addon::get('symfony_mailer');

echo rex_view::title($addon->i18n('queue'));

$queue = new MailQueue();

// Handle actions
$func = rex_request('func', 'string');
$queueId = rex_request('queue_id', 'int');

if ($func && $queueId) {
    $success = false;
    $message = '';
    
    switch ($func) {
        case 'retry':
            $success = $queue->retryEmail($queueId);
            $message = $success ? $addon->i18n('queue_retry_successful') : 'Fehler beim Wiederholen';
            break;
            
        case 'cancel':
            $success = $queue->cancelEmail($queueId);
            $message = $success ? $addon->i18n('queue_cancel_successful') : 'Fehler beim Abbrechen';
            break;
            
        case 'delete':
            $sql = rex_sql::factory();
            $sql->setQuery('DELETE FROM ' . rex::getTable('symfony_mailer_queue') . ' WHERE id = ?', [$queueId]);
            $success = $sql->getRows() > 0;
            $message = $success ? $addon->i18n('queue_delete_successful') : 'Fehler beim Löschen';
            break;
    }
    
    if ($success) {
        echo rex_view::success($message);
    } else {
        echo rex_view::error($message);
    }
}

// Handle batch processing
if (rex_post('process_batch', 'bool')) {
    $batchSize = $addon->getConfig('queue_batch_size', 10);
    $result = $queue->processBatch($batchSize);
    
    if ($result['processed'] > 0) {
        echo rex_view::success($addon->i18n('queue_processing_successful', $result['processed']));
    }
    
    if (!empty($result['errors'])) {
        echo rex_view::error($addon->i18n('queue_processing_errors', implode(', ', $result['errors'])));
    }
}

// Handle cleanup
if (rex_post('cleanup', 'bool')) {
    $cleaned = $queue->cleanup();
    echo rex_view::success($addon->i18n('queue_cleanup_successful', $cleaned));
}

// Get queue statistics
$stats = $queue->getStats();

?>

<section class="rex-page-section">
    <div class="panel panel-default">
        <div class="panel-heading">
            <div class="panel-title"><?= $addon->i18n('queue_stats') ?></div>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-sm-2">
                    <div class="rex-tile">
                        <div class="rex-tile-body">
                            <div class="rex-tile-title"><?= $addon->i18n('queue_total') ?></div>
                            <div class="rex-tile-text"><?= $stats['total'] ?? 0 ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-2">
                    <div class="rex-tile">
                        <div class="rex-tile-body">
                            <div class="rex-tile-title"><?= $addon->i18n('queue_pending') ?></div>
                            <div class="rex-tile-text"><?= $stats['pending'] ?? 0 ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-2">
                    <div class="rex-tile">
                        <div class="rex-tile-body">
                            <div class="rex-tile-title"><?= $addon->i18n('queue_sent') ?></div>
                            <div class="rex-tile-text"><?= $stats['sent'] ?? 0 ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-2">
                    <div class="rex-tile">
                        <div class="rex-tile-body">
                            <div class="rex-tile-title"><?= $addon->i18n('queue_failed') ?></div>
                            <div class="rex-tile-text"><?= $stats['failed'] ?? 0 ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-2">
                    <div class="rex-tile">
                        <div class="rex-tile-body">
                            <div class="rex-tile-title"><?= $addon->i18n('queue_processing') ?></div>
                            <div class="rex-tile-text"><?= $stats['processing'] ?? 0 ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-2">
                    <div class="rex-tile">
                        <div class="rex-tile-body">
                            <div class="rex-tile-title"><?= $addon->i18n('queue_scheduled') ?></div>
                            <div class="rex-tile-text"><?= $stats['scheduled'] ?? 0 ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="rex-page-section">
    <div class="panel panel-default">
        <div class="panel-heading">
            <div class="panel-title"><?= $addon->i18n('queue_process_now') ?></div>
        </div>
        <div class="panel-body">
            <form method="post">
                <div class="row">
                    <div class="col-sm-6">
                        <button type="submit" name="process_batch" value="1" class="btn btn-primary">
                            <?= $addon->i18n('queue_process_batch') ?>
                        </button>
                    </div>
                    <div class="col-sm-6">
                        <button type="submit" name="cleanup" value="1" class="btn btn-warning">
                            <?= $addon->i18n('queue_cleanup') ?>
                        </button>
                    </div>
                </div>
            </form>
            <div class="help-block"><?= $addon->i18n('queue_cleanup_notice') ?></div>
        </div>
    </div>
</section>

<?php

// Get queue items
$query = '
    SELECT * FROM ' . rex::getTable('symfony_mailer_queue') . ' 
    ORDER BY 
        CASE WHEN status = "processing" THEN 1
             WHEN status = "pending" THEN 2  
             WHEN status = "failed" THEN 3
             ELSE 4 END,
        priority DESC, 
        created_at DESC 
    LIMIT 100
';

$list = rex_list::factory($query);
$list->addTableAttribute('class', 'table-striped table-hover');

$list->removeColumn('id');
$list->removeColumn('from_name');
$list->removeColumn('cc_addresses');
$list->removeColumn('bcc_addresses');
$list->removeColumn('reply_to');
$list->removeColumn('body_text');
$list->removeColumn('body_html');
$list->removeColumn('attachments');
$list->removeColumn('headers');
$list->removeColumn('max_attempts');
$list->removeColumn('updated_at');
$list->removeColumn('processed_at');

$list->setColumnLabel('id', $addon->i18n('queue_id'));
$list->setColumnLabel('from_address', $addon->i18n('queue_from'));
$list->setColumnLabel('to_addresses', $addon->i18n('queue_to'));
$list->setColumnLabel('subject', $addon->i18n('queue_subject'));
$list->setColumnLabel('status', $addon->i18n('queue_status'));
$list->setColumnLabel('priority', $addon->i18n('queue_priority'));
$list->setColumnLabel('scheduled_at', $addon->i18n('queue_scheduled_at'));
$list->setColumnLabel('created_at', $addon->i18n('queue_created_at'));
$list->setColumnLabel('attempts', $addon->i18n('queue_attempts'));
$list->setColumnLabel('error_message', $addon->i18n('queue_error'));

// Format columns
$list->setColumnFormat('to_addresses', 'custom', function($params) {
    $addresses = json_decode($params['value'], true);
    if (is_array($addresses)) {
        return implode(', ', array_slice($addresses, 0, 3)) . (count($addresses) > 3 ? '...' : '');
    }
    return $params['value'];
});

$list->setColumnFormat('subject', 'custom', function($params) {
    $subject = $params['value'];
    return strlen($subject) > 50 ? substr($subject, 0, 50) . '...' : $subject;
});

$list->setColumnFormat('status', 'custom', function($params) {
    $status = $params['value'];
    $class = '';
    switch ($status) {
        case 'pending':
            $class = 'label-info';
            break;
        case 'processing':
            $class = 'label-warning';
            break;
        case 'sent':
            $class = 'label-success';
            break;
        case 'failed':
            $class = 'label-danger';
            break;
        case 'cancelled':
            $class = 'label-default';
            break;
    }
    return '<span class="label ' . $class . '">' . ucfirst($status) . '</span>';
});

$list->setColumnFormat('priority', 'custom', function($params) {
    $priority = (int) $params['value'];
    switch ($priority) {
        case 1:
            return '<span class="label label-default">Niedrig</span>';
        case 3:
            return '<span class="label label-info">Normal</span>';
        case 5:
            return '<span class="label label-warning">Hoch</span>';
        default:
            return $priority;
    }
});

$list->setColumnFormat('created_at', 'custom', function($params) {
    return rex_formatter::strftime($params['value'], 'datetime');
});

$list->setColumnFormat('scheduled_at', 'custom', function($params) {
    return $params['value'] ? rex_formatter::strftime($params['value'], 'datetime') : '-';
});

$list->setColumnFormat('attempts', 'custom', function($params) {
    $row = $params['list']->getRow();
    return $params['value'] . '/' . $row['max_attempts'];
});

$list->setColumnFormat('error_message', 'custom', function($params) {
    $error = $params['value'];
    return $error ? '<span title="' . rex_escape($error) . '">' . substr($error, 0, 30) . '...</span>' : '-';
});

// Add action column
$list->addColumn($addon->i18n('queue_actions'), '', -1);
$list->setColumnFormat($addon->i18n('queue_actions'), 'custom', function($params) use ($addon) {
    $row = $params['list']->getRow();
    $id = $row['id'];
    $status = $row['status'];
    
    $actions = [];
    
    if ($status === 'failed' || $status === 'cancelled') {
        $actions[] = '<a href="' . rex_url::currentBackendPage(['func' => 'retry', 'queue_id' => $id]) . '" class="btn btn-xs btn-success" title="' . $addon->i18n('queue_retry') . '"><i class="rex-icon rex-icon-refresh"></i></a>';
    }
    
    if ($status === 'pending') {
        $actions[] = '<a href="' . rex_url::currentBackendPage(['func' => 'cancel', 'queue_id' => $id]) . '" class="btn btn-xs btn-warning" title="' . $addon->i18n('queue_cancel') . '"><i class="rex-icon rex-icon-ban"></i></a>';
    }
    
    if (in_array($status, ['sent', 'failed', 'cancelled'])) {
        $actions[] = '<a href="' . rex_url::currentBackendPage(['func' => 'delete', 'queue_id' => $id]) . '" class="btn btn-xs btn-danger" title="' . $addon->i18n('queue_delete') . '" onclick="return confirm(\'Wirklich löschen?\')"><i class="rex-icon rex-icon-delete"></i></a>';
    }
    
    return implode(' ', $actions);
});

echo $list->get();

?>

<script>
// Auto-refresh every 30 seconds if there are processing items
<?php if (($stats['processing'] ?? 0) > 0): ?>
setTimeout(function() {
    location.reload();
}, 30000);
<?php endif; ?>
</script>