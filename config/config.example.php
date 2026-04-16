<?php
return [
    'db' => [
        'host' => getenv('DB_HOST') ?: 'database',
        'dbname' => getenv('DB_NAME') ?: 'u357871217_institutodona',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
        'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
    ],
    'app' => [
        'base_url' => getenv('APP_BASE_URL') ?: '/',
        'version' => 'v1.24.0',
    ],
];
