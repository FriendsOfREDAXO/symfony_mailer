<?php

/**
 * Uninstallation script for Symfony Mailer addon
 * Removes the mail queue table
 */

$error = '';

// Drop mail queue table
rex_sql_table::get(rex::getTable('symfony_mailer_queue'))->drop();

if ($error != '') {
    $this->setProperty('install', false);
    $this->setProperty('installmsg', $error);
}