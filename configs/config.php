<?php

return [
    'app_name' => 'NovaBank',
    'app_url' => $_ENV['APP_URL'] ?? getenv('APP_URL') ?: 'http://localhost/ProjetIntégration',
    'mail' => [
        'host' => $_ENV['SMTP_HOST'] ?? getenv('SMTP_HOST') ?: '',
        'username' => $_ENV['SMTP_USER'] ?? getenv('SMTP_USER') ?: '',
        'password' => $_ENV['SMTP_PASSWORD'] ?? getenv('SMTP_PASSWORD') ?: '',
        'smtp_secure' => $_ENV['SMTP_SECURE'] ?? getenv('SMTP_SECURE') ?: 'tls',
        'port' => (int) ($_ENV['SMTP_PORT'] ?? getenv('SMTP_PORT') ?: 587),
        'from_email' => $_ENV['SMTP_FROM_EMAIL'] ?? getenv('SMTP_FROM_EMAIL') ?: '',
        'from_name' => $_ENV['SMTP_FROM_NAME'] ?? getenv('SMTP_FROM_NAME') ?: 'NovaBank',
    ],
];
