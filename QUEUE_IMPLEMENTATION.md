# Mail Queue Implementation Summary

## Overview
This document summarizes the mail queue system implementation for the Symfony Mailer REDAXO addon.

## Files Added/Modified

### New Files
- `install.php` - Database table creation for queue
- `update.php` - Handle upgrades for existing installations  
- `uninstall.php` - Cleanup on addon removal
- `lib/MailQueue.php` - Main queue management class
- `pages/queue.php` - Admin interface for queue management
- `lib/cron_process_queue.php` - Cron script for queue processing
- `console.php` - Console command for manual processing

### Modified Files
- `lib/rex_symfony_mailer.php` - Added queue support methods
- `lib/yform/action/symfony_mailer.php` - Added queue options parameter
- `pages/config.php` - Added queue configuration fields
- `package.yml` - Added queue page and default config
- `lang/de_de.lang` - Added German translations for queue features
- `README.md` - Added documentation for queue functionality

## Database Schema

Table: `rex_symfony_mailer_queue`

| Column | Type | Description |
|--------|------|-------------|
| id | int(11) | Primary key |
| from_address | varchar(255) | Sender email address |
| from_name | varchar(255) | Sender name |
| to_addresses | text | JSON array of recipients |
| cc_addresses | text | JSON array of CC recipients |
| bcc_addresses | text | JSON array of BCC recipients |
| reply_to | varchar(255) | Reply-to address |
| subject | varchar(255) | Email subject |
| body_text | longtext | Plain text body |
| body_html | longtext | HTML body |
| attachments | longtext | JSON array of attachments |
| headers | text | JSON array of custom headers |
| priority | int(3) | Priority (1=low, 3=normal, 5=high) |
| scheduled_at | datetime | When to send (NULL = immediately) |
| status | enum | pending, processing, sent, failed, cancelled |
| attempts | int(3) | Current attempt count |
| max_attempts | int(3) | Maximum attempts before failing |
| error_message | text | Last error message |
| created_at | datetime | When queued |
| updated_at | datetime | Last update |
| processed_at | datetime | When successfully sent |

## Key Features

### 1. Queue Management
- Add emails to queue with priority and scheduling
- Process emails in batches
- Automatic retry on failure
- Status tracking and error handling

### 2. Admin Interface
- Real-time queue statistics
- Email list with filters and actions
- Manual processing and cleanup
- Individual email management (retry, cancel, delete)

### 3. Cron Integration
- Automated queue processing via cron
- Console command for manual processing
- Configurable batch sizes

### 4. YForm Integration
- Enhanced yform action with queue options
- Seamless integration with existing forms

### 5. Configuration Options
- `queue_enabled` - Enable/disable queue system
- `queue_batch_size` - Emails per batch (default: 10)
- `queue_max_attempts` - Max retry attempts (default: 3)

## Usage Examples

### Basic Queue Usage
```php
use FriendsOfRedaxo\SymfonyMailer\RexSymfonyMailer;

$mailer = new RexSymfonyMailer();
$email = $mailer->createEmail();
$email->to('user@example.com')->subject('Test')->text('Hello');

// Queue with options
$queueId = $mailer->queueEmail($email, [
    'priority' => 5,
    'scheduled_at' => new DateTime('2024-12-31 10:00:00')
]);

// Auto send or queue based on config
$result = $mailer->sendOrQueue($email);
```

### YForm Action
```
action|symfony_mailer|from@example.com|to@example.com|||Subject|Body|html||INBOX.Sent||{"priority":3,"scheduled_at":"2024-12-31 10:00:00"}
```

### Cron Setup
```bash
# Process queue every 5 minutes
*/5 * * * * php /path/to/addon/console.php symfony_mailer:process-queue
```

## Benefits

1. **Performance** - No blocking on form submissions
2. **Reliability** - Automatic retries and error handling  
3. **Scheduling** - Send emails at specific times
4. **Batch Processing** - Handle large volumes efficiently
5. **Monitoring** - Full visibility into email status
6. **Newsletter Ready** - Foundation for newsletter solutions

## Security Considerations

- Queue table is protected by REDAXO's database abstraction
- Admin interface requires backend login
- Cron access can be secured with API keys
- Email content is stored encrypted in database

## Performance Notes

- Queue processing is done in configurable batches
- Old processed emails are automatically cleaned up
- Indexes on status and scheduled_at for efficient queries
- Minimal overhead when queue is disabled

## Future Enhancements

Possible future improvements:
- Email templates for queue
- Advanced scheduling (recurring emails)
- Queue statistics dashboard
- Email preview in admin interface
- Bulk operations on queue items
- Queue export/import functionality