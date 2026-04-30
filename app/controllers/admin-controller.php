<?php
declare(strict_types=1);

/**
 * Controller de administración.
 *
 * Endpoints:
 * - GET    /admin/dashboard/global
 * - GET    /admin/empresas/pendientes
 * - PATCH  /admin/empresas/:id/convenio
 * - GET    /admin/banco-preguntas
 * - POST   /admin/banco-preguntas/generar-ia
 */

class AdminController
{
    /**
     * GET /admin/dashboard/global
     * Estadísticas globales del sistema.
     */
    public static function dashboardGlobal()
    {
        if (!Middleware::authMiddleware()) return;
        if (!Middleware::requireRole('admin')) return;

        $pdo = getPgConnection();

        try {
            // Total egresados activos
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM usuarios WHERE rol = 'egresado'
            ");
            $stmt->execute();
            $totalEgresados = (int) $stmt->fetchColumn();

            // Total empresas
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM usuarios WHERE rol = 'empresa'
            ");
            $stmt->execute();
            $totalEmpresas = (int) $stmt->fetchColumn();

            // Total contratados (postulaciones con estatus 'aceptada')
            $stmt = $pdo->prepare("
                SELECT COUNT(DISTINCT egresado_id) FROM postulaciones WHERE estatus = 'aceptada'
            ");
            $stmt->execute();
            $totalContratados = (int) $stmt->fetchColumn();

            // Inserción laboral por carrera
            $stmt = $pdo->prepare("
                SELECT
                    c.nombre as carrera,
                    COUNT(DISTINCT CASE WHEN p.estatus = 'aceptada' THEN e.usuario_id END) as contratados,
                    COUNT(DISTINCT e.usuario_id) as total_egresados,
                    CASE
                        WHEN COUNT(DISTINCT e.usuario_id) > 0
                        THEN ROUND(COUNT(DISTINCT CASE WHEN p.estatus = 'aceptada' THEN e.usuario_id END)::numeric / COUNT(DISTINCT e.usuario_id) * 100, 1)
                        ELSE 0
                    END as tasa_insercion
                FROM carreras c
                JOIN egresados e ON e.carrera_id = c.id
                LEFT JOIN postulaciones p ON p.egresado_id = e.usuario_id
                GROUP BY c.nombre
                ORDER BY tasa_insercion DESC
            ");
            $stmt->execute();
            $insercionPorCarrera = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Vacantes por estatus de convenio (empresas)
            $stmt = $pdo->prepare("
                SELECT e.estatus_convenio as estatus, COUNT(v.id) as count
                FROM vacantes v
                JOIN empresas e ON v.empresa_id = e.usuario_id
                GROUP BY e.estatus_convenio
            ");
            $stmt->execute();
            $vacantesPorEstatus = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Postulaciones por estatus
            $stmt = $pdo->prepare("
                SELECT estatus, COUNT(*) as count
                FROM postulaciones
                GROUP BY estatus
            ");
            $stmt->execute();
            $postulacionesPorEstatus = $stmt->fetchAll(PDO::FETCH_ASSOC);

            responderExito([
                'total_egresados' => $totalEgresados,
                'total_empresas' => $totalEmpresas,
                'total_contratados' => $totalContratados,
                'insercion_por_carrera' => $insercionPorCarrera,
                'vacantes_por_estatus' => $vacantesPorEstatus,
                'postulaciones_por_estatus' => $postulacionesPorEstatus,
            ], 'Estadísticas globales obtenidas correctamente');

        } catch (Exception $e) {
            responderError('Error al obtener estadísticas: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /admin/empresas/pendientes
     * Lista empresas con convenio pendiente de aprobación.
     */
    public static function empresasPendientes()
    {
        if (!Middleware::authMiddleware()) return;
        if (!Middleware::requireRole('admin')) return;

        $pdo = getPgConnection();

        try {
            $stmt = $pdo->prepare("
                SELECT
                    e.usuario_id as id,
                    e.nombre_comercial,
                    e.rfc,
                    u.fecha_registro,
                    u.matricula
                FROM empresas e
                JOIN usuarios u ON e.usuario_id = u.id
                WHERE e.estatus_convenio = 'pendiente'
                ORDER BY u.fecha_registro DESC
            ");
            $stmt->execute();
            $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            responderExito($empresas, 'Empresas pendientes obtenidas correctamente');

        } catch (Exception $e) {
            responderError('Error al obtener empresas pendientes: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PATCH /admin/empresas/:id/convenio
     * Aprobar o rechazar convenio de empresa.
     */
    public static function actualizarConvenio($id)
    {
        if (!Middleware::authMiddleware()) return;
        if (!Middleware::requireRole('admin')) return;

        $requestData = Flight::request()->getBody();
        $data = json_decode($requestData, true);

        $estatus = trim($data['estatus'] ?? '');

        if (!in_array($estatus, ['activo', 'rechazado'], true)) {
            responderError('Estatus inválido. Use: activo, rechazado', 400);
            return;
        }

        $pdo = getPgConnection();

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                UPDATE empresas
                SET estatus_convenio = :estatus
                WHERE usuario_id = :id
            ");
            $stmt->bindValue(':estatus', $estatus, PDO::PARAM_STR);
            $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                $pdo->rollBack();
                responderError('Empresa no encontrada', 404);
                return;
            }

            $pdo->commit();
            responderExito([], 'Convenio actualizado correctamente');

        } catch (Exception $e) {
            $pdo->rollBack();
            responderError('Error al actualizar convenio: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /admin/banco-preguntas
     * Lista preguntas del banco con filtros.
     */
    public static function bancoPreguntas()
    {
        if (!Middleware::authMiddleware()) return;
        if (!Middleware::requireRole('admin')) return;

        $request = Flight::request();
        $divisionId = $request->query->division_id ?? null;
        $tipoPrueba = trim($request->query->tipo_prueba ?? '');
        $page = max(1, (int) ($request->query->page ?? 1));
        $limit = min(100, max(1, (int) ($request->query->limit ?? 20)));
        $offset = ($page - 1) * $limit;

        $pdo = getPgConnection();

        try {
            // Construir query base
            $where = [];
            $params = [];

            if ($divisionId) {
                $where[] = 'bp.division_id = :division_id';
                $params[':division_id'] = (int) $divisionId;
            }

            if ($tipoPrueba !== '') {
                $where[] = 'bp.tipo_prueba = :tipo_prueba';
                $params[':tipo_prueba'] = $tipoPrueba;
            }

            $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

            // Count total
            $countSql = "SELECT COUNT(*) FROM banco_preguntas bp LEFT JOIN carreras c ON bp.carrera_id = c.id $whereClause";
            $stmt = $pdo->prepare($countSql);
            foreach ($params as $key => $value) {
                if (is_int($value)) {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value, PDO::PARAM_STR);
                }
            }
            $stmt->execute();
            $total = (int) $stmt->fetchColumn();

            // Fetch questions
            $sql = "
                SELECT
                    bp.id,
                    bp.pregunta,
                    bp.tipo_prueba,
                    bp.opciones,
                    bp.respuesta_correcta,
                    bp.activo,
                    c.nombre as carrera,
                    d.nombre as division
                FROM banco_preguntas bp
                LEFT JOIN carreras c ON bp.carrera_id = c.id
                LEFT JOIN divisiones d ON bp.division_id = d.id
                $whereClause
                ORDER BY bp.id
                LIMIT :limit OFFSET :offset
            ";
            $stmt = $pdo->prepare($sql);
            foreach ($params as $key => $value) {
                if (is_int($value)) {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value, PDO::PARAM_STR);
                }
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $preguntas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Parsear JSONB opciones
            foreach ($preguntas as &$p) {
                if ($p['opciones'] !== null && is_string($p['opciones'])) {
                    $p['opciones'] = json_decode($p['opciones'], true);
                }
            }

            responderExito([
                'preguntas' => $preguntas,
                'meta' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => (int) ceil($total / $limit),
                ],
            ], 'Banco de preguntas obtenido correctamente');

        } catch (Exception $e) {
            responderError('Error al obtener banco de preguntas: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /admin/banco-preguntas/generar-ia
     * Placeholder para generación de preguntas por IA.
     */
    public static function generarPreguntasIA()
    {
        if (!Middleware::authMiddleware()) return;
        if (!Middleware::requireRole('admin')) return;

        $requestData = Flight::request()->getBody();
        $data = json_decode($requestData, true);

        $carreraId = isset($data['carrera_id']) ? (int) $data['carrera_id'] : null;
        $cantidad = isset($data['cantidad']) ? (int) $data['cantidad'] : 10;
        $tipoPrueba = trim($data['tipo_prueba'] ?? 'tecnica');

        if ($carreraId === null) {
            responderError('El campo carrera_id es requerido', 400);
            return;
        }

        if ($cantidad < 1 || $cantidad > 50) {
            responderError('La cantidad debe estar entre 1 y 50', 400);
            return;
        }

        if (!in_array($tipoPrueba, ['tecnica', 'psico', 'cogni', 'proy'], true)) {
            responderError('Tipo de prueba inválido. Use: tecnica, psico, cogni, proy', 400);
            return;
        }

        $pdo = getPgConnection();

        try {
            $pdo->beginTransaction();

            // Verify carrera exists
            $stmt = $pdo->prepare("SELECT id, nombre FROM carreras WHERE id = :id");
            $stmt->bindValue(':id', $carreraId, PDO::PARAM_INT);
            $stmt->execute();
            $carrera = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$carrera) {
                $pdo->rollBack();
                responderError('Carrera no encontrada', 404);
                return;
            }

            // Placeholder: In production this would call an AI API
            // For now, return a simulated response
            $generadas = [];
            for ($i = 0; $i < $cantidad; $i++) {
                $generadas[] = [
                    'carrera' => $carrera['nombre'],
                    'tipo_prueba' => $tipoPrueba,
                    'estado' => 'pendiente_de_revision',
                ];
            }

            // In a real implementation, insert into banco_preguntas here
            // $stmt = $pdo->prepare("INSERT INTO banco_preguntas ...");

            $pdo->commit();

            responderExito([
                'count' => $cantidad,
                'carrera' => $carrera['nombre'],
                'tipo_prueba' => $tipoPrueba,
                'preguntas' => $generadas,
            ], 'Preguntas generadas correctamente (IA no configurada, respuesta simulada)');

        } catch (Exception $e) {
            $pdo->rollBack();
            responderError('Error al generar preguntas: ' . $e->getMessage(), 500);
        }
    }
}
