<?php
declare(strict_types=1);

/**
 * Helpers centrales del proyecto.
 *
 * - handleTransactionError(): manejo centralizado de errores con folio de soporte
 * - generarFolioError(): genera folio con formato ERR-AAAAMMDD-XXXX
 * - validarCampo(): validación simple de campos de entrada
 */

/**
 * Genera un folio de soporte único con formato ERR-AAAAMMDD-XXXX.
 */
function generarFolioError(): string {
    $fecha = date('Ymd');
    $secuencia = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    return "ERR-{$fecha}-{$secuencia}";
}

/**
 * Maneja errores de transacción de forma centralizada.
 *
 * - Hace rollback de la transacción
 * - Genera un folio de soporte
 * - Registra el error en la tabla tickets_error (si existe)
 * - Devuelve respuesta JSON al cliente
 *
 * @param PDO    $pdo     Conexión activa
 * @param string $mensaje Descripción del error
 * @param int    $codigo  Código HTTP de respuesta (default 500)
 */
function handleTransactionError(PDO $pdo, string $mensaje, int $codigo = 500): void {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $folio = generarFolioError();

    // Intentar registrar el ticket (la tabla puede no existir aún)
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO tickets_error (folio, mensaje, fecha_creacion)
             VALUES (:folio, :mensaje, NOW())"
        );
        $stmt->execute([
            'folio' => $folio,
            'mensaje' => $mensaje,
        ]);
    } catch (\Throwable) {
        // Si la tabla no existe, simplemente no registramos
    }

    Flight::json([
        'success' => false,
        'error' => $mensaje,
        'ticket' => $folio,
    ], $codigo);
}

/**
 * Valida que un campo exista y no esté vacío.
 *
 * @param mixed  $valor  Valor a validar
 * @param string $campo  Nombre del campo (para el mensaje de error)
 * @return string|null   Mensaje de error o null si es válido
 */
function validarCampo(mixed $valor, string $campo): ?string {
    if ($valor === null || $valor === '') {
        return "El campo '{$campo}' es requerido";
    }
    return null;
}

/**
 * Valida múltiples campos de entrada.
 *
 * @param array<string, mixed> $campos Mapa de [nombre => valor]
 * @return string|null Mensaje de error del primer campo inválido, o null
 */
function validarCampos(array $campos): ?string {
    foreach ($campos as $nombre => $valor) {
        $error = validarCampo($valor, $nombre);
        if ($error !== null) {
            return $error;
        }
    }
    return null;
}

/**
 * Obtiene los datos del usuario actualmente autenticado
 * (almacenados en Flight por el middleware de auth).
 *
 * @return array|null Datos del usuario o null si no hay sesión
 */
function getUsuarioActual(): ?array {
    return Flight::get('usuario_actual');
}

/**
 * Responde con JSON de éxito estándar.
 */
function responderExito(array $data = [], string $mensaje = 'Operación exitosa', int $codigo = 200): void {
    Flight::json([
        'success' => true,
        'message' => $mensaje,
        'data' => $data,
    ], $codigo);
}

/**
 * Responde con JSON de error estándar.
 */
function responderError(string $error, int $codigo = 400): void {
    Flight::json([
        'success' => false,
        'error' => $error,
    ], $codigo);
}
