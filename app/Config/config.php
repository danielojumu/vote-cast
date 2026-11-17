<?php

return [
    'app' => [
        'name' => $_ENV['APP_NAME'] ?? 'BUI Beta Set Alumni Voting',
        'env' => $_ENV['APP_ENV'] ?? 'production',
        'url' => $_ENV['APP_URL'] ?? 'https://vote.buibetaset.org',
        'timezone' => 'UTC',
    ],
    'db' => [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'port' => $_ENV['DB_PORT'] ?? '3306',
        'name' => $_ENV['DB_NAME'] ?? 'vcast_db',
        'user' => $_ENV['DB_USER'] ?? 'vcast_user',
        'pass' => $_ENV['DB_PASS'] ?? 'vcast_pass',
        'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
    ],
    'smtp' => [
        'host' => $_ENV['SMTP_HOST'] ?? 'smtp.hostinger.com',
        'port' => $_ENV['SMTP_PORT'] ?? 465,
        'secure' => $_ENV['SMTP_SECURE'] ?? 'ssl',
        'username' => $_ENV['SMTP_USERNAME'] ?? 'noreply@vote.buibetaset.org',
        'password' => $_ENV['SMTP_PASSWORD'] ?? '',
        'from_email' => $_ENV['SMTP_FROM'] ?? 'noreply@vote.buibetaset.org',
        'from_name' => $_ENV['SMTP_NAME'] ?? 'BUI Beta Set Alumni Electoral Committee',
    ]
];
