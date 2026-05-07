<?php
/**
 * Local environment configuration template.
 *
 *   cp config.example.php config.php
 *   then edit values for your machine.
 *
 * config.php is git-ignored — never commit real credentials.
 */
return [
    'db' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'dbname'   => 'tenurex',
        'user'     => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
    ],

    // Hash any string that should not appear in logs (e.g. CSRF salt).
    'app_secret' => 'change-me-to-a-random-32-char-string',

    // Set to false in production
    'debug' => true,
];
