<?php

/**
 * Cron script for processing the mail queue
 * 
 * This script can be called via cron job or manually to process pending emails
 * Example cron job: * * * * * /usr/bin/php /path/to/redaxo/redaxo/bin/console mailer:process-queue
 */

use FriendsOfRedaxo\SymfonyMailer\MailQueue;

// Include REDAXO bootstrap if called from command line
if (php_sapi_name() === 'cli') {
    // Assume this is called from REDAXO root with: php index.php --page=symfony_mailer/cron --cron-key=YOUR_KEY
    // or directly: php redaxo/src/addons/symfony_mailer/lib/cron_process_queue.php
    
    // Find REDAXO path
    $redaxoPath = '';
    $currentDir = __DIR__;
    while ($currentDir !== '/') {
        if (file_exists($currentDir . '/redaxo/bin/console')) {
            $redaxoPath = $currentDir . '/redaxo';
            break;
        }
        if (file_exists($currentDir . '/redaxo/src/core/boot.php')) {
            $redaxoPath = $currentDir . '/redaxo';
            break;
        }
        $currentDir = dirname($currentDir);
    }
    
    if ($redaxoPath && file_exists($redaxoPath . '/src/core/boot.php')) {
        require_once $redaxoPath . '/src/core/boot.php';
        rex::setProperty('setup', true);
        rex_addon::initialize();
    } else {
        echo "REDAXO installation not found.\n";
        exit(1);
    }
}

// Security check for web access
if (!rex::isBackend() && php_sapi_name() !== 'cli') {
    $cronKey = rex_request('cron_key', 'string');
    $expectedKey = rex_addon::get('symfony_mailer')->getConfig('cron_key', '');
    
    if (!$expectedKey || $cronKey !== $expectedKey) {
        http_response_code(403);
        echo "Access denied. Invalid or missing cron key.";
        exit;
    }
}

$addon = rex_addon::get('symfony_mailer');

if (!$addon->isInstalled()) {
    echo "Symfony Mailer addon is not installed.\n";
    exit(1);
}

if (!$addon->getConfig('queue_enabled', false)) {
    echo "Mail queue is not enabled.\n";
    exit(0);
}

$queue = new MailQueue();
$batchSize = $addon->getConfig('queue_batch_size', 10);

try {
    $result = $queue->processBatch($batchSize);
    
    $output = sprintf(
        "Queue processing completed: %d emails processed",
        $result['processed']
    );
    
    if (!empty($result['errors'])) {
        $output .= sprintf(
            ", %d errors: %s",
            count($result['errors']),
            implode(', ', $result['errors'])
        );
    }
    
    echo $output . "\n";
    
    // Log the result
    rex_logger::log('symfony_mailer', 'info', $output);
    
} catch (Exception $e) {
    $error = "Queue processing failed: " . $e->getMessage();
    echo $error . "\n";
    rex_logger::log('symfony_mailer', 'error', $error);
    exit(1);
}