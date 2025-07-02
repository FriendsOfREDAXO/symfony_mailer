<?php

/**
 * Console script for processing the mail queue
 * Usage: php console.php symfony_mailer:process-queue [--batch-size=10]
 */

use FriendsOfRedaxo\SymfonyMailer\MailQueue;

// Simple console argument parser
function parseArgs($argv) {
    $args = [];
    for ($i = 1; $i < count($argv); $i++) {
        $arg = $argv[$i];
        if (strpos($arg, '--') === 0) {
            $parts = explode('=', substr($arg, 2), 2);
            $args[$parts[0]] = isset($parts[1]) ? $parts[1] : true;
        } else {
            $args[] = $arg;
        }
    }
    return $args;
}

// Check if running from command line
if (php_sapi_name() !== 'cli') {
    echo "This script can only be run from the command line.\n";
    exit(1);
}

$args = parseArgs($argv);

// Check if this is a symfony_mailer command
if (!isset($args[0]) || strpos($args[0], 'symfony_mailer:') !== 0) {
    echo "Available commands:\n";
    echo "  symfony_mailer:process-queue  Process pending emails in the queue\n";
    exit(0);
}

// Find REDAXO path
$redaxoPath = '';
$currentDir = dirname(__FILE__);

// Go up to find REDAXO installation
while ($currentDir !== '/' && $currentDir !== '.') {
    if (file_exists($currentDir . '/redaxo/src/core/boot.php')) {
        $redaxoPath = $currentDir . '/redaxo';
        break;
    }
    if (file_exists($currentDir . '/src/core/boot.php')) {
        $redaxoPath = $currentDir;
        break;
    }
    $currentDir = dirname($currentDir);
}

if (!$redaxoPath || !file_exists($redaxoPath . '/src/core/boot.php')) {
    echo "REDAXO installation not found.\n";
    exit(1);
}

// Bootstrap REDAXO
require_once $redaxoPath . '/src/core/boot.php';
rex::setProperty('setup', true);
rex_addon::initialize();

$addon = rex_addon::get('symfony_mailer');

if (!$addon->isInstalled()) {
    echo "Symfony Mailer addon is not installed.\n";
    exit(1);
}

// Handle commands
switch ($args[0]) {
    case 'symfony_mailer:process-queue':
        
        if (!$addon->getConfig('queue_enabled', false)) {
            echo "Mail queue is not enabled in configuration.\n";
            exit(0);
        }
        
        $batchSize = isset($args['batch-size']) ? (int) $args['batch-size'] : $addon->getConfig('queue_batch_size', 10);
        
        try {
            $queue = new MailQueue();
            $result = $queue->processBatch($batchSize);
            
            echo sprintf(
                "Queue processing completed: %d emails processed\n",
                $result['processed']
            );
            
            if (!empty($result['errors'])) {
                echo sprintf(
                    "Errors encountered: %s\n",
                    implode(', ', $result['errors'])
                );
                exit(1);
            }
            
        } catch (Exception $e) {
            echo "Queue processing failed: " . $e->getMessage() . "\n";
            exit(1);
        }
        
        break;
        
    default:
        echo "Unknown command: " . $args[0] . "\n";
        exit(1);
}