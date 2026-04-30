<?php
declare(strict_types=1);

/**
 * Script de configuración inicial del proyecto.
 * Crea el archivo .env desde .env.example si no existe.
 */

$envFile = __DIR__ . '/.env';
$envExample = __DIR__ . '/.env.example';

if (!file_exists($envExample)) {
    echo "[ERROR] No se encontró .env.example\n";
    exit(1);
}

if (file_exists($envFile)) {
    echo "[INFO] El archivo .env ya existe. No se sobrescribe.\n";
} else {
    copy($envExample, $envFile);
    echo "[OK] Archivo .env creado desde .env.example\n";
    echo "[INFO] Edita .env con tus credenciales reales antes de continuar.\n";
}

// Verificar directorios necesarios
$dirs = [
    __DIR__ . '/uploads',
    __DIR__ . '/db/migrations',
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "[OK] Directorio creado: " . basename($dir) . "\n";
    }
}

echo "\nPróximos pasos:\n";
echo "  1. Edita .env con tus credenciales de Supabase\n";
echo "  2. Ejecuta: composer install\n";
echo "  3. Ejecuta: vendor/bin/phinx migrate\n";
echo "  4. Inicia el servidor: php -S localhost:8080 -t public\n";
