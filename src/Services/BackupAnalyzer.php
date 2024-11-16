<?php

// src/Services/BackupAnalyzer.php
namespace App\Services;

use App\Models\Mail;
use App\Models\BackupJob;
use App\Models\BackupResult;
use PDO;

class BackupAnalyzer {
    private $db;
    private $mailModel;
    private $backupJobModel;
    private $backupResultModel;
    private $logger;

    // Status-Definitionen
    private $errorPatterns = [
        'error',
        'failure',
        'failed',
        'fehler',
        'fehlgeschlagen',
        'abgebrochen',
        'kritisch'
    ];

    private $warningPatterns = [
        'warning',
        'warnung',
        'warn',
        'attention',
        'achtung'
    ];

    private $successPatterns = [
        'success',
        'successful',
        'completed',
        'erfolgreich',
        'abgeschlossen'
    ];

    public function __construct(PDO $db) {
        $this->db = $db;
        $this->mailModel = new Mail($db);
        $this->backupJobModel = new BackupJob($db);
        $this->backupResultModel = new BackupResult($db);
        $this->logger = new Logger('backup-analyzer');
    }

    public function analyzeUnprocessedMails() {
        try {
            $unprocessedMails = $this->mailModel->getUnprocessed();
            
            foreach ($unprocessedMails as $mail) {
                try {
                    $this->analyzeSingleMail($mail);
                } catch (\Exception $e) {
                    $this->logger->error("Error analyzing mail {$mail['id']}: " . $e->getMessage());
                    continue;
                }
            }
        } catch (\Exception $e) {
            $this->logger->error("Backup analysis error: " . $e->getMessage());
            throw $e;
        }
    }

    private function analyzeSingleMail($mail) {
        // Passenden Backup-Job finden
        $matchingJobs = $this->backupJobModel->findBySearchTerms(
            $mail['sender_email'],
            $mail['subject'],
            $mail['content']
        );

        if (empty($matchingJobs)) {
            $this->logger->warning("No matching backup job found for mail {$mail['id']}");
            $this->mailModel->markAsProcessed($mail['id']);
            return;
        }

        foreach ($matchingJobs as $job) {
            // Status analysieren
            $status = $this->determineBackupStatus(
                $mail['subject'],
                $mail['content']
            );

            // Backup-Ergebnis speichern
            $resultData = [
                'backup_job_id' => $job['id'],
                'mail_id' => $mail['id'],
                'status' => $status,
                'date' => date('Y-m-d', strtotime($mail['date'])),
                'time' => date('H:i:s', strtotime($mail['date']))
            ];

            $this->backupResultModel->create($resultData);
        }

        // Mail als verarbeitet markieren
        $this->mailModel->markAsProcessed($mail['id']);
    }

    private function determineBackupStatus($subject, $content) {
        $combinedText = strtolower($subject . ' ' . $content);

        // Zuerst nach Fehlern suchen
        foreach ($this->errorPatterns as $pattern) {
            if (strpos($combinedText, $pattern) !== false) {
                return 'error';
            }
        }

        // Dann nach Warnungen
        foreach ($this->warningPatterns as $pattern) {
            if (strpos($combinedText, $pattern) !== false) {
                return 'warning';
            }
        }

        // Zuletzt nach Erfolg
        foreach ($this->successPatterns as $pattern) {
            if (strpos($combinedText, $pattern) !== false) {
                return 'success';
            }
        }

        // Standardmäßig als Warnung markieren, wenn keine klare Zuordnung möglich
        return 'warning';
    }
}