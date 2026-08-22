<?php
declare(strict_types=1);

/**
 * Motor de matching puro — cálculos de compatibilidad entre egresado y vacante.
 * Extraído de VacantesController para testing directo.
 *
 * ponytail: clase estática pura, sin dependencias externas. Tests sin mocks.
 */
class MatchingCalculator {

    /**
     * Calcula el porcentaje de match entre habilidades del egresado y las requeridas.
     */
    public static function calcularMatch(array $habilidadesEgresado, array $perfilIdoneo): int {
        $tecnicas = $habilidadesEgresado['tecnicas'] ?? [];
        $requeridas = $perfilIdoneo['habilidades_requeridas'] ?? [];

        if (empty($requeridas)) {
            return 0;
        }

        $coincidencias = array_intersect(
            array_map('strtolower', $tecnicas),
            array_map('strtolower', $requeridas)
        );

        return (int) round((count($coincidencias) / count($requeridas)) * 100);
    }

    /**
     * Compara niveles de inglés y retorna un score 0-100.
     */
    public static function compararNivelesIngles(string $nivelEgresado, string $nivelRequerido): int {
        $niveles = ['A1' => 1, 'A2' => 2, 'B1' => 3, 'B2' => 4, 'C1' => 5, 'C2' => 6];
        $valE = $niveles[strtoupper($nivelEgresado)] ?? 0;
        $valR = $niveles[strtoupper($nivelRequerido)] ?? 0;

        if ($valE >= $valR) {
            return 100;
        }

        // Score proporcional al nivel alcanzado vs requerido
        return (int) round(($valE / $valR) * 100);
    }

    /**
     * Evalúa si la experiencia del egresado cumple el mínimo requerido.
     */
    public static function matchExperiencia(string $expEgresado, string $expMinRequerida): int {
        $rangos = [
            '0-1 años' => 1,
            '1-2 años' => 2,
            '1-3 años' => 2,
            '3-5 años' => 4,
            '5+ años' => 5,
        ];

        $valE = $rangos[$expEgresado] ?? 0;
        $valR = $rangos[$expMinRequerida] ?? 0;

        if ($valE >= $valR) {
            return 100;
        }

        return (int) round(($valE / $valR) * 100);
    }

    /**
     * Calcula match de soft skills.
     */
    public static function calcularSoftSkills(array $habilidadesEgresado, array $perfilIdoneo): int {
        $blandas = $habilidadesEgresado['blandas'] ?? [];
        $idiomas = $habilidadesEgresado['idiomas'] ?? [];
        $todas = array_merge($blandas, $idiomas);

        if (empty($todas)) {
            return 0;
        }

        // Soft skills comunes como referencia
        $softSkillsComunes = [
            'trabajo en equipo', 'comunicación', 'liderazgo', 'resolución de problemas',
            'proactividad', 'adaptabilidad', 'creatividad', 'pensamiento crítico',
            'time management', 'teamwork', 'communication', 'leadership'
        ];

        $coincidencias = array_intersect(
            array_map('strtolower', $todas),
            array_map('strtolower', $softSkillsComunes)
        );

        // Score basado en cuántas soft skills tiene (max 5 para 100%)
        return min(100, (int) round((count($coincidencias) / 5) * 100));
    }

    /**
     * Calcula el match completo de 5 dimensiones.
     *
     * @return array{match_total: int, tecnico: int, ingles: int, experiencia: int, carrera: int, soft_skills: int}
     */
    public static function calcularMatchCompleto(
        array $habilidades,
        string $ingles,
        string $periodoEgreso,
        string $carrera,
        array $perfilIdoneo,
        string $carreraVacante
    ): array {
        $tecnico = self::calcularMatch($habilidades, $perfilIdoneo);
        $inglesScore = self::compararNivelesIngles(
            $ingles,
            $perfilIdoneo['nivel_ingles'] ?? 'B1'
        );
        $experiencia = self::matchExperiencia(
            $periodoEgreso,
            $perfilIdoneo['experiencia_min'] ?? '0-1 años'
        );
        $carreraMatch = (stripos($carrera, $carreraVacante) !== false) ? 100 : 0;
        $softSkills = self::calcularSoftSkills($habilidades, $perfilIdoneo);

        $total = (int) round(($tecnico + $inglesScore + $experiencia + $carreraMatch + $softSkills) / 5);

        return [
            'match_total' => $total,
            'tecnico' => $tecnico,
            'ingles' => $inglesScore,
            'experiencia' => $experiencia,
            'carrera' => $carreraMatch,
            'soft_skills' => $softSkills,
        ];
    }

    /**
     * Genera feedback textual basado en los scores.
     */
    public static function generarFeedback(int $match, int $matchTecnico, int $matchIngles, int $matchCarrera): string {
        $partes = [];

        if ($match >= 80) {
            $partes[] = 'Tu perfil coincide muy bien con los requisitos de esta vacante.';
        } elseif ($match >= 60) {
            $partes[] = 'Tu perfil tiene una buena coincidencia con esta vacante, pero hay áreas de oportunidad.';
        } else {
            $partes[] = 'Tu perfil no coincide completamente con esta vacante, pero aún puedes aplicar.';
        }

        if ($matchTecnico < 60) {
            $partes[] = 'Te recomendamos reforzar tus habilidades técnicas para este puesto.';
        }

        if ($matchIngles < 60) {
            $partes[] = 'El nivel de inglés requerido es alto; considera mejorar tu certificación.';
        }

        if ($matchCarrera === 0) {
            $partes[] = 'La carrera solicitada es diferente a la tuya, pero las habilidades transferibles son valiosas.';
        }

        return implode(' ', $partes);
    }
}
