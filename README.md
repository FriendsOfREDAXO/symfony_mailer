# Symfony Mailer AddOn für REDAXO 🐣

> Das AddOn ist noch in Entwicklung, aber ihr könnt schon testen

Bye-bye PHPMailer! 👋 Dieses REDAXO AddOn bringt den Symfony Mailer ins Spiel, um E-Mails in REDAXO-Projekten zu rocken. Das Ding hat 'ne mega flexible Konfiguration für verschiedene SMTP-Einstellungen, E-Mail-Archivierung und Logging am Start.

`mail()` und `sendmail` haben wir mal links liegen gelassen. Stattdessen kannst du hier in IMAP-Ordnern speichern.

## Features im Überblick

| Feature                               | Beschreibung                                                                                                                                        |
| ------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Symfony Mailer Integration**        | Nutzt die Power der Symfony Mailer Library für 'nen zuverlässigen E-Mail-Versand.                                                                 |
| **SMTP Konfiguration**                | Konfigurierbare SMTP-Einstellungen wie Host, Port, Verschlüsselung (SSL/TLS), Authentifizierung mit Benutzername und Passwort. Dynamische Einstellungen pro E-Mail möglich. |
| **E-Mail-Archivierung**               | Optionale Speicherung versendeter E-Mails als `.eml`-Dateien im Dateisystem, sortiert nach Jahren und Monaten.                                     |
| **IMAP-Archivierung**                | Optionale Ablage der Mails in einem konfigurierbaren IMAP-Ordner. Dynamische IMAP-Ordner pro E-Mail sind möglich.                                 |
| **Logging**                            | Protokolliert versendete E-Mails (Status, Absender, Empfänger, Betreff, Fehlermeldungen) in einer Logdatei.                                       |
| **Testverbindung**                    | Überprüft die SMTP-Verbindung, auch mit eigenen Einstellungen.                                                                                    |
| **Detour-Modus**                      | Leitet alle E-Mails an eine konfigurierbare Testadresse um, nützlich für Testumgebungen.                                                        |
| **Einfache Bedienung**                | Intuitive Konfiguration im REDAXO-Backend.                                                                                                          |
| **Flexibilität**                      | Nutze verschiedene SMTP Server mit dynamischen Einstellungen pro Mail.                                                                              |
| **HTML E-Mails**                     | Versende HTML-formatierte Mails.                                                                                                                  |
| **Anhänge**                          | Hänge Dateien an E-Mails an.                                                                                                                       |
| **Inline-Bilder**                     | Betten Bilder direkt in den HTML-Inhalt der Mail ein.                                                                                               |
| **YForm Actions**                      | Stellt zwei YForm Actions bereit, um E-Mails aus YForm-Formularen zu senden (`rex_yform_action_symfony_mailer` und `rex_yform_action_symfony_mailer_tpl2email`).|
| **Externe Konfiguration**             | SMTP- und IMAP-Einstellungen über `custom_config.yml` definierbar, um z.B. lokale Entwicklungsumgebungen zu konfigurieren.  |

## Installation

1.  AddOn aus dem REDAXO-Repository oder von GitHub laden. (später mal im Installer) 
2.  AddOn in den REDAXO-AddOn-Ordner (`/redaxo/src/addons`) entpacken.
3.  AddOn im REDAXO-Backend aktivieren.
4.  Standard-SMTP- und IMAP-Einstellungen im AddOn-Konfigurationsbereich eintragen.

## Konfiguration

Die folgenden Konfigurationsoptionen stehen im AddOn-Konfigurationsbereich zur Verfügung. Diese Einstellungen dienen als Standardwerte, die beim Versenden der E-Mails genutzt werden, wenn keine dynamischen Einstellungen übergeben werden:

### Allgemeine Einstellungen

*   **Absender-E-Mail:** Die Standard-E-Mail-Adresse, von der Mails gesendet werden sollen.
*   **Absender-Name:** Der Name, der als Absender angezeigt werden soll.
*   **Zeichensatz:** Der Zeichensatz für E-Mails (Standard: `utf-8`).
*   **E-Mail-Archivierung:** Speichert E-Mails als EML-Dateien im Dateisystem.
*   **IMAP Archivierung:** Aktiviert die Archivierung der Mails in einem IMAP-Ordner.
*   **Logging:** Schreibt die E-Mail-Versendung in eine Logdatei.

