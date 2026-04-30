<?php
declare(strict_types=1);

/**
 * Controller de servicios IA y documentos.
 *
 * Endpoints:
 * - POST /ia/cv/optimizar-biografia
 * - GET  /ia/cv/recomendaciones
 * - POST /ia/chat/asesor
 * - GET  /egresado/cv/pdf
 */

class IaController
{
    /**
     * POST /ia/cv/optimizar-biografia
     * Optimiza la biografía del egresado usando IA (placeholder).
     */
    public static function optimizarBiografia()
    {
        if (!Middleware::authMiddleware()) return;
        if (!Middleware::requireRole('egresado')) return;

        $usuario = getUsuarioActual();
        $userId = $usuario['id'];

        $requestData = Flight::request()->getBody();
        $data = json_decode($requestData, true);

        $textoActual = trim($data['texto_actual'] ?? '');

        if (empty($textoActual)) {
            responderError('El campo texto_actual es requerido', 400);
            return;
        }

        if (strlen($textoActual) > 2000) {
            responderError('El texto no puede exceder 2000 caracteres', 400);
            return;
        }

        $pdo = getPgConnection();

        try {
            $pdo->beginTransaction();

            // Obtener datos del egresado para contexto
            $stmt = $pdo->prepare("
                SELECT e.habilidades, e.trayectoria, c.nombre as carrera
                FROM egresados e
                JOIN carreras c ON e.carrera_id = c.id
                WHERE e.usuario_id = :usuario_id
            ");
            $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $egresado = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$egresado) {
                $pdo->rollBack();
                responderError('Perfil de egresado no encontrado', 404);
                return;
            }

            // Placeholder: En producción llamar a API de IA
            // Simulación de biografía optimizada
            $habilidades = json_decode($egresado['habilidades'] ?? '{}', true);
            $tecnicas = $habilidades['tecnicas'] ?? [];
            $trayectoria = json_decode($egresado['trayectoria'] ?? '[]', true);

            $biografiaOptimizada = "Profesional en {$egresado['carrera']}";

            if (!empty($tecnicas)) {
                $biografiaOptimizada .= ' con dominio de ' . implode(', ', array_slice($tecnicas, 0, 4));
            }

            if (!empty($trayectoria)) {
                $ultimaExp = end($trayectoria);
                if (isset($ultimaExp['descripcion'])) {
                    $biografiaOptimizada .= ". Experiencia comprobada en {$ultimaExp['descripcion']}";
                }
            }

            $biografiaOptimizada .= '. Orientado a resultados con capacidad de aprendizaje continuo y trabajo colaborativo en equipos multidisciplinarios.';

            // Actualizar biografia_ia en la BD
            $stmt = $pdo->prepare("
                UPDATE egresados
                SET biografia_ia = :biografia
                WHERE usuario_id = :usuario_id
            ");
            $stmt->bindValue(':biografia', $biografiaOptimizada, PDO::PARAM_STR);
            $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            $pdo->commit();

            responderExito([
                'biografia_optimizada' => $biografiaOptimizada,
                'longitud_original' => strlen($textoActual),
                'longitud_optimizada' => strlen($biografiaOptimizada),
            ], 'Biografía optimizada correctamente');

        } catch (Exception $e) {
            $pdo->rollBack();
            responderError('Error al optimizar biografía: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /ia/cv/recomendaciones
     * Genera recomendaciones basadas en el perfil del egresado.
     */
    public static function recomendaciones()
    {
        if (!Middleware::authMiddleware()) return;
        if (!Middleware::requireRole('egresado')) return;

        $usuario = getUsuarioActual();
        $userId = $usuario['id'];

        $pdo = getPgConnection();

        try {
            // Obtener perfil del egresado
            $stmt = $pdo->prepare("
                SELECT
                    e.habilidades,
                    e.trayectoria,
                    e.cv_drive_id,
                    e.foto_drive_id,
                    c.nombre as carrera
                FROM egresados e
                JOIN carreras c ON e.carrera_id = c.id
                WHERE e.usuario_id = :usuario_id
            ");
            $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $egresado = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$egresado) {
                responderError('Perfil de egresado no encontrado', 404);
                return;
            }

            // Obtener evaluaciones completadas
            $stmt = $pdo->prepare("
                SELECT
                    tipo_prueba,
                    puntaje_global,
                    fecha_fin
                FROM evaluaciones
                WHERE egresado_id = :usuario_id
                ORDER BY fecha_fin DESC
            ");
            $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $allEvaluaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Filter to only completed ones (fecha_fin not null and not empty)
            $evaluaciones = array_filter($allEvaluaciones, fn($e) => !empty($e['fecha_fin']));

            // Obtener vacantes donde se ha postulado
            $stmt = $pdo->prepare("
                SELECT
                    v.titulo,
                    p.match_porcentaje,
                    p.estatus
                FROM postulaciones p
                JOIN vacantes v ON p.vacante_id = v.id
                WHERE p.egresado_id = :usuario_id
                ORDER BY p.fecha DESC
                LIMIT 10
            ");
            $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $postulaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Generar puntos fuertes
            $puntosFuertes = [];
            $habilidades = json_decode($egresado['habilidades'] ?? '{}', true);
            $tecnicas = $habilidades['tecnicas'] ?? [];
            $blandas = $habilidades['blandas'] ?? [];
            $idiomas = $habilidades['idiomas'] ?? [];

            if (count($tecnicas) >= 3) {
                $puntosFuertes[] = "Dominio técnico en " . count($tecnicas) . " tecnologías";
            }

            // Puntajes de evaluaciones
            $puntajeTecnico = null;
            $puntajeCognitivo = null;
            foreach ($evaluaciones as $ev) {
                if ($ev['tipo_prueba'] === 'tecnica' && $puntajeTecnico === null) {
                    $puntajeTecnico = (float) $ev['puntaje_global'];
                }
                if ($ev['tipo_prueba'] === 'cogni' && $puntajeCognitivo === null) {
                    $puntajeCognitivo = (float) $ev['puntaje_global'];
                }
            }

            if ($puntajeTecnico !== null && $puntajeTecnico >= 80) {
                $puntosFuertes[] = "Excelente puntaje técnico: {$puntajeTecnico}%";
            }

            if ($puntajeCognitivo !== null && $puntajeCognitivo >= 80) {
                $puntosFuertes[] = "Alto rendimiento cognitivo: {$puntajeCognitivo}%";
            }

            $b2orHigher = false;
            foreach ($idiomas as $idioma) {
                if (stripos($idioma, 'B2') !== false || stripos($idioma, 'C1') !== false || stripos($idioma, 'C2') !== false) {
                    $b2orHigher = true;
                    break;
                }
            }
            if ($b2orHigher) {
                $puntosFuertes[] = 'Nivel de inglés B2 o superior';
            }

            if (empty($puntosFuertes)) {
                $puntosFuertes[] = 'Perfil en construcción - completar evaluaciones y habilidades';
            }

            // Generar puntos débiles
            $puntosDebiles = [];

            if ($egresado['foto_drive_id'] === null) {
                $puntosDebiles[] = 'Sin foto de perfil (afecta visibilidad en búsquedas de empleadores)';
            }

            if ($egresado['cv_drive_id'] === null) {
                $puntosDebiles[] = 'Sin CV subido (genera CV automático desde tu perfil)';
            }

            if ($puntajeTecnico === null) {
                $puntosDebiles[] = 'Sin prueba técnica completada (mejora tu match con vacantes)';
            }

            if (empty($tecnicas)) {
                $puntosDebiles[] = 'Sin habilidades técnicas registradas';
            }

            if (empty($trayectoria = json_decode($egresado['trayectoria'] ?? '[]', true))) {
                $puntosDebiles[] = 'Sin experiencia o proyectos registrados';
            }

            // Cursos sugeridos basados en brechas
            $cursosSugeridos = [];

            if ($puntajeTecnico !== null && $puntajeTecnico < 70) {
                $cursosSugeridos[] = [
                    'titulo' => 'Refuerzo técnico en ' . $egresado['carrera'],
                    'descripcion' => 'Tu puntaje técnico está por debajo del promedio. Considera cursos de actualización.',
                    'prioridad' => 'alta',
                ];
            }

            if ($puntajeCognitivo !== null && $puntajeCognitivo < 70) {
                $cursosSugeridos[] = [
                    'titulo' => 'Desarrollo de habilidades cognitivas',
                    'descripcion' => 'Ejercicios de razonamiento lógico y resolución de problemas.',
                    'prioridad' => 'media',
                ];
            }

            // Analizar vacantes con bajo match
            $bajoMatch = array_filter($postulaciones, fn($p) => $p['match_porcentaje'] < 50);
            if (count($bajoMatch) > 0) {
                $cursosSugeridos[] = [
                    'titulo' => 'Mejora tu perfil para vacantes específicas',
                    'descripcion' => 'Tienes postulaciones con bajo match. Revisa los requisitos de las vacantes y fortalece esas áreas.',
                    'prioridad' => 'media',
                ];
            }

            if (empty($cursosSugeridos)) {
                $cursosSugeridos[] = [
                    'titulo' => 'Mantén tu perfil actualizado',
                    'descripcion' => 'Tu perfil está completo. Sigue aplicando a vacantes y actualizando tus habilidades.',
                    'prioridad' => 'info',
                ];
            }

            responderExito([
                'puntos_fuertes' => $puntosFuertes,
                'puntos_debiles' => $puntosDebiles,
                'cursos_sugeridos' => $cursosSugeridos,
                'resumen' => [
                    'puntaje_tecnico' => $puntajeTecnico,
                    'puntaje_cognitivo' => $puntajeCognitivo,
                    'total_habilidades' => count($tecnicas),
                    'total_postulaciones' => count($postulaciones),
                    'evaluaciones_completadas' => count($evaluaciones),
                ],
            ], 'Recomendaciones generadas correctamente');

        } catch (Exception $e) {
            responderError('Error al generar recomendaciones: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /ia/chat/asesor
     * Chat con asesor IA (placeholder).
     */
    public static function chatAsesor()
    {
        if (!Middleware::authMiddleware()) return;

        $usuario = getUsuarioActual();
        $rol = $usuario['rol'];

        $requestData = Flight::request()->getBody();
        $data = json_decode($requestData, true);

        $mensaje = trim($data['mensaje'] ?? '');
        $contextoPantalla = trim($data['contexto_pantalla'] ?? '');

        if (empty($mensaje)) {
            responderError('El campo mensaje es requerido', 400);
            return;
        }

        if (strlen($mensaje) > 1000) {
            responderError('El mensaje no puede exceder 1000 caracteres', 400);
            return;
        }

        // Placeholder: En producción conectar con modelo de IA
        // Respuestas contextuales simuladas basadas en el contexto
        $respuestas = [
            'vacantes' => 'Puedes buscar vacantes en la sección de "Empleos". Usa los filtros por ubicación y área para encontrar las mejores opciones. Te recomiendo completar tus evaluaciones para mejorar tu match.',
            'evaluaciones' => 'Las evaluaciones disponibles son: Técnica (cada 6 meses), Psicométrica, Cognitiva y Proyectiva (una sola vez). Completa todas para maximizar tu perfil.',
            'cv' => 'Tu CV se genera automáticamente con la información de tu perfil. Asegúrate de tener completada tu biografía, trayectoria y habilidades para un CV más completo.',
            'perfil' => 'Un perfil completo incluye: foto, biografía, habilidades técnicas y blandas, trayectoria profesional, y evaluaciones completadas.',
            'postulaciones' => 'Puedes ver el estado de tus postulaciones en "Mis Aplicaciones". Los estatus son: pendiente, revisada, aceptada y rechazada.',
            'default' => 'Gracias por tu consulta. Como asesor virtual puedo ayudarte con información sobre vacantes, evaluaciones, tu perfil CV y el proceso de postulación. ¿Sobre qué tema te gustaría saber más?',
        ];

        $respuesta = $respuestas['default'];
        $lower = strtolower($mensaje);

        if (strpos($lower, 'vacante') !== false || strpos($lower, 'empleo') !== false || strpos($lower, 'trabajo') !== false) {
            $respuesta = $respuestas['vacantes'];
        } elseif (strpos($lower, 'evaluaci') !== false || strpos($lower, 'prueba') !== false || strpos($lower, 'examen') !== false) {
            $respuesta = $respuestas['evaluaciones'];
        } elseif (strpos($lower, 'cv') !== false || strpos($lower, 'curriculum') !== false || strpos($lower, 'hoja de vida') !== false) {
            $respuesta = $respuestas['cv'];
        } elseif (strpos($lower, 'perfil') !== false || strpos($lower, 'foto') !== false || strpos($lower, 'biografía') !== false) {
            $respuesta = $respuestas['perfil'];
        } elseif (strpos($lower, 'postul') !== false || strpos($lower, 'aplic') !== false || strpos($lower, 'estatus') !== false) {
            $respuesta = $respuestas['postulaciones'];
        }

        responderExito([
            'respuesta' => $respuesta,
            'contexto' => $contextoPantalla,
        ], 'Respuesta del asesor obtenida');
    }

    /**
     * GET /egresado/cv/pdf
     * Genera o recupera link de CV en PDF.
     */
    public static function cvPdf()
    {
        if (!Middleware::authMiddleware()) return;
        if (!Middleware::requireRole('egresado')) return;

        $usuario = getUsuarioActual();
        $userId = $usuario['id'];

        $pdo = getPgConnection();

        try {
            // Obtener perfil completo del egresado
            $stmt = $pdo->prepare("
                SELECT
                    e.nombre,
                    e.apellido_paterno,
                    e.apellido_materno,
                    e.periodo_egreso,
                    e.biografia_ia,
                    e.trayectoria,
                    e.habilidades,
                    e.contacto,
                    e.cv_drive_id,
                    c.nombre as carrera,
                    d.nombre as division
                FROM egresados e
                JOIN carreras c ON e.carrera_id = c.id
                JOIN divisiones d ON c.division_id = d.id
                WHERE e.usuario_id = :usuario_id
            ");
            $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $egresado = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$egresado) {
                responderError('Perfil de egresado no encontrado', 404);
                return;
            }

            // Si ya existe un CV en Drive, retornar el link
            if ($egresado['cv_drive_id'] !== null) {
                responderExito([
                    'pdf_url' => "https://drive.google.com/file/d/{$egresado['cv_drive_id']}/view",
                    'ultima_generacion' => date('c'),
                ], 'CV recuperado correctamente');
                return;
            }

            // Placeholder: En producción generar PDF y subir a Drive
            // Por ahora, simular la generación
            $nombreCompleto = trim("{$egresado['nombre']} {$egresado['apellido_paterno']} {$egresado['apellido_materno']}");
            $contacto = json_decode($egresado['contacto'] ?? '{}', true);
            $habilidades = json_decode($egresado['habilidades'] ?? '{}', true);
            $trayectoria = json_decode($egresado['trayectoria'] ?? '[]', true);

            $cvPreview = [
                'nombre' => $nombreCompleto,
                'carrera' => $egresado['carrera'],
                'division' => $egresado['division'],
                'periodo_egreso' => $egresado['periodo_egreso'],
                'biografia' => $egresado['biografia_ia'] ?? 'Sin biografía',
                'contacto' => $contacto,
                'habilidades_tecnicas' => $habilidades['tecnicas'] ?? [],
                'habilidades_blandas' => $habilidades['blandas'] ?? [],
                'trayectoria' => $trayectoria,
            ];

            responderExito([
                'pdf_url' => null,
                'ultima_generacion' => null,
                'preview' => $cvPreview,
                'nota' => 'La generación de PDF requiere integración con Google Drive API',
            ], 'Vista previa del CV generada. Para el PDF completo, configura Google Drive API.');

        } catch (Exception $e) {
            responderError('Error al generar CV: ' . $e->getMessage(), 500);
        }
    }
}
