<?php declare(strict_types=1);

/**
 * Controlador de Evaluaciones.
 *
 * Endpoints:
 *   GET  /evaluaciones/catalogo      — Lista pruebas disponibles con estado
 *   POST /evaluaciones/iniciar        — Iniciar nueva evaluación
 *   POST /evaluaciones/respuesta      — Guardar respuesta individual
 *   POST /evaluaciones/finalizar      — Finalizar evaluación y calcular puntaje
 *   GET  /evaluaciones/radar          — Datos para spider chart
 */
class EvaluacionesController {

    // ============================================
    // Helpers internos
    // ============================================

    /**
     * Nombres legibles para tipos de prueba.
     */
    private static array $tiposNombres = [
        'tecnica' => 'Prueba Técnica',
        'psico' => 'Prueba Psicométrica',
        'cogni' => 'Prueba Cognitiva',
        'proy' => 'Prueba Proyectiva',
    ];

    /**
     * Verifica si un tipo de prueba está bloqueado para el egresado.
     * 
     * - tecnica: cada 6 meses
     * - psico, cogni, proy: una sola vez para siempre
     */
    private static function estaBloqueada(string $tipo, ?array $ultimaEvaluacion): array {
        if (!$ultimaEvaluacion) {
            return ['bloqueada' => false, 'mensaje' => null, 'disponible_en' => null];
        }

        if (empty($ultimaEvaluacion['fecha_fin'])) {
            // Evaluación en progreso
            return [
                'bloqueada' => true,
                'mensaje' => 'Ya tienes una evaluación en progreso de este tipo',
                'disponible_en' => null,
            ];
        }

        if ($tipo === 'tecnica') {
            $fechaFin = strtotime($ultimaEvaluacion['fecha_fin']);
            $disponible = strtotime('+6 months', $fechaFin);
            
            if (time() < $disponible) {
                return [
                    'bloqueada' => true,
                    'mensaje' => 'La prueba técnica solo se puede realizar cada 6 meses',
                    'disponible_en' => date('Y-m-d', $disponible),
                ];
            }
            return ['bloqueada' => false, 'mensaje' => null, 'disponible_en' => null];
        }

        // Psico, cogni, proy: una sola vez
        return [
            'bloqueada' => true,
            'mensaje' => 'Esta prueba ya fue completada y no se puede repetir',
            'disponible_en' => null,
        ];
    }

