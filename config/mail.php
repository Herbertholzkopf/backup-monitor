<?php
// config/mail.php
return [
    'host' => 'mail.example.com',
    'port' => 995,
    'username' => 'backup@example.com',
    'password' => 'your-secure-password',
    'options' => [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]
];