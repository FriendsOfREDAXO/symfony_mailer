<?php

/**
 * Update script for Symfony Mailer addon
 * Handles database schema updates for new versions
 */

$error = '';

// Check if queue table exists, create if not
if (!rex_sql_table::get(rex::getTable('symfony_mailer_queue'))->exists()) {
    // Create mail queue table (same as in install.php)
    rex_sql_table::get(rex::getTable('symfony_mailer_queue'))
        ->ensureColumn(new rex_sql_column('id', 'int(11)', false, null, 'auto_increment'))
        ->ensureColumn(new rex_sql_column('from_address', 'varchar(255)', false))
        ->ensureColumn(new rex_sql_column('from_name', 'varchar(255)', true))
        ->ensureColumn(new rex_sql_column('to_addresses', 'text', false))
        ->ensureColumn(new rex_sql_column('cc_addresses', 'text', true))
        ->ensureColumn(new rex_sql_column('bcc_addresses', 'text', true))
        ->ensureColumn(new rex_sql_column('reply_to', 'varchar(255)', true))
        ->ensureColumn(new rex_sql_column('subject', 'varchar(255)', false))
        ->ensureColumn(new rex_sql_column('body_text', 'longtext', true))
        ->ensureColumn(new rex_sql_column('body_html', 'longtext', true))
        ->ensureColumn(new rex_sql_column('attachments', 'longtext', true))
        ->ensureColumn(new rex_sql_column('headers', 'text', true))
        ->ensureColumn(new rex_sql_column('priority', 'int(3)', false, '3'))
        ->ensureColumn(new rex_sql_column('scheduled_at', 'datetime', true))
        ->ensureColumn(new rex_sql_column('status', 'enum("pending","processing","sent","failed","cancelled")', false, 'pending'))
        ->ensureColumn(new rex_sql_column('attempts', 'int(3)', false, '0'))
        ->ensureColumn(new rex_sql_column('max_attempts', 'int(3)', false, '3'))
        ->ensureColumn(new rex_sql_column('error_message', 'text', true))
        ->ensureColumn(new rex_sql_column('created_at', 'datetime', false))
        ->ensureColumn(new rex_sql_column('updated_at', 'datetime', false))
        ->ensureColumn(new rex_sql_column('processed_at', 'datetime', true))
        ->ensurePrimaryIdColumn()
        ->ensureIndex(new rex_sql_index('status', ['status']))
        ->ensureIndex(new rex_sql_index('scheduled_at', ['scheduled_at']))
        ->ensureIndex(new rex_sql_index('priority', ['priority']))
        ->ensureIndex(new rex_sql_index('status_scheduled', ['status', 'scheduled_at']))
        ->ensure();
}

if ($error != '') {
    $this->setProperty('update', false);
    $this->setProperty('updatemsg', $error);
} else {
    $this->setProperty('update', true);
}