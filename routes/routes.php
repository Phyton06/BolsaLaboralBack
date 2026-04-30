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

// === MÓDULO: EGRESADO ===

// GET /egresado/perfil — Obtener perfil completo
Flight::route('GET /egresado/perfil', function () {
    EgresadoController::getProfile();
});

// PUT /egresado/perfil/biografia — Actualizar biografía
Flight::route('PUT /egresado/perfil/biografia', function () {
    EgresadoController::updateBiography();
});

// PUT /egresado/perfil/trayectoria — Actualizar trayectoria laboral
Flight::route('PUT /egresado/perfil/trayectoria', function () {
    EgresadoController::updateTrayectoria();
});

// PUT /egresado/perfil/habilidades — Actualizar habilidades
Flight::route('PUT /egresado/perfil/habilidades', function () {
    EgresadoController::updateHabilidades();
});

// POST /egresado/foto — Subir foto de perfil
Flight::route('POST /egresado/foto', function () {
    EgresadoController::uploadFoto();
});

// GET /egresado/stats — Obtener estadísticas del egresado
Flight::route('GET /egresado/stats', function () {
    EgresadoController::getStats();
});

// === MÓDULO: VACANTES Y POSTULACIONES ===

// GET /vacantes — Listar vacantes con filtros (público)
Flight::route('GET /vacantes', function () {
    VacantesController::listar();
});

// GET /vacantes/:id — Detalle de vacante (público)
Flight::route('GET /vacantes/@id', function ($id) {
    VacantesController::getDetalle($id);
});

// GET /egresado/postulaciones — Mis aplicaciones (egresado)
Flight::route('GET /egresado/postulaciones', function () {
    if (!Middleware::authMiddleware()) return;
    VacantesController::getMisPostulaciones();
});

// POST /vacantes/:id/postular — Aplicar a vacante (egresado)
Flight::route('POST /vacantes/@id/postular', function ($id) {
    if (!Middleware::authMiddleware()) return;
    VacantesController::postular($id);
});

// DELETE /vacantes/:id/cancelar-postulacion — Cancelar aplicación (egresado)
Flight::route('DELETE /vacantes/@id/cancelar-postulacion', function ($id) {
    if (!Middleware::authMiddleware()) return;
    VacantesController::cancelarPostulacion($id);
});

// GET /vacantes/:id/match-detalle — Match score (egresado)
Flight::route('GET /vacantes/@id/match-detalle', function ($id) {
    if (!Middleware::authMiddleware()) return;
    VacantesController::getMatchDetalle($id);
});

// === HEALTH CHECK ===

Flight::route('GET /auth/health', function () {
    Flight::json([
        'success' => true,
        'status' => 'ok',
        'timestamp' => date('c'),
    ]);
});
