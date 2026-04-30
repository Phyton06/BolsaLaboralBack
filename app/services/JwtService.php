<?php
declare(strict_types=1);

/**
 * Servicio de JWT para autenticación.
 *
 * - Access Token: 1 hora de validez
 * - Refresh Token: 30 días de validez
 * - Blacklist: tokens revocados se almacenan en DB
 */

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtService
{
    private static string $secret;
    private static string $algo = 'HS256';
    private static int $accessExpire;
    private static int $refreshExpire;

    /**
     * Inicializa el servicio leyendo las variables de entorno.
     */
    public static function init(): void {
        self::$secret = $_ENV['API_KEY'] ?? 'change_this_secret';
        self::$accessExpire = (int) ($_ENV['JWT_EXPIRE'] ?? 3600);
        self::$refreshExpire = (int) ($_ENV['JWT_REFRESH_EXPIRE'] ?? 2592000);
    }

    /**
     * Genera un access token JWT.
     *
     * @param array $userData Datos del usuario a incluir en el payload
     */
    public static function generateAccessToken(array $userData): string {
        self::init();

        $now = time();
        $payload = [
            'iss' => 'bolsa-laboral',
            'iat' => $now,
            'exp' => $now + self::$accessExpire,
            'sub' => $userData['id'],
            'matricula' => $userData['matricula'],
            'rol' => $userData['rol'],
            'primer_ingreso' => $userData['primer_ingreso'] ?? true,
        ];

        return JWT::encode($payload, self::$secret, self::$algo);
    }

    /**
     * Genera un refresh token JWT.
     *
     * @param array $userData Datos del usuario
     */
    public static function generateRefreshToken(array $userData): string {
        self::init();

        $now = time();
        $payload = [
            'iss' => 'bolsa-laboral',
            'iat' => $now,
            'exp' => $now + self::$refreshExpire,
            'sub' => $userData['id'],
            'type' => 'refresh',
        ];

        return JWT::encode($payload, self::$secret, self::$algo);
    }

    /**
     * Valida y decodifica un token JWT.
     *
     * @return array Payload decodificado
     * @throws \Firebase\JWT\ExpiredException
     * @throws \Firebase\JWT\SignatureInvalidException
     */
    public static function validateToken(string $token): array {
        self::init();

        $decoded = JWT::decode($token, new Key(self::$secret, self::$algo));
        return (array) $decoded;
    }

    /**
     * Verifica si un token está en la blacklist.
     *
     * @param string $token Token JWT completo
     */
    public static function isBlacklisted(string $token): bool {
        $pdo = getPgConnection();
        $hash = hash('sha256', $token);

        $stmt = $pdo->prepare(
            "SELECT 1 FROM token_blacklist WHERE token_hash = :hash LIMIT 1"
        );
        $stmt->execute(['hash' => $hash]);

        return $stmt->fetch() !== false;
    }

    /**
     * Añade un token a la blacklist.
     *
     * @param string $token Token JWT completo
     * @param int    $userId ID del usuario que revoca
     */
    public static function blacklistToken(string $token, int $userId): bool {
        $pdo = getPgConnection();
        $hash = hash('sha256', $token);

        // Decodificar para obtener expiración
        try {
            $payload = self::validateToken($token);
            $expiresAt = $payload['exp'] ?? (time() + self::$accessExpire);
        } catch (\Throwable) {
            // Si no se puede decodificar, usar expiración por defecto
            $expiresAt = time() + self::$accessExpire;
        }

        $stmt = $pdo->prepare(
            "INSERT INTO token_blacklist (token_hash, usuario_id, expires_at)
             VALUES (:hash, :user_id, to_timestamp(:expires_at))
             ON CONFLICT (token_hash) DO NOTHING"
        );

        return $stmt->execute([
            'hash' => $hash,
            'user_id' => $userId,
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * Limpia tokens expirados de la blacklist.
     */
    public static function cleanBlacklist(): void {
        $pdo = getPgConnection();
        $pdo->exec("DELETE FROM token_blacklist WHERE expires_at < NOW()");
    }

    /**
     * Obtiene el ID del sub (usuario) de un token sin validarlo completamente.
     * Útil para obtener el usuario de un refresh token.
     */
    public static function getUserIdFromToken(string $token): ?int {
        try {
            $payload = self::validateToken($token);
            return isset($payload['sub']) ? (int) $payload['sub'] : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
