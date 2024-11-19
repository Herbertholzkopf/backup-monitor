<?php
// /public/settings/api/index.php

// Fehleranzeige für Entwicklung
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Setze JSON Header
header('Content-Type: application/json');

require_once __DIR__ . '/../../../vendor/autoload.php';
$config = require_once __DIR__ . '/../../../config/database.php';

try {
    // Debug-Log
    error_log("API Request received");
    error_log("REQUEST_URI: " . $_SERVER['REQUEST_URI']);
    error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
    
    // Datenbank-Verbindung
    $db = new PDO(
        "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4",
        $config['username'],
        $config['password']
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Router initialisieren
    $router = new \App\Router($db);
    
    // API Routen
    $router->addRoute('GET', '/mail', 'SettingsController', 'getMailSettings');
    $router->addRoute('POST', '/mail', 'SettingsController', 'updateMailSettings');

    // Weitere Routen...
    $router->addRoute('GET', '/customers', 'SettingsController', 'getCustomers');
    $router->addRoute('POST', '/customers', 'SettingsController', 'createCustomer');
    $router->addRoute('PUT', '/customers/{id}', 'SettingsController', 'updateCustomer');
    $router->addRoute('DELETE', '/customers/{id}', 'SettingsController', 'deleteCustomer');

    $router->addRoute('GET', '/backup-jobs', 'SettingsController', 'getBackupJobs');
    $router->addRoute('POST', '/backup-jobs', 'SettingsController', 'createBackupJob');
    $router->addRoute('PUT', '/backup-jobs/{id}', 'SettingsController', 'updateBackupJob');
    $router->addRoute('DELETE', '/backup-jobs/{id}', 'SettingsController', 'deleteBackupJob');

    $router->addRoute('GET', '/backup-types', 'SettingsController', 'getBackupTypes');

    error_log("About to handle request");
    $router->handleRequest();
    
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}