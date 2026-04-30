<?php declare(strict_types=1);

/**
 * Controlador de Vacantes y Postulaciones.
 *
 * Endpoints:
 *   GET    /vacantes                          — Listar vacantes con filtros y paginación
 *   GET    /vacantes/:id                      — Detalle de una vacante
 *   GET    /egresado/postulaciones             — Mis aplicaciones
 *   POST   /vacantes/:id/postular              — Aplicar a una vacante
 *   DELETE /vacantes/:id/cancelar-postulacion  — Cancelar aplicación
 *   GET    /vacantes/:id/match-detalle         — Match score con radar para egresado
 */
class VacantesController {

    // ============================================
    // Helpers internos
    // ============================================

    /**
     * Calcula el porcentaje de match entre habilidades del egresado y las requeridas.
     */
    private static function calcularMatch(array $habilidadesEgresado, array $perfilIdoneo): int {
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
    private static function compararNivelesIngles(string $nivelEgresado, string $nivelRequerido): int {
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
    private static function matchExperiencia(string $expEgresado, string $expMinRequerida): int {
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
    private static function calcularSoftSkills(array $habilidadesEgresado, array $perfilIdoneo): int {
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

    // ============================================
    // Endpoints públicos
    // ============================================

    /**
     * GET /vacantes/:id
     * Detalle completo de una vacante (público).
     */
    public static function getDetalle($id) {

        if (!$id || !is_numeric($id)) {
            responderError('ID de vacante inválido', 400);
            return;
        }

        $pdo = getPgConnection();

        try {
            $stmt = $pdo->prepare("
                SELECT 
                    v.id,
                    v.empresa_id,
                    v.titulo,
                    v.descripcion,
                    v.ubicacion,
                    v.division_destino,
                    v.perfil_idoneo,
                    v.analisis_gemini,
                    v.es_externa,
                    v.url_externa,
                    v.fecha_publicacion,
                    e.nombre_comercial as empresa_nombre,
                    d.nombre as division_nombre
                FROM vacantes v
                JOIN empresas e ON v.empresa_id = e.usuario_id
                LEFT JOIN divisiones d ON v.division_destino = d.id
                WHERE v.id = :id
            ");
            $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);
            $stmt->execute();
            $vacante = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$vacante) {
                responderError('Vacante no encontrada', 404);
                return;
            }

            // Decodificar JSONB
            $perfilIdoneo = $vacante['perfil_idoneo'] 
                ? json_decode($vacante['perfil_idoneo'], true) 
                : null;

            responderExito([
                'id' => (int) $vacante['id'],
                'empresa_id' => (int) $vacante['empresa_id'],
                'empresa_nombre' => $vacante['empresa_nombre'],
                'titulo' => $vacante['titulo'],
                'descripcion' => $vacante['descripcion'],
                'ubicacion' => $vacante['ubicacion'],
                'division_destino' => $vacante['division_nombre'],
                'perfil_idoneo' => $perfilIdoneo,
                'analisis_ia' => $vacante['analisis_gemini'],
                'es_externa' => filter_var($vacante['es_externa'], FILTER_VALIDATE_BOOLEAN),
                'url_externa' => $vacante['url_externa'],
                'fecha_publicacion' => $vacante['fecha_publicacion'],
            ], 'Vacante obtenida correctamente');

        } catch (Exception $e) {
            responderError('Error al obtener vacante: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /vacantes
     * Listar vacantes con filtros y paginación (público).
     */
    public static function listar() {
        $pdo = getPgConnection();
        $request = Flight::request();

        // Parámetros de paginación
        $page = max(1, (int) ($request->query->page ?? 1));
        $limit = min(50, max(1, (int) ($request->query->limit ?? 10)));
        $offset = ($page - 1) * $limit;

        // Filtros opcionales
        $search = trim($request->query->search ?? '');
        $ubicacion = trim($request->query->ubicacion ?? '');
        $divisionId = $request->query->division_id ?? null;
        $soloConvenio = filter_var($request->query->solo_convenio ?? false, FILTER_VALIDATE_BOOLEAN);

        // Construir WHERE dinámico
        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = '(LOWER(v.titulo) LIKE LOWER(:search) OR LOWER(v.descripcion) LIKE LOWER(:search_desc))';
            $params[':search'] = '%' . $search . '%';
            $params[':search_desc'] = '%' . $search . '%';
        }

        if ($ubicacion !== '') {
            $where[] = 'LOWER(v.ubicacion) = LOWER(:ubicacion)';
            $params[':ubicacion'] = $ubicacion;
        }

        if ($divisionId !== null && is_numeric($divisionId)) {
            $where[] = 'v.division_destino = :division_id';
            $params[':division_id'] = (int) $divisionId;
        }

        if ($soloConvenio) {
            $where[] = "e.estatus_convenio = 'activo'";
        }

        $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

        try {
            // Count total
            $countSql = "SELECT COUNT(*) as total FROM vacantes v JOIN empresas e ON v.empresa_id = e.usuario_id {$whereClause}";
            $countStmt = $pdo->prepare($countSql);
            foreach ($params as $key => $val) {
                $countStmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $countStmt->execute();
            $total = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Verificar si hay usuario autenticado (opcional)
            $usuarioActual = getUsuarioActual();
            $calcularMatch = $usuarioActual !== null && $usuarioActual['rol'] === 'egresado';

            // Fetch vacantes
            $sql = "
                SELECT 
                    v.id,
                    v.titulo,
                    e.nombre_comercial as empresa,
                    v.ubicacion,
                    v.modalidad,
                    e.estatus_convenio,
                    v.es_externa,
                    v.perfil_idoneo,
                    v.fecha_publicacion
                FROM vacantes v
                JOIN empresas e ON v.empresa_id = e.usuario_id
                {$whereClause}
                ORDER BY v.fecha_publicacion DESC
                LIMIT :limit OFFSET :offset
            ";
            $stmt = $pdo->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Si hay usuario egresado, calcular match para cada vacante
            $habilidadesEgresado = null;
            if ($calcularMatch) {
                $stmtE = $pdo->prepare("SELECT habilidades FROM egresados WHERE usuario_id = :uid");
                $stmtE->bindValue(':uid', $usuarioActual['id'], PDO::PARAM_INT);
                $stmtE->execute();
                $egresado = $stmtE->fetch(PDO::FETCH_ASSOC);
                if ($egresado && $egresado['habilidades']) {
                    $habilidadesEgresado = json_decode($egresado['habilidades'], true);
                }
            }

            $vacantes = [];
            foreach ($rows as $row) {
                $match = null;
                if ($habilidadesEgresado !== null) {
                    $perfilIdoneo = $row['perfil_idoneo'] 
                        ? json_decode($row['perfil_idoneo'], true) 
                        : null;
                    if ($perfilIdoneo) {
                        $match = self::calcularMatch($habilidadesEgresado, $perfilIdoneo);
                    }
                }

                $vacantes[] = [
                    'id' => (int) $row['id'],
                    'titulo' => $row['titulo'],
                    'empresa' => $row['empresa'],
                    'ubicacion' => $row['ubicacion'],
                    'modalidad' => $row['modalidad'] ?? 'Presencial',
                    'estatus_convenio' => $row['estatus_convenio'],
                    'es_externa' => filter_var($row['es_externa'], FILTER_VALIDATE_BOOLEAN),
                    'match' => $match,
                    'fecha_publicacion' => $row['fecha_publicacion'],
                ];
            }

            $pages = (int) ceil($total / $limit);

            responderExito([
                'vacantes' => $vacantes,
                'meta' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => $pages,
                ],
            ], 'Vacantes obtenidas correctamente');

        } catch (Exception $e) {
            responderError('Error al listar vacantes: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /vacantes/externas
     * Busca vacantes externas via API de Jooble.
     * 
     * Parámetros (query string):
     *   q - keywords de búsqueda (default: "desarrollador")
     *   location - ubicación (default: "México")
     *   page - página (default: 1)
     *   limit - resultados por página (default: 20)
     */
    public static function buscarExternas() {
        $request = Flight::request();
        
        // Cargar config de API keys
        $config = require __DIR__ . '/../../config/api-keys.php';
        $joobleConfig = $config['jooble'] ?? null;
        
        if (!$joobleConfig || empty($joobleConfig['api_key'])) {
            responderError('Configuración de API externa no disponible', 500);
            return;
        }

        // Parámetros
        $keywords = trim($request->query->q ?? 'software developer');
        $location = trim($request->query->location ?? 'Mexico');
        $page = max(1, (int) ($request->query->page ?? 1));
        $limit = min(50, max(1, (int) ($request->query->limit ?? 20)));

        $apiKey = $joobleConfig['api_key'];
        $baseUrl = $joobleConfig['base_url'];

        // Request body para Jooble
        $body = [
            'keywords' => $keywords,
            'location' => $location,
            'page' => $page,
        ];

        // Llamar a Jooble API
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "{$baseUrl}/{$apiKey}",
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError || $httpCode !== 200) {
            responderError('Error al conectar con API externa: ' . ($curlError ?: "HTTP {$httpCode}"), 502);
            return;
        }

        $data = json_decode($response, true);

        if (!$data || !isset($data['jobs'])) {
            responderError('Formato de respuesta inválido', 502);
            return;
        }

        $allJobs = $data['jobs'];

        // Limitar resultados
        $allJobs = array_slice($allJobs, 0, 20);

        // Transformar al formato de vacantes
        $vacantes = [];
        $now = date('Y-m-d H:i:s');

        foreach ($allJobs as $job) {
            // Calcular match simulado basado en keywords
            $match = rand(60, 95);

            // Extraer salary
            $salario = $job['salary'] ?? 'A consultar';

            // Tags del snippet
            $tags = [];
            if (!empty($job['snippet'])) {
                // Extraer palabras clave simples
                $words = preg_split('/[\s,]+/', $job['snippet']);
                foreach ($words as $w) {
                    $w = trim($w, '.,;');
                    if (strlen($w) > 3 && strlen($w) < 20) {
                        $tags[] = $w;
                        if (count($tags) >= 5) break;
                    }
                }
            }

            // Detectar modalidad basada en location y título
            $jobLocation = strtolower($job['location'] ?? '');
            $jobTitle = strtolower($job['title'] ?? '');
            $modalidad = 'Presencial';
            if (stripos($jobLocation, 'remoto') !== false || stripos($jobLocation, 'remote') !== false ||
                stripos($jobTitle, '(remote)') !== false || stripos($jobTitle, '(remoto)') !== false) {
                $modalidad = 'Remoto';
            }

            $vacantes[] = [
                'id' => -($job['id'] ?? rand(1000, 9999)), // IDs negativos para externa
                'titulo' => $job['title'] ?? 'Sin título',
                'empresa' => $job['company'] ?? 'Empresa no especificada',
                'ubicacion' => $job['location'] ?? $location,
                'modalidad' => $modalidad,
                'salario' => $salario,
                'tags' => array_slice($tags, 0, 5),
                'fuente' => $job['source'] ?? 'Jooble',
                'url' => $job['link'] ?? null,
                'match' => $match,
                'es_externa' => true,
                'fecha_publicacion' => $job['updated'] ?? $now,
            ];
        }

        // Ordenar por match
        usort($vacantes, function($a, $b) {
            return $b['match'] - $a['match'];
        });

        responderExito([
            'vacantes' => $vacantes,
            'meta' => [
                'total' => count($vacantes),
                'page' => $page,
                'limit' => $limit,
                'pages' => (int) ceil(count($vacantes) / $limit),
            ],
        ], 'Vacantes externas obtenidas correctamente');
    }

    /**
     * GET /vacantes/filtros
     */
    public static function getFiltros() {
        // Los 32 estados de México + opciones especiales
        $estados = [
            'Nayarit',
            'Aguascalientes',
            'Baja California',
            'Baja California Sur',
            'Campeche',
            'Chiapas',
            'Chihuahua',
            'Ciudad de México',
            'Coahuila',
            'Colima',
            'Durango',
            'Guanajuato',
            'Guerrero',
            'Hidalgo',
            'Jalisco',
            'México',
            'Michoacán',
            'Morelos',
            'Nuevo León',
            'Oaxaca',
            'Puebla',
            'Querétaro',
            'Quintana Roo',
            'San Luis Potosí',
            'Sinaloa',
            'Sonora',
            'Tabasco',
            'Tamaulipas',
            'Tlaxcala',
            'Veracruz',
            'Yucatán',
            'Zacatecas',
            'Remoto',
            'Sin preferencia'
        ];
        sort($estados);

        // Modalidades
        $modalidades = ['Presencial', 'Remoto', 'Sin preferencia'];

        responderExito([
            'ubicaciones' => array_values($estados),
            'modalidades' => array_values($modalidades),
        ], 'Filtros obtenidos correctamente');

    }

    // ============================================
    // Endpoints protegidos (egresado)
    // ============================================

    /**
     * GET /egresado/postulaciones
     * Lista las aplicaciones del egresado autenticado.
     */
    public static function getMisPostulaciones() {
        if (!Middleware::authMiddleware()) return;
        if (!Middleware::requireRole('egresado')) return;

        $usuario = getUsuarioActual();
        $userId = $usuario['id'];

        $pdo = getPgConnection();

        try {
            $stmt = $pdo->prepare("
                SELECT 
                    p.id as id_postulacion,
                    p.vacante_id,
                    v.titulo as vacante_titulo,
                    e.nombre_comercial as empresa,
                    p.estatus,
                    p.match_porcentaje as match,
                    p.fecha
                FROM postulaciones p
                JOIN vacantes v ON p.vacante_id = v.id
                JOIN empresas e ON v.empresa_id = e.usuario_id
                WHERE p.egresado_id = :usuario_id
                ORDER BY p.fecha DESC
            ");
            $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $postulaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Formatear respuesta
            $result = [];
            foreach ($postulaciones as $p) {
                $result[] = [
                    'id_postulacion' => (int) $p['id_postulacion'],
                    'vacante_id' => (int) $p['vacante_id'],
                    'vacante_titulo' => $p['vacante_titulo'],
                    'empresa' => $p['empresa'],
                    'estatus' => $p['estatus'],
                    'match' => (int) $p['match'],
                    'fecha' => $p['fecha'],
                ];
            }

            responderExito($result, 'Postulaciones obtenidas correctamente');

        } catch (Exception $e) {
            responderError('Error al obtener postulaciones: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /vacantes/:id/postular
     * Aplicar a una vacante. Calcula match y crea postulación.
     */
    public static function postular($vacanteId) {
        if (!Middleware::authMiddleware()) return;
        if (!Middleware::requireRole('egresado')) return;

        $usuario = getUsuarioActual();
        $userId = $usuario['id'];

        if (!$vacanteId || !is_numeric($vacanteId)) {
            responderError('ID de vacante inválido', 400);
            return;
        }

        $pdo = getPgConnection();

        try {
            $pdo->beginTransaction();

            // 1. Verificar que la vacante existe
            $stmt = $pdo->prepare("
                SELECT id, perfil_idoneo FROM vacantes WHERE id = :id
            ");
            $stmt->bindValue(':id', (int) $vacanteId, PDO::PARAM_INT);
            $stmt->execute();
            $vacante = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$vacante) {
                $pdo->rollBack();
                responderError('Vacante no encontrada', 404);
                return;
            }

            // 2. Verificar que no existe postulación previa
            $stmt = $pdo->prepare("
                SELECT id FROM postulaciones 
                WHERE egresado_id = :egresado_id AND vacante_id = :vacante_id
            ");
            $stmt->bindValue(':egresado_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':vacante_id', (int) $vacanteId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->fetch()) {
                $pdo->rollBack();
                responderError('Ya tienes una postulación para esta vacante', 400);
                return;
            }

            // 3. Obtener habilidades del egresado
            $stmt = $pdo->prepare("SELECT habilidades FROM egresados WHERE usuario_id = :uid");
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $egresado = $stmt->fetch(PDO::FETCH_ASSOC);

            $match = 0;
            if ($egresado && $egresado['habilidades']) {
                $habilidadesEgresado = json_decode($egresado['habilidades'], true);
                $perfilIdoneo = json_decode($vacante['perfil_idoneo'], true);

                if ($habilidadesEgresado && $perfilIdoneo) {
                    $match = self::calcularMatch($habilidadesEgresado, $perfilIdoneo);
                }
            }

            // 4. Crear postulación
            $stmt = $pdo->prepare("
                INSERT INTO postulaciones (egresado_id, vacante_id, match_porcentaje, estatus, fecha)
                VALUES (:egresado_id, :vacante_id, :match, 'pendiente', NOW())
            ");
            $stmt->bindValue(':egresado_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':vacante_id', (int) $vacanteId, PDO::PARAM_INT);
            $stmt->bindValue(':match', $match, PDO::PARAM_INT);
            $stmt->execute();

            $idPostulacion = (int) $pdo->lastInsertId('postulaciones_id_seq');

            $pdo->commit();

            responderExito([
                'id_postulacion' => $idPostulacion,
                'estatus' => 'pendiente',
                'match' => $match,
            ], 'Postulación creada correctamente');

        } catch (Exception $e) {
            handleTransactionError($pdo, 'Error al postular: ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /vacantes/:id/cancelar-postulacion
     * Cancelar (eliminar) una postulación pendiente.
     */
    public static function cancelarPostulacion($vacanteId) {
        if (!Middleware::authMiddleware()) return;
        if (!Middleware::requireRole('egresado')) return;

        $usuario = getUsuarioActual();
        $userId = $usuario['id'];

        if (!$vacanteId || !is_numeric($vacanteId)) {
            responderError('ID de vacante inválido', 400);
            return;
        }

        $pdo = getPgConnection();

        try {
            $pdo->beginTransaction();

            // Verificar que existe y está pendiente
            $stmt = $pdo->prepare("
                SELECT id, estatus FROM postulaciones 
                WHERE egresado_id = :egresado_id AND vacante_id = :vacante_id
            ");
            $stmt->bindValue(':egresado_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':vacante_id', (int) $vacanteId, PDO::PARAM_INT);
            $stmt->execute();
            $postulacion = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$postulacion) {
                $pdo->rollBack();
                responderError('No se encontró postulación para esta vacante', 404);
                return;
            }

            if ($postulacion['estatus'] !== 'pendiente') {
                $pdo->rollBack();
                responderError('Solo se pueden cancelar postulaciones en estado pendiente', 400);
                return;
            }

            // Eliminar postulación
            $stmt = $pdo->prepare("
                DELETE FROM postulaciones 
                WHERE egresado_id = :egresado_id AND vacante_id = :vacante_id
            ");
            $stmt->bindValue(':egresado_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':vacante_id', (int) $vacanteId, PDO::PARAM_INT);
            $stmt->execute();

            $pdo->commit();

            responderExito([], 'Postulación cancelada correctamente');

        } catch (Exception $e) {
            handleTransactionError($pdo, 'Error al cancelar postulación: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /vacantes/:id/match-detalle
     * Detalle del match con radar de 5 dimensiones.
     */
    public static function getMatchDetalle($vacanteId) {
        if (!Middleware::authMiddleware()) return;
        if (!Middleware::requireRole('egresado')) return;

        $usuario = getUsuarioActual();
        $userId = $usuario['id'];

        if (!$vacanteId || !is_numeric($vacanteId)) {
            responderError('ID de vacante inválido', 400);
            return;
        }

        $pdo = getPgConnection();

        try {
            // 1. Obtener datos del egresado
            $stmt = $pdo->prepare("
                SELECT e.habilidades, e.periodo_egreso, c.nombre as carrera
                FROM egresados e
                LEFT JOIN carreras c ON e.carrera_id = c.id
                WHERE e.usuario_id = :uid
            ");
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $egresado = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$egresado) {
                responderError('Perfil de egresado no encontrado', 404);
                return;
            }

            // 2. Obtener datos de la vacante
            $stmt = $pdo->prepare("
                SELECT id, perfil_idoneo FROM vacantes WHERE id = :id
            ");
            $stmt->bindValue(':id', (int) $vacanteId, PDO::PARAM_INT);
            $stmt->execute();
            $vacante = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$vacante) {
                responderError('Vacante no encontrada', 404);
                return;
            }

            $habilidadesEgresado = $egresado['habilidades'] 
                ? json_decode($egresado['habilidades'], true) 
                : ['tecnicas' => [], 'blandas' => [], 'idiomas' => []];
            $perfilIdoneo = json_decode($vacante['perfil_idoneo'], true);

            if (!$perfilIdoneo) {
                responderError('Perfil idóneo no disponible para esta vacante', 400);
                return;
            }

            // 3. Si ya existe postulación, usar match guardado
            $matchGuardado = null;
            $stmt = $pdo->prepare("
                SELECT match_porcentaje FROM postulaciones 
                WHERE egresado_id = :egresado_id AND vacante_id = :vacante_id
            ");
            $stmt->bindValue(':egresado_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':vacante_id', (int) $vacanteId, PDO::PARAM_INT);
            $stmt->execute();
            $postulacion = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($postulacion) {
                $matchGuardado = (int) $postulacion['match_porcentaje'];
            }

            // 4. Calcular radar de 5 dimensiones
            $matchTecnico = self::calcularMatch($habilidadesEgresado, $perfilIdoneo);

            $inglesEgresado = $habilidadesEgresado['ingles'] ?? 'A1';
            $inglesRequerido = $perfilIdoneo['nivel_ingles'] ?? 'B1';
            $matchIngles = self::compararNivelesIngles($inglesEgresado, $inglesRequerido);

            $expEgresado = $egresado['periodo_egreso'] ?? '';
            $expRequerida = $perfilIdoneo['experiencia_min'] ?? '0-1 años';
            $matchExperiencia = self::matchExperiencia($expEgresado, $expRequerida);

            $carreraEgresado = $egresado['carrera'] ?? '';
            $carreraRequerida = $perfilIdoneo['carrera'] ?? '';
            $matchCarrera = (stripos($carreraEgresado, $carreraRequerida) !== false) ? 100 : 0;

            $matchSoftSkills = self::calcularSoftSkills($habilidadesEgresado, $perfilIdoneo);

            // 5. Calcular match general (promedio o usar guardado)
            $matchCalculado = (int) round(($matchTecnico + $matchIngles + $matchExperiencia + $matchCarrera + $matchSoftSkills) / 5);
            $match = $matchGuardado !== null ? $matchGuardado : $matchCalculado;

            // 6. Generar feedback
            $feedback = self::generarFeedback($match, $matchTecnico, $matchIngles, $matchCarrera);

            responderExito([
                'match' => $match,
                'comparativa_radar' => [
                    'labels' => ['Habilidades técnicas', 'Nivel de inglés', 'Experiencia', 'Carrera', 'Soft skills'],
                    'alumno' => [$matchTecnico, $matchIngles, $matchExperiencia, $matchCarrera, $matchSoftSkills],
                    'idoneo' => [100, 100, 100, 100, 100],
                ],
                'feedback_ia' => $feedback,
            ], 'Match calculado correctamente');

        } catch (Exception $e) {
            responderError('Error al calcular match: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Genera feedback textual basado en los scores.
     */
    private static function generarFeedback(int $match, int $matchTecnico, int $matchIngles, int $matchCarrera): string {
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
