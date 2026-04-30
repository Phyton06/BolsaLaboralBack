<?php
declare(strict_types=1);

/**
 * Controlador de Autenticación.
 *
 * Endpoints:
 *   POST /auth/login        — Iniciar sesión
 *   POST /auth/logout       — Cerrar sesión
 *   POST /auth/onboarding   — Completar datos de contacto
 *   PUT  /auth/password     — Cambiar contraseña
 */

class AuthController
{
    /**
     * POST /auth/login
     *
     * Inicia sesión con matrícula y contraseña.
     * Devuelve JWT access token + refresh token + datos del usuario.
     */
    public static function login(): void {
        try {
            $pdo = getPgConnection();
            $data = Flight::request()->data;

            // Validar campos requeridos
            $matricula = $data->matricula ?? null;
            $password = $data->password ?? null;

            $error = validarCampos([
                'matricula' => $matricula,
                'password' => $password,
            ]);

            if ($error !== null) {
                responderError($error, 400);
                return;
            }

            // Buscar usuario por matrícula
            $stmt = $pdo->prepare(
                'SELECT u.id, u.matricula, u.password_hash, u.rol, u.primer_ingreso
                 FROM usuarios u
                 WHERE u.matricula = :matricula
                 LIMIT 1'
            );
            $stmt->execute(['matricula' => $matricula]);
            $usuario = $stmt->fetch();

            if (!$usuario) {
                responderError('Matricula o contraseña incorrectos', 401);
                return;
            }

            // Verificar contraseña
            if (!password_verify($password, $usuario['password_hash'])) {
                responderError('Matricula o contraseña incorrectos', 401);
                return;
            }

            // Obtener nombre según el rol
            $nombre = self::obtenerNombre($pdo, (int) $usuario['id'], $usuario['rol']);

            // Generar tokens
            $userData = [
                'id' => (int) $usuario['id'],
                'matricula' => $usuario['matricula'],
                'rol' => $usuario['rol'],
                'primer_ingreso' => (bool) $usuario['primer_ingreso'],
            ];

            $accessToken = JwtService::generateAccessToken($userData);
            $refreshToken = JwtService::generateRefreshToken($userData);

            responderExito([
                'token' => $accessToken,
                'refresh_token' => $refreshToken,
                'user' => [
                    'id' => (int) $usuario['id'],
                    'nombre' => $nombre,
                    'rol' => $usuario['rol'],
                    'primer_ingreso' => (bool) $usuario['primer_ingreso'],
                ],
            ], 'Inicio de sesión exitoso');

        } catch (\Throwable $e) {
            responderError('Error interno del servidor', 500);
        }
    }

