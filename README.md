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

### Mailjet Einstellungen

*   **Transport-Typ:** `mailjet`
*   **Host:** `in-v3.mailjet.com`
*   **Port:** 587
*   **Benutzername:** Dein Mailjet API Key
*   **Passwort:** Dein Mailjet Secret Key
*   **Sicherheit:** `tls`

Beispiel:

```yaml
transport_type: 'mailjet'
host: 'in-v3.mailjet.com'
port: 587
username: 'your-mailjet-api-key'
password: 'your-mailjet-secret-key'
security: 'tls'
```

### Mailchimp Einstellungen

*   **Transport-Typ:** `mailchimp`
*   **Host:** `smtp.mailchimp.com`
*   **Port:** 587
*   **Benutzername:** beliebig (z.B. `apikey`)
*   **Passwort:** Dein Mailchimp API Key
*   **Sicherheit:** `tls`

Beispiel:

```yaml
transport_type: 'mailchimp'
host: 'smtp.mailchimp.com'
port: 587
username: 'apikey'
password: 'your-mailchimp-api-key'
security: 'tls'
```

> **Hinweis:** Mailjet und Mailchimp werden wie SMTP angesprochen, benötigen aber die jeweiligen API-Zugangsdaten. Die Auswahl erfolgt über den Transport-Typ. Es gibt keine eigenen Backend-Seiten, die Einstellungen werden wie bei SMTP gepflegt.

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
        'username' => 'your-smtp-username',
        'password' => 'your-smtp-password',
    ];
    
    if ($mailer->send($email, $smtpSettings)) {
        echo "E-Mail via SMTP-Fallback erfolgreich gesendet!";
    } else {
        echo "Beide Transport-Methoden fehlgeschlagen.";
    }
}
```

## YForm Actions

Die YForm Actions unterstützen jetzt auch Microsoft Graph Transport:

### `rex_yform_action_symfony_mailer`

```php
$yform->setActionField(
    'symfony_mailer',
    'from@example.com',  // mail_from
    '###email###',  // mail_to
    '', // mail_cc
    '', // mail_bcc
    'Test Email from YForm', // mail_subject
    'Hallo ###name###', // mail_body
    'text', // mail_body_type
    '{"transport_type":"microsoft_graph","graph_tenant_id":"...","graph_client_id":"...","graph_client_secret":"..."}', // transport_settings_json
    'Sent', // imap_folder
    '' // mail_attachments
);
```

### `rex_yform_action_symfony_mailer_tpl2email`

```php
$yform->setActionField(
    'symfony_mailer_tpl2email',
    'mein_email_template', // template_name
    'email',            // email_to (Feldname)
    '',              // email_to_name
    'Es ist ein Fehler aufgetreten!', // warning_message
    '{"transport_type":"microsoft_graph","graph_tenant_id":"...","graph_client_id":"...","graph_client_secret":"..."}', // transport_settings_json
    'Sent'    // imap_folder
);
```

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

### Mailjet Beispiel

```yaml
# Mailjet Transport
transport_type: 'mailjet'
from: "noreply@yourcompany.com"
name: "Your Company"

# Mailjet Einstellungen
host: "in-v3.mailjet.com"
port: 587
username: "your-mailjet-api-key"
password: "your-mailjet-secret-key"
security: "tls"

# Allgemeine Einstellungen
charset: "utf-8"
archive: true
imap_archive: false
debug: true
logging: 2
detour_mode: false
```

### Mailchimp Beispiel

```yaml
# Mailchimp Transport
transport_type: 'mailchimp'
from: "noreply@yourcompany.com"
name: "Your Company"

# Mailchimp Einstellungen
host: "smtp.mailchimp.com"
port: 587
username: "apikey"
password: "your-mailchimp-api-key"
security: "tls"

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
