<?php
declare(strict_types=1);

/**
 * Definición de rutas del API.
 *
 * Módulo AUTH:
 *   POST   /auth/login         — Iniciar sesión
 *   POST   /auth/logout        — Cerrar sesión (blacklist del token)
 *   POST   /auth/onboarding    — Completar datos de contacto en primer inicio
 *   PUT    /auth/password      — Cambiar contraseña
 */

// === MÓDULO: AUTENTICACIÓN ===

// POST /auth/login — Login (público)
Flight::route('POST /auth/login', function () {
    AuthController::login();
});

// POST /auth/logout — Logout (requiere autenticación)
Flight::route('POST /auth/logout', function () {
    if (!Middleware::authMiddleware()) return;
    AuthController::logout();
});

// POST /auth/onboarding — Onboarding (requiere autenticación)
Flight::route('POST /auth/onboarding', function () {
    if (!Middleware::authMiddleware()) return;
    AuthController::onboarding();
});

// PUT /auth/password — Cambiar contraseña (requiere autenticación)
Flight::route('PUT /auth/password', function () {
    if (!Middleware::authMiddleware()) return;
    AuthController::cambiarPassword();
});

// === HEALTH CHECK ===

Flight::route('GET /auth/health', function () {
    Flight::json([
        'success' => true,
        'status' => 'ok',
        'timestamp' => date('c'),
    ]);
});
