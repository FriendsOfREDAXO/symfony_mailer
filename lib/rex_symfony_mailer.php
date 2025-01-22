<?php

namespace FriendsOfRedaxo\SymfonyMailer;

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use rex;
use rex_addon;
use rex_path;
use rex_i18n;
use rex_log_file;
use rex_file;
use rex_dir;
use rex_logger;
use rex_string;
use rex_yaml_parse_exception;

class RexSymfonyMailer
{
    private Mailer $mailer;
    private string $charset;
    private string $fromAddress;
    private string $fromName;
    private bool $archive;
    private bool $imapArchive;
    private array $errorInfo = [];
    private bool $debug;
    private bool $devMode = false;

    /**
     * @var array<string, mixed>
     */
    private array $smtpSettings = [];

    /**
     * @var array<string, mixed>
     */
    private array $imapSettings = [];

    public function __construct()
    {
        $addon = rex_addon::get('symfony_mailer');

        $this->fromAddress = $addon->getConfig('from');
        $this->fromName = $addon->getConfig('name');
        $this->charset = $addon->getConfig('charset', 'utf-8');
        $this->archive = (bool)$addon->getConfig('archive', false);
        $this->imapArchive = (bool)$addon->getConfig('imap_archive', false);
        $this->debug = (bool)$addon->getConfig('debug', false);


         // DEV-Mode Check
        $devConfigFile = rex_path::addonData('symfony_mailer', 'symfony_mailer_dev.yml');
         if (file_exists($devConfigFile)) {
            $this->devMode = true;
             $devConfig = rex_file::get($devConfigFile);

            if (trim($devConfig)) {
               try {
                 $this->smtpSettings = rex_string::yamlDecode($devConfig);
               } catch (rex_yaml_parse_exception $e) {
                 $this->logError('Failed to parse dev config file', $e);
                 $this->smtpSettings = []; // Setze auf leer um Fehler zu vermeiden.
               }
             } else {
                $this->smtpSettings = [];
              }

         } else {
           $this->smtpSettings = [
                'host' => $addon->getConfig('host'),
                'port' => $addon->getConfig('port'),
                'security' => $addon->getConfig('security'),
                'auth' => $addon->getConfig('auth'),
                'username' => $addon->getConfig('username'),
                'password' => $addon->getConfig('password'),
            ];
        }



        $this->imapSettings = [
            'host' => $addon->getConfig('imap_host'),
            'port' => $addon->getConfig('imap_port', 993),
            'username' => $addon->getConfig('imap_username'),
            'password' => $addon->getConfig('imap_password'),
            'folder' => $addon->getConfig('imap_folder', 'Sent')
        ];
        
        $this->initializeMailer();
    }

    private function initializeMailer(): void
    {
        $dsn = $this->buildDsn();
        try {
            $transport = Transport::fromDsn($dsn);
            $this->mailer = new Mailer($transport);
        } catch (\Exception $e) {
            $this->logError('Mailer initialization failed', $e);
            throw new \RuntimeException('Failed to initialize mailer: ' . $e->getMessage());
        }
    }

    private function buildDsn(array $smtpSettings = []): string
    {
       $settings = empty($smtpSettings) ? $this->smtpSettings : $smtpSettings;


        if($this->devMode && empty($settings)){
              return 'null://';
        }

        $host = $settings['host'];
        $port = $settings['port'];
        $security = $settings['security'];
        $auth = $settings['auth'];
        $username = $settings['username'];
        $password = $settings['password'];

        $dsn = 'smtp://';

        if ($auth && $username && $password) {
            $dsn .= urlencode($username) . ':' . urlencode($password) . '@';
        }

        $dsn .= $host . ':' . $port;

        $options = [];
        if ($security) {
            $options['transport'] = 'smtp';
            $options['encryption'] = $security;
        }

        if (!empty($options)) {
            $dsn .= '?' . http_build_query($options);
        }

        return $dsn;
    }

    private function getErrorHint(string $error): ?string
    {
        if (str_contains($error, 'authentication failed') || str_contains($error, 'Expected response code "235"')) {
            return rex_i18n::msg('symfony_mailer_error_auth');
        }

        if (str_contains($error, 'Connection could not be established')) {
            return rex_i18n::msg('symfony_mailer_error_connection');
        }

        if (str_contains($error, 'SSL')) {
            return rex_i18n::msg('symfony_mailer_error_ssl');
        }

        if (str_contains($error, 'relay access denied') || str_contains($error, 'relay not permitted')) {
            return rex_i18n::msg('symfony_mailer_error_relay');
        }

        if (str_contains($error, 'sender address rejected')) {
            return rex_i18n::msg('symfony_mailer_error_sender');
        }

        if (str_contains($error, 'STARTTLS')) {
            return rex_i18n::msg('symfony_mailer_error_starttls');
        }

        return null;
    }

