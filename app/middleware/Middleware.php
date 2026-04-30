<?php
declare(strict_types=1);

/**
 * Middleware de autenticación y control de acceso.
 *
 * - authMiddleware(): valida JWT, verifica blacklist, extrae usuario
 * - requireRole(): verifica que el usuario tenga el rol necesario
 * - extraerToken(): obtiene el token del header Authorization o query param
 */

class Middleware
{
    /**
     * Obtiene el token JWT del header Authorization o del query param.
     */
    public static function extraerToken(): ?string {
        $request = Flight::request();

        // Intentar header Authorization: Bearer <token>
        $authHeader = $request->getHeader('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }

        // Intentar query param ?token=
        $token = $request->query->get('token');
        if ($token) {
            return $token;
        }

        return null;
    }

    /**
     * Middleware de autenticación.
     *
     * Verifica:
     * 1. Existencia del token
     * 2. Validez del token (firma + expiración)
     * 3. Que no esté en la blacklist
     *
     * Si pasa todas las verificaciones, almacena los datos del usuario
     * en Flight para que los controllers puedan acceder a ellos.
     *
     * @return bool true si está autenticado, false si responde con error
     */
    public static function authMiddleware(): bool {
        $token = self::extraerToken();

        if ($token === null) {
            Flight::json([
                'success' => false,
                'error' => 'Token de autenticación requerido',
            ], 401);
            return false;
        }

        // Verificar blacklist
        try {
            if (JwtService::isBlacklisted($token)) {
                Flight::json([
                    'success' => false,
                    'error' => 'Token revocado. Inicia sesión nuevamente',
                ], 401);
                return false;
            }
        } catch (\Throwable $e) {
            // Si falla la verificación de blacklist, continuar
            error_log('Error verificando blacklist: ' . $e->getMessage());
        }

        // Validar token
        try {
            $payload = JwtService::validateToken($token);
        } catch (\Firebase\JWT\ExpiredException $e) {
            Flight::json([
                'success' => false,
                'error' => 'Token expirado',
            ], 401);
            return false;
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            Flight::json([
                'success' => false,
                'error' => 'Token inválido',
            ], 401);
            return false;
        } catch (\Throwable $e) {
            Flight::json([
                'success' => false,
                'error' => 'Error de autenticación',
            ], 401);
            return false;
        }

        // Almacenar datos del usuario en Flight para uso en controllers
        Flight::set('usuario_actual', [
            'id' => $payload['sub'],
            'matricula' => $payload['matricula'] ?? '',
            'rol' => $payload['rol'] ?? '',
            'primer_ingreso' => $payload['primer_ingreso'] ?? true,
        ]);
        Flight::set('token_jwt', $token);

        return true;
    }

    /**
     * Verifica que el usuario autenticado tenga el rol requerido.
     *
     * @param string       $rolRequerido Rol necesario (egresado, empresa, admin)
     * @param string|array $rolesPermitidos Roles adicionales permitidos (opcional)
     * @return bool true si tiene permiso, false si responde con error
     */
    public static function requireRole(string $rolRequerido, $rolesPermitidos = []): bool {
        $usuario = getUsuarioActual();

        if ($usuario === null) {
            Flight::json([
                'success' => false,
                'error' => 'Usuario no autenticado',
            ], 401);
            return false;
        }

        $roles = array_merge([$rolRequerido], (array) $rolesPermitidos);

        if (!in_array($usuario['rol'], $roles, true)) {
            Flight::json([
                'success' => false,
                'error' => 'No tienes permiso para acceder a este recurso',
            ], 403);
            return false;
        }

        return true;
    }
}
