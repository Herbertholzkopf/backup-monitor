<!-- /public/settings/index.php -->
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Einstellungen</title>
    <link rel="stylesheet" href="../assets/css/style.css"> <!-- Falls vorhanden -->
</head>
<body>
    <div class="settings-container">
        <aside class="settings-menu">
            <ul>
                <li><a href="?page=mail">Mail-Einstellungen</a></li>
                <li><a href="?page=customers">Kunden</a></li>
                <li><a href="?page=backup_jobs">Backup-Jobs</a></li>
            </ul>
        </aside>
        <main class="settings-content">
            <?php
                $page = $_GET['page'] ?? 'mail';
                switch ($page) {
                    case 'customers':
                        include 'customers.php';
                        break;
                    case 'backup_jobs':
                        include 'backup_jobs.php';
                        break;
                    default:
                        include 'mail_setup.php';
                        break;
                }
            ?>
        </main>
    </div>
</body>
</html>