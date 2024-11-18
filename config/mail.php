// config/mail.php
<?php
return [
    'server' => 'mail.example.com',
    'port' => 993,
    'username' => 'backup@example.com',
    'password' => 'your-password',
    'protocol' => 'imap',  // oder pop3
    'encryption' => 'ssl'  // oder tls
];