### SMTP Einstellungen

*   **Host:** Der Standard-SMTP-Host.
*   **Port:** Der Standard-SMTP-Port.
*   **Sicherheit:** Die Standard-Verschlüsselungsmethode (keine, SSL oder TLS).
*   **Authentifizierung:** Standard-Authentifizierung aktivieren (falls nötig).
*   **Benutzername:** Der Standard-Benutzername für die SMTP-Authentifizierung.
*   **Passwort:** Das Standard-Passwort für die SMTP-Authentifizierung.

### IMAP Einstellungen

*   **IMAP-Host:** Der Standard-IMAP-Host.
*   **IMAP-Port:** Der Standard-IMAP-Port. Standard ist 993 für IMAPS.
*   **IMAP-Benutzername:** Der Standard-Benutzername für die IMAP-Verbindung.
*   **IMAP-Passwort:** Das Standard-Passwort für die IMAP-Verbindung.
*   **IMAP-Ordner:** Der Standard-Ordner, in dem die E-Mails gespeichert werden sollen (z.B. "Sent").

## Verwendung

Um das AddOn in deinem REDAXO-Projekt zu verwenden, schnapp dir die `RexSymfonyMailer` Klasse und nutze die Methoden `createEmail()` und `send()`. Die `send()` Methode hat optionale Parameter für dynamische SMTP- und IMAP-Einstellungen.

**Beispiel 1: E-Mail mit Standard-Einstellungen senden:**

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

// E-Mail mit Standard-SMTP- und IMAP-Einstellungen senden
if ($mailer->send($email)) {
    echo "E-Mail mit Default-Einstellungen erfolgreich gesendet!";
} else {
    echo "E-Mail mit Default-Einstellungen konnte nicht gesendet werden.";
    var_dump($mailer->getDebugInfo());
}
```

**Beispiel 2: E-Mail mit eigenen SMTP-Einstellungen senden:**

```php
<?php

use FriendsOfRedaxo\SymfonyMailer\RexSymfonyMailer;
use Symfony\Component\Mime\Address;

$mailer = new RexSymfonyMailer();
$email = $mailer->createEmail();
$email->to(new Address('empfaenger@example.com', 'Empfänger Name'))
      ->subject('Test Mail mit eigenen SMTP Settings')
      ->text('This is a test email with custom SMTP settings!');

// Eigene SMTP-Einstellungen
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

**Beispiel 3: E-Mail mit eigenem IMAP-Ordner senden:**

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

**Beispiel 4: E-Mail mit eigenen SMTP-Einstellungen und IMAP-Ordner senden:**

```php
<?php

use FriendsOfRedaxo\SymfonyMailer\RexSymfonyMailer;
use Symfony\Component\Mime\Address;

$mailer = new RexSymfonyMailer();
$email = $mailer->createEmail();
$email->to(new Address('empfaenger@example.com', 'Empfänger Name'))
    ->subject('Test Mail mit eigenen SMTP Settings und IMAP Ordner')
    ->text('This is a test email with custom SMTP settings and IMAP folder!');

// Eigene SMTP-Einstellungen
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

**Beispiel 5: Verbindung mit eigenen SMTP-Einstellungen testen**

```php
<?php

use FriendsOfRedaxo\SymfonyMailer\RexSymfonyMailer;

$mailer = new RexSymfonyMailer();

// Eigene SMTP-Einstellungen
$smtpSettings = [
    'host' => 'mail.example.com',
    'port' => 587,
    'security' => 'tls',
    'auth' => true,
    'username' => 'testuser',
    'password' => 'testpassword',
];

// Teste die Verbindung mit eigenen SMTP-Einstellungen
$testResult = $mailer->testConnection($smtpSettings);
if ($testResult['success']) {
    echo "Testverbindung mit benutzerdefinierten SMTP-Einstellungen erfolgreich!\n";
} else {
    echo "Testverbindung mit benutzerdefinierten SMTP-Einstellungen fehlgeschlagen: " . $testResult['message'] . "\n";
    var_dump($testResult['debug']);
}
```

**Beispiel 6: E-Mail mit HTML-Inhalt, Anhängen und Inline-Bildern senden:**

```php
<?php

