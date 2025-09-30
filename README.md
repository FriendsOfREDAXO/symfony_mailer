# Symfony Mailer AddOn für REDAXO 🐣

> Das AddOn ist noch in Entwicklung, aber ihr könnt schon testen

Bye-bye PHPMailer! 👋 Dieses REDAXO AddOn bringt den Symfony Mailer ins Spiel, um E-Mails in REDAXO-Projekten zu rocken. Das Ding hat 'ne mega flexible Konfiguration für verschiedene Transport-Methoden (SMTP & Microsoft Graph), E-Mail-Archivierung und Logging am Start.

`mail()` und `sendmail` haben wir mal links liegen gelassen. Stattdessen kannst du hier in IMAP-Ordnern speichern oder über Microsoft Graph senden.

## Features im Überblick

| Feature                               | Beschreibung                                                                                                                                        |
| ------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Symfony Mailer Integration**        | Nutzt die Power der Symfony Mailer Library für 'nen zuverlässigen E-Mail-Versand.                                                                 |
| **Multi-Transport Support**           | Unterstützt SMTP und Microsoft Graph als Transport-Methoden.                                                                                      |
| **SMTP Konfiguration**                | Konfigurierbare SMTP-Einstellungen wie Host, Port, Verschlüsselung (SSL/TLS), Authentifizierung mit Benutzername und Passwort. Dynamische Einstellungen pro E-Mail möglich. |
| **Microsoft Graph Integration**       | Direkter E-Mail-Versand über Microsoft Graph API mit Azure AD App Registration.                                                                   |
| **E-Mail-Archivierung**               | Optionale Speicherung versendeter E-Mails als `.eml`-Dateien im Dateisystem, sortiert nach Jahren und Monaten.                                     |
| **IMAP-Archivierung**                | Optionale Ablage der Mails in einem konfigurierbaren IMAP-Ordner. Dynamische IMAP-Ordner pro E-Mail sind möglich. Die Funktion steht zur Verfügung wenn die IMAP-Erweiterung installiert ist in PHP.|
| **Logging**                            | Protokolliert versendete E-Mails (Status, Absender, Empfänger, Betreff, Fehlermeldungen) in einer Logdatei.                                       |
| **Testverbindung**                    | Überprüft die Transport-Verbindung (SMTP oder Microsoft Graph), auch mit eigenen Einstellungen.                                                  |
| **Detour-Modus**                      | Leitet alle E-Mails an eine konfigurierbare Testadresse um, nützlich für Testumgebungen.                                                        |
| **Einfache Bedienung**                | Intuitive Konfiguration im REDAXO-Backend.                                                                                                          |
| **Flexibilität**                      | Nutze verschiedene Transport-Methoden mit dynamischen Einstellungen pro Mail.                                                                     |
| **HTML E-Mails**                     | Versende HTML-formatierte Mails.                                                                                                                  |
| **Anhänge**                          | Hänge Dateien an E-Mails an.                                                                                                                       |
| **Inline-Bilder**                     | Betten Bilder direkt in den HTML-Inhalt der Mail ein.                                                                                               |
| **YForm Actions**                      | Stellt zwei YForm Actions bereit, um E-Mails aus YForm-Formularen zu senden (`rex_yform_action_symfony_mailer` und `rex_yform_action_symfony_mailer_tpl2email`).|
| **Externe Konfiguration**             | Transport- und IMAP-Einstellungen über `custom_config.yml` definierbar, um z.B. lokale Entwicklungsumgebungen zu konfigurieren.  |

## Installation

1.  AddOn aus dem REDAXO-Repository oder von GitHub laden. (später mal im Installer) 
2.  AddOn in den REDAXO-AddOn-Ordner (`/redaxo/src/addons`) entpacken.
3.  AddOn im REDAXO-Backend aktivieren.
4.  Transport-Typ (SMTP oder Microsoft Graph) wählen und entsprechende Einstellungen konfigurieren.

## Konfiguration

### Transport-Auswahl

Wählen Sie zwischen zwei Transport-Methoden:

- **SMTP**: Klassischer E-Mail-Versand über einen SMTP-Server
- **Microsoft Graph**: Moderner E-Mail-Versand über Microsoft 365/Azure AD

