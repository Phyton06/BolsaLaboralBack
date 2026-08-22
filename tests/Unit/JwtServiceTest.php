<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtServiceTest extends TestCase {

    private static string $testSecret = 'test-secret-key-for-unit-tests';

    protected function setUp(): void {
        $_ENV['API_KEY'] = self::$testSecret;
        $_ENV['JWT_EXPIRE'] = '3600';
        $_ENV['JWT_REFRESH_EXPIRE'] = '2592000';
    }

    private function dummyUser(): array {
        return [
            'id' => 42,
            'matricula' => 'ABC123',
            'rol' => 'egresado',
            'primer_ingreso' => false,
        ];
    }

    public function testGenerateAccessTokenContieneClaims(): void {
        $token = JwtService::generateAccessToken($this->dummyUser());
        $payload = JWT::decode($token, new Key(self::$testSecret, 'HS256'));

        $this->assertSame('bolsa-laboral', $payload->iss);
        $this->assertSame(42, $payload->sub);
        $this->assertSame('ABC123', $payload->matricula);
        $this->assertSame('egresado', $payload->rol);
        $this->assertFalse($payload->primer_ingreso);
    }

    public function testGenerateAccessTokenExpiracion(): void {
        $token = JwtService::generateAccessToken($this->dummyUser());
        $payload = JWT::decode($token, new Key(self::$testSecret, 'HS256'));

        $this->assertObjectHasProperty('exp', $payload);
        $this->assertGreaterThan(time(), $payload->exp);
        // Should be ~3600s from now
        $this->assertLessThanOrEqual(time() + 3700, $payload->exp);
    }

    public function testGenerateRefreshTokenContieneType(): void {
        $token = JwtService::generateRefreshToken($this->dummyUser());
        $payload = JWT::decode($token, new Key(self::$testSecret, 'HS256'));

        $this->assertSame('refresh', $payload->type);
        $this->assertSame(42, $payload->sub);
    }

    public function testGenerateRefreshTokenExpiracionLarga(): void {
        $token = JwtService::generateRefreshToken($this->dummyUser());
        $payload = JWT::decode($token, new Key(self::$testSecret, 'HS256'));

        // ~30 days = 2592000s
        $this->assertGreaterThan(time() + 2590000, $payload->exp);
    }

    public function testValidateTokenValido(): void {
        $token = JwtService::generateAccessToken($this->dummyUser());
        $payload = JwtService::validateToken($token);

        $this->assertSame(42, $payload['sub']);
        $this->assertSame('egresado', $payload['rol']);
    }

    public function testValidateTokenExpirado(): void {
        // Generar token con expiración en el pasado
        $now = time();
        $payload = [
            'iss' => 'bolsa-laboral',
            'iat' => $now - 7200,
            'exp' => $now - 3600,
            'sub' => 1,
            'rol' => 'egresado',
        ];
        $token = JWT::encode($payload, self::$testSecret, 'HS256');

        $this->expectException(\Firebase\JWT\ExpiredException::class);
        JwtService::validateToken($token);
    }

    public function testValidateTokenFirmaInvalida(): void {
        $token = JWT::encode(
            ['sub' => 1, 'exp' => time() + 3600],
            'wrong-secret',
            'HS256'
        );

        $this->expectException(\Firebase\JWT\SignatureInvalidException::class);
        JwtService::validateToken($token);
    }

    public function testGetUserIdFromTokenValido(): void {
        $token = JwtService::generateAccessToken($this->dummyUser());
        $userId = JwtService::getUserIdFromToken($token);
        $this->assertSame(42, $userId);
    }

    public function testGetUserIdFromTokenInvalido(): void {
        $userId = JwtService::getUserIdFromToken('token-falso');
        $this->assertNull($userId);
    }
}
