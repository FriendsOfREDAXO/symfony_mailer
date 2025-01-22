# Symfony Mailer AddOn für REDAXO 🐣

Tschüss PHPMailer! 👋 Dieses REDAXO AddOn bringt den Symfony Mailer ins Spiel, um E-Mails aus REDAXO-Projekten zu versenden. Es bietet eine super flexible Konfiguration für verschiedene SMTP-Einstellungen, E-Mail-Archivierung und Logging.

`mail()` und `sendmail` haben wir hier einfach mal weggelassen. Dafür dürft ihr hier in IMAP-Ordnern speichern. 

Okay, hier ist eine Ergänzung zur `README.md`, die die neuen YForm Actions `rex_yform_action_symfony_mailer` und `rex_yform_action_symfony_mailer_tpl2email` ab der Überschrift "## Verwendung" beschreibt:

## Features

-   **Symfony Mailer Integration:** Nutzt die mächtige Symfony Mailer Library für zuverlässigen E-Mail-Versand.
-   **SMTP Konfiguration:**
    -   SMTP-Einstellungen wie Host, Port, Verschlüsselung (SSL/TLS), Authentifizierung mit Benutzername und Passwort sind easy konfigurierbar.
    -   Dynamische SMTP-Einstellungen pro E-Mail sind auch kein Problem.
-   **E-Mail-Archivierung:** Optionale Speicherung der versendeten E-Mails als `.eml`-Dateien im Dateisystem (nach Jahren und Monaten strukturiert).
-   **IMAP-Archivierung:**
    -   Optional werden die E-Mails in einem konfigurierbaren IMAP-Ordner archiviert.
    -   Dynamische IMAP-Ordner pro E-Mail sind möglich.
-   **Logging:** Protokolliert die versendeten E-Mails (Status, Absender, Empfänger, Betreff, Fehlermeldungen) in einer Logdatei.
-   **Testverbindung:** Checkt die SMTP-Verbindung, auch mit eigenen Einstellungen.
-   **Einfache Bedienung:** Intuitive Konfiguration im REDAXO-Backend.
-   **Flexibilität:** Nutze verschiedene SMTP Server mit dynamischen Einstellungen pro Mail.
-   **HTML E-Mails:** Versende HTML-formatierte E-Mails.
-   **Attachments:** Füge Dateien an E-Mails an.
-   **Inline-Bilder:** Betten Bilder direkt in den HTML-Inhalt der E-Mail ein.

## Installation

1.  AddOn aus dem REDAXO-Repository oder von GitHub laden.
2.  AddOn in den REDAXO-AddOn-Ordner (`/redaxo/src/addons`) entpacken.
3.  AddOn im REDAXO-Backend aktivieren.
4.  Standard-SMTP- und IMAP-Einstellungen im AddOn-Konfigurationsbereich eintragen.

## Konfiguration

Die folgenden Konfigurationsoptionen sind im AddOn-Konfigurationsbereich verfügbar. Diese Einstellungen dienen als Standardwerte, die beim Versenden der E-Mails benutzt werden, wenn keine dynamischen Einstellungen übergeben werden:

### Allgemeine Einstellungen

*   **Absender-E-Mail:** Die Standard-E-Mail-Adresse, von der aus E-Mails gesendet werden sollen.
*   **Absender-Name:** Der Name, der als Absender angezeigt werden soll.
*   **Zeichensatz:** Der Zeichensatz für E-Mails (Standard: `utf-8`).
*   **E-Mail-Archivierung:** Speichert E-Mails als EML-Dateien im Dateisystem.
*   **IMAP Archivierung:** Aktiviert die Archivierung der E-Mails in einem IMAP-Ordner.
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

Um das AddOn in Ihrem REDAXO-Projekt zu verwenden, instanziieren Sie die `RexSymfonyMailer` Klasse und verwenden Sie die Methoden `createEmail()` und `send()`. Die `send()` Methode bietet optionale Parameter für dynamische SMTP- und IMAP-Einstellungen.

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

Dieses AddOn stellt zwei YForm Actions bereit, um E-Mails aus YForm Formularen zu versenden: `rex_yform_action_symfony_mailer` und `rex_yform_action_symfony_mailer_tpl2email`.

### `rex_yform_action_symfony_mailer`

Diese Action ermöglicht es, E-Mails direkt aus YForm-Formularen zu versenden. Sie bietet folgende Optionen:

