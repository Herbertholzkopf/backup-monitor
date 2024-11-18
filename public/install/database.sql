CREATE TABLE IF NOT EXISTS customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    number VARCHAR(20) NOT NULL,
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS backup_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    icon VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

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

CREATE TABLE IF NOT EXISTS mails (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sender_email VARCHAR(255) NOT NULL,
    date DATETIME NOT NULL,
    subject TEXT,
    content TEXT,
    content_type VARCHAR(50),
    has_attachment BOOLEAN DEFAULT FALSE,
    attachment_path VARCHAR(255),
    processed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS backup_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    backup_job_id INT,
    mail_id INT,
    status ENUM('success', 'warning', 'error') NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    note TEXT,
    size_mb DECIMAL(10,2),
    duration_minutes INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (backup_job_id) REFERENCES backup_jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (mail_id) REFERENCES mails(id)
);

INSERT INTO backup_types (name, icon) VALUES 
    ('Proxmox Backup', 'server'),
    ('Veeam Backup', 'database'),
    ('Veeam Agent', 'laptop'),
    ('Synology HyperBackup', 'nas'),
    ('Synaxon CloudBackup', 'cloud');

INSERT INTO customers (name, number, note) VALUES 
    ('Musterfirma GmbH', 'KD-001', 'Beispiel-Kunde'),
    ('Beispiel AG', 'KD-002', 'Zweiter Beispiel-Kunde');

INSERT INTO backup_jobs (name, customer_id, backup_type_id, email) VALUES 
    ('Server-Backup', 1, 2, 'backup@musterfirma.de'), 
    ('Exchange Online', 1, 5, 'cloud@musterfirma.de');

INSERT INTO backup_results (backup_job_id, status, date, time, note, size_mb, duration_minutes) VALUES 
    (1, 'success', CURDATE(), '22:19:00', 'Erfolgreiches Backup', 1024.50, 45),
    (1, 'warning', DATE_SUB(CURDATE(), INTERVAL 1 DAY), '21:46:00', 'Warnung: Große Änderungen', 1124.75, 52),
    (1, 'success', DATE_SUB(CURDATE(), INTERVAL 2 DAY), '22:17:00', 'Erfolgreiches Backup', 1018.25, 43),
    (1, 'success', DATE_SUB(CURDATE(), INTERVAL 2 DAY), '22:15:00', 'Erfolgreiches Backup', 1015.75, 44);