use FriendsOfRedaxo\SymfonyMailer\RexSymfonyMailer;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File;

$mailer = new RexSymfonyMailer();
$email = $mailer->createEmail();
$email->to(new Address('empfaenger@example.com', 'Empfänger Name'))
    ->subject('Test Mail mit HTML, Anhang und Inline-Bild')
    ->html('<p>Dies ist eine <b>Test-E-Mail</b> mit <i>HTML</i>-Inhalt und einem Inline-Bild:</p>' .
           '<img src="cid:inline-image" alt="Inline Bild">' ) //Verwendung von cid
    ->addPart(new DataPart('Testdaten', 'text/plain', 'test.txt'))
    ->addPart(new File('/path/to/your/file.pdf')) //Datei-Anhang
    ->addPart(new DataPart(file_get_contents('/path/to/your/image.png'), 'image/png', 'inline-image'))
    // Inline-Bild mit angepasster cid (Content-ID)

// E-Mail mit HTML-Inhalt, Anhang und Inline-Bild senden
if ($mailer->send($email)) {
    echo "E-Mail mit HTML-Inhalt, Anhängen und Inline-Bild erfolgreich gesendet!";
} else {
    echo "E-Mail mit HTML-Inhalt, Anhängen und Inline-Bild konnte nicht gesendet werden.";
    var_dump($mailer->getDebugInfo());
}
```

## YForm Actions

Dieses AddOn stellt zwei YForm Actions bereit, um E-Mails aus YForm Formularen zu senden: `rex_yform_action_symfony_mailer` und `rex_yform_action_symfony_mailer_tpl2email`.

### `rex_yform_action_symfony_mailer`

Mit dieser Action lassen sich E-Mails direkt aus YForm-Formularen raushauen. Sie hat folgende Optionen:

*   **`from@email.de`:** Die Absender-E-Mail-Adresse. Kann Platzhalter wie `###feldname###` oder `+++feldname+++` enthalten.
*   **`to@email.de[,to2@email.de]`:**  Die Empfänger-E-Mail-Adresse(n), mit Komma getrennt. Kann Platzhalter wie `###feldname###` oder `+++feldname+++` enthalten.
*   **`cc@email.de[,cc2@email.de]`:** (Optional) Die CC-Empfänger-E-Mail-Adresse(n), mit Komma getrennt. Kann Platzhalter wie `###feldname###` oder `+++feldname+++` enthalten.
*   **`bcc@email.de[,bcc2@email.de]`:** (Optional) Die BCC-Empfänger-E-Mail-Adresse(n), mit Komma getrennt. Kann Platzhalter wie `###feldname###` oder `+++feldname+++` enthalten.
*   **`Mailsubject`:** Der Betreff der E-Mail. Kann Platzhalter wie `###feldname###` oder `+++feldname+++` enthalten.
*   **`Mailbody###name###`:** Der Inhalt der E-Mail. Kann Platzhalter wie `###feldname###` oder `+++feldname+++` enthalten.
*   **`text/html`:**  (Optional) Gibt an, ob der E-Mail-Body als `text` (Standard) oder `html` interpretiert werden soll.
*   **`{"host":"...", "port":"...", ...}`:** (Optional) Ein JSON-String mit eigenen SMTP-Einstellungen.
*  **`IMAP-Folder`:**  (Optional) Ein IMAP Ordner in dem die Mails abgelegt werden soll.
*   **`[{"type":"file", "path":"/path/to/file.pdf"}, {"type":"data", "data":"...", "contentType":"...", "filename":"..."}]`:** (Optional) Ein JSON-String mit Array von Anhangsdaten. Die Anhänge können entweder eine Datei (type:file, path:Pfad) sein oder über `DataPart` (type: data, data: Inhalt, contentType: Typ, filename: Dateiname) eingebunden werden.

**Beispiel:**

