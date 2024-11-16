// /var/www/backup-monitor/public/index.php
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    require_once __DIR__ . '/../vendor/autoload.php';

    // Router und Datenbank-Konfiguration laden
    $config = require_once __DIR__ . '/../config/database.php';

    // Datenbank-Verbindung
    try {
        $db = new PDO(
            "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4",
            $config['username'],
            $config['password']
        );
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        throw new Exception("Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
    }

    // Router initialisieren
    $router = new App\Router($db);
    
    // Routes definieren
    $router->addRoute('GET', '/', 'DashboardController', 'index');
    $router->addRoute('GET', '/customers', 'CustomerController', 'index');
    $router->addRoute('POST', '/customers', 'CustomerController', 'create');
    $router->addRoute('PUT', '/customers/{id}', 'CustomerController', 'update');
    $router->addRoute('DELETE', '/customers/{id}', 'CustomerController', 'delete');
    
    // Request verarbeiten
    $router->handleRequest();
    
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    if (ini_get('display_errors')) {
        echo "<h1>Error</h1>";
        echo "<pre>";
        echo "Message: " . htmlspecialchars($e->getMessage()) . "\n\n";
        echo "Stack trace:\n" . htmlspecialchars($e->getTraceAsString());
        echo "</pre>";
    } else {
        echo "<h1>Internal Server Error</h1>";
        error_log($e->getMessage());
        error_log($e->getTraceAsString());
    }
}