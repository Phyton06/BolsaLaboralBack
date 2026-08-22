<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tests para MatchingCalculator — el corazón del sistema de matching.
 *
 * ponytail: tests puros sin DB ni mocks. Funciones 100% deterministas.
 */
class MatchingCalculatorTest extends TestCase {

    // ==========================================
    // calcularMatch — técnicas
    // ==========================================

    public function testCalcularMatchExacto(): void {
        $egresado = ['tecnicas' => ['PHP', 'MySQL', 'JavaScript']];
        $perfil = ['habilidades_requeridas' => ['PHP', 'MySQL', 'JavaScript']];

        $this->assertSame(100, MatchingCalculator::calcularMatch($egresado, $perfil));
    }

    public function testCalcularMatchParcial(): void {
        $egresado = ['tecnicas' => ['PHP', 'JavaScript']];
        $perfil = ['habilidades_requeridas' => ['PHP', 'MySQL', 'JavaScript']];

        // 2 de 3 = 67%
        $this->assertSame(67, MatchingCalculator::calcularMatch($egresado, $perfil));
    }

    public function testCalcularMatchNinguno(): void {
        $egresado = ['tecnicas' => ['Python', 'Go']];
        $perfil = ['habilidades_requeridas' => ['PHP', 'MySQL']];

        $this->assertSame(0, MatchingCalculator::calcularMatch($egresado, $perfil));
    }

    public function testCalcularMatchVacio(): void {
        $this->assertSame(0, MatchingCalculator::calcularMatch([], []));
    }

    public function testCalcularMatchPerfilSinRequeridas(): void {
        $egresado = ['tecnicas' => ['PHP']];
        $perfil = ['otro_campo' => 'valor'];

        $this->assertSame(0, MatchingCalculator::calcularMatch($egresado, $perfil));
    }

    public function testCalcularMatchCaseInsensitive(): void {
        $egresado = ['tecnicas' => ['PHP', 'MYSQL']];
        $perfil = ['habilidades_requeridas' => ['php', 'mysql']];

        $this->assertSame(100, MatchingCalculator::calcularMatch($egresado, $perfil));
    }

    public function testCalcularMatchSinTecnicas(): void {
        $egresado = ['blandas' => ['liderazgo']];
        $perfil = ['habilidades_requeridas' => ['PHP']];

        $this->assertSame(0, MatchingCalculator::calcularMatch($egresado, $perfil));
    }

    // ==========================================
    // compararNivelesIngles
    // ==========================================

    public function testInglesNivelExacto(): void {
        $this->assertSame(100, MatchingCalculator::compararNivelesIngles('B2', 'B2'));
    }

    public function testInglesNivelMayor(): void {
        $this->assertSame(100, MatchingCalculator::compararNivelesIngles('C1', 'B2'));
    }

    public function testInglesNivelMenor(): void {
        // A1=1, B2=4 → 1/4 = 25%
        $this->assertSame(25, MatchingCalculator::compararNivelesIngles('A1', 'B2'));
    }

    public function testInglesCaseInsensitive(): void {
        $this->assertSame(100, MatchingCalculator::compararNivelesIngles('b2', 'B2'));
    }

    public function testInglesNivelDesconocido(): void {
        // Desconocido = 0, B1 = 3 → 0%
        $this->assertSame(0, MatchingCalculator::compararNivelesIngles('X1', 'B1'));
    }

    public function testInglesB1RequeridoConB2(): void {
        $this->assertSame(100, MatchingCalculator::compararNivelesIngles('B2', 'B1'));
    }

    public function testInglesA2RequeridoConB1(): void {
        $this->assertSame(100, MatchingCalculator::compararNivelesIngles('B1', 'A2'));
    }

    // ==========================================
    // matchExperiencia
    // ==========================================

    public function testExperienciaExacta(): void {
        $this->assertSame(100, MatchingCalculator::matchExperiencia('3-5 años', '3-5 años'));
    }

    public function testExperienciaExcede(): void {
        $this->assertSame(100, MatchingCalculator::matchExperiencia('5+ años', '0-1 años'));
    }

    public function testExperienciaInsuficiente(): void {
        // 0-1=1, 3-5=4 → 25%
        $this->assertSame(25, MatchingCalculator::matchExperiencia('0-1 años', '3-5 años'));
    }

    public function testExperienciaRango1a3(): void {
        // 1-3=2, 5+=5 → 40%
        $this->assertSame(40, MatchingCalculator::matchExperiencia('1-3 años', '5+ años'));
    }

    public function testExperienciaDesconocida(): void {
        $this->assertSame(0, MatchingCalculator::matchExperiencia('N/A', '3-5 años'));
    }

    // ==========================================
    // calcularSoftSkills
    // ==========================================

    public function testSoftSkillsVarias(): void {
        $egresado = [
            'blandas' => ['trabajo en equipo', 'comunicación', 'liderazgo'],
            'idiomas' => ['Inglés'],
        ];
        $this->assertSame(60, MatchingCalculator::calcularSoftSkills($egresado, []));
    }

