<?php declare(strict_types=1);

class EgresadoController {
    // GET /egresado/perfil
    public static function getProfile() {
        Middleware::authMiddleware();
        Middleware::requireRole('egresado');
        
        $usuario = getUsuarioActual();
        $userId = $usuario['id'];
        
        $pdo = getPgConnection();
        
        try {
            $sql = "SELECT 
                        e.usuario_id as id,
                        e.nombre,
                        e.apellido_paterno,
                        e.apellido_materno,
                        c.nombre as carrera,
                        d.nombre as division,
                        e.periodo_egreso,
                        e.biografia_ia,
                        e.contacto,
                        e.trayectoria,
                        e.habilidades,
                        e.vistas_perfil
                    FROM egresados e
                    LEFT JOIN carreras c ON e.carrera_id = c.id
                    LEFT JOIN divisiones d ON c.division_id = d.id
                    WHERE e.usuario_id = :usuario_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':usuario_id' => $userId]);
            $perfil = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$perfil) {
                responderError('Perfil de egresado no encontrado', 404);
                return;
            }
            
            // Process JSONB fields
            $contacto = $perfil['contacto'] ? json_decode($perfil['contacto'], true) : null;
            $trayectoria = $perfil['trayectoria'] ? json_decode($perfil['trayectoria'], true) : null;
            $habilidades = $perfil['habilidades'] ? json_decode($perfil['habilidades'], true) : null;
            
            $responseData = [
                'id' => $perfil['id'],
                'nombre' => $perfil['nombre'],
                'apellido_paterno' => $perfil['apellido_paterno'],
                'apellido_materno' => $perfil['apellido_materno'],
                'carrera' => $perfil['carrera'] ?? null,
                'division' => $perfil['division'] ?? null,
                'periodo_egreso' => $perfil['periodo_egreso'],
                'foto_url' => null, // Placeholder, no Google Drive integration yet
                'contacto' => $contacto,
                'biografia_ia' => $perfil['biografia_ia'],
                'trayectoria' => $trayectoria,
                'habilidades' => $habilidades,
                'vistas_perfil' => (int) $perfil['vistas_perfil']
            ];
            
            responderExito($responseData, 'Perfil obtenido correctamente');
            
        } catch (Exception $e) {
            responderError('Error al obtener perfil: ' . $e->getMessage(), 500);
        }
    }
    
    // PUT /egresado/perfil/biografia
    public static function updateBiography() {
        Middleware::authMiddleware();
        Middleware::requireRole('egresado');
        
        $usuario = getUsuarioActual();
        $userId = $usuario['id'];
        
        $requestData = Flight::request()->data;
        $biografia = $requestData->biografia ?? null;
        
        // Validate required field
        $error = validarCampos(['biografia' => $biografia]);
        if ($error !== null) {
            responderError($error, 400);
            return;
        }
        
        $pdo = getPgConnection();
        
        try {
            $pdo->beginTransaction();
            
            $sql = "UPDATE egresados SET biografia_ia = :biografia WHERE usuario_id = :usuario_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':biografia', $biografia, PDO::PARAM_STR);
            $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                $pdo->rollBack();
                responderError('Egresado no encontrado', 404);
                return;
            }
            
            $pdo->commit();
            
            responderExito(['biografia_ia' => $biografia], 'Biografía actualizada correctamente');
            
        } catch (Exception $e) {
            handleTransactionError($pdo, 'Error al actualizar biografía: ' . $e->getMessage(), 500);
        }
    }
    
    // PUT /egresado/perfil/trayectoria
    public static function updateTrayectoria() {
        Middleware::authMiddleware();
        Middleware::requireRole('egresado');
        
        $usuario = getUsuarioActual();
        $userId = $usuario['id'];
        
        $requestData = Flight::request()->data;
        $trayectoria = $requestData->trayectoria ?? null;
        
        // Validate required field
        $error = validarCampos(['trayectoria' => $trayectoria]);
        if ($error !== null) {
            responderError($error, 400);
            return;
        }
        
        // Validate trayectoria is an array
        if (!is_array($trayectoria)) {
            responderError('El campo trayectoria debe ser un arreglo', 400);
            return;
        }
        
        // Encode to JSON for jsonb storage
        $trayectoriaJson = json_encode($trayectoria, JSON_UNESCAPED_UNICODE);
        if (json_last_error() !== JSON_ERROR_NONE) {
            responderError('Formato de trayectoria inválido', 400);
            return;
        }
        
        $pdo = getPgConnection();
        
        try {
            $pdo->beginTransaction();
            
            $sql = "UPDATE egresados SET trayectoria = CAST(:trayectoria AS jsonb) WHERE usuario_id = :usuario_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':trayectoria', $trayectoriaJson, PDO::PARAM_STR);
            $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                $pdo->rollBack();
                responderError('Egresado no encontrado', 404);
                return;
            }
            
            $pdo->commit();
            
            responderExito([], 'Trayectoria actualizada correctamente');
            
        } catch (Exception $e) {
            handleTransactionError($pdo, 'Error al actualizar trayectoria: ' . $e->getMessage(), 500);
        }
    }
    
    // PUT /egresado/perfil/habilidades
    public static function updateHabilidades() {
        Middleware::authMiddleware();
        Middleware::requireRole('egresado');
        
        $usuario = getUsuarioActual();
        $userId = $usuario['id'];
        
        $requestData = Flight::request()->data;
        $habilidades = (array) $requestData;
        
        if (empty($habilidades)) {
            responderError('No se proporcionaron habilidades', 400);
            return;
        }
        
        // Encode to JSON for jsonb storage
        $habilidadesJson = json_encode($habilidades, JSON_UNESCAPED_UNICODE);
        if (json_last_error() !== JSON_ERROR_NONE) {
            responderError('Formato de habilidades inválido', 400);
            return;
        }
        
        $pdo = getPgConnection();
        
        try {
            $pdo->beginTransaction();
            
            $sql = "UPDATE egresados SET habilidades = CAST(:habilidades AS jsonb) WHERE usuario_id = :usuario_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':habilidades', $habilidadesJson, PDO::PARAM_STR);
            $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                $pdo->rollBack();
                responderError('Egresado no encontrado', 404);
                return;
            }
            
            $pdo->commit();
            
            responderExito([], 'Habilidades actualizadas correctamente');
            
        } catch (Exception $e) {
            handleTransactionError($pdo, 'Error al actualizar habilidades: ' . $e->getMessage(), 500);
        }
    }
    
    // POST /egresado/foto
    public static function uploadFoto() {
        Middleware::authMiddleware();
        Middleware::requireRole('egresado');
        
        // Check if file was uploaded
        if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            responderError('No se subió ningún archivo de foto válido', 400);
            return;
        }
        
        // Mock response since Google Drive integration is not implemented
        responderExito([
            'foto_url' => 'placeholder',
            'drive_id' => 'pending_integration'
        ], 'Foto subida correctamente');
    }
    
    // GET /egresado/stats
    public static function getStats() {
        Middleware::authMiddleware();
        Middleware::requireRole('egresado');
        
        $usuario = getUsuarioActual();
        $userId = $usuario['id'];
        
        $pdo = getPgConnection();
        
        try {
            // Postulaciones activas (pendiente, revisada)
            $stmtPostulaciones = $pdo->prepare("SELECT COUNT(*) FROM postulaciones WHERE egresado_id = :egresado_id AND estatus IN ('pendiente', 'revisada')");
            $stmtPostulaciones->execute([':egresado_id' => $userId]);
            $postulacionesActivas = (int) $stmtPostulaciones->fetchColumn();
            
            // Match promedio
            $stmtMatch = $pdo->prepare("SELECT AVG(match_porcentaje) FROM postulaciones WHERE egresado_id = :egresado_id");
            $stmtMatch->execute([':egresado_id' => $userId]);
            $matchPromedio = $stmtMatch->fetchColumn();
            $matchPromedio = $matchPromedio ? round((float) $matchPromedio, 2) : 0;
            
            // Pruebas completadas (puntaje_global no nulo)
            $stmtPruebas = $pdo->prepare("SELECT COUNT(*) FROM evaluaciones WHERE egresado_id = :egresado_id AND puntaje_global IS NOT NULL");
            $stmtPruebas->execute([':egresado_id' => $userId]);
            $pruebasCompletadas = (int) $stmtPruebas->fetchColumn();
            
            // Vistas perfil
            $stmtVistas = $pdo->prepare("SELECT vistas_perfil FROM egresados WHERE usuario_id = :usuario_id");
            $stmtVistas->execute([':usuario_id' => $userId]);
            $vistasPerfil = (int) ($stmtVistas->fetchColumn() ?? 0);
            
            $stats = [
                'postulaciones_activas' => $postulacionesActivas,
                'match_promedio' => $matchPromedio,
                'pruebas_completadas' => $pruebasCompletadas,
                'vistas_perfil' => $vistasPerfil
            ];
            
            responderExito($stats, 'Estadísticas obtenidas correctamente');
            
        } catch (Exception $e) {
            responderError('Error al obtener estadísticas: ' . $e->getMessage(), 500);
        }
    }
}