*   **`from@email.de`:** Die Absender-E-Mail-Adresse. Kann Platzhalter wie `###feldname###` oder `+++feldname+++` enthalten.
*   **`to@email.de[,to2@email.de]`:**  Die Empfänger-E-Mail-Adresse(n), kommagetrennt. Kann Platzhalter wie `###feldname###` oder `+++feldname+++` enthalten.
*   **`cc@email.de[,cc2@email.de]`:** (Optional) Die CC-Empfänger-E-Mail-Adresse(n), kommagetrennt. Kann Platzhalter wie `###feldname###` oder `+++feldname+++` enthalten.
*   **`bcc@email.de[,bcc2@email.de]`:** (Optional) Die BCC-Empfänger-E-Mail-Adresse(n), kommagetrennt. Kann Platzhalter wie `###feldname###` oder `+++feldname+++` enthalten.
*   **`Mailsubject`:** Der Betreff der E-Mail. Kann Platzhalter wie `###feldname###` oder `+++feldname+++` enthalten.
*   **`Mailbody###name###`:** Der Inhalt der E-Mail. Kann Platzhalter wie `###feldname###` oder `+++feldname+++` enthalten.
*   **`text/html`:**  (Optional) Gibt an, ob der E-Mail-Body als `text` (Standard) oder `html` interpretiert werden soll.
*   **`{"host":"...", "port":"...", ...}`:** (Optional) Ein JSON-String mit eigenen SMTP-Einstellungen.
*  **`IMAP-Folder`:**  (Optional) Ein IMAP Ordner in dem die Mails abgelegt werden soll.
*   **`[{"type":"file", "path":"/path/to/file.pdf"}, {"type":"data", "data":"...", "contentType":"...", "filename":"..."}]`:** (Optional) Ein JSON-String mit Array von Anhangsdaten. Die Anhänge können entweder eine Datei (type:file, path:Pfad) sein oder über `DataPart` (type: data, data: Inhalt, contentType: Typ, filename: Dateiname) eingebunden werden.

**Beispiel:**

```
action|symfony_mailer|from@example.com|to@example.com|cc@example.com|bcc@example.com|Betreff|Hallo ###name###!|text|{"host":"mail.example.com", "port":587, "security":"tls", "auth":true, "username":"testuser", "password":"testpassword"}|"MyCustomSentFolder"|[{"type":"file", "path":"/path/to/file.pdf"}, {"type":"data", "data":"Dies ist ein Textinhalt", "contentType":"text/plain", "filename":"mytext.txt"}]
```
### `rex_yform_action_symfony_mailer_tpl2email`

Diese Action verwendet E-Mail-Vorlagen, die über das YForm-E-Mail-Template-AddOn konfiguriert werden. Sie bietet folgende Optionen:

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