PIPE
```
action|symfony_mailer|from@example.com|to@example.com|cc@example.com|bcc@example.com|Betreff|Hallo ###name###!|text|{"host":"mail.example.com", "port":587, "security":"tls", "auth":true, "username":"testuser", "password":"testpassword"}|"MyCustomSentFolder"|[{"type":"file", "path":"/path/to/file.pdf"}, {"type":"data", "data":"Dies ist ein Textinhalt", "contentType":"text/plain", "filename":"mytext.txt"}]
```

PHP
```php
$yform->setActionField(
    'symfony_mailer',
    'info@example.com',  // mail_from
    '###email###',  // mail_to
    'cc@example.com', // mail_cc
    '',  // mail_bcc
    'Test Email from YForm', // mail_subject
    'Hallo ###name###,\n\nNachricht: ###message###', // mail_body
    'text', // mail_body_type
   '', // smtp_settings_json
   'Sent', // imap_folder
    '' // mail_attachments
);
```


### `rex_yform_action_symfony_mailer_tpl2email`

Diese Action nutzt E-Mail-Vorlagen, die im YForm-E-Mail-Template-AddOn erstellt werden. Sie hat folgende Optionen:

*   **`emailtemplate`:** Der Name der E-Mail-Vorlage.
*   **`[email@domain.de/email_label]`:** Die Empfänger-E-Mail-Adresse oder ein Feldname, der die E-Mail-Adresse enthält. Kann Platzhalter wie `###feldname###` oder `+++feldname+++` enthalten.
*   **`[email_name]`:** (Optional) Der Name des Empfängers.
*   **`[Fehlermeldung wenn Versand fehlgeschlagen ist/html]`:** (Optional) Eine Fehlermeldung, die ausgegeben wird, wenn der E-Mail-Versand fehlschlägt. Kann HTML enthalten.
*   **`{"host":"...", "port":"...", ...}`:** (Optional) Ein JSON-String mit eigenen SMTP-Einstellungen.
*  **`IMAP-Folder`:**  (Optional) Ein IMAP Ordner in dem die Mails abgelegt werden soll.

Die E-Mail Vorlagen sollten folgende Daten enthalten:

*   `mail_from`
*   `mail_from_name`
*  `mail_to` (wird durch die YForm-Action gesetzt, kann in der Template für CC/BCC genutzt werden)
*  `mail_to_name` (wird durch die YForm-Action gesetzt)
*   `mail_subject`
*   `mail_body`
* `mail_body_type` (optional: text oder html)
* `mail_cc`  (optional)
* `mail_bcc` (optional)
*   `attachments` (optional): Ein Array von Anhängen (mit `path` - oder `data`, `contentType`, `filename`)

**Beispiel:**

PIPE
```
action|symfony_mailer_tpl2email|mein_email_template|email@example.com|Name|E-Mail konnte nicht gesendet werden|{"host":"mail.example.com", "port":587, "security":"tls", "auth":true, "username":"testuser", "password":"testpassword"}|"MyCustomSentFolder"
```

PHP
```php
$yform->setActionField(
    'symfony_mailer_tpl2email',
    'mein_email_template', // template_name
    'email',            // email_to (Feldname)
    '',              // email_to_name
    'Es ist ein Fehler aufgetreten!', // warning_message
    '{"host":"mailhog","port":1025, "auth":false}', // smtp_settings_json
    'Sent'    // imap_folder

);
```

## Wichtige Hinweise

-   Standard-SMTP- und IMAP-Einstellungen im AddOn-Konfigurationsbereich konfigurieren.
-   Eigene SMTP- und IMAP-Einstellungen für jede Mail direkt in der `send()` Methode angeben.
-   Die Logdatei (`/redaxo/data/log/symfony_mailer.log`) hilft bei der Fehlersuche.
-   E-Mails werden im Ordner unterhalb `/redaxo/data/addons/symfony_mailer/mail_archive` gespeichert.
-   Eigene SMTP Einstellungen müssen komplett sein (host, port, security, username, password), sonst gibt's Probleme.
-   Eigene IMAP Ordner müssen auf dem IMAP Server existieren, sonst klappt das Archivieren nicht.
-   Symfony-Exceptions werden gefangen und in `$debugInfo` gespeichert.

