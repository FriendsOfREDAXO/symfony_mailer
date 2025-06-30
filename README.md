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
