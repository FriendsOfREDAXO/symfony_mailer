# Symfony Mailer AddOn für REDAXO

Dieses REDAXO AddOn integriert den Symfony Mailer, um E-Mails aus REDAXO-Projekten zu versenden. Es bietet eine flexible Konfiguration für verschiedene SMTP-Einstellungen, E-Mail-Archivierung und Logging.

## Features

-   **Symfony Mailer Integration:** Nutzt die mächtige Symfony Mailer Library für zuverlässigen E-Mail-Versand.
-   **SMTP Konfiguration:**
    -   Konfigurierbare SMTP-Einstellungen wie Host, Port, Verschlüsselung (SSL/TLS), Authentifizierung mit Benutzername und Passwort.
    -   Dynamische SMTP-Einstellungen pro E-Mail möglich.
-   **E-Mail-Archivierung:** Optionale Speicherung der versendeten E-Mails als `.eml`-Dateien im Dateisystem (nach Jahren und Monaten strukturiert).
-   **IMAP-Archivierung:**
    -   Optionale Archivierung der versendeten E-Mails in einem konfigurierbaren IMAP-Ordner.
    -   Dynamische IMAP-Ordner pro E-Mail möglich.
-   **Logging:** Protokollierung der versendeten E-Mails (Status, Absender, Empfänger, Betreff, Fehlermeldungen) in einer Logdatei.
-   **Testverbindung:** Testfunktion, um die SMTP-Verbindung zu überprüfen, auch mit benutzerdefinierten Einstellungen.
-   **Einfache Bedienung:** Intuitive Konfiguration im REDAXO-Backend.
-   **Flexibilität:** Unterstützung für verschiedene SMTP Server mit dynamischen Einstellungen pro Mail.

## Installation

1.  Laden Sie das AddOn aus dem REDAXO-Repository oder von GitHub herunter.
2.  Entpacken Sie das AddOn in den REDAXO-AddOn-Ordner (`/redaxo/src/addons`).
3.  Aktivieren Sie das AddOn im REDAXO-Backend.
4.  Konfigurieren Sie die Standard-SMTP- und IMAP-Einstellungen im AddOn-Konfigurationsbereich.

## Konfiguration

Die folgenden Konfigurationsoptionen sind im AddOn-Konfigurationsbereich verfügbar. Diese Einstellungen dienen als Standardwerte, die beim Versenden der E-Mails benutzt werden, wenn keine dynamischen Einstellungen übergeben werden:

### Allgemeine Einstellungen

*   **Absender-E-Mail:** Die Standard-E-Mail-Adresse, von der aus E-Mails gesendet werden sollen.
*   **Absender-Name:** Der Name, der als Absender angezeigt werden soll.
*   **Zeichensatz:** Der Zeichensatz für E-Mails (Standard: `utf-8`).
*   **E-Mail-Archivierung:** Aktiviert die Speicherung der E-Mails als EML-Dateien im Dateisystem.
*   **IMAP Archivierung:** Aktiviert die Archivierung der E-Mails in einem IMAP-Ordner.
*   **Logging:** Aktiviert das Logging der E-Mail-Versendung in einer Logdatei.

### SMTP Einstellungen

*   **Host:** Der Standard-SMTP-Host.
*   **Port:** Der Standard-SMTP-Port.
*   **Sicherheit:** Die Standard-Verschlüsselungsmethode (keine, SSL oder TLS).
*   **Authentifizierung:** Aktiviert die Standard-Authentifizierung (falls erforderlich).
*   **Benutzername:** Der Standard-Benutzername für die SMTP-Authentifizierung.
*   **Passwort:** Das Standard-Passwort für die SMTP-Authentifizierung.

### IMAP Einstellungen

*   **IMAP-Host:** Der Standard-IMAP-Host.
*   **IMAP-Port:** Der Standard-IMAP-Port. Standard ist 993 für IMAPS.
*   **IMAP-Benutzername:** Der Standard-Benutzername für die IMAP-Verbindung.
*   **IMAP-Passwort:** Das Standard-Passwort für die IMAP-Verbindung.
*   **IMAP-Ordner:** Der Standard-Ordner, in dem die E-Mails gespeichert werden sollen (z.B. "Sent").