### `DataPart` und `File` - Anhänge und Inline-Bilder im Detail

Im Symfony Mailer, werden die E-Mail Anhänge nicht über ein Array von Datei-Pfaden übergeben, sondern mit Objekten der Klasse `DataPart` oder `File`. Das ist ein wichtiger Unterschied zum PHPMailer, mit dem viele REDAXO-Nutzer vertraut sind.

**`DataPart`**: Stellt einen E-Mail-Anhang dar, der aus Daten (z.B. einem String) erzeugt wird, nicht aus einer Datei. Das bedeutet, dass Daten direkt in den Anhang eingebettet werden, ohne eine temporäre Datei auf der Festplatte anlegen zu müssen.

```php
use Symfony\Component\Mime\Part\DataPart;

// Ein Text-Anhang:
new DataPart('Dies ist der Inhalt des Textanhangs.', 'text/plain', 'mytext.txt');

// Ein Inline-Bild (siehe unten):
new DataPart(file_get_contents('/pfad/zum/bild.png'), 'image/png', 'inline-image');
```

**`File`**: Stellt einen Anhang dar, der aus einer Datei auf der Festplatte erzeugt wird. Das ist vergleichbar mit dem Anhängen von Dateien im PHPMailer, aber auch hier wird anstelle eines Dateipfades, ein File Objekt übergeben.

```php
use Symfony\Component\Mime\Part\File;
new File('/pfad/zu/datei.pdf');
```
### Inline-Bilder mit `DataPart`

Um Inline-Bilder zu verwenden, werden die Bilder ebenfalls als `DataPart` hinzugefügt. Hier ist der Trick:

1.  **Einzigartige ID (`cid`):** `cid:` (Content-ID) als URI im `<img>`-Tag (z.B. `<img src="cid:inline-image">`).
2.  **`DataPart`:** `DataPart` Instanz mit den Bilddaten, dem Bildtyp und der gleichen ID als Dateiname.
3.  **Zuordnung:** Der Mail Client verknüpft den String `inline-image` in deinem HTML mit dem korrespondierenden `DataPart` Objekt.

```php
   $email->html('<img src="cid:inline-image" alt="Inline Bild">')
   ->addPart(new DataPart(file_get_contents('/path/to/your/image.png'), 'image/png', 'inline-image'));
```
In diesem Beispiel wird der Inhalt der Bilddatei `/path/to/your/image.png` als Inline-Bild an die E-Mail angehängt.

## 2. Konfiguration über `custom_config.yml` z.B. für eine lokale Entwicklungsumgebung

Das AddOn bietet die Möglichkeit, die SMTP- und IMAP-Einstellungen über eine externe Konfigurationsdatei zu definieren. Dies erlaubt eine flexible Anpassung der Einstellungen, ohne direkt in die AddOn-Konfiguration einzugreifen. Die Einstellungen in der `custom_config.yml` Datei überschreiben die Einstellungen der AddOn-Konfiguration.

### 2.1 Erstellung der `custom_config.yml`

1.  Erstelle eine Datei namens `custom_config.yml` im Ordner `data/addons/symfony_mailer/`.
2.  Füge die gewünschten Einstellungen im YAML-Format hinzu.
3.  **Wichtig**: Alle Parameter aus der Addon-Konfiguration müssen in der `custom_config.yml` vorhanden sein, da sonst die Parameter der Addon-Konfiguration verwendet werden. Es wird immer die gesamte Konfiguration überschrieben.

### 2.2 Struktur der `custom_config.yml`

Hier ist ein Beispiel für die Struktur der `custom_config.yml` Datei:

```yaml
from: "your-custom-from@example.tld"
name: "Your Custom Name"
charset: "utf-8"
archive: true
imap_archive: false
debug: true
host: "your.custom.smtp.host"
port: 587
security: "tls"
auth: true
username: "your.custom.smtp.username"
password: "your.custom.smtp.password"
imap_host: "your.custom.imap.host"
imap_port: 993
imap_username: "your.custom.imap.username"
imap_password: "your.custom.imap.password"
imap_folder: "INBOX.Sent"
detour_mode: false
detrour_adresse: 'some_adress@example.tld'

```

