# backup-monitor

Durchsucht Mails nach Absendern, bestimmten Begriffen und ordnet diese dann angelegten Kunden und den verschiedenen Backup-Jobs der Kunden zu. Aus den Mails wird dann noch der Status des Backups (z.B. Fehler, Erfolgreich, Warnung) ausgelesen und visuell in einem Web-Dashboard angezeigt.


## Installation

# 1. Skript herunterladen
```
wget https://raw.githubusercontent.com/Herbertholzkopf/backup-monitor/refs/heads/main/install.sh
```

# 2. Ausführbar machen
```
chmod +x install.sh
```

# 3. Ausführen
```
sudo ./install.sh
```

# 4. Browser öffnen

http://IP-Adresse-des-Servers/install
