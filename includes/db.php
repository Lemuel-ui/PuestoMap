<?php
/**
 * db.php
 * MySQL/MariaDB database connection using PDO.
 */

require_once __DIR__ . '/config.php';

function get_db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST
         . ';port=' . DB_PORT
         . ';dbname=' . DB_NAME
         . ';charset=' . DB_CHARSET;

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        return $pdo;

    } catch (PDOException $e) {
        error_log(APP_NAME . ' database connection failed: ' . $e->getMessage());

        http_response_code(500);

        exit(
            'Database connection failed. Please check that MySQL is running, '
            . 'the ' . DB_NAME . ' database exists, and includes/config.php '
            . 'contains the correct credentials.'
        );
    }
}