### Allgemeine Einstellungen

*   **Absender-E-Mail:** Die Standard-E-Mail-Adresse, von der Mails gesendet werden sollen.
*   **Absender-Name:** Der Name, der als Absender angezeigt werden soll.
*   **Zeichensatz:** Der Zeichensatz für E-Mails (Standard: `utf-8`).
*   **E-Mail-Archivierung:** Speichert E-Mails als EML-Dateien im Dateisystem.
*   **IMAP Archivierung:** Aktiviert die Archivierung der Mails in einem IMAP-Ordner.
*   **Logging:** Schreibt die E-Mail-Versendung in eine Logdatei.

### SMTP Einstellungen

*   **Host:** Der SMTP-Host.
*   **Port:** Der SMTP-Port.
*   **Sicherheit:** Die Verschlüsselungsmethode (keine, SSL oder TLS).
*   **Authentifizierung:** Authentifizierung aktivieren (falls nötig).
*   **Benutzername:** Der Benutzername für die SMTP-Authentifizierung.
*   **Passwort:** Das Passwort für die SMTP-Authentifizierung.

### Microsoft Graph Einstellungen

*   **Tenant ID:** Die Azure AD Tenant ID (Directory ID).
*   **Client ID:** Die Application (Client) ID der registrierten App.
*   **Client Secret:** Das Client Secret der registrierten App.

### IMAP Einstellungen

*   **IMAP-Host:** Der IMAP-Host.
*   **IMAP-Port:** Der IMAP-Port. Standard ist 993 für IMAPS.
*   **IMAP-Benutzername:** Der Benutzername für die IMAP-Verbindung.
*   **IMAP-Passwort:** Das Passwort für die IMAP-Verbindung.
*   **IMAP-Ordner:** Der Ordner, in dem die E-Mails gespeichert werden sollen (z.B. "Sent").

## Microsoft Graph Setup

### Voraussetzungen

- Azure AD Tenant (Microsoft 365 oder Azure Subscription)
- Administrative Berechtigung zum Erstellen von App Registrations
- Exchange Online Lizenz für den Absender-Benutzer

### 1. Azure AD App Registration erstellen