**Erläuterung der Parameter:**

*   **`from`**: Die Absender-E-Mail-Adresse.
*   **`name`**: Der Name des Absenders.
*   **`charset`**: Der Zeichensatz für E-Mails (standardmäßig `utf-8`).
*   **`archive`**: `true` um versendete E-Mails in einem Archivordner zu speichern, ansonsten `false`.
*   **`imap_archive`**: `true` um versendete E-Mails in einem IMAP-Ordner zu speichern, ansonsten `false`.
*   **`debug`**: `true` um zusätzliche Debug-Informationen in Fehlermeldungen anzuzeigen, ansonsten `false`.
*   **`host`**: Die Adresse des SMTP-Servers.
*   **`port`**: Der Port des SMTP-Servers.
*   **`security`**: Die Sicherheitsoption für die SMTP-Verbindung (`tls`, `ssl` oder leer für keine Verschlüsselung).
*   **`auth`**: `true` um SMTP-Authentifizierung zu aktivieren, ansonsten `false`.
*   **`username`**: Der Benutzername für die SMTP-Authentifizierung.
*   **`password`**: Das Passwort für die SMTP-Authentifizierung.
*   **`imap_host`**: Die Adresse des IMAP-Servers.
*   **`imap_port`**: Der Port des IMAP-Servers (standardmäßig `993`).
*   **`imap_username`**: Der Benutzername für die IMAP-Authentifizierung.
*   **`imap_password`**: Das Passwort für die IMAP-Authentifizierung.
*   **`imap_folder`**: Der IMAP-Ordner, in dem E-Mails gespeichert werden sollen (standardmäßig `Sent`).

### 2.3 Priorisierung der Konfiguration

Die Konfigurationseinstellungen werden in folgender Reihenfolge geladen und überschrieben:

1.  **REDAXO AddOn Konfiguration**: Die Standardwerte werden aus der AddOn Konfiguration geladen.
2.  **`custom_config.yml`**: Wenn die Datei existiert, werden die Einstellungen aus dieser Datei geladen. Sie überschreiben die Standardeinstellungen der Addon Konfiguration.

### 2.4 Auswirkungen auf die Konfigurationsseite

Wenn die `custom_config.yml` Datei existiert, wird auf der Konfigurationsseite des Addons eine Warnmeldung angezeigt. Die Formularfelder für die SMTP- und IMAP-Einstellungen werden ausgeblendet, da diese nun über die externe Konfigurationsdatei gesteuert werden. Die Logging-Einstellungen bleiben weiterhin aktiv.

### 2.5 Manuelle Zugangsdaten bei der `send()` Methode

Die Klasse `RexSymfonyMailer` erlaubt die Übergabe von SMTP-Zugangsdaten über die `send()` Methode. Die übergebenen Einstellungen werden genutzt um einen neuen `Mailer` zu erstellen, der temporär für den Versand der E-Mail genutzt wird. Die globalen Einstellungen der Klasse, die in der `custom_config.yml` oder der AddOn Konfiguration definiert wurden, bleiben davon unberührt.

```php
$mailer = new \FriendsOfRedaxo\SymfonyMailer\RexSymfonyMailer();

$email = $mailer->createEmail();
$email->to('empfaenger@example.com');
$email->subject('Test-Mail mit anderen Zugangsdaten');
$email->text('Dies ist eine Test-Mail.');

$smtpSettings = [
    'host' => 'anderer.smtp.host',
    'port' => 587,
    'security' => 'tls',
    'auth' => true,
    'username' => 'anderer_benutzer',
    'password' => 'anderes_passwort',
];

$success = $mailer->send($email, $smtpSettings);
```

## Detour-Modus

Der Detour-Modus ist ein spezieller Modus, der in erster Linie für Test- und Entwicklungsumgebungen gedacht ist. Wenn dieser Modus aktiviert ist, werden alle ausgehenden E-Mails nicht an die eigentlichen Empfänger gesendet, sondern stattdessen an eine definierte Testadresse umgeleitet. Dies ist nützlich, um sicherzustellen, dass während der Entwicklung oder im Test keine E-Mails versehentlich an echte Benutzer gesendet werden.