## Verwendung

Um das AddOn in Ihrem REDAXO-Projekt zu verwenden, instanziieren Sie die `RexSymfonyMailer` Klasse und verwenden Sie die Methoden `createEmail()` und `send()`. Die `send()` Methode bietet optionale Parameter für dynamische SMTP- und IMAP-Einstellungen.

**Beispiel 1: E-Mail mit Standard-Einstellungen senden:**

```php
<?php

use FriendsOfRedaxo\SymfonyMailer\RexSymfonyMailer;
use Symfony\Component\Mime\Address;

// Instanz der Mailer-Klasse erstellen
$mailer = new RexSymfonyMailer();

// E-Mail erstellen
$email = $mailer->createEmail();
$email->to(new Address('empfaenger@example.com', 'Empfänger Name'))
      ->subject('Test Mail')
      ->text('This is a test email!');

// E-Mail mit Standard-SMTP- und IMAP-Einstellungen senden
if ($mailer->send($email)) {
    echo "E-Mail mit Default-Einstellungen erfolgreich gesendet!";
} else {
    echo "E-Mail mit Default-Einstellungen konnte nicht gesendet werden.";
    var_dump($mailer->getDebugInfo());
}
```

**Beispiel 2: E-Mail mit benutzerdefinierten SMTP-Einstellungen senden:**

```php
<?php

use FriendsOfRedaxo\SymfonyMailer\RexSymfonyMailer;
use Symfony\Component\Mime\Address;

$mailer = new RexSymfonyMailer();
$email = $mailer->createEmail();
$email->to(new Address('empfaenger@example.com', 'Empfänger Name'))
      ->subject('Test Mail mit eigenen SMTP Settings')
      ->text('This is a test email with custom SMTP settings!');

// Benutzerdefinierte SMTP-Einstellungen
$smtpSettings = [
    'host' => 'mail.example.com',
    'port' => 587,
    'security' => 'tls',
    'auth' => true,
    'username' => 'testuser',
    'password' => 'testpassword',
];

if ($mailer->send($email, $smtpSettings)) {
    echo "E-Mail mit benutzerdefinierten SMTP-Einstellungen erfolgreich gesendet!";
} else {
    echo "E-Mail mit benutzerdefinierten SMTP-Einstellungen konnte nicht gesendet werden.";
    var_dump($mailer->getDebugInfo());
}
```

**Beispiel 3: E-Mail mit benutzerdefiniertem IMAP-Ordner senden:**

```php
<?php

use FriendsOfRedaxo\SymfonyMailer\RexSymfonyMailer;
use Symfony\Component\Mime\Address;

$mailer = new RexSymfonyMailer();
$email = $mailer->createEmail();
$email->to(new Address('empfaenger@example.com', 'Empfänger Name'))
      ->subject('Test Mail mit eigenem IMAP Ordner')
      ->text('This is a test email with custom IMAP folder!');

// E-Mail mit benutzerdefiniertem IMAP-Ordner senden
if ($mailer->send($email, [], 'MyCustomSentFolder')) {
     echo "E-Mail mit benutzerdefiniertem IMAP-Ordner erfolgreich gesendet!";
} else {
    echo "E-Mail mit benutzerdefiniertem IMAP-Ordner konnte nicht gesendet werden.";
    var_dump($mailer->getDebugInfo());
}
```

**Beispiel 4: E-Mail mit benutzerdefinierten SMTP-Einstellungen und IMAP-Ordner senden:**

