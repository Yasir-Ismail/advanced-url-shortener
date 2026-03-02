<?php
/**
 * Database configuration & singleton PDO connection.
 *
 * Uses PDO with ERRMODE_EXCEPTION so failures surface immediately.
 * Connection is created once per request and reused.
 */

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'url_shortener');
define('DB_USER', 'root');
define('DB_PASS', '');          // default XAMPP/WAMP password
define('DB_CHARSET', 'utf8mb4');

// Base URL — change this to match your local setup
// Example: http://localhost/url-shortener/
define('BASE_URL', 'http://localhost/url-shortener/');

// Short-code settings
define('SHORT_CODE_LENGTH', 7);            // characters
define('SHORT_CODE_MAX_RETRIES', 10);      // collision retry limit

/**
 * Return a singleton PDO instance.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,   // real prepared stmts
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4'",
        ];

        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }

    return $pdo;
}
