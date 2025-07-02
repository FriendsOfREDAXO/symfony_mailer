# Symfony Mailer AddOn für REDAXO 🐣

> Das AddOn ist noch in Entwicklung, aber ihr könnt schon testen

## Quickstart für Umsteiger vom PHPMailer-Addon

**Du hast bisher das PHPMailer-Addon genutzt?**  
So gelingt der Umstieg auf das Symfony Mailer AddOn:

- **Konfiguration:** Die Einstellungen (Absender, SMTP, etc.) sind ähnlich, aber moderner strukturiert. SMTP bleibt Standard, Microsoft Graph, Mailjet und Mailchimp sind neu.
- **Migration:** Die meisten alten PHPMailer-Konfigurationen lassen sich direkt übernehmen. Für Mailjet/Mailchimp nutze die `custom_config.yml`.
- **API:** Die Methoden sind ähnlich, aber du nutzt jetzt `RexSymfonyMailer` statt `rex_mailer`/`rex_phpmailer`.
- **YForm:** Die Actions funktionieren wie gewohnt, aber mit mehr Optionen (z.B. Microsoft Graph).

**Schnellstart – Beispiel:**

```php
use FriendsOfRedaxo\SymfonyMailer\RexSymfonyMailer;
use Symfony\Component\Mime\Address;

$mailer = new RexSymfonyMailer();
$email = $mailer->createEmail();
$email->to(new Address('empfaenger@example.com', 'Empfänger Name'))
      ->subject('Testmail')
      ->text('Dies ist eine Testmail!');

if ($mailer->send($email)) {
    echo "E-Mail erfolgreich gesendet!";
} else {
    echo "Fehler: ";
    var_dump($mailer->getErrorInfo());
}
```


---

## Was ist das? – Überblick & unterstützte Dienste

