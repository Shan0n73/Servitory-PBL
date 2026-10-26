<?php
$host = getenv('SERVITORY_DB_HOST') ?: 'localhost';
$port = getenv('SERVITORY_DB_PORT') ?: '5432';
$db = getenv('SERVITORY_DB_NAME') ?: 'servitory';
$user = getenv('SERVITORY_DB_USER') ?: 'postgres';
$password = getenv('SERVITORY_DB_PASSWORD');
$pass = $password === false ? 'postgres' : $password;

try {
    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$db",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    error_log('Servitory database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Koneksi database gagal. Periksa konfigurasi PostgreSQL dan log server.');
}
