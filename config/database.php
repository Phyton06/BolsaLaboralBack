<?php
declare(strict_types=1);

/**
 * Fábrica de conexiones PDO a PostgreSQL.
 *
 * Soporta:
 *   - DATABASE_URL (Render, Supabase, etc.)
 *   - Variables individuales DB_HOST, DB_PORT, etc.
 */

function getPgConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $databaseUrl = $_ENV['DATABASE_URL'] ?? null;

        if ($databaseUrl) {
            // Parse DATABASE_URL: postgresql://user:pass@host:port/dbname
            $parsed = parse_url($databaseUrl);
            $host = $parsed['host'] ?? 'localhost';
            $port = $parsed['port'] ?? '5432';
            $dbname = ltrim($parsed['path'] ?? '/postgres', '/');
            $user = $parsed['user'] ?? 'postgres';
            $pass = $parsed['pass'] ?? '';
        } else {
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $port = $_ENV['DB_PORT'] ?? '5432';
            $dbname = $_ENV['DB_NAME'] ?? 'postgres';
            $user = $_ENV['DB_USER'] ?? 'postgres';
            $pass = $_ENV['DB_PASS'] ?? '';
        }

        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => true,
        ]);
    }

    return $pdo;
}