    /**
     * Obtiene el ID de carrera del egresado.
     */
    private static function getCarreraId(PDO $pdo, int $userId): ?int {
        $stmt = $pdo->prepare("SELECT carrera_id FROM egresados WHERE usuario_id = :uid");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int) $row['carrera_id'] : null;
    }

    // ============================================
    // Endpoints
    // ============================================

    /**
     * GET /evaluaciones/catalogo
     * Lista las 4 pruebas disponibles con estado y bloqueo.
     */
    public static function getCatalogo() {
        if (!Middleware::authMiddleware()) return;
        if (!Middleware::requireRole('egresado')) return;

        $usuario = getUsuarioActual();
        $userId = $usuario['id'];
        $pdo = getPgConnection();

        try {
            // Obtener evaluaciones del egresado
            $stmt = $pdo->prepare("
                SELECT tipo_prueba, puntaje_global, fecha_fin, es_base
                FROM evaluaciones
                WHERE egresado_id = :uid
                ORDER BY fecha_inicio DESC
            ");
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $evaluaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Indexar por tipo_prueba
            $porTipo = [];
            foreach ($evaluaciones as $ev) {
                $porTipo[$ev['tipo_prueba']] = $ev;
            }

            // Obtener config de todas las pruebas
            $stmt = $pdo->prepare("
                SELECT id, tipo_prueba, duracion_minutos, cantidad_preguntas
                FROM config_pruebas
                ORDER BY id
            ");
            $stmt->execute();
            $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $catalogo = [];
            foreach ($configs as $config) {
                $tipo = $config['tipo_prueba'];
                $ultima = $porTipo[$tipo] ?? null;
                $bloqueo = self::estaBloqueada($tipo, $ultima);

                $catalogo[] = [
                    'id' => (int) $config['id'],
                    'nombre' => self::$tiposNombres[$tipo] ?? $tipo,
                    'tipo' => $tipo,
                    'minutos' => (int) $config['duracion_minutos'],
                    'cantidad_preguntas' => (int) $config['cantidad_preguntas'],
                    'completada' => $ultima !== null && !empty($ultima['fecha_fin']),
                    'en_progreso' => $ultima !== null && empty($ultima['fecha_fin']),
                    'ultimo_puntaje' => $ultima && $ultima['puntaje_global'] !== null
                        ? (int) $ultima['puntaje_global']
                        : null,
                    'bloqueada' => $bloqueo['bloqueada'],
                    'mensaje_bloqueo' => $bloqueo['mensaje'],
                    'disponible_en' => $bloqueo['disponible_en'],
                ];
            }

            responderExito($catalogo, 'Catálogo de evaluaciones obtenido correctamente');

        } catch (Exception $e) {
            responderError('Error al obtener catálogo: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /evaluaciones/iniciar
     * Iniciar una nueva evaluación.
     */
    public static function iniciar() {
        if (!Middleware::authMiddleware()) return;
        if (!Middleware::requireRole('egresado')) return;

        $usuario = getUsuarioActual();
        $userId = $usuario['id'];
        $requestData = Flight::request()->data;
        $tipoPrueba = $requestData->tipo_prueba ?? null;

        if (!$tipoPrueba || !in_array($tipoPrueba, ['tecnica', 'psico', 'cogni', 'proy'], true)) {
            responderError('Tipo de prueba inválido. Use: tecnica, psico, cogni, proy', 400);
            return;
        }

        $pdo = getPgConnection();

        try {
            $pdo->beginTransaction();

            // 1. Verificar bloqueo
            $stmt = $pdo->prepare("
                SELECT id, fecha_fin FROM evaluaciones
                WHERE egresado_id = :uid AND tipo_prueba = :tipo
                ORDER BY fecha_inicio DESC
                LIMIT 1
            ");
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':tipo', $tipoPrueba, PDO::PARAM_STR);
            $stmt->execute();
            $ultima = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            $bloqueo = self::estaBloqueada($tipoPrueba, $ultima);

            if ($bloqueo['bloqueada']) {
                $pdo->rollBack();
                responderError($bloqueo['mensaje'] ?? 'Evaluación bloqueada', 400);
                return;
            }

            // 2. Obtener config de la prueba
            $stmt = $pdo->prepare("
                SELECT duracion_minutos, cantidad_preguntas FROM config_pruebas
                WHERE tipo_prueba = :tipo
            ");
            $stmt->bindValue(':tipo', $tipoPrueba, PDO::PARAM_STR);
            $stmt->execute();
            $config = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$config) {
                $pdo->rollBack();
                responderError('Configuración de prueba no encontrada', 404);
                return;
            }

            // 3. Obtener carrera del egresado
            $carreraId = self::getCarreraId($pdo, $userId);

            // 4. Seleccionar preguntas aleatorias
            $where = "tipo_prueba = :tipo AND activo = true";
            $params = [':tipo' => $tipoPrueba];

            // Para técnica, filtrar por carrera
            if ($tipoPrueba === 'tecnica' && $carreraId !== null) {
                $where .= " AND carrera_id = :carrera_id";
                $params[':carrera_id'] = $carreraId;
            }

            // Psico/cogni/proy: carrera_id IS NULL en el banco
            if (in_array($tipoPrueba, ['psico', 'cogni', 'proy'], true)) {
                $where .= " AND carrera_id IS NULL";
            }

            $stmt = $pdo->prepare("
                SELECT id, pregunta, opciones
                FROM banco_preguntas
                WHERE {$where}
                ORDER BY RANDOM()
                LIMIT :cantidad
            ");
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->bindValue(':cantidad', (int) $config['cantidad_preguntas'], PDO::PARAM_INT);
            $stmt->execute();
            $preguntas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($preguntas)) {
                $pdo->rollBack();
                responderError('No hay preguntas disponibles para esta prueba', 404);
                return;
            }

            // 5. Crear evaluación
            $expiraEn = date('Y-m-d H:i:s', strtotime("+{$config['duracion_minutos']} minutes"));

            $stmt = $pdo->prepare("
                INSERT INTO evaluaciones (egresado_id, tipo_prueba, puntaje_global, detalle_resultados, es_base, fecha_inicio, fecha_fin)
                VALUES (:uid, :tipo, NULL, NULL, false, NOW(), NULL)
            ");
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':tipo', $tipoPrueba, PDO::PARAM_STR);
            $stmt->execute();

            $evaluacionId = (int) $pdo->lastInsertId('evaluaciones_id_seq');

            // Guardar expira_en en un metadata temporal (usaremos fecha_fin para expiración real)
            // Por ahora, calculamos el tiempo de expiración
            $expiraTimestamp = strtotime($expiraEn);

            $pdo->commit();

            // Formatear preguntas (sin respuesta_correcta)
            $preguntasResponse = [];
            foreach ($preguntas as $p) {
                $preguntasResponse[] = [
                    'id' => (int) $p['id'],
                    'pregunta' => $p['pregunta'],
                    'opciones' => json_decode($p['opciones'], true),
                ];
            }

            responderExito([
                'evaluacion_id' => $evaluacionId,
                'tipo_prueba' => $tipoPrueba,
                'duracion_minutos' => (int) $config['duracion_minutos'],
                'expira_en' => $expiraEn,
                'preguntas' => $preguntasResponse,
            ], 'Evaluación iniciada correctamente');

        } catch (Exception $e) {
            handleTransactionError($pdo, 'Error al iniciar evaluación: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /evaluaciones/respuesta
     * Guardar una respuesta individual.
     */
    public static function guardarRespuesta() {
        if (!Middleware::authMiddleware()) return;
        if (!Middleware::requireRole('egresado')) return;

        $usuario = getUsuarioActual();
        $userId = $usuario['id'];
        $requestData = Flight::request()->data;
        $evaluacionId = $requestData->evaluacion_id ?? null;
        $preguntaId = $requestData->pregunta_id ?? null;
        $opcion = $requestData->opcion ?? null;

        if (!$evaluacionId || !$preguntaId || !$opcion) {
            responderError('evaluacion_id, pregunta_id y opcion son requeridos', 400);
            return;
        }

        if (!in_array(strtolower($opcion), ['a', 'b', 'c', 'd'], true)) {
            responderError('Opción inválida. Use: a, b, c, d', 400);
            return;
        }

        $pdo = getPgConnection();

        try {
            $pdo->beginTransaction();

            // 1. Verificar evaluación existe y pertenece al egresado
            $stmt = $pdo->prepare("
                SELECT id, tipo_prueba, fecha_inicio, fecha_fin FROM evaluaciones
                WHERE id = :id AND egresado_id = :uid AND es_base = false
            ");
            $stmt->bindValue(':id', (int) $evaluacionId, PDO::PARAM_INT);
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $evaluacion = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$evaluacion) {
                $pdo->rollBack();
                responderError('Evaluación no encontrada', 404);
                return;
            }

            if (!empty($evaluacion['fecha_fin'])) {
                $pdo->rollBack();
                responderError('Esta evaluación ya fue finalizada', 400);
                return;
            }

            // 2. Validar tiempo de expiración (60 minutos desde inicio por defecto)
            $inicio = strtotime($evaluacion['fecha_inicio']);
            $stmtConfig = $pdo->prepare("SELECT duracion_minutos FROM config_pruebas WHERE tipo_prueba = :tipo");
            $stmtConfig->bindValue(':tipo', $evaluacion['tipo_prueba'], PDO::PARAM_STR);
            $stmtConfig->execute();
            $config = $stmtConfig->fetch(PDO::FETCH_ASSOC);
            $duracion = $config ? (int) $config['duracion_minutos'] : 60;
            $expira = $inicio + ($duracion * 60);

            if (time() > $expira) {
                $pdo->rollBack();
                // Auto-finalizar
                self::finalizarEvaluacionInterna($pdo, (int) $evaluacionId);
                responderError('Tiempo expirado. Evaluación finalizada automáticamente', 400);
                return;
            }

            // 3. Verificar que la pregunta pertenece al tipo de prueba
            $stmt = $pdo->prepare("
                SELECT id, tipo_prueba, respuesta_correcta FROM banco_preguntas WHERE id = :id
            ");
            $stmt->bindValue(':id', (int) $preguntaId, PDO::PARAM_INT);
            $stmt->execute();
            $pregunta = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$pregunta) {
                $pdo->rollBack();
                responderError('Pregunta no encontrada', 404);
                return;
            }

            // 4. Verificar que no haya respuesta previa
            $stmt = $pdo->prepare("
                SELECT id FROM respuestas_detalle 
                WHERE evaluacion_id = :eval_id AND pregunta_id = :preg_id
            ");
            $stmt->bindValue(':eval_id', (int) $evaluacionId, PDO::PARAM_INT);
            $stmt->bindValue(':preg_id', (int) $preguntaId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->fetch()) {
                $pdo->rollBack();
                responderError('Ya respondiste esta pregunta', 400);
                return;
            }

            // 5. Calcular si es correcta (NULL para psico/proy)
            $esCorrecta = null;
            if ($pregunta['respuesta_correcta'] !== null) {
                $esCorrecta = strtolower($pregunta['respuesta_correcta']) === strtolower($opcion);
            }

            // 6. Guardar respuesta
            $stmt = $pdo->prepare("
                INSERT INTO respuestas_detalle (evaluacion_id, pregunta_id, respuesta_dada, es_correcta)
                VALUES (:eval_id, :preg_id, :opcion, :es_correcta)
            ");
            $stmt->bindValue(':eval_id', (int) $evaluacionId, PDO::PARAM_INT);
            $stmt->bindValue(':preg_id', (int) $preguntaId, PDO::PARAM_INT);
            $stmt->bindValue(':opcion', strtolower($opcion), PDO::PARAM_STR);
            if ($esCorrecta !== null) {
                $stmt->bindValue(':es_correcta', $esCorrecta, PDO::PARAM_BOOL);
            } else {
                $stmt->bindValue(':es_correcta', null, PDO::PARAM_NULL);
            }
            $stmt->execute();

            $pdo->commit();

            responderExito(['status' => 'saved'], 'Respuesta guardada correctamente');

        } catch (Exception $e) {
            handleTransactionError($pdo, 'Error al guardar respuesta: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /evaluaciones/finalizar
     * Finalizar evaluación y calcular puntaje.
     */
    public static function finalizar() {
        if (!Middleware::authMiddleware()) return;
        if (!Middleware::requireRole('egresado')) return;

        $usuario = getUsuarioActual();
        $userId = $usuario['id'];
        $requestData = Flight::request()->data;
        $evaluacionId = $requestData->evaluacion_id ?? null;

        if (!$evaluacionId) {
            responderError('evaluacion_id es requerido', 400);
            return;
        }

        $pdo = getPgConnection();

        try {
            $pdo->beginTransaction();

            // Verificar evaluación
            $stmt = $pdo->prepare("
                SELECT id, tipo_prueba, fecha_fin FROM evaluaciones
                WHERE id = :id AND egresado_id = :uid AND es_base = false
            ");
            $stmt->bindValue(':id', (int) $evaluacionId, PDO::PARAM_INT);
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $evaluacion = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$evaluacion) {
                $pdo->rollBack();
                responderError('Evaluación no encontrada', 404);
                return;
            }

            if (!empty($evaluacion['fecha_fin'])) {
                $pdo->rollBack();
                responderError('Esta evaluación ya fue finalizada', 400);
                return;
            }

            $resultado = self::finalizarEvaluacionInterna($pdo, (int) $evaluacionId, true);

            $pdo->commit();

            responderExito($resultado, 'Evaluación finalizada correctamente');

        } catch (Exception $e) {
            handleTransactionError($pdo, 'Error al finalizar evaluación: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Lógica interna para finalizar una evaluación.
     * Retorna los resultados calculados.
     */
    private static function finalizarEvaluacionInterna(PDO $pdo, int $evaluacionId, bool $inTransaction = false): array {
        // Obtener tipo de prueba
        $stmt = $pdo->prepare("SELECT tipo_prueba FROM evaluaciones WHERE id = :id");
        $stmt->bindValue(':id', $evaluacionId, PDO::PARAM_INT);
        $stmt->execute();
        $ev = $stmt->fetch(PDO::FETCH_ASSOC);
        $tipoPrueba = $ev['tipo_prueba'];

        // Para técnica y cogni: calcular puntaje
        if (in_array($tipoPrueba, ['tecnica', 'cogni'], true)) {
            // Total de preguntas respondidas
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM respuestas_detalle WHERE evaluacion_id = :id");
            $stmt->bindValue(':id', $evaluacionId, PDO::PARAM_INT);
            $stmt->execute();
            $total = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Correctas
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as correctas FROM respuestas_detalle 
                WHERE evaluacion_id = :id AND es_correcta = true
            ");
            $stmt->bindValue(':id', $evaluacionId, PDO::PARAM_INT);
            $stmt->execute();
            $correctas = (int) $stmt->fetch(PDO::FETCH_ASSOC)['correctas'];

            $incorrectas = $total - $correctas;
            $puntaje = $total > 0 ? (int) round(($correctas / $total) * 100) : 0;

            // Detalle por categorías (si aplica, para técnica)
            $detalle = ['correctas' => $correctas, 'incorrectas' => $incorrectas];
            
            if ($tipoPrueba === 'tecnica') {
                $stmt = $pdo->prepare("
                    SELECT bp.pregunta, rd.es_correcta 
                    FROM respuestas_detalle rd
                    JOIN banco_preguntas bp ON rd.pregunta_id = bp.id
                    WHERE rd.evaluacion_id = :id
                ");
                $stmt->bindValue(':id', $evaluacionId, PDO::PARAM_INT);
                $stmt->execute();
                $respuestas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Simple categorización por keywords en la pregunta
                $categorias = [];
                foreach ($respuestas as $r) {
                    $pregunta = strtolower($r['pregunta']);
                    if (str_contains($pregunta, 'algoritmo') || str_contains($pregunta, 'complejidad') || str_contains($pregunta, 'orden')) {
                        $cat = 'algoritmos';
                    } elseif (str_contains($pregunta, 'tabla') || str_contains($pregunta, 'join') || str_contains($pregunta, 'base')) {
                        $cat = 'bases_datos';
                    } elseif (str_contains($pregunta, 'red') || str_contains($pregunta, 'servidor') || str_contains($pregunta, 'api')) {
                        $cat = 'redes';
                    } else {
                        $cat = 'general';
                    }
                    
                    if (!isset($categorias[$cat])) {
                        $categorias[$cat] = ['correctas' => 0, 'total' => 0];
                    }
                    $categorias[$cat]['total']++;
                    if ($r['es_correcta']) {
                        $categorias[$cat]['correctas']++;
                    }
                }
                
                // Convertir a porcentajes
                $categoriasPct = [];
                foreach ($categorias as $cat => $data) {
                    $categoriasPct[$cat] = $data['total'] > 0
                        ? (int) round(($data['correctas'] / $data['total']) * 100)
                        : 0;
                }
                $detalle['categorias'] = $categoriasPct;
            }

            // Actualizar evaluación
            $stmt = $pdo->prepare("
                UPDATE evaluaciones 
                SET puntaje_global = :puntaje, detalle_resultados = :detalle, fecha_fin = NOW()
                WHERE id = :id
            ");
            $stmt->bindValue(':puntaje', $puntaje, PDO::PARAM_INT);
            $stmt->bindValue(':detalle', json_encode($detalle, JSON_UNESCAPED_UNICODE), PDO::PARAM_STR);
            $stmt->bindValue(':id', $evaluacionId, PDO::PARAM_INT);
            $stmt->execute();

            return [
                'puntaje_global' => $puntaje,
                'detalle_resultados' => $detalle,
                'match_actualizado' => false,
            ];
        }

        // Para psico y proy: solo cerrar sin puntaje
        $stmt = $pdo->prepare("
            UPDATE evaluaciones 
            SET fecha_fin = NOW()
            WHERE id = :id
        ");
        $stmt->bindValue(':id', $evaluacionId, PDO::PARAM_INT);
        $stmt->execute();

        // Obtener respuestas para psico
        $stmt = $pdo->prepare("
            SELECT bp.pregunta, rd.respuesta_dada 
            FROM respuestas_detalle rd
            JOIN banco_preguntas bp ON rd.pregunta_id = bp.id
            WHERE rd.evaluacion_id = :id
        ");
        $stmt->bindValue(':id', $evaluacionId, PDO::PARAM_INT);
        $stmt->execute();
        $respuestas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $detalle = ['respuestas' => count($respuestas), 'completada' => true];

        $stmt = $pdo->prepare("
            UPDATE evaluaciones 
            SET detalle_resultados = :detalle
            WHERE id = :id
        ");
        $stmt->bindValue(':detalle', json_encode($detalle, JSON_UNESCAPED_UNICODE), PDO::PARAM_STR);
        $stmt->bindValue(':id', $evaluacionId, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'puntaje_global' => null,
            'detalle_resultados' => $detalle,
            'match_actualizado' => false,
        ];
    }

    /**
     * GET /evaluaciones/radar
     * Datos para spider chart: alumno vs promedio de carrera.
     */
    public static function getRadar() {
        if (!Middleware::authMiddleware()) return;
        if (!Middleware::requireRole('egresado')) return;

        $usuario = getUsuarioActual();
        $userId = $usuario['id'];
        $pdo = getPgConnection();

        try {
            // Obtener carrera del egresado
            $carreraId = self::getCarreraId($pdo, $userId);

            // Obtener evaluaciones completadas del egresado
            $stmt = $pdo->prepare("
                SELECT tipo_prueba, puntaje_global FROM evaluaciones
                WHERE egresado_id = :uid AND fecha_fin IS NOT NULL
                ORDER BY fecha_fin DESC
            ");
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $evaluaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Construir labels y alumno
            $labels = [];
            $alumno = [];
            $tiposObtenidos = [];

            foreach ($evaluaciones as $ev) {
                if (in_array($ev['puntaje_global'], [null, 'null'], true)) continue;
                $labels[] = self::$tiposNombres[$ev['tipo_prueba']] ?? $ev['tipo_prueba'];
                $alumno[] = (int) $ev['puntaje_global'];
                $tiposObtenidos[] = $ev['tipo_prueba'];
            }

            // Calcular promedio de carrera
            $promedioCarrera = [];
            if ($carreraId !== null && !empty($tiposObtenidos)) {
                // Build named parameters for each tipo
                $paramKeys = [];
                foreach ($tiposObtenidos as $i => $tipo) {
                    $paramKeys[] = ":tipo_$i";
                }
                $placeholders = implode(', ', $paramKeys);
                
                $stmt = $pdo->prepare("
                    SELECT e.tipo_prueba, AVG(e.puntaje_global) as avg_puntaje
                    FROM evaluaciones e
                    JOIN egresados eg ON e.egresado_id = eg.usuario_id
                    WHERE eg.carrera_id = :carrera_id 
                    AND e.fecha_fin IS NOT NULL
                    AND e.tipo_prueba IN ($placeholders)
                    AND e.puntaje_global IS NOT NULL
                    GROUP BY e.tipo_prueba
                ");
                $stmt->bindValue(':carrera_id', $carreraId, PDO::PARAM_INT);
                foreach ($tiposObtenidos as $i => $tipo) {
                    $stmt->bindValue(":tipo_$i", $tipo, PDO::PARAM_STR);
                }
                $stmt->execute();
                $promedios = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $promediosMap = [];
                foreach ($promedios as $p) {
                    $promediosMap[$p['tipo_prueba']] = (int) round((float) $p['avg_puntaje']);
                }

                foreach ($evaluaciones as $ev) {
                    if (in_array($ev['puntaje_global'], [null, 'null'], true)) continue;
                    $promedioCarrera[] = $promediosMap[$ev['tipo_prueba']] ?? 0;
                }
            }

            responderExito([
                'labels' => $labels,
                'alumno' => $alumno,
                'promedio_carrera' => $promedioCarrera,
            ], 'Datos de radar obtenidos correctamente');

        } catch (Exception $e) {
            responderError('Error al obtener datos de radar: ' . $e->getMessage(), 500);
        }
    }
}
