<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class MiddlewareTest extends TestCase {

    protected function setUp(): void {
        // Limpiar superglobals
        $_SERVER['HTTP_AUTHORIZATION'] = '';
        $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] = '';
        $_GET['token'] = '';
    }

    // ==========================================
    // extraerToken
    // ==========================================

    public function testExtraerTokenDesdeHeader(): void {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer mi-token-secreto';
        $token = Middleware::extraerToken();
        $this->assertSame('mi-token-secreto', $token);
    }

    public function testExtraerTokenDesdeQuery(): void {
        $_SERVER['HTTP_AUTHORIZATION'] = '';
        $_GET['token'] = 'token-via-query';
        $token = Middleware::extraerToken();
        $this->assertSame('token-via-query', $token);
    }

    public function testExtraerTokenNullCuandoNoExiste(): void {
        $_SERVER['HTTP_AUTHORIZATION'] = '';
        $_GET['token'] = '';
        $token = Middleware::extraerToken();
        $this->assertNull($token);
    }

    public function testExtraerTokenHeaderPrioridadSobreQuery(): void {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer header-token';
        $_GET['token'] = 'query-token';
        $token = Middleware::extraerToken();
        $this->assertSame('header-token', $token);
    }

    public function testExtraerTokenHeaderSinBearer(): void {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Token basico';
        $token = Middleware::extraerToken();
        $this->assertNull($token);
    }

    // ==========================================
    // requireRole
    // ==========================================

    public function testRequireRoleCoincide(): void {
        Flight::set('usuario_actual', ['id' => 1, 'rol' => 'egresado']);
        $result = Middleware::requireRole('egresado');
        $this->assertTrue($result);
    }

    public function testRequireRoleNoCoincide(): void {
        Flight::set('usuario_actual', ['id' => 1, 'rol' => 'empresa']);
        $result = Middleware::requireRole('egresado');
        $this->assertFalse($result);
    }

    public function testRequireRoleSinUsuario(): void {
        Flight::set('usuario_actual', null);
        $result = Middleware::requireRole('egresado');
        $this->assertFalse($result);
    }
}