    /**
     * POST /auth/logout
     *
     * Revoca el token actual agregándolo a la blacklist.
     */
    public static function logout(): void {
        try {
            $pdo = getPgConnection();
            $token = Flight::get('token_jwt', null);
            $usuario = getUsuarioActual();

            if ($token === null || $usuario === null) {
                responderError('No hay sesión activa', 401);
                return;
            }

            JwtService::blacklistToken($token, $usuario['id']);

            responderExito([], 'Sesión cerrada correctamente');

        } catch (\Throwable $e) {
            handleTransactionError($pdo, 'Error al cerrar sesión: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /auth/onboarding
     *
     * Completa los datos de contacto obligatorios en el primer inicio de sesión.
     * Actualiza el campo `contacto` (jsonb) en la tabla correspondiente al rol
     * y marca primer_ingreso como false.
     */
    public static function onboarding(): void {
        try {
            $pdo = getPgConnection();
            $pdo->beginTransaction();

            $usuario = getUsuarioActual();
            if ($usuario === null) {
                responderError('Usuario no autenticado', 401);
                return;
            }

            $data = Flight::request()->data;

            $telefono = $data->telefono ?? null;
            $correoPersonal = $data->correo_personal ?? null;
            $linkedin = $data->linkedin ?? null;

            $error = validarCampos([
                'telefono' => $telefono,
                'correo_personal' => $correoPersonal,
            ]);

            if ($error !== null) {
                $pdo->rollBack();
                responderError($error, 400);
                return;
            }

            // Construir objeto contacto
            $contacto = json_encode([
                'telefono' => $telefono,
                'correo_personal' => $correoPersonal,
                'linkedin' => $linkedin ?? null,
            ]);

            // Actualizar según el rol
            if ($usuario['rol'] === 'egresado') {
                $stmt = $pdo->prepare(
                    "UPDATE egresados
                     SET contacto = :contacto::jsonb
                     WHERE usuario_id = :user_id"
                );
            } elseif ($usuario['rol'] === 'empresa') {
                $stmt = $pdo->prepare(
                    "UPDATE empresas
                     SET contacto = :contacto::jsonb
                     WHERE usuario_id = :user_id"
                );
            } else {
                // Para admin u otros roles, no hay tabla de contacto
                $pdo->rollBack();
                responderError('No se requiere onboarding para este rol', 400);
                return;
            }

            $stmt->execute([
                'contacto' => $contacto,
                'user_id' => $usuario['id'],
            ]);

            // Marcar primer_ingreso como false
            $stmt = $pdo->prepare(
                'UPDATE usuarios SET primer_ingreso = false WHERE id = :id'
            );
            $stmt->execute(['id' => $usuario['id']]);

            $pdo->commit();

            responderExito([
                'user' => [
                    'id' => $usuario['id'],
                    'primer_ingreso' => false,
                ],
            ], 'Datos de contacto actualizados correctamente');

        } catch (\Throwable $e) {
            handleTransactionError($pdo, 'Error en onboarding: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /auth/password
     *
     * Cambia la contraseña del usuario autenticado.
     * Requiere proporcionar la contraseña actual para verificar la identidad.
     */
    public static function cambiarPassword(): void {
        try {
            $pdo = getPgConnection();
            $pdo->beginTransaction();

            $usuario = getUsuarioActual();
            if ($usuario === null) {
                responderError('Usuario no autenticado', 401);
                return;
            }

            $data = Flight::request()->data;

            $oldPassword = $data->old_password ?? null;
            $newPassword = $data->new_password ?? null;

            $error = validarCampos([
                'old_password' => $oldPassword,
                'new_password' => $newPassword,
            ]);

            if ($error !== null) {
                $pdo->rollBack();
                responderError($error, 400);
                return;
            }

            // Verificar contraseña actual
            $stmt = $pdo->prepare(
                'SELECT password_hash FROM usuarios WHERE id = :id LIMIT 1'
            );
            $stmt->execute(['id' => $usuario['id']]);
            $row = $stmt->fetch();

            if (!$row || !password_verify($oldPassword, $row['password_hash'])) {
                $pdo->rollBack();
                responderError('La contraseña actual es incorrecta', 400);
                return;
            }

            // Validar longitud de nueva contraseña
            if (strlen($newPassword) < 6) {
                $pdo->rollBack();
                responderError('La nueva contraseña debe tener al menos 6 caracteres', 400);
                return;
            }

            // Actualizar contraseña
            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare(
                'UPDATE usuarios SET password_hash = :hash WHERE id = :id'
            );
            $stmt->execute([
                'hash' => $newHash,
                'id' => $usuario['id'],
            ]);

            $pdo->commit();

            responderExito([], 'Contraseña actualizada correctamente');

        } catch (\Throwable $e) {
            handleTransactionError($pdo, 'Error al cambiar contraseña: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtiene el nombre del usuario según su rol.
     */
    private static function obtenerNombre(PDO $pdo, int $userId, string $rol): string {
        try {
            if ($rol === 'egresado') {
                $stmt = $pdo->prepare(
                    'SELECT nombre, apellido_paterno, apellido_materno
                     FROM egresados
                     WHERE usuario_id = :id
                     LIMIT 1'
                );
                $stmt->execute(['id' => $userId]);
                $row = $stmt->fetch();

                if ($row) {
                    $nombre = trim(($row['nombre'] ?? '') . ' ' . ($row['apellido_paterno'] ?? '') . ' ' . ($row['apellido_materno'] ?? ''));
                    return $nombre ?: 'Usuario';
                }
            } elseif ($rol === 'empresa') {
                $stmt = $pdo->prepare(
                    'SELECT nombre_comercial FROM empresas WHERE usuario_id = :id LIMIT 1'
                );
                $stmt->execute(['id' => $userId]);
                $row = $stmt->fetch();

                if ($row && !empty($row['nombre_comercial'])) {
                    return $row['nombre_comercial'];
                }
            }
        } catch (\Throwable) {
            // Ignorar errores al obtener el nombre
        }

        return 'Usuario';
    }
}