    public function testConnection(array $smtpSettings = []): array
    {
         if($this->devMode){
             return [
                'success' => true,
                'message' => rex_i18n::msg('symfony_mailer_test_connection_success') . ' (DEV-Mode)'
            ];
        }
        try {
            $transport = Transport::fromDsn($this->buildDsn($smtpSettings));
            $transport->start();

            return [
                'success' => true,
                'message' => rex_i18n::msg('symfony_mailer_test_connection_success')
            ];
        } catch (\Exception $e) {
            $this->logError('SMTP connection test failed', $e);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error_details' => $this->getErrorDetails($e)
            ];
        }
    }

    public function createEmail(): Email
    {
        $email = new Email();
        $email->from(new Address($this->fromAddress, $this->fromName));
        $email->charset = $this->charset;

        return $email;
    }

    public function send(Email $email, array $smtpSettings = [], string $imapFolder = ''): bool
    {
        $mailer = $this->mailer;
        if (!empty($smtpSettings)) {
            $dsn = $this->buildDsn($smtpSettings);
            try {
                $transport = Transport::fromDsn($dsn);
                $mailer = new Mailer($transport);
            } catch (\Exception $e) {
                $this->logError('Failed to create custom mailer', $e);
                return false;
            }
        }


      if ($this->devMode) {
          $this->archiveEmail($email);
            $this->log('OK', $email, ' (DEV-Mode)');
         return true;
       }


        try {
            $mailer->send($email);

            if ($this->archive) {
                $this->archiveEmail($email);
            }

            if ($this->imapArchive) {
                $this->archiveToImap($email, $imapFolder);
            }

            $this->log('OK', $email);
            return true;
        } catch (TransportExceptionInterface $e) {
            $this->logError('Failed to send email', $e);
            return false;
        }
    }

    private function archiveEmail(Email $email): void
    {
        $dir = self::getArchiveFolder() . '/' . date('Y') . '/' . date('m');
        if (!is_dir($dir)) {
            rex_dir::create($dir);
        }

        $count = 1;
        $filename = date('Y-m-d_H_i_s') . '.eml';
        while (is_file($dir . '/' . $filename)) {
            $filename = date('Y-m-d_H_i_s') . '_' . (++$count) . '.eml';
        }

        rex_file::put($dir . '/' . $filename, $email->toString());
    }

    private function archiveToImap(Email $email, string $folder = ''): void
    {
        $settings = $this->imapSettings;
        if (!empty($folder)) {
            $settings['folder'] = $folder;
        }

        $host = $settings['host'];
        $port = $settings['port'];
        $username = $settings['username'];
        $password = $settings['password'];
        $folder = $settings['folder'];

        $mailbox = sprintf('{%s:%d/imap/ssl}%s', $host, $port, $folder);

        if ($connection = imap_open($mailbox, $username, $password)) {
            imap_append($connection, $mailbox, $email->toString());
            imap_close($connection);
        }
    }

    private function logError(string $context, \Exception $e): void
    {
        $this->errorInfo = $this->getErrorDetails($e);

        // Logging nur wenn gewünscht (nach Einstellung)
        $addon = rex_addon::get('symfony_mailer');
        $logging = (int)$addon->getConfig('logging');

        if ($logging === 1) { // nur Fehler loggen
            $this->log('ERROR', new Email(), $e->getMessage());
        } elseif ($logging === 2) { // alles loggen
            $this->log('ERROR', new Email(), $context . ': ' . $e->getMessage() . ($this->debug ? "\n" . $e->getTraceAsString() : ''));
        }
    }

    private function getErrorDetails(\Exception $e): array
    {
        $details = [
            'message' => $e->getMessage(),
            'dsn' => $this->buildDsn()
        ];

        if ($hint = $this->getErrorHint($e->getMessage())) {
            $details['hint'] = $hint;
        }

        if ($this->debug) {
            $details['file'] = $e->getFile();
            $details['line'] = $e->getLine();
            $details['trace'] = $e->getTraceAsString();
        }

        return $details;
    }

    private function log(string $status, Email $email, string $error = ''): void
    {
        $addon = rex_addon::get('symfony_mailer');
        $logging = (int)$addon->getConfig('logging');

        if ($logging === 0) {
            return;
        }

        // Bei Fehlern immer loggen wenn logging = 1 oder 2
        // Bei Erfolg nur loggen wenn logging = 2
        if ($logging === 1 && $status !== 'ERROR') {
            return;
        }

        $fromAddresses = [];
        foreach ($email->getFrom() as $address) {
            $fromAddresses[] = $address->toString();
        }

        $toAddresses = [];
        foreach ($email->getTo() as $address) {
            $toAddresses[] = $address->toString();
        }

        $log = rex_log_file::factory(self::getLogFile());
        $data = [
            $status,
            implode(', ', $fromAddresses),
            implode(', ', $toAddresses),
            $email->getSubject(),
            $error
        ];
        $log->add($data);
    }

    public function getErrorInfo(): array
    {
        return $this->errorInfo;
    }

    public static function getArchiveFolder(): string
    {
        return rex_path::addonData('symfony_mailer', 'mail_archive');
    }

    public static function getLogFile(): string
    {
        return rex_path::log('symfony_mailer.log');
    }
}
