#!/bin/bash
# update.sh

# Farben für Ausgaben
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo "Backup-Monitor Update Script"
echo "==========================="

# Root-Rechte prüfen
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}Bitte als root ausführen${NC}"
    exit 1
fi

# Backup erstellen
echo -e "${YELLOW}Erstelle Backup...${NC}"
BACKUP_DIR="/var/backup/backup-monitor/$(date +%Y%m%d_%H%M%S)"
mkdir -p $BACKUP_DIR

# Datenbank-Backup
source /var/www/backup-monitor/config/database.php
mysqldump -u$username -p$password $database > $BACKUP_DIR/database.sql

# Dateisystem-Backup
cp -r /var/www/backup-monitor $BACKUP_DIR/files

# Code aktualisieren
echo -e "${YELLOW}Aktualisiere Code...${NC}"
cd /var/www/backup-monitor

# Falls Git verwendet wird:
# git pull origin main

# Composer-Abhängigkeiten aktualisieren
composer install --no-dev --optimize-autoloader

# Berechtigungen aktualisieren
echo -e "${YELLOW}Aktualisiere Berechtigungen...${NC}"
chown -R www-data:www-data /var/www/backup-monitor
chmod -R 755 /var/www/backup-monitor
chmod -R 770 /var/www/backup-monitor/logs

# Cache leeren
rm -rf /var/www/backup-monitor/src/Views/cache/*

# Dienste neustarten
echo -e "${YELLOW}Starte Dienste neu...${NC}"
systemctl restart php8.2-fpm
systemctl restart nginx

echo -e "${GREEN}Update abgeschlossen!${NC}"
echo -e "${YELLOW}Backup wurde erstellt unter: $BACKUP_DIR${NC}"