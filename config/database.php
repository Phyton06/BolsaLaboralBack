<?php
declare(strict_types=1);

/**
 * Fábrica de conexiones PDO a PostgreSQL (Supabase).
 *
 * Uso:
 *   $pdo = getPgConnection();
 *
 * La conexión se crea una sola vez y se reutiliza (patrón singleton).
 */

function getPgConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $port = $_ENV['DB_PORT'] ?? '5432';
        $dbname = $_ENV['DB_NAME'] ?? 'postgres';
        $user = $_ENV['DB_USER'] ?? 'postgres';
        $pass = $_ENV['DB_PASS'] ?? '';

        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require";

        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => true,
        ]);
    }

    return $pdo;
}
