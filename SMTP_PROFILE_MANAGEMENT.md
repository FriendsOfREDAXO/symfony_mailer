# SMTP Profile Verwaltung mit Symfony Mailer & YForm

Diese Anleitung zeigt, wie du eine zentrale Verwaltung für mehrere SMTP-Profile erstellst und diese in YForm Actions verwendest.

## 1. SMTP-Profile Tabelle erstellen

Erstelle eine YForm-Tabelle für die SMTP-Profile:

```sql
CREATE TABLE `rex_smtp_profiles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `transport_type` enum('smtp','microsoft_graph') DEFAULT 'smtp',
  `host` varchar(255) DEFAULT NULL,
  `port` int(11) DEFAULT 587,
  `security` enum('tls','ssl','none') DEFAULT 'tls',
  `auth` tinyint(1) DEFAULT 1,
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `graph_tenant_id` varchar(255) DEFAULT NULL,
  `graph_client_id` varchar(255) DEFAULT NULL,
  `graph_client_secret` varchar(255) DEFAULT NULL,
  `from_email` varchar(255) NOT NULL,
  `from_name` varchar(255) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## 2. YForm Tabellenkonfiguration

### Felder für die SMTP-Profile Verwaltung:

```
// Basis-Informationen
text|name|Name|1|Profil-Name (eindeutig)
textarea|description|Beschreibung|0|Optionale Beschreibung

// Transport-Typ
choice|transport_type|Transport-Typ|smtp,microsoft_graph|smtp|1

// SMTP-Einstellungen (nur bei transport_type=smtp)
text|host|SMTP-Host|0|z.B. smtp.gmail.com
number|port|Port|0|587|0|65535
choice|security|Verschlüsselung|tls,ssl,none|tls|0
checkbox|auth|Authentifizierung|1|1

// Zugangsdaten
text|username|Benutzername|0
password|password|Passwort|0

// Microsoft Graph (nur bei transport_type=microsoft_graph)
text|graph_tenant_id|Tenant ID|0
text|graph_client_id|Client ID|0
password|graph_client_secret|Client Secret|0

// Absender-Informationen
email|from_email|Absender E-Mail|1
text|from_name|Absender Name|0

// Status
checkbox|active|Aktiv|1|1
datetime|created|Erstellt|0|Y-m-d H:i:s|1|0|0
datetime|updated|Aktualisiert|0|Y-m-d H:i:s|0|1|0

// Actions
action|db_save
```

## 3. Helper-Klasse für SMTP-Profile

Erstelle eine Helper-Klasse für die Verwaltung der Profile:

```php
<?php
// /redaxo/src/addons/project/lib/SmtpProfileManager.php

class SmtpProfileManager
{
    /**
     * Lädt ein SMTP-Profil anhand der ID
     */
    public static function getProfile(int $profileId): ?array
    {
        $sql = rex_sql::factory();
        $sql->setQuery('SELECT * FROM rex_smtp_profiles WHERE id = ? AND active = 1', [$profileId]);
        
        if ($sql->getRows() === 0) {
            return null;
        }
        
        return $sql->getRow();
    }
    
    /**
     * Lädt ein SMTP-Profil anhand des Namens
     */
    public static function getProfileByName(string $name): ?array
    {
        $sql = rex_sql::factory();
        $sql->setQuery('SELECT * FROM rex_smtp_profiles WHERE name = ? AND active = 1', [$name]);
        
        if ($sql->getRows() === 0) {
            return null;
        }
        
        return $sql->getRow();
    }
    
    /**
     * Konvertiert ein Profil in das Format für Symfony Mailer
     */
    public static function getTransportSettings(int $profileId): ?string
    {
        $profile = self::getProfile($profileId);
        
        if (!$profile) {
            return null;
        }
        
        $settings = [];
        
        if ($profile['transport_type'] === 'microsoft_graph') {
            $settings = [
                'transport_type' => 'microsoft_graph',
                'graph_tenant_id' => $profile['graph_tenant_id'],
                'graph_client_id' => $profile['graph_client_id'],
                'graph_client_secret' => $profile['graph_client_secret']
            ];
        } else {
            $settings = [
                'host' => $profile['host'],
                'port' => (int)$profile['port'],
                'security' => $profile['security'],
                'auth' => (bool)$profile['auth'],
                'username' => $profile['username'],
                'password' => $profile['password']
            ];
        }
        
        return json_encode($settings, JSON_UNESCAPED_SLASHES);
    }
    
    /**
     * Gibt alle aktiven Profile zurück
     */
    public static function getAllProfiles(): array
    {
        $sql = rex_sql::factory();
        $sql->setQuery('SELECT id, name, description, transport_type, from_email FROM rex_smtp_profiles WHERE active = 1 ORDER BY name');
        
        return $sql->getArray();
    }
    
    /**
     * Erstellt ein neues Profil programmatisch
     */
    public static function createProfile(array $data): bool
    {
        $sql = rex_sql::factory();
        $sql->setTable('rex_smtp_profiles');
        
        foreach ($data as $key => $value) {
            $sql->setValue($key, $value);
        }
        
        $sql->setValue('created', date('Y-m-d H:i:s'));
        $sql->setValue('updated', date('Y-m-d H:i:s'));
        
        try {
            $sql->insert();
            return true;
        } catch (rex_sql_exception $e) {
            rex_logger::logException($e);
            return false;
        }
    }
}
```

## 4. Verwendung in YForm Actions

### Variante A: Mit Profil-ID (einfach)