```php
<?php

use FriendsOfRedaxo\SymfonyMailer\RexSymfonyMailer;
use Symfony\Component\Mime\Address;

$mailer = new RexSymfonyMailer();
$email = $mailer->createEmail();
$email->to(new Address('empfaenger@example.com', 'Empfänger Name'))
    ->subject('Test Mail mit eigenen SMTP Settings und IMAP Ordner')
    ->text('This is a test email with custom SMTP settings and IMAP folder!');

// Benutzerdefinierte SMTP-Einstellungen
$smtpSettings = [
    'host' => 'mail.example.com',
    'port' => 587,
    'security' => 'tls',
    'auth' => true,
    'username' => 'testuser',
    'password' => 'testpassword',
];

// E-Mail mit benutzerdefinierten SMTP-Einstellungen und IMAP-Ordner senden
if ($mailer->send($email, $smtpSettings, 'MyCustomSentFolder')) {
    echo "E-Mail mit benutzerdefinierten SMTP-Einstellungen und IMAP-Ordner erfolgreich gesendet!";
} else {
    echo "E-Mail mit benutzerdefinierten SMTP-Einstellungen und IMAP-Ordner konnte nicht gesendet werden.";
    var_dump($mailer->getDebugInfo());
}
```

**Beispiel 5: Testen der Verbindung mit benutzerdefinierten SMTP-Einstellungen**

```php
<?php

use FriendsOfRedaxo\SymfonyMailer\RexSymfonyMailer;

$mailer = new RexSymfonyMailer();

// Benutzerdefinierte SMTP-Einstellungen
$smtpSettings = [
    'host' => 'mail.example.com',
    'port' => 587,
    'security' => 'tls',
    'auth' => true,
    'username' => 'testuser',
    'password' => 'testpassword',
];

// Testen der Verbindung mit benutzerdefinierten SMTP-Einstellungen
$testResult = $mailer->testConnection($smtpSettings);
if ($testResult['success']) {
    echo "Testverbindung mit benutzerdefinierten SMTP-Einstellungen erfolgreich!\n";
} else {
    echo "Testverbindung mit benutzerdefinierten SMTP-Einstellungen fehlgeschlagen: " . $testResult['message'] . "\n";
    var_dump($testResult['debug']);
}
```

## Wichtige Hinweise

-   Die Standard-SMTP- und IMAP-Einstellungen werden im AddOn-Konfigurationsbereich konfiguriert.
-   Dynamische SMTP- und IMAP-Einstellungen können pro E-Mail beim Aufruf der `send()`-Methode übergeben werden.
-   Die Logdatei (`/redaxo/data/log/symfony_mailer.log`) kann zur Fehlersuche verwendet werden.
-   E-Mails werden in der Konfiguration angegebenen Verzeichnis unterhalb `/redaxo/data/addons/symfony_mailer/mail_archive` gespeichert.
-   Wenn Sie eigene SMTP Einstellungen übergeben, müssen Sie alle (host, port, security, username, password) angeben, sonst wird der Mailversand fehlschlagen.
-   Wenn Sie einen eigenen IMAP Ordner übergeben, muss der Ordner auf dem IMAP Server vorhanden sein, sonst wird der Mailversand fehlschlagen.
-   Fehler werden durch Symfony Exceptions abgefangen und in der `$debugInfo` Eigenschaft gespeichert.

## Fehlerbehebung

*   **Fehler beim Senden:** Überprüfen Sie die Standard-Konfigurationseinstellungen (Host, Port, Benutzername, Passwort) oder die dynamisch übergebenen SMTP-Einstellungen.
*   **Keine E-Mails im Archiv:** Stellen Sie sicher, dass die E-Mail-Archivierung aktiviert ist und die Verzeichnisstruktur korrekt ist.
*   **Fehler bei der IMAP-Archivierung:** Überprüfen Sie die Standard-IMAP-Einstellungen oder den dynamisch übergebenen IMAP-Ordner. Stellen Sie sicher, dass der Ordner auf dem Server vorhanden ist.
*   **Log-Einträge:** Analysieren Sie die Logdatei, um detailliertere Informationen zu finden.
*   **Debug Informationen:** Die `getDebugInfo()` Methode kann Fehlerinformationen ausgeben.

## Lizenz

Dieses AddOn ist unter der MIT-Lizenz lizenziert.
