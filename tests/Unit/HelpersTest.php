<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tests para helpers.php — funciones globales del proyecto.
 *
 * ponytail: no necesito Flight para testear funciones puras.
 */
class HelpersTest extends TestCase {

    public function testGenerarFolioErrorFormato(): void {
        $folio = generarFolioError();
        // ERR-YYYYMMDD-XXXX
        $this->assertMatchesRegularExpression('/^ERR-\d{8}-\d{4}$/', $folio);
    }

    public function testGenerarFolioErrorPrefijo(): void {
        $folio = generarFolioError();
        $this->assertStringStartsWith('ERR-', $folio);
    }

    public function testGenerarFolioErrorFechaHoy(): void {
        $folio = generarFolioError();
        $fechaEsperada = date('Ymd');
        $this->assertStringContainsString($fechaEsperada, $folio);
    }

    public function testGenerarFolioErrorUnicidad(): void {
        // ponytail: 10k posibles por día, 50 intentos con assertGreaterThan cubre la probabilidad
        $folios = array_map(fn() => generarFolioError(), range(1, 50));
        $this->assertGreaterThan(45, count(array_unique($folios)));
    }

    public function testValidarCampoVacio(): void {
        $this->assertNotNull(validarCampo('', 'nombre'));
    }

    public function testValidarCampoNull(): void {
        $this->assertNotNull(validarCampo(null, 'email'));
    }

    public function testValidarCampoValido(): void {
        $this->assertNull(validarCampo('Juan', 'nombre'));
    }

    public function testValidarCampoMensajeError(): void {
        $error = validarCampo('', 'telefono');
        $this->assertStringContainsString('telefono', $error);
        $this->assertStringContainsString('requerido', $error);
    }

    public function testValidarCamposTodosValidos(): void {
        $this->assertNull(validarCampos([
            'nombre' => 'Juan',
            'email' => 'juan@test.com',
        ]));
    }

    public function testValidarCamposUnoFaltante(): void {
        $error = validarCampos([
            'nombre' => 'Juan',
            'email' => '',
        ]);
        $this->assertNotNull($error);
        $this->assertStringContainsString('email', $error);
    }

    public function testValidarCamposTodosFaltantes(): void {
        $error = validarCampos([
            'nombre' => '',
            'email' => null,
        ]);
        $this->assertNotNull($error);
        // Devuelve el primer error
        $this->assertStringContainsString('nombre', $error);
    }

    public function testValidarCamposArrayVacio(): void {
        $this->assertNull(validarCampos([]));
    }
}
