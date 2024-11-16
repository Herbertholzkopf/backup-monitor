<?php

// src/Services/Logger.php
namespace App\Services;

class Logger {
    private $context;
    private $logFile;

    public function __construct($context) {
        $this->context = $context;
        $this->logFile = __DIR__ . '/../../logs/' . date('Y-m') . '.log';
    }

    public function info($message) {
        $this->log('INFO', $message);
    }

    public function warning($message) {
        $this->log('WARNING', $message);
    }

    public function error($message) {
        $this->log('ERROR', $message);
    }

    private function log($level, $message) {
        $date = date('Y-m-d H:i:s');
        $logMessage = "[$date][$level][$this->context] $message" . PHP_EOL;
        
        error_log($logMessage, 3, $this->logFile);
    }
}