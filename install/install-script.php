<?php
// install/index.php

session_start();

class Installer {
    private $steps = [
        1 => 'Systemanforderungen prüfen',
        2 => 'Datenbank-Konfiguration',
        3 => 'Datenbank-Installation',
        4 => 'Admin-Account erstellen',
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
                return $this->configureDatabae();
            case 3:
                return $this->installDatabase();
            case 4:
                return $this->createAdminAccount();
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
            '../config',
            '../public/assets',
            '../src/Views/cache'
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
    
    private function configureDatabae() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $config = [
                'host' => $_POST['db_host'],
                'database' => $_POST['db_name'],
                'username' => $_POST['db_user'],
                'password' => $_POST['db_pass']
            ];
            
            // Datenbankverbindung testen
            try {
                $dsn = "mysql:host={$config['host']};charset=utf8mb4";
                $pdo = new PDO($dsn, $config['username'], $config['password']);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Konfiguration speichern
                $configContent = "<?php\nreturn " . var_export($config, true) . ";\n";
                file_put_contents('../config/database.php', $configContent);
                
                $_SESSION['install_step'] = 3;
                $_SESSION['db_config'] = $config;
                
                return [
                    'success' => true,
                    'message' => 'Datenbank-Konfiguration erfolgreich!'
                ];
            } catch (PDOException $e) {
                return [
                    'success' => false,
                    'message' => 'Datenbankverbindung fehlgeschlagen:',
                    'error' => $e->getMessage()
                ];
            }
        }
        
        // Formular anzeigen
        return [
            'success' => true,
            'showForm' => true,
            'form' => $this->getDatabaseForm()
        ];
    }
    
    private function getDatabaseForm() {
        return '
            <form method="post" class="space-y-4">
                <div>
                    <label for="db_host">Database Host:</label>
                    <input type="text" name="db_host" id="db_host" value="localhost" required>
                </div>
                <div>
                    <label for="db_name">Database Name:</label>
                    <input type="text" name="db_name" id="db_name" value="backup_monitor" required>
                </div>
                <div>
                    <label for="db_user">Database User:</label>
                    <input type="text" name="db_user" id="db_user" required>
                </div>
                <div>
                    <label for="db_pass">Database Password:</label>
                    <input type="password" name="db_pass" id="db_pass" required>
                </div>
                <button type="submit">Konfiguration speichern</button>
            </form>
        ';
    }
    
    private function installDatabase() {
        try {
            $config = $_SESSION['db_config'];
            $pdo = new PDO(
                "mysql:host={$config['host']};charset=utf8mb4",
                $config['username'],
                $config['password']
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Datenbank erstellen
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$config['database']}`");
            
            // SQL-Datei einlesen und ausführen
            $sql = file_get_contents('database.sql');
            $pdo->exec($sql);
            
            $_SESSION['install_step'] = 4;
            return [
                'success' => true,
                'message' => 'Datenbank erfolgreich installiert!'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Datenbankinstallation fehlgeschlagen:',
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function createAdminAccount() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Admin-Account erstellen
            $username = $_POST['username'];
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            
            try {
                $config = $_SESSION['db_config'];
                $pdo = new PDO(
                    "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4",
                    $config['username'],
                    $config['password']
                );
                
                $stmt = $pdo->prepare("INSERT INTO users (username, password, is_admin) VALUES (?, ?, 1)");
                $stmt->execute([$username, $password]);
                
                $_SESSION['install_step'] = 5;
                return [
                    'success' => true,
                    'message' => 'Admin-Account erfolgreich erstellt!'
                ];
            } catch (PDOException $e) {
                return [
                    'success' => false,
                    'message' => 'Fehler beim Erstellen des Admin-Accounts:',
                    'error' => $e->getMessage()
                ];
            }
        }
        
        // Formular anzeigen
        return [
            'success' => true,
            'showForm' => true,
            'form' => $this->getAdminForm()
        ];
    }
    
    private function getAdminForm() {
        return '
            <form method="post" class="space-y-4">
                <div>
                    <label for="username">Admin Username:</label>
                    <input type="text" name="username" id="username" required>
                </div>
                <div>
                    <label for="password">Admin Password:</label>
                    <input type="password" name="password" id="password" required>
                </div>
                <button type="submit">Admin-Account erstellen</button>
            </form>
        ';
    }
    
    private function finishInstallation() {
        // Installation abschließen
        unset($_SESSION['install_step']);
        unset($_SESSION['db_config']);
        
        return [
            'success' => true,
            'message' => 'Installation erfolgreich abgeschlossen!',
            'note' => 'Bitte löschen Sie das /install Verzeichnis aus Sicherheitsgründen.'
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
                        <p><?= htmlspecialchars($result['message']) ?></p>
                        <?php if (isset($result['errors'])): ?>
                            <ul class="list-disc list-inside mt-2">
                                <?php foreach ($result['errors'] as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        
                        <?php if (isset($result['note'])): ?>
                            <p class="mt-4 font-bold"><?= htmlspecialchars($result['note']) ?></p>
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