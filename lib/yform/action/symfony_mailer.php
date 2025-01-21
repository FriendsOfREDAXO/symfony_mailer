<?php

use FriendsOfRedaxo\SymfonyMailer\RexSymfonyMailer;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File;

class rex_yform_action_symfony_mailer extends rex_yform_action_abstract
{
    public function executeAction(): void
    {
        $mail_from = $this->getElement(2);
        $mail_to = $this->getElement(3); // Kann jetzt ein Komma-separierter String sein
        $mail_cc = $this->getElement(4); // Optional
        $mail_bcc = $this->getElement(5); // Optional
        $mail_subject = $this->getElement(6);
        $mail_body = $this->getElement(7);
        $mail_body_type = $this->getElement(8) ?: 'text'; // 'text' oder 'html' - Standard ist 'text'
        $smtp_settings_json = $this->getElement(9);  // Optional: JSON für SMTP-Einstellungen
        $imap_folder = $this->getElement(10); //Optional: IMAP-Ordner

        $mail_attachments = $this->getElement(11); // Optional: json String mit Array von Anhangsdaten
        
        // Dynamische SMTP Einstellungen
        $smtpSettings = [];
        if ($smtp_settings_json) {
           $smtpSettings = json_decode($smtp_settings_json, true);
             if (!is_array($smtpSettings)) {
                $this->params['form_error'][] = 'Symfony Mailer Action: Invalid JSON for SMTP settings';
                  return;
            }
         }

        // Vorbereiten der E-Mail
        $mailer = new RexSymfonyMailer();
        $email = $mailer->createEmail();

         //FROM
         foreach ($this->params['value_pool']['email'] as $search => $replace) {
                $mail_from = str_replace('###' . $search . '###', $replace, $mail_from);
                $mail_from = str_replace('+++' . $search . '+++', urlencode($replace), $mail_from);
            }

        // Absender
         try {
              $email->from(new Address($mail_from, $mail_from));
         } catch (\Exception $e) {
                $this->params['form_error'][] = 'Symfony Mailer Action: Invalid From Address';
                 return;
           }

        // Empfänger (TO)
         $toAddresses = $this->parseRecipients($mail_to);
         if(empty($toAddresses)) {
              $this->params['form_error'][] = 'Symfony Mailer Action: No valid To Address';
              return;
         }
        foreach ($toAddresses as $address) {
           try {
              $email->addTo($address);
          } catch (\Exception $e) {
               $this->params['form_error'][] = 'Symfony Mailer Action: Invalid To Address';
              return;
          }
        }

        // CC-Empfänger (optional)
         if ($mail_cc) {
            $ccAddresses = $this->parseRecipients($mail_cc);
             foreach ($ccAddresses as $address) {
                  try {
                       $email->addCc($address);
                   } catch (\Exception $e) {
                       $this->params['form_error'][] = 'Symfony Mailer Action: Invalid CC Address';
                     return;
                   }
             }
         }


        // BCC-Empfänger (optional)
        if ($mail_bcc) {
            $bccAddresses = $this->parseRecipients($mail_bcc);
             foreach ($bccAddresses as $address) {
                 try {
                      $email->addBcc($address);
                     } catch (\Exception $e) {
                         $this->params['form_error'][] = 'Symfony Mailer Action: Invalid BCC Address';
                         return;
                     }
            }
        }

        // Betreff und Body
         foreach ($this->params['value_pool']['email'] as $search => $replace) {
             $mail_body = str_replace('###' . $search . '###', $replace, $mail_body);
             $mail_body = str_replace('+++' . $search . '+++', urlencode($replace), $mail_body);
             $mail_subject = str_replace('###' . $search . '###', $replace, $mail_subject);
             $mail_subject = str_replace('+++' . $search . '+++', urlencode($replace), $mail_subject);
         }

       $email->subject($mail_subject);

        if ($mail_body_type === 'html') {
            $email->html($mail_body);
        } else {
            $email->text($mail_body);
        }
       // Anhänge hinzufügen
       if ($mail_attachments) {
           $attachments = json_decode($mail_attachments, true);
            if (is_array($attachments)) {
                 foreach ($attachments as $attachment) {
                     if(isset($attachment['type']) && $attachment['type'] === 'file' && isset($attachment['path']) ) {
                          try {
                              $email->addPart(new File($attachment['path']));
                             } catch (\Exception $e) {
                                 $this->params['form_error'][] = 'Symfony Mailer Action: Attachment-Error File not found: ' . $attachment['path'];
                             }

                         }
                     elseif (isset($attachment['type']) && $attachment['type'] === 'data' && isset($attachment['data']) && isset($attachment['contentType']) && isset($attachment['filename']) ) {
                          try {
                                 $email->addPart(new DataPart($attachment['data'], $attachment['contentType'], $attachment['filename'] ));
                             } catch (\Exception $e) {
                                 $this->params['form_error'][] = 'Symfony Mailer Action: Attachment-Error DataPart invalid: ' . $attachment['filename'];
                             }
                     }
                    else {
                         $this->params['form_error'][] = 'Symfony Mailer Action: Attachment-Error invalid data';
                         return;
                    }
                 }
           } else {
               $this->params['form_error'][] = 'Symfony Mailer Action: Invalid JSON for Attachments';
                return;
           }
        }

       // E-Mail senden
       if (!$mailer->send($email, $smtpSettings, $imap_folder)) {
            $this->params['form_error'][] = 'Symfony Mailer Action: Email could not be sent. See Logfile for more details.';
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
        return 'action|symfony_mailer|from@email.de|to@email.de[,to2@email.de]|cc@email.de[,cc2@email.de]|bcc@email.de[,bcc2@email.de]|Mailsubject|Mailbody###name###|text/html|{"host":"...","port":"...", ...}|IMAP-Folder|[{"type":"file", "path":"/path/to/file.pdf"}, {"type":"data", "data":"...", "contentType":"...", "filename":"..."}]';
    }
}
