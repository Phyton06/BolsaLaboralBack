<?php declare(strict_types=1);

/**
 * Controlador de Empresa.
 *
 * Endpoints:
 *   GET    /empresa/perfil                 — Obtener perfil de empresa
 *   PUT    /empresa/perfil                 — Actualizar perfil
 *   GET    /empresa/dashboard/stats        — Estadísticas del dashboard
 *   GET    /empresa/mis-vacantes           — Lista de vacantes de la empresa
 *   POST   /empresa/vacantes               — Crear nueva vacante
 *   GET    /empresa/vacantes/:id/postulantes — Lista postulantes de una vacante
 *   PATCH  /postulaciones/:id/estatus      — Cambiar estatus de postulación
 */
class EmpresaController {

    // ============================================
    // Endpoints de Perfil
    // ============================================

    /**
     * GET /empresa/perfil
     */
    public static function getPerfil() {
        if (!Middleware::authMiddleware()) return;
        if (!Middleware::requireRole('empresa')) return;

        $usuario = getUsuarioActual();
        $userId = $usuario['id'];
        $pdo = getPgConnection();

        try {
            $stmt = $pdo->prepare("
                SELECT nombre_comercial, rfc, foto_drive_id, estatus_convenio, calificacion_ia, contacto
                FROM empresas WHERE usuario_id = :uid
            ");
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $empresa = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$empresa) {
                responderError('Perfil de empresa no encontrado', 404);
                return;
            }

            $contacto = $empresa['contacto'] ? json_decode($empresa['contacto'], true) : null;

            responderExito([
                'nombre_comercial' => $empresa['nombre_comercial'],
                'rfc' => $empresa['rfc'],
                'foto_url' => $empresa['foto_drive_id'],
                'estatus_convenio' => $empresa['estatus_convenio'],
                'calificacion_ia' => $empresa['calificacion_ia'] !== null ? (int) $empresa['calificacion_ia'] : null,
                'contacto' => $contacto,
            ], 'Perfil obtenido correctamente');

        } catch (Exception $e) {
            responderError('Error al obtener perfil: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /empresa/perfil
     */
    public static function updatePerfil() {
        if (!Middleware::authMiddleware()) return;
        if (!Middleware::requireRole('empresa')) return;

        $usuario = getUsuarioActual();
        $userId = $usuario['id'];
        $requestData = Flight::request()->data;
        $nombreComercial = $requestData->nombre_comercial ?? null;
        $contacto = $requestData->contacto ?? null;

        if (!$nombreComercial && $contacto === null) {
            responderError('Se requiere nombre_comercial o contacto', 400);
            return;
        }

        $pdo = getPgConnection();

        try {
            $pdo->beginTransaction();

            $updates = [];
            $params = [':uid' => $userId];

            if ($nombreComercial !== null) {
                $updates[] = 'nombre_comercial = :nombre';
                $params[':nombre'] = $nombreComercial;
            }

            if ($contacto !== null) {
                $updates[] = 'contacto = CAST(:contacto AS jsonb)';
                $params[':contacto'] = json_encode($contacto, JSON_UNESCAPED_UNICODE);
            }

            $sql = "UPDATE empresas SET " . implode(', ', $updates) . " WHERE usuario_id = :uid";
            $stmt = $pdo->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->execute();

            $pdo->commit();

            responderExito([], 'Perfil actualizado correctamente');

        } catch (Exception $e) {
            handleTransactionError($pdo, 'Error al actualizar perfil: ' . $e->getMessage(), 500);
        }
    }

    // ============================================
    // Dashboard y Vacantes
    // ============================================

    /**
     * GET /empresa/dashboard/stats
     */
    public static function getDashboardStats() {
        if (!Middleware::authMiddleware()) return;
        if (!Middleware::requireRole('empresa')) return;

        $usuario = getUsuarioActual();
        $userId = $usuario['id'];
        $pdo = getPgConnection();

        try {
            // Vacantes activas (todas las vacantes de la empresa)
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total FROM vacantes WHERE empresa_id = :uid
            ");
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $vacantesActivas = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Total postulantes
            $stmt = $pdo->prepare("
                SELECT COUNT(DISTINCT p.egresado_id) as total
                FROM postulaciones p
                JOIN vacantes v ON p.vacante_id = v.id
                WHERE v.empresa_id = :uid
            ");
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $totalPostulantes = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Entrevistas pendientes (postulaciones con estatus 'revisada')
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total
                FROM postulaciones p
                JOIN vacantes v ON p.vacante_id = v.id
                WHERE v.empresa_id = :uid AND p.estatus = 'revisada'
            ");
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $entrevistasPendientes = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            responderExito([
                'vacantes_activas' => $vacantesActivas,
                'total_postulantes' => $totalPostulantes,
                'entrevistas_pendientes' => $entrevistasPendientes,
            ], 'Estadísticas obtenidas correctamente');

        } catch (Exception $e) {
            responderError('Error al obtener estadísticas: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /empresa/mis-vacantes
     */
    public static function getMisVacantes() {
        if (!Middleware::authMiddleware()) return;
        if (!Middleware::requireRole('empresa')) return;

        $usuario = getUsuarioActual();
        $userId = $usuario['id'];
        $pdo = getPgConnection();

        try {
            $stmt = $pdo->prepare("
                SELECT 
                    v.id,
                    v.titulo,
                    v.descripcion,
                    v.ubicacion,
                    v.fecha_publicacion,
                    COUNT(p.id) as postulantes_count
                FROM vacantes v
                LEFT JOIN postulaciones p ON v.id = p.vacante_id
                WHERE v.empresa_id = :uid
                GROUP BY v.id
                ORDER BY v.fecha_publicacion DESC
            ");
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $vacantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $result = [];
            foreach ($vacantes as $v) {
                $result[] = [
                    'id' => (int) $v['id'],
                    'titulo' => $v['titulo'],
                    'descripcion' => $v['descripcion'],
                    'ubicacion' => $v['ubicacion'],
                    'postulantes_count' => (int) $v['postulantes_count'],
                    'fecha_pub' => $v['fecha_publicacion'],
                ];
            }

            responderExito($result, 'Vacantes obtenidas correctamente');

        } catch (Exception $e) {
            responderError('Error al obtener vacantes: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /empresa/vacantes
     */
    public static function crearVacante() {
        if (!Middleware::authMiddleware()) return;
        if (!Middleware::requireRole('empresa')) return;

        $usuario = getUsuarioActual();
        $userId = $usuario['id'];
        $requestData = Flight::request()->data;

        $titulo = $requestData->titulo ?? null;
        $descripcion = $requestData->descripcion ?? null;
        $ubicacion = $requestData->ubicacion ?? null;
        $perfilIdoneo = $requestData->perfil_idoneo ?? null;
        $divisionDestino = $requestData->division_destino ?? null;

        $errores = validarCampos([
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'ubicacion' => $ubicacion,
        ]);
        if ($errores !== null) {
            responderError($errores, 400);
            return;
        }

        if ($perfilIdoneo === null) {
            responderError('perfil_idoneo es requerido', 400);
            return;
        }

        $pdo = getPgConnection();

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO vacantes (empresa_id, titulo, descripcion, ubicacion, division_destino, perfil_idoneo, analisis_gemini, es_externa, url_externa, fecha_publicacion)
                VALUES (:empresa_id, :titulo, :descripcion, :ubicacion, :division, CAST(:perfil AS jsonb), NULL, false, NULL, NOW())
            ");
            $stmt->bindValue(':empresa_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':titulo', $titulo, PDO::PARAM_STR);
            $stmt->bindValue(':descripcion', $descripcion, PDO::PARAM_STR);
            $stmt->bindValue(':ubicacion', $ubicacion, PDO::PARAM_STR);
            $stmt->bindValue(':division', $divisionDestino !== null ? (int) $divisionDestino : null, $divisionDestino !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':perfil', json_encode($perfilIdoneo, JSON_UNESCAPED_UNICODE), PDO::PARAM_STR);
            $stmt->execute();

            $vacanteId = (int) $pdo->lastInsertId('vacantes_id_seq');

            $pdo->commit();

            responderExito([
                'id' => $vacanteId,
                'analisis_ia' => 'Pendiente de análisis por IA',
            ], 'Vacante creada correctamente');

        } catch (Exception $e) {
            handleTransactionError($pdo, 'Error al crear vacante: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /empresa/vacantes/:id/postulantes
     */
    public static function getPostulantes($id) {
        if (!Middleware::authMiddleware()) return;
        if (!Middleware::requireRole('empresa')) return;

        $usuario = getUsuarioActual();
        $userId = $usuario['id'];
        $pdo = getPgConnection();

        try {
            // Verificar que la vacante pertenece a esta empresa
            $stmt = $pdo->prepare("SELECT id FROM vacantes WHERE id = :id AND empresa_id = :empresa_id");
            $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);
            $stmt->bindValue(':empresa_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            if (!$stmt->fetch()) {
                responderError('Vacante no encontrada o no pertenece a tu empresa', 404);
                return;
            }

            // Obtener postulantes
            $stmt = $pdo->prepare("
                SELECT 
                    p.id as id_postulacion,
                    p.egresado_id,
                    e.nombre,
                    e.apellido_paterno,
                    e.apellido_materno,
                    p.match_porcentaje as match,
                    p.estatus,
                    p.fecha,
                    c.nombre as carrera
                FROM postulaciones p
                JOIN egresados e ON p.egresado_id = e.usuario_id
                LEFT JOIN carreras c ON e.carrera_id = c.id
                WHERE p.vacante_id = :vacante_id
                ORDER BY p.match_porcentaje DESC, p.fecha DESC
            ");
            $stmt->bindValue(':vacante_id', (int) $id, PDO::PARAM_INT);
            $stmt->execute();
            $postulantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $result = [];
            foreach ($postulantes as $p) {
                $result[] = [
                    'id_postulacion' => (int) $p['id_postulacion'],
                    'alumno_nombre' => trim("{$p['nombre']} {$p['apellido_paterno']} {$p['apellido_materno']}"),
                    'match' => (int) $p['match'],
                    'estatus' => $p['estatus'],
                    'carrera' => $p['carrera'],
                    'fecha' => $p['fecha'],
                    'egresado_id' => (int) $p['egresado_id'] ?? 0,
                ];
            }

            responderExito($result, 'Postulantes obtenidos correctamente');

        } catch (Exception $e) {
            responderError('Error al obtener postulantes: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PATCH /postulaciones/:id/estatus
     */
    public static function cambiarEstatusPostulacion($id) {
        if (!Middleware::authMiddleware()) return;
        if (!Middleware::requireRole('empresa')) return;

        $usuario = getUsuarioActual();
        $userId = $usuario['id'];
        $requestData = Flight::request()->data;
        $nuevoEstatus = $requestData->nuevo_estatus ?? null;

        $estatusValidos = ['pendiente', 'revisada', 'aceptada', 'rechazada'];
        if (!$nuevoEstatus || !in_array($nuevoEstatus, $estatusValidos, true)) {
            responderError('Estatus inválido. Use: ' . implode(', ', $estatusValidos), 400);
            return;
        }

        $pdo = getPgConnection();

        try {
            $pdo->beginTransaction();

            // Verificar que la postulación es de una vacante de esta empresa
            $stmt = $pdo->prepare("
                SELECT p.id FROM postulaciones p
                JOIN vacantes v ON p.vacante_id = v.id
                WHERE p.id = :id AND v.empresa_id = :empresa_id
            ");
            $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);
            $stmt->bindValue(':empresa_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            if (!$stmt->fetch()) {
                $pdo->rollBack();
                responderError('Postulación no encontrada o no pertenece a tu empresa', 404);
                return;
            }

            $stmt = $pdo->prepare("
                UPDATE postulaciones SET estatus = :estatus WHERE id = :id
            ");
            $stmt->bindValue(':estatus', $nuevoEstatus, PDO::PARAM_STR);
            $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);
            $stmt->execute();

            $pdo->commit();

            responderExito([], 'Estatus de postulación actualizado correctamente');

        } catch (Exception $e) {
            handleTransactionError($pdo, 'Error al actualizar estatus: ' . $e->getMessage(), 500);
        }
    }
}