    public function testSoftSkillsVacias(): void {
        $this->assertSame(0, MatchingCalculator::calcularSoftSkills(['blandas' => [], 'idiomas' => []], []));
    }

    public function testSoftSkillsSinLlaves(): void {
        $this->assertSame(0, MatchingCalculator::calcularSoftSkills([], []));
    }

    public function testSoftSkillsMaximo100(): void {
        // 10 skills = 200% raw, capped at 100
        $egresado = [
            'blandas' => [
                'trabajo en equipo', 'comunicación', 'liderazgo',
                'resolución de problemas', 'proactividad', 'adaptabilidad',
                'creatividad', 'pensamiento crítico', 'extra1', 'extra2',
            ],
            'idiomas' => [],
        ];
        $this->assertSame(100, MatchingCalculator::calcularSoftSkills($egresado, []));
    }

    public function testSoftSkillsCaseInsensitive(): void {
        $egresado = [
            'blandas' => ['TRABAJO EN EQUIPO', 'Comunicación'],
            'idiomas' => [],
        ];
        $this->assertSame(40, MatchingCalculator::calcularSoftSkills($egresado, []));
    }

    // ==========================================
    // calcularMatchCompleto — 5 dimensiones
    // ==========================================

    public function testMatchCompletoPerfecto(): void {
        $habilidades = [
            'tecnicas' => ['PHP', 'MySQL'],
            'blandas' => ['liderazgo', 'comunicación', 'trabajo en equipo', 'proactividad', 'creatividad'],
            'idiomas' => [],
        ];
        $perfil = [
            'habilidades_requeridas' => ['PHP', 'MySQL'],
            'nivel_ingles' => 'A1',
            'experiencia_min' => '0-1 años',
            'carrera' => 'Ingeniería en Sistemas',
        ];

        $resultado = MatchingCalculator::calcularMatchCompleto(
            $habilidades, 'B2', '3-5 años',
            'Ingeniería en Sistemas', $perfil, 'Ingeniería en Sistemas'
        );

        $this->assertSame(100, $resultado['tecnico']);
        $this->assertSame(100, $resultado['ingles']);
        $this->assertSame(100, $resultado['experiencia']);
        $this->assertSame(100, $resultado['carrera']);
        $this->assertSame(100, $resultado['soft_skills']);
        $this->assertSame(100, $resultado['match_total']);
    }

    public function testMatchCompletoNinguno(): void {
        $habilidades = ['tecnicas' => [], 'blandas' => [], 'idiomas' => []];
        $perfil = [
            'habilidades_requeridas' => ['PHP'],
            'nivel_ingles' => 'C2',
            'experiencia_min' => '5+ años',
            'carrera' => 'Ingeniería en Sistemas',
        ];

        $resultado = MatchingCalculator::calcularMatchCompleto(
            $habilidades, 'A1', '0-1 años',
            'Administración', $perfil, 'Ingeniería en Sistemas'
        );

        $this->assertSame(0, $resultado['tecnico']);
        // A1(1) vs C2(6) → 17%
        $this->assertSame(17, $resultado['ingles']);
        // 0-1 años(1) vs 5+ años(5) → 20%
        $this->assertSame(20, $resultado['experiencia']);
        $this->assertSame(0, $resultado['carrera']);
        $this->assertSame(0, $resultado['soft_skills']);
        // total = round((0+17+20+0+0)/5) = 7
        $this->assertSame(7, $resultado['match_total']);
    }

    // ==========================================
    // generarFeedback
    // ==========================================

    public function testFeedbackAltoMatch(): void {
        $feedback = MatchingCalculator::generarFeedback(85, 90, 80, 100);
        $this->assertStringContainsString('coincide muy bien', $feedback);
    }

    public function testFeedbackMatchMedio(): void {
        $feedback = MatchingCalculator::generarFeedback(65, 70, 65, 100);
        $this->assertStringContainsString('buena coincidencia', $feedback);
    }

    public function testFeedbackMatchBajo(): void {
        $feedback = MatchingCalculator::generarFeedback(30, 20, 40, 0);
        $this->assertStringContainsString('no coincide completamente', $feedback);
    }

    public function testFeedbackConHabilidadesDebiles(): void {
        $feedback = MatchingCalculator::generarFeedback(50, 40, 50, 100);
        $this->assertStringContainsString('habilidades técnicas', $feedback);
    }

    public function testFeedbackConInglesDebil(): void {
        $feedback = MatchingCalculator::generarFeedback(70, 70, 30, 100);
        $this->assertStringContainsString('inglés', $feedback);
    }

    public function testFeedbackCarreraDiferente(): void {
        $feedback = MatchingCalculator::generarFeedback(60, 70, 80, 0);
        $this->assertStringContainsString('carrera', $feedback);
    }

    public function testFeedbackSinDebilidades(): void {
        $feedback = MatchingCalculator::generarFeedback(85, 90, 80, 100);
        // Solo la línea base, sin recomendaciones
        $this->assertStringContainsString('coincide muy bien', $feedback);
        $this->assertStringNotContainsString('habilidades técnicas', $feedback);
    }
}
