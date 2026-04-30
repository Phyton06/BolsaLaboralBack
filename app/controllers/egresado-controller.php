<?php declare(strict_types=1);

class EgresadoController {
    // GET /egresado/perfil
    public static function getProfile() {
        if (!Middleware::authMiddleware()) return;
        if (!Middleware::requireRole('egresado')) return;
        
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
                        e.foto_drive_id as foto_url,
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
                'foto_url' => $perfil['foto_url'] ?? null,
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
        if (!Middleware::authMiddleware()) return;
        if (!Middleware::requireRole('egresado')) return;
        
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
        if (!Middleware::authMiddleware()) return;
        if (!Middleware::requireRole('egresado')) return;
        
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
        if (!Middleware::authMiddleware()) return;
        if (!Middleware::requireRole('egresado')) return;
        
        $usuario = getUsuarioActual();
        $userId = $usuario['id'];
        
        $requestData = Flight::request()->data;
        $tecnicas = $requestData->tecnicas ?? [];
        $blandas = $requestData->blandas ?? [];
        $idiomas = $requestData->idiomas ?? [];
        $habilidades = [
            'tecnicas' => is_array($tecnicas) ? $tecnicas : [],
            'blandas' => is_array($blandas) ? $blandas : [],
            'idiomas' => is_array($idiomas) ? $idiomas : [],
        ];
        
        if (empty($habilidades['tecnicas']) && empty($habilidades['blandas']) && empty($habilidades['idiomas'])) {
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
    
    // PUT /egresado/perfil/contacto
    public static function updateContacto() {
        if (!Middleware::authMiddleware()) return;
        if (!Middleware::requireRole('egresado')) return;

        $usuario = getUsuarioActual();
        $userId = $usuario['id'];

        $requestData = Flight::request()->data;
        $telefono = $requestData->telefono ?? null;
        $correoPersonal = $requestData->correo_personal ?? null;
        $linkedin = $requestData->linkedin ?? null;

        // Validate formats
        if ($telefono !== null && $telefono !== '') {
            if (!preg_match('/^\+?[0-9\s\-]{7,15}$/', $telefono)) {
                responderError('Formato de teléfono no válido', 400);
                return;
            }
        }

        if ($correoPersonal !== null && $correoPersonal !== '') {
            if (!filter_var($correoPersonal, FILTER_VALIDATE_EMAIL)) {
                responderError('Formato de correo electrónico no válido', 400);
                return;
            }
        }

        if ($linkedin !== null && $linkedin !== '') {
            if (!preg_match('/^https:\/\/(www\.)?linkedin\.com\/.+$/', $linkedin)) {
                responderError('URL de LinkedIn no válida (debe comenzar con https://linkedin.com/)', 400);
                return;
            }
        }

        // Build contacto object (only include provided fields, preserve existing)
        $pdo = getPgConnection();

        try {
            // Get existing contacto first
            $stmt = $pdo->prepare("SELECT contacto FROM egresados WHERE usuario_id = :usuario_id");
            $stmt->execute([':usuario_id' => $userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $contactoExistente = $row && $row['contacto'] ? json_decode($row['contacto'], true) : [];

            // Merge: update only provided fields
            $nuevoContacto = [
                'telefono' => $telefono !== null ? $telefono : ($contactoExistente['telefono'] ?? null),
                'correo_personal' => $correoPersonal !== null ? $correoPersonal : ($contactoExistente['correo_personal'] ?? null),
                'linkedin' => $linkedin !== null ? $linkedin : ($contactoExistente['linkedin'] ?? null),
            ];

            $contactoJson = json_encode($nuevoContacto, JSON_UNESCAPED_UNICODE);
            if (json_last_error() !== JSON_ERROR_NONE) {
                responderError('Error al procesar datos de contacto', 400);
                return;
            }

            $pdo->beginTransaction();

            $sql = "UPDATE egresados SET contacto = CAST(:contacto AS jsonb) WHERE usuario_id = :usuario_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':contacto', $contactoJson, PDO::PARAM_STR);
            $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                $pdo->rollBack();
                responderError('Egresado no encontrado', 404);
                return;
            }

            $pdo->commit();

            responderExito($nuevoContacto, 'Contacto actualizado correctamente');

        } catch (Exception $e) {
            handleTransactionError($pdo, 'Error al actualizar contacto: ' . $e->getMessage(), 500);
        }
    }

    // POST /egresado/foto
    public static function uploadFoto() {
        if (!Middleware::authMiddleware()) return;
        if (!Middleware::requireRole('egresado')) return;

        // Check if file was uploaded
        if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            responderError('No se subió ningún archivo de foto válido', 400);
            return;
        }

        $file = $_FILES['foto'];

        // Validate MIME type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes, true)) {
            responderError('Formato de imagen no válido. Solo se permiten JPG, PNG y WebP', 400);
            return;
        }

        // Validate file size (max 5MB)
        $maxSize = 5 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            responderError('La imagen no debe superar los 5MB', 400);
            return;
        }

        // Get file extension
        $extension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        // Generate filename
        $usuario = getUsuarioActual();
        $userId = $usuario['id'];
        $timestamp = time();
        $filename = "usuario_{$userId}_{$timestamp}.{$extension}";

        // Ensure uploads directory exists
        $uploadDir = __DIR__ . '/../../uploads/fotos';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                responderError('Error al crear directorio de uploads', 500);
                return;
            }
        }

        // Move uploaded file
        $destination = $uploadDir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            responderError('Error al guardar la imagen', 500);
            return;
        }

        // Build public URL
        $baseUrl = rtrim(Flight::get('base_url') ?? 'http://localhost:8080', '/');
        $fotoUrl = "{$baseUrl}/uploads/fotos/{$filename}";

        // Update database
        $pdo = getPgConnection();

        try {
            $pdo->beginTransaction();

            $sql = "UPDATE egresados SET foto_drive_id = :foto_url WHERE usuario_id = :usuario_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':foto_url', $fotoUrl, PDO::PARAM_STR);
            $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                $pdo->rollBack();
                // Remove uploaded file if user not found
                @unlink($destination);
                responderError('Egresado no encontrado', 404);
                return;
            }

            $pdo->commit();

            responderExito(['foto_url' => $fotoUrl], 'Foto actualizada correctamente');

        } catch (Exception $e) {
            $pdo->rollBack();
            // Remove uploaded file on error
            @unlink($destination);
            responderError('Error al guardar foto: ' . $e->getMessage(), 500);
        }
    }
    
    // GET /egresado/stats
    public static function getStats() {
        if (!Middleware::authMiddleware()) return;
        if (!Middleware::requireRole('egresado')) return;
        
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
