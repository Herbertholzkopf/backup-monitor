-- Datei: public/install/database.sql

-- Kunden-Tabelle
CREATE TABLE IF NOT EXISTS customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    number VARCHAR(20) NOT NULL,
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Backup-Arten-Tabelle
CREATE TABLE IF NOT EXISTS backup_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Backup-Jobs-Tabelle
CREATE TABLE IF NOT EXISTS backup_jobs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    customer_id INT,
    email VARCHAR(255),
    note TEXT,
    backup_type_id INT,
    search_term1 VARCHAR(255),
    search_term2 VARCHAR(255),
    search_term3 VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (backup_type_id) REFERENCES backup_types(id)
);

-- Mails-Tabelle
CREATE TABLE IF NOT EXISTS mails (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sender_email VARCHAR(255) NOT NULL,
    date DATETIME NOT NULL,
    subject TEXT,
    content TEXT,
    processed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Backup-Ergebnisse-Tabelle
CREATE TABLE IF NOT EXISTS backup_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    backup_job_id INT,
    mail_id INT,
    status ENUM('success', 'warning', 'error') NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (backup_job_id) REFERENCES backup_jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (mail_id) REFERENCES mails(id)
);

-- Standard-Backup-Typen einfügen
INSERT INTO backup_types (name) VALUES 
    ('Proxmox Backup'),
    ('Veeam Backup'),
    ('Veeam Agent'),
    ('Synology HyperBackup'),
    ('Synaxon CloudBackup');