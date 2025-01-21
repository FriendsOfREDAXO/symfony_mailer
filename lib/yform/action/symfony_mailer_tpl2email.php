<?php

use FriendsOfRedaxo\SymfonyMailer\RexSymfonyMailer;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File;

class rex_yform_action_symfony_mailer_tpl2email extends rex_yform_action_abstract
{
    public function executeAction(): void
    {
        $template_name = $this->getElement(2);
        $email_to = $this->getElement(3); // Kann jetzt ein Komma-separierter String sein oder ein Feldname
        $email_to_name = $this->getElement(4); //Optional
        $warning_message = $this->getElement(5); //Optional
        $smtp_settings_json = $this->getElement(6);  // Optional: JSON für SMTP-Einstellungen
        $imap_folder = $this->getElement(7); //Optional: IMAP-Ordner
       
        
        if ($etpl = rex_yform_email_template::getTemplate($template_name)) {
          //Dynamische SMTP Einstellungen
            $smtpSettings = [];
            if ($smtp_settings_json) {
                $smtpSettings = json_decode($smtp_settings_json, true);
                if (!is_array($smtpSettings)) {
                    $this->params['form_error'][] = 'Symfony Mailer TPL2Email Action: Invalid JSON for SMTP settings';
                     return;
                 }
             }

            $etpl = rex_yform_email_template::replaceVars($etpl, $this->params['value_pool']['email']);

            // Empfänger (TO)
             if (filter_var($email_to, FILTER_VALIDATE_EMAIL)) {
                 $etpl['mail_to'] = $email_to; //Direkte Mailadresse
            } else {
                 foreach ($this->params['value_pool']['email'] as $key => $value) {
                     if ($email_to == $key) {
                         $etpl['mail_to'] = $value; // Mailadresse aus Feldinhalt
                         break;
                      }
                 }
            }
            
             if (empty($etpl['mail_to'])) {
                 $etpl['mail_to'] = rex::getErrorEmail(); //Fallback
             }
           
            $etpl['mail_to_name'] = $email_to_name;

            // Vorbereiten der E-Mail
            $mailer = new RexSymfonyMailer();
            $email = $mailer->createEmail();

             // Absender
               try {
                     $email->from(new Address($etpl['mail_from'], $etpl['mail_from_name']));
                } catch (\Exception $e) {
                     $this->params['form_error'][] = 'Symfony Mailer TPL2Email Action: Invalid From Address';
                      return;
                }
           
            // Empfänger (TO)
            $toAddresses = $this->parseRecipients($etpl['mail_to']);
             if(empty($toAddresses)) {
                $this->params['form_error'][] = 'Symfony Mailer TPL2Email Action: No valid To Address';
                  return;
             }

            foreach ($toAddresses as $address) {
                try {
                     $email->addTo($address);
                 } catch (\Exception $e) {
                        $this->params['form_error'][] = 'Symfony Mailer TPL2Email Action: Invalid To Address';
                        return;
                  }
            }

            // CC-Empfänger (optional)
            if (isset($etpl['mail_cc']) && $etpl['mail_cc']) {
                $ccAddresses = $this->parseRecipients($etpl['mail_cc']);
                foreach ($ccAddresses as $address) {
                      try {
                           $email->addCc($address);
                       } catch (\Exception $e) {
                            $this->params['form_error'][] = 'Symfony Mailer TPL2Email Action: Invalid CC Address';
                            return;
                       }
                }
            }

            // BCC-Empfänger (optional)
            if (isset($etpl['mail_bcc']) && $etpl['mail_bcc']) {
                $bccAddresses = $this->parseRecipients($etpl['mail_bcc']);
                foreach ($bccAddresses as $address) {
                    try {
                         $email->addBcc($address);
                        } catch (\Exception $e) {
                             $this->params['form_error'][] = 'Symfony Mailer TPL2Email Action: Invalid BCC Address';
                              return;
                           }
               }
           }

            // Betreff
            $email->subject($etpl['mail_subject']);

             // Body
             if (isset($etpl['mail_body_type']) && $etpl['mail_body_type'] === 'html') {
                $email->html($etpl['mail_body']);
            } else {
                $email->text($etpl['mail_body']);
            }

           // Anhänge
           if (isset($etpl['attachments']) && is_array($etpl['attachments'])) {
                foreach ($etpl['attachments'] as $attachment) {
                    if(isset($attachment['path']) && is_readable($attachment['path']) ) {
                        try {
                            $email->addPart(new File($attachment['path']));
                         } catch (\Exception $e) {
                            $this->params['form_error'][] = 'Symfony Mailer TPL2Email Action: Attachment-Error File not found: ' . $attachment['path'];
                        }
                    }
                    elseif (isset($attachment['data']) && isset($attachment['contentType']) && isset($attachment['filename'])) {
                           try {
                                 $email->addPart(new DataPart($attachment['data'], $attachment['contentType'], $attachment['filename'] ));
                             } catch (\Exception $e) {
                                 $this->params['form_error'][] = 'Symfony Mailer TPL2Email Action: Attachment-Error DataPart invalid: ' . $attachment['filename'];
                             }
                    }
                    else {
                         $this->params['form_error'][] = 'Symfony Mailer TPL2Email Action: Attachment-Error invalid data';
                         return;
                    }
                }
            }

              if (isset($this->params['value_pool']['email_attachments']) && is_array($this->params['value_pool']['email_attachments'])) {
                    foreach ($this->params['value_pool']['email_attachments'] as $v) {
                          try {
                                $email->addPart(new File($v[1]));
                            } catch (\Exception $e) {
                                $this->params['form_error'][] = 'Symfony Mailer TPL2Email Action: Attachment-Error File not found: ' . $v[1];
                            }
                    }
                }

            // E-Mail senden
             if (!$mailer->send($email, $smtpSettings, $imap_folder)) {
                    if ('' != $warning_message) {
                         $this->params['output'] .= $warning_message;
                    }
                     $this->params['form_error'][] = 'Symfony Mailer TPL2Email Action: Email could not be sent. See Logfile for more details.';
                    return;
                }


            if ($this->params['debug']) {
                dump('email sent');
            }
            return;
        }

        if ($this->params['debug']) {
            dump('Template: "' . rex_escape($template_name) . '" not found');
        }
    }
    /**
     * @return array<Address>
     */
    private function parseRecipients(string $recipients): array
    {
        $addresses = [];
         $recipients = str_replace(' ', '', $recipients);
        $recipientArray = explode(',', $recipients);
        foreach ($recipientArray as $recipient) {
             if(filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
               $addresses[] = new Address($recipient, $recipient);
            }
        }
        return $addresses;
    }

    public function getDescription(): string
    {
        return 'action|symfony_mailer_tpl2email|emailtemplate|[email@domain.de/email_label]|[email_name]|[Fehlermeldung wenn Versand fehlgeschlagen ist/html]|{"host":"...","port":"...", ...}|IMAP-Folder';
    }
}
