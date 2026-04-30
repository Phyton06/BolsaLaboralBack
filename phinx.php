<?php
declare(strict_types=1);

/**
 * Configuración de Phinx para migraciones de base de datos.
 * Las credenciales se leen desde variables de entorno (.env).
 */

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

return [
    'paths' => [
        'migrations' => __DIR__ . '/db/migrations',
        'seeds' => __DIR__ . '/db',
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        'development' => [
            'adapter' => 'pgsql',
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? '5432',
            'name' => $_ENV['DB_NAME'] ?? 'bolsa_laboral',
            'user' => $_ENV['DB_USER'] ?? 'postgres',
            'pass' => $_ENV['DB_PASS'] ?? '',
            'charset' => 'utf8',
        ],
    ],
];
