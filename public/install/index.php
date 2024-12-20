<?php

session_start();

// Debug-Ausgabe der Session
error_log('Current Session State: ' . print_r($_SESSION, true));

// Sicherstellen, dass die Session funktioniert
if (!isset($_SESSION['test'])) {
    $_SESSION['test'] = true;
    if (!isset($_SESSION['test'])) {
        die('Session-Speicherung funktioniert nicht korrekt.');
    }
}

class Installer {
    private $steps = [
        1 => 'Systemanforderungen prüfen',
        2 => 'Datenbank-Konfiguration',
        3 => 'Datenbank-Installation',
        5 => 'Installation abschließen'  
    ];
    
    private $requiredExtensions = [
        'mysqli',
        'pdo',
        'pdo_mysql',
        'imap',
        'json',
        'mbstring'
    ];
    
    private $requiredPhpVersion = '7.4.0';
    
    public function __construct() {
        if (!isset($_SESSION['install_step'])) {
            $_SESSION['install_step'] = 1;
        }
    }
    
    public function run() {
        $step = $_SESSION['install_step'];
        
        switch($step) {
            case 1:
                return $this->checkRequirements();
            case 2:
                return $this->configureDatabase();
            case 3:
                return $this->installDatabase();
            case 5:
                return $this->finishInstallation();
            default:
                return $this->checkRequirements();
        }
    }
    
    private function checkRequirements() {
        $errors = [];
        
        // PHP Version prüfen
        if (version_compare(PHP_VERSION, $this->requiredPhpVersion, '<')) {
            $errors[] = "PHP Version muss mindestens {$this->requiredPhpVersion} sein. Aktuelle Version: " . PHP_VERSION;
        }
        
        // Extensions prüfen
        foreach ($this->requiredExtensions as $ext) {
            if (!extension_loaded($ext)) {
                $errors[] = "PHP Extension '{$ext}' ist nicht installiert.";
            }
        }
        
        // Verzeichnisberechtigungen prüfen
        $writableDirs = [
            '../../config',
            '../../public/assets',
            '../../src/Views/cache'
        ];
        
        foreach ($writableDirs as $dir) {
            if (!is_writable($dir)) {
                $errors[] = "Verzeichnis '{$dir}' muss beschreibbar sein.";
            }
        }
        
        if (empty($errors)) {
            $_SESSION['install_step'] = 2;
            return [
                'success' => true,
                'message' => 'Systemanforderungen erfüllt!'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Einige Anforderungen wurden nicht erfüllt:',
            'errors' => $errors
        ];
    }
    
    private function configureDatabase() {
        // Debug-Ausgabe
        error_log('Current SESSION in configureDatabase: ' . print_r($_SESSION, true));
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Wenn das Formular mit den Datenbank-Daten gesendet wurde
            if (isset($_POST['db_host']) && isset($_POST['db_name']) && 
                isset($_POST['db_user']) && isset($_POST['db_pass'])) {
                
                $config = [
                    'host' => $_POST['db_host'],
                    'database' => $_POST['db_name'],
                    'username' => $_POST['db_user'],
                    'password' => $_POST['db_pass']
                ];
                
                // Teste die Verbindung
                try {
                    $dsn = "mysql:host={$config['host']};charset=utf8mb4";
                    $pdo = new PDO($dsn, $config['username'], $config['password']);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // Speichere Konfiguration in Session
                    $_SESSION['db_config'] = $config;
                    
                    // Direkt zum nächsten Schritt
                    $_SESSION['install_step'] = 3;
                    
                    return [
                        'success' => true,
                        'message' => 'Datenbank-Konfiguration erfolgreich! Installation wird fortgesetzt...'
                    ];
                } catch (PDOException $e) {
                    return [
                        'success' => false,
                        'message' => 'Datenbankverbindung fehlgeschlagen:',
                        'error' => $e->getMessage()
                    ];
                }
            }
        }
        
        // Formular anzeigen
        return [
            'showForm' => true,
            'form' => $this->getDatabaseForm()
        ];
    }
    
