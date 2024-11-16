<?php

// public/index.php
require_once __DIR__ . '/../vendor/autoload.php';

$config = require_once __DIR__ . '/../config/database.php';

try {
    $db = new PDO(
        "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4",
        $config['username'],
        $config['password']
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed'
    ]);
    exit;
}

$router = new Router($db);

// Define routes
$router->addRoute('GET', '/', 'DashboardController', 'index');

// Customer routes
$router->addRoute('GET', '/customers', 'CustomerController', 'index');
$router->addRoute('POST', '/customers', 'CustomerController', 'create');
$router->addRoute('PUT', '/customers/{id}', 'CustomerController', 'update');
$router->addRoute('DELETE', '/customers/{id}', 'CustomerController', 'delete');

// Backup job routes
$router->addRoute('POST', '/backup-jobs', 'BackupJobController', 'create');
$router->addRoute('PUT', '/backup-jobs/{id}', 'BackupJobController', 'update');
$router->addRoute('GET', '/backup-jobs/{id}/results', 'BackupJobController', 'getResults');

// Backup result routes
$router->addRoute('POST', '/backup-results/note', 'BackupResultController', 'updateNote');

// Handle the request
$router->handleRequest();