### Aktivierung des Detour-Modus

Der Detour-Modus kann auf folgende Weisen aktiviert werden:

1.  **Backend-Konfiguration:**
    *   Im Konfigurationsbereich des AddOns gibt es ein Checkbox-Feld mit dem Namen "Detour-Modus".
    *   Wenn diese Checkbox aktiviert ist, wird der Detour-Modus eingeschaltet.

2.  **`custom_config.yml`**:
    *   Du kannst den Detour-Modus auch über die `custom_config.yml` aktivieren.
    *   Füge die Zeile `detour_mode: true` in deine `custom_config.yml`-Datei ein.
    *   Wenn die `custom_config.yml` Datei existiert, wird die Option im Backend nicht mehr editierbar sein.
3.  **Programmgesteuert**:
     * Du kannst die Einstellung programmgesteuert über  `rex_config::set('symfony_mailer', 'detour_mode', true);` setzen.

### Festlegen der Detour-Adresse

Die E-Mail-Adresse, an die E-Mails im Detour-Modus umgeleitet werden, kann auf folgende Weise festgelegt werden:

1.  **`custom_config.yml`**:
    *   Füge die Zeile `detour_address: "deine_testadresse@example.com"` in deine `custom_config.yml`-Datei ein.
    *   Ersetze `"deine_testadresse@example.com"` durch die gewünschte Testadresse.
2.  **Programmgesteuert**:
    * Du kannst die Adresse programmgesteuert über `rex_config::set('symfony_mailer', 'detour_address', "deine_testadresse@example.com");` setzen.
3.  **Standardadresse:**
    *   Wenn keine Detour-Adresse in der `custom_config.yml` Datei konfiguriert ist oder über `setConfig()` gesetzt wurde, wird die Standardadresse `test@example.com` verwendet.

### Funktion des Detour-Modus

*   Wenn der Detour-Modus aktiviert ist, werden alle E-Mails an die konfigurierte Detour-Adresse gesendet, unabhängig davon, welche E-Mail-Adressen als Empfänger in der E-Mail festgelegt wurden.
*   Die ursprünglichen Empfänger werden im Header der E-Mail unter `X-Original-To` gespeichert, so dass sie in der empfangenen E-Mail eingesehen werden können.
*   Der Detour-Modus ist nur für den E-Mail-Versand relevant. Alle anderen Funktionen wie E-Mail-Archivierung und Logging funktionieren weiterhin wie gewohnt.

**Beispiel:**

Nehmen wir an, du hast den Detour-Modus aktiviert und die Detour-Adresse auf `test@example.com` gesetzt. Wenn du eine E-Mail an `user1@example.com` und `user2@example.com` sendest, wird die E-Mail trotzdem nur an `test@example.com` gesendet. Die Information das die Mail eigentlich an `user1@example.com` und `user2@example.com` gehen sollte, ist im Header unter `X-Original-To` zu finden.

**Wichtig:**

*   Vergiss nicht, den Detour-Modus zu deaktivieren, wenn du E-Mails an echte Benutzer senden möchtest.
*   Die Detour-Adresse sollte immer eine gültige E-Mail-Adresse sein.

## Fehlerbehebung

*   **Fehler beim Senden:** Check die Standard-Konfigurationen (Host, Port, Benutzername, Passwort) oder die eigenen SMTP-Einstellungen.
*   **Keine E-Mails im Archiv:** Check ob die E-Mail-Archivierung aktiv ist und die Ordnerstruktur passt.
*   **Fehler bei der IMAP-Archivierung:** Check die Standard-IMAP-Einstellungen oder den eigenen IMAP-Ordner. Der Ordner muss auf dem Server existieren.
*   **Log-Einträge:** Logdatei checken, da steht mehr drin.
*   **Debug Informationen:** Die `getDebugInfo()` Methode kann Fehlerinfos ausgeben.

## Author

**Friends Of REDAXO**

* http://www.redaxo.org
* https://github.com/FriendsOfREDAXO

## Credits

**Project Lead**

[Thomas Skerbis](https://github.com/skerbis)
