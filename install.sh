#!/bin/bash
# install.sh

# Farben für Ausgaben
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo "Backup-Monitor Installation Script"
echo "================================"

# Root-Rechte prüfen
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}Bitte als root ausführen${NC}"
    exit 1
fi

# Systemaktualisierung
echo -e "${YELLOW}System wird aktualisiert...${NC}"
apt-get update
apt-get upgrade -y

# Benötigte Pakete installieren
echo -e "${YELLOW}Installiere benötigte Pakete...${NC}"
apt-get install -y nginx php8.2-fpm php8.2-mysql php8.2-imap php8.2-mbstring php8.2-xml php8.2-curl mysql-server composer unzip

# PHP-FPM Konfiguration
echo -e "${YELLOW}Konfiguriere PHP-FPM...${NC}"
sed -i 's/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/' /etc/php/8.2/fpm/php.ini
systemctl restart php8.2-fpm

# MySQL Sicherheit
echo -e "${YELLOW}Konfiguriere MySQL...${NC}"
mysql_secure_installation

# Backup-Monitor Datenbank und Benutzer erstellen
echo -e "${YELLOW}Erstelle Datenbank und Benutzer...${NC}"
read -p "MySQL Root Passwort: " mysqlpass
read -p "Backup-Monitor Datenbank-Benutzer Passwort: " dbpass

mysql -uroot -p$mysqlpass <<EOF
CREATE DATABASE backup_monitor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'backup_monitor'@'localhost' IDENTIFIED BY '$dbpass';
GRANT ALL PRIVILEGES ON backup_monitor.* TO 'backup_monitor'@'localhost';
FLUSH PRIVILEGES;
EOF

# Projekt-Verzeichnis erstellen
echo -e "${YELLOW}Erstelle Projekt-Verzeichnis...${NC}"
mkdir -p /var/www/backup-monitor
chown -R www-data:www-data /var/www/backup-monitor

# Git Repository klonen (falls vorhanden)
# git clone https://your-repo.git /var/www/backup-monitor

# Nginx Konfiguration
echo -e "${YELLOW}Konfiguriere Nginx...${NC}"
cat > /etc/nginx/sites-available/backup-monitor <<EOF
server {
    listen 80;
    server_name _;
    root /var/www/backup-monitor/public;
    index index.php;

    location / {
        try_files \$uri \$uri/ /index.php?\$args;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }

    location ~ /\. {
        deny all;
    }
}
EOF

# Nginx Site aktivieren
ln -s /etc/nginx/sites-available/backup-monitor /etc/nginx/sites-enabled/
rm /etc/nginx/sites-enabled/default
nginx -t && systemctl restart nginx

# Projekt-Verzeichnisse erstellen
echo -e "${YELLOW}Erstelle Projekt-Struktur...${NC}"
mkdir -p /var/www/backup-monitor/{config,public,src,logs,cron}
chown -R www-data:www-data /var/www/backup-monitor

# Konfigurationsdateien erstellen
echo -e "${YELLOW}Erstelle Konfigurationsdateien...${NC}"
cat > /var/www/backup-monitor/config/database.php <<EOF
<?php
return [
    'host' => 'localhost',
    'database' => 'backup_monitor',
    'username' => 'backup_monitor',
    'password' => '$dbpass'
];
EOF

# Cron-Jobs einrichten
echo -e "${YELLOW}Richte Cron-Jobs ein...${NC}"
cat > /etc/cron.d/backup-monitor <<EOF
# Backup-Monitor Cron Jobs
*/5 * * * * www-data php /var/www/backup-monitor/cron/fetch_mails.php
2-59/5 * * * * www-data php /var/www/backup-monitor/cron/analyze_mails.php
EOF

# Berechtigungen setzen
echo -e "${YELLOW}Setze Berechtigungen...${NC}"
chown -R www-data:www-data /var/www/backup-monitor
chmod -R 755 /var/www/backup-monitor
chmod -R 770 /var/www/backup-monitor/logs

echo -e "${GREEN}Installation abgeschlossen!${NC}"
echo -e "${YELLOW}Bitte führen Sie nun den Setup-Assistenten im Browser aus: http://ihre-domain/install${NC}"