    private function getDatabaseForm() {
        // Vorhandene Konfiguration aus der Session holen
        $config = isset($_SESSION['db_config']) ? $_SESSION['db_config'] : [
            'host' => 'localhost',
            'database' => 'backup_monitor',
            'username' => 'backup_monitor',
            'password' => ''
        ];
        
        return '
            <form method="post" class="space-y-4">
                <div class="space-y-2">
                    <label for="db_host" class="block text-sm font-medium text-gray-700">Database Host:</label>
                    <input type="text" name="db_host" id="db_host" value="' . htmlspecialchars($config['host']) . '" required 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="space-y-2">
                    <label for="db_name" class="block text-sm font-medium text-gray-700">Database Name:</label>
                    <input type="text" name="db_name" id="db_name" value="' . htmlspecialchars($config['database']) . '" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="space-y-2">
                    <label for="db_user" class="block text-sm font-medium text-gray-700">Database User:</label>
                    <input type="text" name="db_user" id="db_user" value="' . htmlspecialchars($config['username']) . '" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="space-y-2">
                    <label for="db_pass" class="block text-sm font-medium text-gray-700">Database Password:</label>
                    <input type="password" name="db_pass" id="db_pass" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <button type="submit" 
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Verbindung testen und fortfahren
                </button>
            </form>
        ';
    }
    
    
    private function installDatabase() {
        // Debug-Ausgabe am Anfang
        error_log('Session at start of installDatabase: ' . print_r($_SESSION, true));
        
        if (!isset($_SESSION['db_config'])) {
            return [
                'success' => false,
                'message' => 'Datenbankinstallation fehlgeschlagen:',
                'error' => 'Keine Datenbank-Konfiguration gefunden. Bitte kehren Sie zum Konfigurationsschritt zurück.'
            ];
        }
    
        try {
            $config = $_SESSION['db_config'];
            
            // Debug-Information
            error_log("Using Database Config: " . print_r($config, true));
            
            // Prüfen ob die notwendigen Konfigurationsdaten vorhanden sind
            if (empty($config['username']) || !isset($config['password'])) {
                throw new Exception("Unvollständige Datenbank-Konfiguration");
            }
            
            // Socket-Pfad
            $socket = '/var/run/mysqld/mysqld.sock';
            
            // DSN basierend auf Verfügbarkeit
            if (file_exists($socket)) {
                $dsn = "mysql:unix_socket={$socket};charset=utf8mb4";
            } else {
                $dsn = "mysql:host={$config['host']};charset=utf8mb4";
            }
            
            error_log("Attempting connection with DSN: " . $dsn);
            error_log("Using username: " . $config['username']);
            
            $pdo = new PDO(
                $dsn,
                $config['username'],
                $config['password']
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Datenbank erstellen
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$config['database']}`");
            
            // SQL-Datei einlesen
            $sqlFile = __DIR__ . '/database.sql';  // Fixed __DIR__ constant
            
            // Debug-Information
            error_log("SQL File Path: " . $sqlFile);
            error_log("File exists: " . (file_exists($sqlFile) ? 'yes' : 'no'));
            
            if (!file_exists($sqlFile)) {
                throw new Exception("SQL-Datei nicht gefunden. Pfad: " . $sqlFile);
            }
            
            $sql = file_get_contents($sqlFile);
            if ($sql === false) {
                throw new Exception("SQL-Datei konnte nicht gelesen werden. Pfad: " . $sqlFile);
            }
            
            // Debug-Information
            error_log("SQL Content Length: " . strlen($sql));
            
            // Einzelne SQL-Statements ausführen
            $statements = array_filter(
                array_map(
                    'trim',
                    explode(';', $sql)
                ),
                'strlen'
            );
            
            foreach ($statements as $index => $statement) {
                try {
                    if (!empty(trim($statement))) {
                        // Debug-Information
                        error_log("Executing SQL statement #" . ($index + 1));
                        $pdo->exec($statement);
                    }
                } catch (PDOException $e) {
                    throw new Exception("Fehler beim Ausführen des SQL-Statements #" . ($index + 1) . ": " . $e->getMessage() . "\nStatement: " . $statement);
                }
            }
            
            // Direkt zu Schritt 5 (Installation abschließen) gehen
            $_SESSION['install_step'] = 5;  // Geändert von 4 auf 5
            return [
                'success' => true,
                'message' => 'Datenbank erfolgreich installiert!'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Datenbankinstallation fehlgeschlagen:',
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function finishInstallation() {
        // Installation abschließen und Session bereinigen
        unset($_SESSION['install_step']);
        unset($_SESSION['db_config']);
        
        return [
            'success' => true,
            'message' => 'Installation erfolgreich abgeschlossen!',
            'isFinished' => true, // Neues Flag für den letzten Schritt
            'notes' => [
                'Bitte löschen Sie aus Sicherheitsgründen das /install Verzeichnis mit folgendem Befehl:',
                'rm -rf ' . __DIR__,
                'Die Anwendung ist nun unter der folgenden URL erreichbar:',
                'http://' . $_SERVER['HTTP_HOST'] . '/'
            ]
        ];
    }

}
  
// Installation ausführen
$installer = new Installer();
$result = $installer->run();

// HTML-Ausgabe
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup-Monitor Installation</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full space-y-8 p-8 bg-white rounded-lg shadow">
            <h1 class="text-2xl font-bold text-center">Backup-Monitor Installation</h1>
            
            <div class="space-y-4">
            <?php if (isset($result['success'])): ?>
                <div class="p-4 rounded <?= $result['success'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                    <p class="text-lg font-semibold"><?= htmlspecialchars($result['message']) ?></p>
                    
                    <?php if (!$result['success'] && isset($result['error'])): ?>
                        <p class="mt-2 font-bold">Fehler: <?= htmlspecialchars($result['error']) ?></p>
                    <?php endif; ?>
                    
                    <?php if (isset($result['notes'])): ?>
                        <div class="mt-4 space-y-2">
                            <?php foreach ($result['notes'] as $note): ?>
                                <p><?= htmlspecialchars($note) ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($result['success'] && !isset($result['showForm']) && !isset($result['isFinished'])): ?>
                        <form method="post" class="mt-4">
                            <button type="submit" name="next" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                                Weiter
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <?php if (isset($result['isFinished'])): ?>
                        <div class="mt-6">
                            <a href="/" class="inline-block px-6 py-3 bg-green-500 text-white rounded hover:bg-green-600">
                                Zur Anwendung
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($result['showForm'])): ?>
                <?= $result['form'] ?>
            <?php endif; ?>

            </div>
        </div>
    </div>
</body>
</html>