<?php
// Koneksi database.
// Di laptop: otomatis memakai MySQL XAMPP (root, tanpa password).
// Di Vercel: memakai Environment Variables (TiDB Cloud).
function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $name = getenv('DB_NAME') ?: 'marga_mulya';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    // Database online wajib koneksi aman (SSL)
    if (getenv('DB_SSL') === '1') {
        $options[PDO::MYSQL_ATTR_SSL_CA] = __DIR__ . '/isrgrootx1.pem';
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
    }

    $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, $options);
    return $pdo;
}
