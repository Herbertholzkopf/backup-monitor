<?php

// src/Services/MailProcessor.php
namespace App\Services;

use App\Models\Mail;
use PDO;

class MailProcessor {
    private $db;
    private $mailbox;
    private $config;
    private $mailModel;
    private $logger;

    public function __construct(PDO $db, array $config) {
        $this->db = $db;
        $this->config = $config;
        $this->mailModel = new Mail($db);
        $this->logger = new Logger('mail-processor');
    }

    public function connect() {
        $mailbox = "{" . $this->config['host'] . ":" . $this->config['port'] . "/pop3/ssl/novalidate-cert}INBOX";
        $this->mailbox = imap_open(
            $mailbox,
            $this->config['username'],
            $this->config['password']
        );

        if (!$this->mailbox) {
            throw new \Exception("Failed to connect to mailbox: " . imap_last_error());
        }
    }

    public function processNewMails() {
        try {
            $this->connect();
            
            $emails = imap_search($this->mailbox, 'UNSEEN');
            
            if (!$emails) {
                $this->logger->info('No new emails found');
                return;
            }

            foreach ($emails as $emailNumber) {
                try {
                    $this->processSingleMail($emailNumber);
                } catch (\Exception $e) {
                    $this->logger->error("Error processing email {$emailNumber}: " . $e->getMessage());
                    continue;
                }
            }

        } catch (\Exception $e) {
            $this->logger->error("Mail processing error: " . $e->getMessage());
            throw $e;
        } finally {
            if ($this->mailbox) {
                imap_close($this->mailbox);
            }
        }
    }

    private function processSingleMail($emailNumber) {
        $header = imap_headerinfo($this->mailbox, $emailNumber);
        $structure = imap_fetchstructure($this->mailbox, $emailNumber);
        
        // Email-Daten extrahieren
        $subject = $this->decodeSubject($header->subject);
        $from = $header->from[0]->mailbox . '@' . $header->from[0]->host;
        $date = date('Y-m-d H:i:s', strtotime($header->date));
        
        // Email-Body extrahieren
        $body = $this->getEmailBody($emailNumber, $structure);

        // In Datenbank speichern
        $mailData = [
            'sender_email' => $from,
            'date' => $date,
            'subject' => $subject,
            'content' => $body,
            'processed' => 0
        ];

        $this->mailModel->create($mailData);
        
        // Als gelesen markieren
        imap_setflag_full($this->mailbox, $emailNumber, "\\Seen");
    }

    private function getEmailBody($emailNumber, $structure) {
        $body = '';
        
        if ($structure->type == 0) { // Content-Type ist text/plain
            $body = imap_fetchbody($this->mailbox, $emailNumber, 1);
            
            // Encoding behandeln
            switch ($structure->encoding) {
                case 3: // BASE64
                    $body = base64_decode($body);
                    break;
                case 4: // QUOTED-PRINTABLE
                    $body = quoted_printable_decode($body);
                    break;
            }
        } elseif ($structure->type == 1) { // Content-Type ist multipart
            foreach ($structure->parts as $partNumber => $part) {
                if ($part->type == 0) { // text/plain part
                    $body = imap_fetchbody($this->mailbox, $emailNumber, $partNumber + 1);
                    
                    switch ($part->encoding) {
                        case 3:
                            $body = base64_decode($body);
                            break;
                        case 4:
                            $body = quoted_printable_decode($body);
                            break;
                    }
                    break;
                }
            }
        }

        // Zeichensatz konvertieren
        return mb_convert_encoding($body, 'UTF-8', 'AUTO');
    }

    private function decodeSubject($subject) {
        $elements = imap_mime_header_decode($subject);
        $decodedSubject = '';
        
        foreach ($elements as $element) {
            $charset = $element->charset == 'default' ? 'ASCII' : $element->charset;
            $decodedSubject .= mb_convert_encoding($element->text, 'UTF-8', $charset);
        }
        
        return $decodedSubject;
    }
}