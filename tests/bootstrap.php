<?php
declare(strict_types=1);

/**
 * Bootstrap para PHPUnit — carga helpers y clases del proyecto.
 *
 * ponytail: copia selectiva de index.php sin Flight ni DB.
 */
require_once __DIR__ . '/../vendor/autoload.php';

// Helpers (funciones globales)
require_once __DIR__ . '/../app/Lib/helpers.php';

// Clases que necesitamos testear
require_once __DIR__ . '/../app/services/JwtService.php';
require_once __DIR__ . '/../app/services/MatchingCalculator.php';
require_once __DIR__ . '/../app/middleware/Middleware.php';