```php
// SMTP-Profil ID aus einem Hidden-Field oder fester Wert
$profileId = 1; // oder $this->getValue('smtp_profile_id')

$yform->setActionField(
    'symfony_mailer',
    'sender@example.com',
    '###email###',
    '',
    '',
    'Kontaktformular von ###name###',
    'Name: ###name###\nE-Mail: ###email###\nNachricht: ###message###',
    'text',
    SmtpProfileManager::getTransportSettings($profileId), // <- Hier das Profil laden
    '',
    ''
);
```

### Variante B: Mit Profil-Name (flexibel)

```php
// SMTP-Profil per Name auswählen
$transportSettings = SmtpProfileManager::getTransportSettings(
    SmtpProfileManager::getProfileByName('gmail_support')['id']
);

$yform->setActionField(
    'symfony_mailer_tpl2email',
    'support_template',
    'email',
    'name', 
    'E-Mail konnte nicht gesendet werden!',
    $transportSettings,
    'Support'
);
```

### Variante C: Mit Custom Action Class

Erstelle eine erweiterte Action-Klasse:

```php
<?php
// /redaxo/src/addons/project/lib/yform/action/symfony_mailer_profile.php

class rex_yform_action_symfony_mailer_profile extends rex_yform_action_abstract
{
    public function executeAction(): void
    {
        $profileId = (int)$this->getElement(2);
        $mail_to = $this->getElement(3);
        $mail_subject = $this->getElement(4);
        $mail_body = $this->getElement(5);
        $mail_body_type = $this->getElement(6) ?: 'text';
        
        // Profil laden
        $profile = SmtpProfileManager::getProfile($profileId);
        if (!$profile) {
            $this->params['form_error'][] = 'SMTP-Profil nicht gefunden: ' . $profileId;
            return;
        }
        
        // Transport-Einstellungen generieren
        $transportSettings = SmtpProfileManager::getTransportSettings($profileId);
        
        // Standard symfony_mailer Action mit den Profil-Einstellungen ausführen
        $mailerAction = new rex_yform_action_symfony_mailer();
        $mailerAction->setObjectparams($this->getObjectparams());
        $mailerAction->setParams([
            2 => $profile['from_email'], // from
            3 => $mail_to,              // to
            4 => '',                    // cc
            5 => '',                    // bcc
            6 => $mail_subject,         // subject
            7 => $mail_body,            // body
            8 => $mail_body_type,       // body_type
            9 => $transportSettings,    // transport_settings
            10 => '',                   // imap_folder
            11 => ''                    // attachments
        ]);
        
        $mailerAction->executeAction();
    }
    
    public function getDescription(): string
    {
        return 'action|symfony_mailer_profile|profile_id|to_field|subject|body|text/html';
    }
}
```

### Verwendung der Custom Action:

```php
$yform->setActionField(
    'symfony_mailer_profile',
    1,                    // Profil-ID
    '###email###',        // Empfänger-Feld
    'Ihre Nachricht',     // Betreff
    'Hallo ###name###!',  // E-Mail-Text
    'text'                // Format
);
```

## 5. Profile-Auswahl in Formularen

### Select-Field für Profil-Auswahl:

```php
// YForm Choice-Field mit Profilen füllen
$profiles = SmtpProfileManager::getAllProfiles();
$profileChoices = array_column($profiles, 'name', 'id');

$yform->setValueField('choice', ['smtp_profile', 'SMTP-Profil', implode(',', array_keys($profileChoices)), '', '1']);
```

### In Pipe-Notation:

```
choice|smtp_profile|SMTP-Profil|1=Gmail Support,2=Office365 Newsletter,3=Custom SMTP|1|1
```

## 6. Sicherheitshinweise

- **Passwörter verschlüsseln:** Verwende `rex_type::string()` mit Verschlüsselung
- **Zugriffsrechte:** Beschränke den Zugriff auf die Profile-Verwaltung
- **Validation:** Validiere Transport-Einstellungen vor dem Speichern
- **Logging:** Protokolliere Änderungen an SMTP-Profilen

## 7. Beispiel-Profile erstellen

```php
// Gmail-Profil
SmtpProfileManager::createProfile([
    'name' => 'gmail_support',
    'description' => 'Gmail Account für Support-E-Mails',
    'transport_type' => 'smtp',
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'security' => 'tls',
    'auth' => 1,
    'username' => 'support@company.com',
    'password' => 'xxxx-xxxx-xxxx-xxxx',
    'from_email' => 'support@company.com',
    'from_name' => 'Company Support',
    'active' => 1
]);

// Microsoft Graph-Profil
SmtpProfileManager::createProfile([
    'name' => 'office365_newsletter',
    'description' => 'Office365 für Newsletter',
    'transport_type' => 'microsoft_graph',
    'graph_tenant_id' => 'your-tenant-id',
    'graph_client_id' => 'your-client-id', 
    'graph_client_secret' => 'your-client-secret',
    'from_email' => 'newsletter@company.com',
    'from_name' => 'Company Newsletter',
    'active' => 1
]);
```

## Vorteile dieser Lösung

✅ **Zentrale Verwaltung** aller SMTP-Konfigurationen  
✅ **Wiederverwendbare Profile** für verschiedene Formulare  
✅ **Sichere Speicherung** von Zugangsdaten  
✅ **Einfache Integration** in bestehende YForm-Workflows  
✅ **Flexible Auswahl** per ID oder Name  
✅ **Support für multiple Transport-Typen** (SMTP, Microsoft Graph)

Diese Lösung ermöglicht es dir, SMTP-Einstellungen zentral zu verwalten und flexibel in verschiedenen Formularen zu verwenden, ohne die Zugangsdaten in jedem Formular hartcodieren zu müssen.