<?php
declare(strict_types=1);

/**
 * Front Controller — BolsaLaboralBack
 *
 * Punto de entrada principal. Carga dependencias, configura FlightPHP
 * y registra las rutas del API.
 */

// Cargar dependencias de Composer
require_once __DIR__ . '/vendor/autoload.php';

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Cargar helpers centrales
require_once __DIR__ . '/app/Lib/helpers.php';

// Cargar configuración de base de datos
require_once __DIR__ . '/config/database.php';

// Cargar clases
require_once __DIR__ . '/app/services/JwtService.php';
require_once __DIR__ . '/app/middleware/Middleware.php';
require_once __DIR__ . '/app/controllers/AuthController.php';
require_once __DIR__ . '/app/controllers/egresado-controller.php';
require_once __DIR__ . '/app/controllers/vacantes-controller.php';
require_once __DIR__ . '/app/controllers/evaluaciones-controller.php';
require_once __DIR__ . '/app/controllers/empresa-controller.php';
require_once __DIR__ . '/app/controllers/admin-controller.php';
require_once __DIR__ . '/app/controllers/ia-controller.php';

// Configuración de FlightPHP
$baseUrl = $_ENV['APP_URL'] ?? 'http://localhost:8080';
Flight::set('flight.base_url', '/');
Flight::set('base_url', $baseUrl);
Flight::set('flight.handle_errors', true);

// Configuración CORS
Flight::map('handleCors', function () {
    $origin = $_ENV['FRONTEND_URL'] ?? 'http://localhost:3000';
    header("Access-Control-Allow-Origin: {$origin}");
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Credentials: true');

    // Responder a preflight
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
});

Flight::before('start', function (&$params) {
    Flight::handleCors();
});

// Cargar rutas
require_once __DIR__ . '/routes/routes.php';

// Iniciar la aplicación
Flight::start();