Dieses AddOn bringt den modernen [Symfony Mailer](https://symfony.com/doc/current/mailer.html) nach REDAXO. Es unterstützt verschiedene professionelle Versandwege für E-Mails:

- **SMTP**: Klassischer Versand über einen Mailserver
- **Microsoft Graph**: Versand über Microsoft 365/Azure AD (API-basiert)
- **Mailjet**: Versand über die Mailjet-API (Transaktions- & Marketingmails)
- **Mailchimp**: Versand über die Mailchimp-API (Newsletter, Transaktionsmails)

Alle Dienste bieten hohe Zustellraten, Tracking und professionelle Features. Du kannst flexibel wählen, was zu deinem Projekt passt.

### Mailjet & Mailchimp – Was ist das?

**Mailjet** und **Mailchimp** sind professionelle E-Mail-Dienste, die speziell für den Versand von Transaktions- und Marketing-Mails entwickelt wurden. Sie bieten:

- Zuverlässigen Versand großer Mengen von E-Mails (z. B. Newsletter, Systemmails, Transaktionsmails)
- Zustellbarkeits-Optimierung, Bounce-Handling und Statistiken
- API-basierte Integration (kein SMTP nötig)
- DSGVO-konforme Infrastruktur (je nach Anbieter und Tarif)

**Mailjet** eignet sich besonders für Entwickler und Unternehmen, die eine flexible API und gute Zustellbarkeit suchen. **Mailchimp** ist vor allem für Newsletter und Marketing-Automation bekannt, kann aber auch Transaktionsmails per API versenden.

Mit diesem AddOn kannst du beide Dienste als Versand-Backend für REDAXO nutzen – ideal, wenn du hohe Zustellraten, Tracking oder eine skalierbare Cloud-Lösung brauchst.

## Features im Überblick

| Feature                               | Beschreibung                                                                                                                                        |
| ------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Symfony Mailer Integration**        | Nutzt die Power der Symfony Mailer Library für 'nen zuverlässigen E-Mail-Versand.                                                                 |
| **Multi-Transport Support**           | Unterstützt SMTP, Microsoft Graph, Mailjet und Mailchimp als Transport-Methoden.                                                                  |
| **SMTP Konfiguration**                | Konfigurierbare SMTP-Einstellungen wie Host, Port, Verschlüsselung (SSL/TLS), Authentifizierung mit Benutzername und Passwort. Dynamische Einstellungen pro E-Mail möglich. |
| **Microsoft Graph Integration**       | Direkter E-Mail-Versand über Microsoft Graph API mit Azure AD App Registration.                                                                   |
| **Mailjet/Mailchimp API**             | Versand über die jeweilige API, keine SMTP-Server nötig.                                                                                          |
| **E-Mail-Archivierung**               | Optionale Speicherung versendeter E-Mails als `.eml`-Dateien im Dateisystem, sortiert nach Jahren und Monaten.                                     |
| **IMAP-Archivierung**                | Optionale Ablage der Mails in einem konfigurierbaren IMAP-Ordner. Dynamische IMAP-Ordner pro E-Mail sind möglich. Die Funktion steht zur Verfügung wenn die IMAP-Erweiterung installiert ist in PHP.|
| **Mail Queue**                        | Zentrale E-Mail-Warteschlange für zeitversetzten Versand und Batch-Verarbeitung. Ideal für Newsletter und geplante E-Mails.                      |
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

## Konfiguration & unterstützte Dienste

### SMTP (klassisch)

SMTP ist der Standard für den Versand von E-Mails über einen eigenen oder externen Mailserver.

*   **Host:** Der SMTP-Host.
*   **Port:** Der SMTP-Port.
*   **Sicherheit:** Die Verschlüsselungsmethode (keine, SSL oder TLS).
*   **Authentifizierung:** Authentifizierung aktivieren (falls nötig).
*   **Benutzername:** Der Benutzername für die SMTP-Authentifizierung.
*   **Passwort:** Das Passwort für die SMTP-Authentifizierung.

### Microsoft Graph (API)

Versand über Microsoft 365/Azure AD – ideal für Unternehmen mit Microsoft-Infrastruktur.

*   **Tenant ID:** Die Azure AD Tenant ID (Directory ID).
*   **Client ID:** Die Application (Client) ID der registrierten App.
*   **Client Secret:** Das Client Secret der registrierten App.

### Mailjet (API)

> **Hinweis:** Der Versand erfolgt über die Mailjet-API, nicht über SMTP! Es werden nur API-Key und Secret benötigt. Die Felder host/port/security werden ignoriert.
>
> **Mailjet kann im PHP-Code wie jeder andere Transport verwendet werden.** Die Konfiguration erfolgt über die `custom_config.yml` **oder** durch explizite Übergabe der Transport-Settings im Code (siehe Beispiel unten).

*   **Transport-Typ:** `mailjet`
*   **API Key:** Dein Mailjet API Key (als `username`)
*   **API Secret:** Dein Mailjet Secret Key (als `password`)

Beispiel YAML:

```yaml
transport_type: 'mailjet'
username: 'your-mailjet-api-key'
password: 'your-mailjet-secret-key'
```

Beispiel PHP:

```php
$mailjetSettings = [
    'transport_type' => 'mailjet',
    'username' => 'your-mailjet-api-key',
    'password' => 'your-mailjet-secret-key',
];
$mailer->send($email, $mailjetSettings);
```

### Mailchimp (API)

> **Hinweis:** Der Versand erfolgt über die Mailchimp-API, nicht über SMTP! Es wird nur der API Key benötigt. Die Felder host/port/security werden ignoriert.
>
> **Mailchimp kann im PHP-Code wie jeder andere Transport verwendet werden.** Die Konfiguration erfolgt über die `custom_config.yml` **oder** durch explizite Übergabe der Transport-Settings im Code (siehe Beispiel unten).

*   **Transport-Typ:** `mailchimp`
*   **API Key:** Dein Mailchimp API Key (als `password`)
*   **Username:** beliebig (z.B. `apikey`)

Beispiel YAML:

```yaml
transport_type: 'mailchimp'
username: 'apikey'
password: 'your-mailchimp-api-key'
```

Beispiel PHP:

```php
$mailchimpSettings = [
    'transport_type' => 'mailchimp',
    'username' => 'apikey',
    'password' => 'your-mailchimp-api-key',
];
$mailer->send($email, $mailchimpSettings);
```

> **Achtung:** Mailjet und Mailchimp können aktuell **nicht** über die Konfigurationsseite im REDAXO-Backend eingerichtet werden! Die Zugangsdaten müssen in der `custom_config.yml` oder im PHP-Code übergeben werden. Im Backend stehen nur SMTP und Microsoft Graph zur Auswahl.

## Verwendung

### Beispiel 1: E-Mail mit Standard-Transport senden

```php
use FriendsOfRedaxo\SymfonyMailer\RexSymfonyMailer;
use Symfony\Component\Mime\Address;

$mailer = new RexSymfonyMailer();
$email = $mailer->createEmail();
$email->to(new Address('empfaenger@example.com', 'Empfänger Name'))
      ->subject('Test Mail')
      ->text('This is a test email!');

if ($mailer->send($email)) {
    echo "E-Mail erfolgreich gesendet!";
} else {
    echo "E-Mail konnte nicht gesendet werden.";
    var_dump($mailer->getErrorInfo());
}
```

### Beispiel 2: E-Mail mit Microsoft Graph senden

```php
use FriendsOfRedaxo\SymfonyMailer\RexSymfonyMailer;
use Symfony\Component\Mime\Address;

$mailer = new RexSymfonyMailer();
$email = $mailer->createEmail();
$email->to(new Address('empfaenger@example.com', 'Empfänger Name'))
      ->subject('Test Mail via Microsoft Graph')
      ->text('This is a test email sent via Microsoft Graph!');

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

### Beispiel 3: Mailjet/Mailchimp per PHP

```php
// Mailjet
$mailjetSettings = [
    'transport_type' => 'mailjet',
    'username' => 'your-mailjet-api-key',
    'password' => 'your-mailjet-secret-key',
];
$mailer->send($email, $mailjetSettings);

// Mailchimp
$mailchimpSettings = [
    'transport_type' => 'mailchimp',
    'username' => 'apikey',
    'password' => 'your-mailchimp-api-key',
];
$mailer->send($email, $mailchimpSettings);
```

### Beispiel 4: Transport-Verbindung testen

```php
$mailer = new RexSymfonyMailer();
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

### Beispiel 5: Multipart, Inline-Bilder, Anhänge

```php
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File;

$email->to(new Address('kunde@example.com'))
      ->subject('Rechnung #2025-001 via Graph')
      ->text('Rechnung im Anhang')
      ->html('<h2>Rechnung #2025-001</h2><p>Siehe Anhang.</p>')
      ->addPart(new DataPart(file_get_contents('/path/to/logo.png'), 'image/png', 'logo'))
      ->addPart(new File('/path/to/rechnung.pdf'));
$mailer->send($email, $graphSettings);
```

### Beispiel 6: SMTP-Fallback

```php
if (!$mailer->send($email, $graphSettings)) {
    $smtpSettings = [
        'transport_type' => 'smtp',
        'host' => 'smtp.example.com',
        'port' => 587,
        'security' => 'tls',
        'auth' => true,
        'username' => 'your-smtp-username',
        'password' => 'your-smtp-password',
    ];
    $mailer->send($email, $smtpSettings);
}
```

## YForm Actions

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

## Mail Queue - Zentrale E-Mail-Warteschlange

Das AddOn bietet eine zentrale Mail Queue für den zeitversetzten Versand von E-Mails. Dies ist ideal für Newsletter, geplante E-Mails oder die Batch-Verarbeitung großer Mengen.

### Aktivierung der Queue

```php
// In der Konfiguration oder custom_config.yml
queue_enabled: true
queue_batch_size: 10
queue_max_attempts: 3
```

### Verwendung der Queue

#### E-Mail in Queue einreihen

```php
use FriendsOfRedaxo\SymfonyMailer\RexSymfonyMailer;

$mailer = new RexSymfonyMailer();
$email = $mailer->createEmail();
$email->to('empfaenger@example.com')
      ->subject('Geplante E-Mail')
      ->text('Diese E-Mail wird später gesendet.');

// E-Mail für sofortigen Versand in Queue einreihen
$queueId = $mailer->queueEmail($email, [
    'priority' => 3, // 1=niedrig, 3=normal, 5=hoch
    'max_attempts' => 3
]);

// E-Mail für späteren Versand planen
$queueId = $mailer->queueEmail($email, [
    'scheduled_at' => new DateTime('2024-12-31 10:00:00'),
    'priority' => 5
]);
```

#### Automatisches Queuing basierend auf Konfiguration

```php
// Sendet sofort oder reiht in Queue ein, je nach Konfiguration
$result = $mailer->sendOrQueue($email);

if (is_int($result)) {
    echo "E-Mail in Queue eingereiht mit ID: " . $result;
} elseif ($result === true) {
    echo "E-Mail sofort gesendet";
} else {
    echo "Fehler beim Senden/Einreihen";
}
```

#### YForm Action mit Queue-Optionen

```
action|symfony_mailer|from@example.com|to@example.com|||Betreff|Inhalt|html||INBOX.Sent||{"priority":5,"scheduled_at":"2024-12-31 10:00:00"}
```

### Queue-Verarbeitung

#### Über die Admin-Oberfläche
- **Backend → Mailer → Mail Queue**
- Statistiken einsehen
- Batch verarbeiten
- E-Mails verwalten (wiederholen, abbrechen, löschen)

#### Über Cron-Job

```bash
# Alle 5 Minuten Queue verarbeiten
*/5 * * * * php /pfad/zu/redaxo/redaxo/src/addons/symfony_mailer/console.php symfony_mailer:process-queue

# Mit eigener Batch-Größe
*/5 * * * * php /pfad/zu/redaxo/redaxo/src/addons/symfony_mailer/console.php symfony_mailer:process-queue --batch-size=20
```

#### Manuell über PHP

```php
use FriendsOfRedaxo\SymfonyMailer\MailQueue;

$queue = new MailQueue();

// 10 E-Mails verarbeiten
$result = $queue->processBatch(10);
echo "Verarbeitet: " . $result['processed'];

// Statistiken abrufen
$stats = $queue->getStats();
echo "Wartend: " . $stats['pending'];
echo "Gesendet: " . $stats['sent'];
echo "Fehlgeschlagen: " . $stats['failed'];

// Alte E-Mails aufräumen (älter als 30 Tage)
$cleaned = $queue->cleanup(30);
```

### Queue-Status

- **pending**: Wartet auf Verarbeitung
- **processing**: Wird gerade verarbeitet  
- **sent**: Erfolgreich gesendet
- **failed**: Fehlgeschlagen (nach max. Versuchen)
- **cancelled**: Abgebrochen

### Vorteile der Mail Queue

- **Performanz**: Keine Wartezeiten beim Formular-Submit
- **Zuverlässigkeit**: Automatische Wiederholungsversuche bei Fehlern
- **Planung**: E-Mails zu bestimmten Zeiten versenden
- **Batch-Verarbeitung**: Große E-Mail-Mengen effizient verarbeiten
- **Überwachung**: Vollständige Transparenz über Versandstatus

## Konfiguration über `custom_config.yml`

### Microsoft Graph Beispiel

```yaml
transport_type: 'microsoft_graph'
from: "noreply@yourcompany.com"
name: "Your Company"
graph_tenant_id: "your-tenant-id-here"
graph_client_id: "your-client-id-here"
graph_client_secret: "your-client-secret-here"
charset: "utf-8"
archive: true
imap_archive: false
debug: true
logging: 2
detour_mode: false
```

### SMTP Beispiel

```yaml
transport_type: 'smtp'
from: "noreply@yourcompany.com"
name: "Your Company"
host: "smtp.yourcompany.com"
port: 587
security: "tls"
auth: true
username: "your-smtp-username"
password: "your-smtp-password"
charset: "utf-8"
archive: true
imap_archive: false
debug: true
logging: 2
detour_mode: false
```

### Mailjet Beispiel

```yaml
transport_type: 'mailjet'
from: "noreply@yourcompany.com"
name: "Your Company"
username: "your-mailjet-api-key"
password: "your-mailjet-secret-key"
charset: "utf-8"
archive: true
imap_archive: false
debug: true
logging: 2
detour_mode: false
```

### Mailchimp Beispiel

```yaml
transport_type: 'mailchimp'
from: "noreply@yourcompany.com"
name: "Your Company"
username: "apikey"
password: "your-mailchimp-api-key"
charset: "utf-8"
archive: true
imap_archive: false
debug: true
logging: 2
detour_mode: false
```

### Queue-Konfiguration Beispiel

```yaml
# Konfiguration mit aktivierter Mail Queue
transport_type: 'smtp'  # oder 'microsoft_graph', 'mailjet', 'mailchimp'
from: "noreply@yourcompany.com"
name: "Your Company"

# Standard SMTP-Einstellungen
host: "smtp.yourcompany.com"
port: 587
security: "tls"
auth: true
username: "your-smtp-username"
password: "your-smtp-password"

# Queue-spezifische Einstellungen
queue_enabled: true          # Queue aktivieren
queue_batch_size: 20         # 20 E-Mails pro Batch verarbeiten
queue_max_attempts: 5        # 5 Versuche pro E-Mail

# Optional: Cron-Schlüssel für sicheren Web-Zugriff
cron_key: "your-secure-random-key-here"
```

## Detour-Modus

```yaml
detour_mode: true
detour_address: "developer@yourcompany.com"
```

## Extension Point SYMFONY_MAILER_PRE_SEND

```php
rex_extension::register('SYMFONY_MAILER_PRE_SEND', function (rex_extension_point $ep) {
    $email = $ep->getSubject();
    $addon = rex_addon::get('symfony_mailer');
    $transportType = $addon->getConfig('transport_type');
    if ($transportType === 'microsoft_graph') {
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

## Credits

**Projektleitung & Entwicklung**

- [Thomas Skerbis](https://github.com/skerbis)

**Mitwirkende & Inspiration**

- REDAXO Community
- Symfony Mailer Team
- PHPMailer-Addon (Ideengeber für Migration)

**Microsoft Graph Integration**

- Erweiterung um Microsoft Graph API für modernen, cloudbasierten E-Mail-Versand

---

**Lizenz:** MIT