```
action|symfony_mailer_tpl2email|mein_email_template|email@example.com|Name|E-Mail konnte nicht gesendet werden|{"host":"mail.example.com", "port":587, "security":"tls", "auth":true, "username":"testuser", "password":"testpassword"}|"MyCustomSentFolder"
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

   Im Symfony Mailer, werden die E-Mail Anhänge nicht über ein Array von Datei-Pfaden übergeben, sondern mit Objekten der Klasse `DataPart` oder `File`. Dies ist ein wichtiger Unterschied zu PHPMailer, mit dem viele REDAXO-Nutzer vertraut sind.

    -  **`DataPart`**: Stellt einen E-Mail-Anhang dar, der aus Daten (z.B. einem String) erzeugt wird, nicht aus einer Datei. Bedeutet, dass Daten direkt in den Anhang eingebettet werden, ohne eine temporäre Datei auf der Festplatte anlegen zu müssen.

       ```php
       use Symfony\Component\Mime\Part\DataPart;

       // Ein Text-Anhang:
        new DataPart('Dies ist der Inhalt des Textanhangs.', 'text/plain', 'mytext.txt');

        // Ein Inline-Bild (siehe unten):
        new DataPart(file_get_contents('/pfad/zum/bild.png'), 'image/png', 'inline-image');
        ```

    -   **`File`**: Stellt einen Anhang dar, der aus einer Datei auf der Festplatte erzeugt wird. Das ist vergleichbar mit dem Anhängen von Dateien in PHPMailer, aber auch hier wird anstelle eines Dateipfades, ein File Objekt übergeben.

         ```php
         use Symfony\Component\Mime\Part\File;
        new File('/pfad/zu/datei.pdf');
         ```

### Inline-Bilder mit `DataPart`

   Um Inline-Bilder zu verwenden, werden die Bilder ebenfalls als `DataPart` hinzugefügt. Hier ist der Knackpunkt:

    1.  **Einzigartige ID (`cid`):**
        `cid:` (Content-ID) als URI im `<img>`-Tag (z.B. `<img src="cid:inline-image">`).
    2.  **`DataPart`:** `DataPart` Instanz mit den Bilddaten, dem Bildtyp und der gleichen ID als Dateiname.
   3.  **Zuordnung:** Der Mail Client verknüpft den String `inline-image` in deinem HTML mit dem korrespondierenden `DataPart` Objekt.

  ```php
   $email->html('<img src="cid:inline-image" alt="Inline Bild">')
   ->addPart(new DataPart(file_get_contents('/path/to/your/image.png'), 'image/png', 'inline-image'));
   ```

   In diesem Beispiel wird der Inhalt der Bilddatei `/path/to/your/image.png` als Inline-Bild an die E-Mail angehängt.


## DEV-Mode 

Um das Testen von E-Mail-Funktionen in Entwicklungsumgebungen zu erleichtern, wurde ein DEV-Mode in das Symfony Mailer Addon integriert. Dieser Modus ermöglicht es, E-Mails zu simulieren, ohne sie tatsächlich über einen SMTP-Server zu versenden. Stattdessen werden die E-Mails entweder nur archiviert, oder an einen lokalen Test Mailserver umgeleitet, was besonders in Testumgebungen von Vorteil ist.

### Funktionsweise des DEV-Mode

1.  **Erkennung des DEV-Mode:** Der DEV-Mode wird durch das Vorhandensein der Datei `symfony_mailer_dev.yml` im Datenordner des Symfony Mailer Addons (unter `REDAXO_PATH/data/addons/symfony_mailer/symfony_mailer_dev.yml`) aktiviert.
2.  **Konfigurationsdatei `symfony_mailer_dev.yml`:**
    *   Wenn die Datei vorhanden ist, wird der DEV-Mode aktiviert.
    *   Wenn die Datei **leer** ist, werden E-Mails nicht an einen SMTP-Server gesendet, sondern lediglich im Archiv gespeichert. Die in der REDAXO-Konfiguration festgelegten Einstellungen werden hierbei ignoriert.
    *   Wenn die Datei **nicht leer** ist und gültige SMTP-Einstellungen enthält, werden diese Einstellungen anstelle der REDAXO-Konfiguration für das Senden von E-Mails verwendet. Dies ist besonders praktisch, um E-Mails an einen lokalen Testserver wie Mailhog weiterzuleiten.
3.  **Verhalten im DEV-Mode:**
    *   Die Methode `testConnection` gibt im DEV-Mode immer eine Erfolgsmeldung mit dem Hinweis "(DEV-Mode)" zurück. Das hilft zu erkennen, dass sich das Addon im DEV-Mode befindet.
    *   Die `send`-Methode archiviert E-Mails im DEV-Mode immer im E-Mail-Archiv. Wenn keine SMTP-Einstellungen in `symfony_mailer_dev.yml` definiert sind, wird die Mail **nicht** gesendet und nur archiviert. Wenn SMTP-Einstellungen in der Datei angegeben sind wird der Mailserver aus der Datei verwendet.
4.  **Alternative zu echter SMTP-Konfiguration:** In der DEV-Umgebung muss kein echter Mailserver in REDAXO konfiguriert werden. Dies vereinfacht das Set-up der Testumgebung.

### Implementierungsdetails

*   **`$devMode` Property:** Die private Eigenschaft `$devMode` in der `RexSymfonyMailer`-Klasse speichert den Zustand des DEV-Mode.
*   **Initialisierung im Konstruktor:** Im Konstruktor der Klasse wird geprüft, ob die `symfony_mailer_dev.yml`-Datei existiert und der Modus entsprechend aktiviert oder deaktiviert. Die YAML-Datei wird mit `rex_string::yamlDecode()` eingelesen und die Einstellungen werden übernommen.
*   **Modifikation der `buildDsn()`-Methode:** Wenn der DEV-Mode aktiv ist und die Config-Datei leer ist gibt `buildDsn()` `"null://"` zurück, so dass kein Transport mit der Mailer Klasse initialisiert werden kann. Wenn der DEV-Mode aktiv ist und die Config-Datei nicht leer ist werden die Settings aus der Config übernommen.
*   **Modifikation der `send()`-Methode:** Wenn der DEV-Mode aktiv ist, wird die Methode `send()` abgekürzt und die Mail nur archiviert. Ein Hinweis "(DEV-Mode)" wird ins Log geschrieben.
*   **Modifikation der `testConnection()`-Methode:** Wenn der DEV-Mode aktiv ist, gibt die Methode immer ein positives Ergebnis mit Hinweis aus.

### Vorteile des DEV-Mode

*   **Einfaches Testen:** Lokales Testen ohne echten Mailserver.
*   **Schnelle Entwicklung:** Keine Notwendigkeit, jedes Mal echte E-Mails zu versenden.
*   **Flexibilität:** Man kann wählen ob die Mails nur archiviert oder an einen lokalen Mailserver weitergeleitet werden.
*   **Saubere Testumgebung:** Vermeidung von Testmails im Livebetrieb.
*   **Schnelles Debugging:** Dank Log Einträgen kann man schnell überprüfen ob der DEV-Mode greift und die Mails nicht an den Live Mailserver gehen.


## Fehlerbehebung

*   **Fehler beim Senden:** Check die Standard-Konfigurationen (Host, Port, Benutzername, Passwort) oder die eigenen SMTP-Einstellungen.
*   **Keine E-Mails im Archiv:** Sicherstellen, dass die E-Mail-Archivierung aktiv ist und die Ordnerstruktur passt.
*   **Fehler bei der IMAP-Archivierung:** Check die Standard-IMAP-Einstellungen oder den eigenen IMAP-Ordner. Der Ordner muss auf dem Server existieren.
*   **Log-Einträge:** Logdatei checken, da steht mehr drin.
*   **Debug Informationen:** Die `getDebugInfo()` Methode kann Fehlerinfos ausgeben.

## Lizenz

Dieses AddOn ist unter der MIT-Lizenz lizenziert.

## Beiträge

Beiträge zum AddOn sind willkommen. Einfach Pull Requests auf GitHub einreichen.