1. Gehen Sie zum [Azure Portal](https://portal.azure.com)
2. Navigieren Sie zu **Azure Active Directory** > **App registrations**
3. Klicken Sie auf **New registration**
4. Geben Sie einen Namen ein (z.B. "REDAXO Mailer")
5. Wählen Sie **Accounts in this organizational directory only**
6. Klicken Sie auf **Register**

### 2. Client Secret erstellen

1. In der erstellten App gehen Sie zu **Certificates & secrets**
2. Klicken Sie auf **New client secret**
3. Geben Sie eine Beschreibung ein und wählen Sie eine Gültigkeitsdauer
4. **Wichtig:** Kopieren Sie den erstellten Secret-Wert (nur einmal sichtbar!)

### 3. API-Berechtigungen konfigurieren

1. Gehen Sie zu **API permissions**
2. Klicken Sie auf **Add a permission**
3. Wählen Sie **Microsoft Graph**
4. Wählen Sie **Application permissions**
5. Suchen Sie nach **Mail.Send** und wählen Sie es aus
6. Klicken Sie auf **Add permissions**
7. **Wichtig:** Klicken Sie auf **Grant admin consent** (Administrator-Rechte erforderlich)

### 4. Konfigurationswerte sammeln

- **Tenant ID:** Finden Sie unter **Overview** > **Directory (tenant) ID**
- **Client ID:** Finden Sie unter **Overview** > **Application (client) ID**
- **Client Secret:** Der in Schritt 2 erstellte Secret-Wert

### 5. E-Mail-Adresse konfigurieren

Die Absender-E-Mail-Adresse muss:
- Eine gültige E-Mail-Adresse eines Benutzers in Ihrem Azure AD Tenant sein
- Der Benutzer muss über eine Exchange Online Lizenz verfügen

## Verwendung

### Beispiel 1: E-Mail mit Standard-Transport senden

```php
<?php

use FriendsOfRedaxo\SymfonyMailer\RexSymfonyMailer;
use Symfony\Component\Mime\Address;

// Mailer-Klasse schnappen
$mailer = new RexSymfonyMailer();

// E-Mail erstellen
$email = $mailer->createEmail();
$email->to(new Address('empfaenger@example.com', 'Empfänger Name'))
      ->subject('Test Mail')
      ->text('This is a test email!');

// E-Mail mit Standard-Transport senden
if ($mailer->send($email)) {
    echo "E-Mail erfolgreich gesendet!";
} else {
    echo "E-Mail konnte nicht gesendet werden.";
    var_dump($mailer->getErrorInfo());
}
```

### Beispiel 2: E-Mail mit Microsoft Graph senden

```php
<?php

use FriendsOfRedaxo\SymfonyMailer\RexSymfonyMailer;
use Symfony\Component\Mime\Address;

$mailer = new RexSymfonyMailer();
$email = $mailer->createEmail();
$email->to(new Address('empfaenger@example.com', 'Empfänger Name'))
      ->subject('Test Mail via Microsoft Graph')
      ->text('This is a test email sent via Microsoft Graph!');

// Microsoft Graph Einstellungen
$graphSettings = [
    'transport_type' => 'microsoft_graph',
    'graph_tenant_id' => 'your-tenant-id',
    'graph_client_id' => 'your-client-id',
    'graph_client_secret' => 'your-client-secret',
];

if ($mailer->send($email, $graphSettings)) {
    echo "E-Mail via Microsoft Graph erfolgreich gesendet!";
} else {
    echo "E-Mail via Microsoft Graph konnte nicht gesendet werden.";
    var_dump($mailer->getErrorInfo());
}
```

### Beispiel 3: Transport-Verbindung testen

```php
<?php

use FriendsOfRedaxo\SymfonyMailer\RexSymfonyMailer;

$mailer = new RexSymfonyMailer();

// Microsoft Graph Verbindung testen
$graphSettings = [
    'transport_type' => 'microsoft_graph',
    'graph_tenant_id' => 'your-tenant-id',
    'graph_client_id' => 'your-client-id',
    'graph_client_secret' => 'your-client-secret',
];

$testResult = $mailer->testConnection($graphSettings);
if ($testResult['success']) {
    echo "Microsoft Graph Verbindung erfolgreich!\n";
} else {
    echo "Microsoft Graph Verbindung fehlgeschlagen: " . $testResult['message'] . "\n";
}
```

### Beispiel 4: Microsoft Graph Multipart E-Mails

Microsoft Graph unterstützt vollständig multipart E-Mails. Hier sind praktische Beispiele:

#### 4.1 Text + HTML (multipart/alternative)

```php
<?php
use FriendsOfRedaxo\SymfonyMailer\RexSymfonyMailer;
use Symfony\Component\Mime\Address;

$mailer = new RexSymfonyMailer();
$email = $mailer->createEmail();

$email->to(new Address('empfaenger@example.com', 'Empfänger Name'))
      ->subject('Multipart Newsletter via Graph')
      ->text('Newsletter Ausgabe März 2025\n\nHallo!\n\nHier sind unsere Neuigkeiten...\n\nViele Grüße')
      ->html('
        <h1>Newsletter Ausgabe März 2025</h1>
        <p>Hallo!</p>
        <p>Hier sind unsere <strong>Neuigkeiten</strong>:</p>
        <ul>
            <li>Feature A wurde veröffentlicht</li>
            <li>Bug Fix B ist verfügbar</li>
        </ul>
        <p>Viele Grüße<br>Das Team</p>
      ');

$graphSettings = [
    'transport_type' => 'microsoft_graph',
    'graph_tenant_id' => 'your-tenant-id',
    'graph_client_id' => 'your-client-id',
    'graph_client_secret' => 'your-client-secret',
];

// Graph erstellt automatisch multipart/alternative
$mailer->send($email, $graphSettings);
```

#### 4.2 HTML + Inline-Bilder (multipart/related)

```php
<?php
use Symfony\Component\Mime\Part\DataPart;

$email->to(new Address('empfaenger@example.com'))
      ->subject('Newsletter mit Logo via Graph')
      ->text('Newsletter Text-Version - Logo kann nicht angezeigt werden')
      ->html('
        <div style="font-family: Arial;">
            <img src="cid:company-logo" alt="Firmenlogo" style="width:200px;">
            <h1>Wichtige Mitteilung</h1>
            <p>Sehr geehrte Damen und Herren,</p>
            <p>hiermit informieren wir Sie über...</p>
            <img src="cid:chart-image" alt="Verkaufszahlen" style="width:400px;">
        </div>
      ')
      ->addPart(new DataPart(file_get_contents('/path/to/logo.png'), 'image/png', 'company-logo'))
      ->addPart(new DataPart(file_get_contents('/path/to/chart.jpg'), 'image/jpeg', 'chart-image'));

// Graph verarbeitet multipart/related perfekt
$mailer->send($email, $graphSettings);
```

#### 4.3 Komplexe Multipart mit Anhängen (multipart/mixed)

```php
<?php
use Symfony\Component\Mime\Part\File;

$email->to(new Address('kunde@example.com'))
      ->cc(new Address('buchhaltung@example.com'))
      ->subject('Rechnung #2025-001 via Graph')
      ->text('Rechnung im Anhang\n\nSehr geehrte Damen und Herren,\nanbei erhalten Sie Ihre Rechnung.\n\nMit freundlichen Grüßen')
      ->html('
        <div style="font-family: Arial; color: #333;">
            <img src="cid:letterhead" alt="Briefkopf" style="width:100%; max-width:600px;">
            <h2>Rechnung #2025-001</h2>
            <p>Sehr geehrte Damen und Herren,</p>
            <p>anbei erhalten Sie Ihre Rechnung als PDF-Dokument.</p>
            <table style="border-collapse: collapse; width: 100%;">
                <tr style="background: #f5f5f5;">
                    <th style="border: 1px solid #ddd; padding: 8px;">Position</th>
                    <th style="border: 1px solid #ddd; padding: 8px;">Menge</th>
                    <th style="border: 1px solid #ddd; padding: 8px;">Preis</th>
                </tr>
                <tr>
                    <td style="border: 1px solid #ddd; padding: 8px;">REDAXO Lizenz</td>
                    <td style="border: 1px solid #ddd; padding: 8px;">1</td>
                    <td style="border: 1px solid #ddd; padding: 8px;">299,00 €</td>
                </tr>
            </table>
            <p>Mit freundlichen Grüßen<br>Ihr Team</p>
        </div>
      ')
      // Inline-Bild für Briefkopf
      ->addPart(new DataPart(file_get_contents('/path/to/letterhead.png'), 'image/png', 'letterhead'))
      // PDF-Anhang
      ->addPart(new File('/path/to/rechnung-2025-001.pdf'))
      // Excel-Anhang
      ->addPart(new DataPart(
          file_get_contents('/path/to/details.xlsx'), 
          'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 
          'rechnung-details.xlsx'
      ));

// Graph verarbeitet multipart/mixed mit allen Ebenen
$mailer->send($email, $graphSettings);

/*
Resultierende MIME-Struktur in Microsoft Graph:
multipart/mixed
├── multipart/related
│   ├── multipart/alternative
│   │   ├── text/plain (Text-Version)
│   │   └── text/html (HTML-Version)
│   └── image/png (Briefkopf inline)
├── application/pdf (Rechnung)
└── application/vnd.openxml... (Excel)
*/
```

#### 4.4 Microsoft Graph API Multipart-Verarbeitung

Unser Graph Transport konvertiert automatisch die Symfony Email-Struktur in das Graph-Format:

```php
// Symfony Email mit multipart wird zu Graph-JSON:
$message = [
    'message' => [
        'subject' => 'Multipart Test',
        'body' => [
            'contentType' => 'HTML',  // Graph bevorzugt HTML wenn verfügbar
            'content' => '<h1>HTML Content</h1>'
        ],
        // Text-Content wird in Graph als alternative Darstellung behandelt
        'toRecipients' => [...],
        'attachments' => [
            [
                '@odata.type' => '#microsoft.graph.fileAttachment',
                'name' => 'document.pdf',
                'contentType' => 'application/pdf',
                'contentBytes' => 'base64EncodedContent...'
            ],
            // Inline-Bilder werden ebenfalls als Attachments behandelt
            [
                '@odata.type' => '#microsoft.graph.fileAttachment', 
                'name' => 'inline-image',
                'contentType' => 'image/png',
                'contentBytes' => 'base64EncodedContent...',
                'isInline' => true,  // Graph-spezifische Eigenschaft
                'contentId' => 'inline-image'
            ]
        ]
    ]
];
```

### Beispiel 5: E-Mail mit SMTP-Fallback

```php
<?php

use FriendsOfRedaxo\SymfonyMailer\RexSymfonyMailer;
use Symfony\Component\Mime\Address;

$mailer = new RexSymfonyMailer();
$email = $mailer->createEmail();
$email->to(new Address('empfaenger@example.com', 'Empfänger Name'))
      ->subject('Test Mail mit Fallback')
      ->text('This email tries Graph first, then SMTP!');

// Erst Microsoft Graph versuchen
$graphSettings = [
    'transport_type' => 'microsoft_graph',
    'graph_tenant_id' => 'your-tenant-id',
    'graph_client_id' => 'your-client-id',
    'graph_client_secret' => 'your-client-secret',
];

if (!$mailer->send($email, $graphSettings)) {
    // Fallback zu SMTP
    $smtpSettings = [
        'transport_type' => 'smtp',
        'host' => 'smtp.example.com',
        'port' => 587,
        'security' => 'tls',
        'auth' => true,
        'username' => 'smtp-user',
        'password' => 'smtp-password',
    ];
    
    if ($mailer->send($email, $smtpSettings)) {
        echo "E-Mail via SMTP-Fallback erfolgreich gesendet!";
    } else {
        echo "Beide Transport-Methoden fehlgeschlagen.";
    }
}
```

## YForm Actions

Das AddOn stellt zwei YForm Actions zur Verfügung, um E-Mails direkt aus Formularen zu versenden. **Beide Actions unterstützen individuelle SMTP-Zugangsdaten** über den `transport_settings_json` Parameter.

### SMTP-Konfiguration in Actions

#### Allgemeiner Aufbau der SMTP-Zugangsdaten

SMTP-Einstellungen werden als **JSON-String** im 9. Parameter (`transport_settings_json`) der Actions übergeben:

```json
{
  "host": "smtp.example.com",
  "port": 587,
  "security": "tls",
  "auth": true,
  "username": "user@example.com", 
  "password": "secret123"
}
```

#### Parameter-Bedeutung:

- **`host`** - SMTP-Server-Adresse
- **`port`** - Port (25, 587, 465)
- **`security`** - Verschlüsselung (`tls`, `ssl`, `none`)
- **`auth`** - Authentifizierung aktiviert (`true`/`false`)
- **`username`** - SMTP-Benutzername
- **`password`** - SMTP-Passwort

### `rex_yform_action_symfony_mailer`

Diese Action sendet E-Mails direkt mit den angegebenen Inhalten.

#### PHP-Schreibweise (setActionField):

```php
$yform->setActionField(
    'symfony_mailer',
    'sender@example.com',  // mail_from
    '###email###',         // mail_to
    '',                    // mail_cc
    '',                    // mail_bcc
    'Betreff ###name###',  // mail_subject
    'Hallo ###name###!\n\nIhre Nachricht: ###message###', // mail_body
    'text',                // mail_body_type (text/html)
    '{"host":"smtp.gmail.com","port":"587","security":"tls","auth":true,"username":"sender@gmail.com","password":"app-password"}', // transport_settings_json
    '',                    // imap_folder
    ''                     // mail_attachments
);
```

#### Pipe-Schreibweise:

```
action|symfony_mailer|sender@example.com|###email###|||Betreff ###name###|Hallo ###name###!\n\nIhre Nachricht: ###message###|text|{"host":"smtp.gmail.com","port":"587","security":"tls","auth":true,"username":"sender@gmail.com","password":"app-password"}||
```

### `rex_yform_action_symfony_mailer_tpl2email`

Diese Action verwendet REDAXO-Templates für die E-Mail-Gestaltung.

#### PHP-Schreibweise:

```php
$yform->setActionField(
    'symfony_mailer_tpl2email',
    'email_template_name', // template_name
    'email',               // email_to (Feldname)
    'name',                // email_to_name (Feldname)
    'E-Mail konnte nicht gesendet werden!', // warning_message
    '{"host":"smtp.gmail.com","port":"587","security":"tls","auth":true,"username":"sender@gmail.com","password":"app-password"}', // transport_settings_json
    'Sent'                 // imap_folder
);
```

#### Pipe-Schreibweise:

```
action|symfony_mailer_tpl2email|email_template_name|email|name|E-Mail konnte nicht gesendet werden!|{"host":"smtp.gmail.com","port":"587","security":"tls","auth":true,"username":"sender@gmail.com","password":"app-password"}|Sent
```

### SMTP-Provider Beispiele

#### Gmail (mit App-Passwort)
```json
{
  "host": "smtp.gmail.com",
  "port": "587",
  "security": "tls", 
  "auth": true,
  "username": "user@gmail.com",
  "password": "xxxx-xxxx-xxxx-xxxx"
}
```

**Hinweis:** Für Gmail musst du ein [App-Passwort](https://support.google.com/accounts/answer/185833) erstellen!

#### Outlook/Hotmail
```json
{
  "host": "smtp-mail.outlook.com",
  "port": "587",
  "security": "tls",
  "auth": true,
  "username": "user@outlook.com", 
  "password": "your-password"
}
```

#### Office365 Business
```json
{
  "host": "smtp.office365.com",
  "port": "587",
  "security": "tls",
  "auth": true,
  "username": "user@company.com",
  "password": "your-password"
}
```

#### Custom SMTP Server
```json
{
  "host": "mail.example.com",
  "port": "465",
  "security": "ssl",
  "auth": true,
  "username": "noreply@example.com",
  "password": "secret123"
}
```

#### Lokaler SMTP (ohne Auth)
```json
{
  "host": "localhost",
  "port": "25", 
  "security": "none",
  "auth": false
}
```

### Microsoft Graph in YForm Actions

Für Microsoft Graph verwendest du diesen JSON-String:

```json
{
  "transport_type": "microsoft_graph",
  "graph_tenant_id": "your-tenant-id",
  "graph_client_id": "your-client-id", 
  "graph_client_secret": "your-client-secret"
}
```

#### Beispiel Microsoft Graph Action:

```php
$yform->setActionField(
    'symfony_mailer',
    'sender@company.com',  // mail_from
    '###email###',         // mail_to
    '', '', 
    'Formular-Nachricht von ###name###',
    'Name: ###name###\nE-Mail: ###email###\nNachricht: ###message###',
    'text',
    '{"transport_type":"microsoft_graph","graph_tenant_id":"your-tenant-id","graph_client_id":"your-client-id","graph_client_secret":"your-client-secret"}',
    'Sent', 
    ''
);
```

### Vollständige YForm-Beispiele

#### Kontaktformular mit Gmail

```php
// Formular-Felder definieren
$yform->setValueField('text', ['name', 'Name', '1']);
$yform->setValueField('email', ['email', 'E-Mail', '1']);  
$yform->setValueField('textarea', ['message', 'Nachricht', '1']);

// E-Mail-Action mit Gmail SMTP
$yform->setActionField(
    'symfony_mailer',
    'kontakt@example.com',     // Absender
    'admin@example.com',       // Empfänger (feste Adresse)
    '',                        // CC
    '',                        // BCC
    'Kontaktformular: ###name###', // Betreff
    "Neue Nachricht:\n\nName: ###name###\nE-Mail: ###email###\nNachricht:\n###message###", // Body
    'text',                    // Format
    '{"host":"smtp.gmail.com","port":"587","security":"tls","auth":true,"username":"kontakt@example.com","password":"your-app-password"}', // Gmail SMTP
    '',                        // IMAP-Ordner
    ''                         // Anhänge
);

// Erfolgs-Nachricht
$yform->setActionField('showtext', ['Vielen Dank! Ihre Nachricht wurde gesendet.']);
```

#### Newsletter-Anmeldung mit Template und Office365

```php
// Formular-Felder
$yform->setValueField('email', ['email', 'E-Mail-Adresse', '1']);
$yform->setValueField('text', ['firstname', 'Vorname', '1']);
$yform->setValueField('text', ['lastname', 'Nachname', '0']);

// Template-basierte E-Mail mit Office365
$yform->setActionField(
    'symfony_mailer_tpl2email', 
    'newsletter_welcome',      // Template-Name (muss existieren)
    'email',                   // Empfänger-Feld
    'firstname',               // Name-Feld
    'Newsletter-Anmeldung fehlgeschlagen!', // Fehlermeldung
    '{"host":"smtp.office365.com","port":"587","security":"tls","auth":true,"username":"newsletter@company.com","password":"office-password"}', // Office365 SMTP
    'Newsletter'               // IMAP-Ordner
);

// Erfolgs-Action
$yform->setActionField('showtext', ['Willkommen! Sie erhalten eine Bestätigungsmail.']);
```

#### Bestellbestätigung mit Custom SMTP und Anhängen

```php
// Bestell-Felder
$yform->setValueField('text', ['kunde_name', 'Name', '1']);
$yform->setValueField('email', ['kunde_email', 'E-Mail', '1']);
$yform->setValueField('text', ['bestellung_nr', 'Bestellnummer', '1']);

// E-Mail mit Anhang (PDF-Rechnung)
$yform->setActionField(
    'symfony_mailer',
    'shop@example.com',        // Absender
    '###kunde_email###',       // Empfänger aus Formular
    'buchhaltung@example.com', // CC an Buchhaltung
    '',                        // BCC
    'Bestellbestätigung ###bestellung_nr###', // Betreff
    'Sehr geehrte/r ###kunde_name###,\n\nvielen Dank für Ihre Bestellung ###bestellung_nr###.\n\nMit freundlichen Grüßen\nIhr Shop-Team', // Body
    'text',                    // Format
    '{"host":"mail.example.com","port":"465","security":"ssl","auth":true,"username":"shop@example.com","password":"shop-password"}', // Custom SMTP
    'Orders/Sent',             // IMAP-Ordner
    '[{"type":"file","path":"/path/to/agb.pdf"}]' // AGB als Anhang
);
```

### Sicherheitshinweise

1. **Niemals Passwörter** in den Code hartcodieren
2. **Umgebungsvariablen** oder verschlüsselte Config-Dateien verwenden:
   ```php
   $smtp_config = json_encode([
       'host' => $_ENV['SMTP_HOST'],
       'username' => $_ENV['SMTP_USER'],
       'password' => $_ENV['SMTP_PASS'],
       // ...
   ]);
   ```
3. **App-Passwörter** statt normaler Passwörter für Gmail/Outlook
4. **SMTP-Profile-Verwaltung** für zentrale Konfiguration erwägen

## Konfiguration über `custom_config.yml`

### Microsoft Graph Beispiel

```yaml
# Microsoft Graph Transport
transport_type: 'microsoft_graph'
from: "noreply@yourcompany.com"
name: "Your Company"

# Microsoft Graph Einstellungen
graph_tenant_id: "your-tenant-id-here"
graph_client_id: "your-client-id-here"
graph_client_secret: "your-client-secret-here"

# Allgemeine Einstellungen
charset: "utf-8"
archive: true
imap_archive: false
debug: true
logging: 2
detour_mode: false
```

### SMTP Beispiel

```yaml
# SMTP Transport
transport_type: 'smtp'
from: "noreply@yourcompany.com"
name: "Your Company"

# SMTP Einstellungen
host: "smtp.yourcompany.com"
port: 587
security: "tls"
auth: true
username: "your-smtp-username"
password: "your-smtp-password"

# Allgemeine Einstellungen
charset: "utf-8"
archive: true
imap_archive: false
debug: true
logging: 2
detour_mode: false
```

## Detour-Modus

Der Detour-Modus funktioniert mit beiden Transport-Methoden (SMTP und Microsoft Graph):

```yaml
detour_mode: true
detour_address: "developer@yourcompany.com"
```

## Extension Point SYMFONY_MAILER_PRE_SEND

Der Extension Point funktioniert unabhängig vom gewählten Transport:

```php
rex_extension::register('SYMFONY_MAILER_PRE_SEND', function (rex_extension_point $ep) {
    $email = $ep->getSubject();
    
    // Transport-spezifische Validierung
    $addon = rex_addon::get('symfony_mailer');
    $transportType = $addon->getConfig('transport_type');
    
    if ($transportType === 'microsoft_graph') {
        // Graph-spezifische Validierung
        foreach ($email->getTo() as $address) {
            if (!str_ends_with($address->getAddress(), '@yourcompany.com')) {
                return 'Microsoft Graph: Nur interne E-Mail-Adressen erlaubt';
            }
        }
    }
    
    return true;
});
```

## Fehlerbehebung

### SMTP Fehler
*   **Fehler beim Senden:** Check die SMTP-Konfigurationen (Host, Port, Benutzername, Passwort).
*   **SSL/TLS Fehler:** Überprüfe die Verschlüsselungseinstellungen und Server-Unterstützung.

### Microsoft Graph Fehler
*   **Authentifizierung fehlgeschlagen:** Überprüfe Tenant ID, Client ID und Client Secret.
*   **Unzureichende Berechtigungen:** Stelle sicher, dass "Mail.Send" Application Permission erteilt wurde.
*   **Admin Consent fehlt:** Administrator muss die App-Berechtigungen genehmigen.
*   **Absender-Adresse ungültig:** Die Absender-E-Mail muss ein gültiger Azure AD Benutzer mit Exchange Lizenz sein.

### Allgemeine Fehler
*   **Keine E-Mails im Archiv:** Check ob die E-Mail-Archivierung aktiv ist.
*   **IMAP-Fehler:** Check die IMAP-Einstellungen und Ordner-Existenz.
*   **Log-Einträge:** Logdatei checken für detaillierte Fehlermeldungen.
*   **Debug Informationen:** Die `getErrorInfo()` Methode liefert detaillierte Fehlerinfos.

## Microsoft Graph vs SMTP Vergleich

| Feature | SMTP | Microsoft Graph |
|---------|------|-----------------|
| **Setup-Komplexität** | Einfach | Mittel (Azure AD Setup) |
| **Sicherheit** | Abhängig vom Server | OAuth 2.0, moderne Authentifizierung |
| **Skalierbarkeit** | Begrenzt durch Server | Hoch (Microsoft Cloud) |
| **Monitoring** | Server-abhängig | Azure AD Logs, Graph Analytics |
| **Kosten** | Server-abhängig | In Microsoft 365 enthalten |
| **Wartung** | Server-Updates nötig | Managed Service |
| **On-Premise** | Möglich | Cloud-only |
| **Rate Limits** | Server-abhängig | Microsoft Graph Limits |

## Wichtige Hinweise

### Microsoft Graph
- App Registration mit "Mail.Send" Application Permission erforderlich
- Admin Consent für Application Permissions notwendig  
- Absender-E-Mail muss gültiger Azure AD Benutzer mit Exchange Lizenz sein
- Unterstützt OAuth 2.0 Client Credentials Flow
- Rate Limits: [Microsoft Graph Throttling](https://docs.microsoft.com/en-us/graph/throttling)

### SMTP
- Standard-SMTP-Einstellungen im AddOn-Konfigurationsbereich konfigurieren
- Eigene SMTP-Einstellungen für jede Mail direkt in der `send()` Methode angeben
- Eigene SMTP Einstellungen müssen komplett sein, sonst gibt's Probleme

### Allgemein
- Die Logdatei (`/redaxo/data/log/symfony_mailer.log`) hilft bei der Fehlersuche
- E-Mails werden im Ordner `/redaxo/data/addons/symfony_mailer/mail_archive` gespeichert
- Eigene IMAP Ordner müssen auf dem IMAP Server existieren
- Symfony-Exceptions werden gefangen und in `errorInfo` gespeichert

## Author

**Friends Of REDAXO**

* http://www.redaxo.org
* https://github.com/FriendsOfREDAXO

## Credits

**Project Lead**

[Thomas Skerbis](https://github.com/skerbis)

**Microsoft Graph Integration**

Enhanced with Microsoft Graph API support for modern cloud-based email delivery.
