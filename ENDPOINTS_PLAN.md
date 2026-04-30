# Plan de Endpoints - BolsaLaboralBack

**Basado en:** `api_master_contract.md`  
**Seed Data:** `db/seed.sql` ✅ Completado  
**Servidor:** `php -S localhost:8080 -t public`  
**Bruno:** v3.3.0 (OpenAPI 3.0)

---

## Estado del Seed Data ✅

| Tabla | Registros | Detalle |
|-------|-----------|---------|
| divisiones | 4 | Sistemas, Económico-Administrativas, Industrial, Básicas |
| carreras | 2 | ITI, Mercadotecnia |
| config_pruebas | 4 | tecnica(45min), psico(30min), cogni(35min), proy(30min) |
| usuarios | 15 | 10 egresados, 3 empresas, 2 admin |
| egresados | 10 | 5 ITI, 5 Mercadotecnia |
| empresas | 3 | TechCorp (activo), Constructora (pendiente), Innovatech (activo) |
| vacantes | 15 | 8 ITI, 7 Mercadotecnia |
| postulaciones | 25 | 7 pendientes, 7 aceptadas, 6 revisadas, 5 rechazadas |
| evaluaciones | 12 | Pruebas completadas por egresados |
| banco_preguntas | 80 | 25 técnicas ITI, 25 técnicas Mercadotecnia, 8 psico, 12 cogni, 10 proy |
| respuestas_detalle | 77 | Respuestas de ejemplo |

### Credenciales de Prueba

| Rol | Matrícula | Password | Primer Ingreso |
|-----|-----------|----------|----------------|
| Egresado ITI | 20240001 | test1234 | ❌ (ya completó) |
| Egresado ITI | 20240002 | test1234 | ✅ |
| Egresado ITI | 20240003 | test1234 | ✅ |
| Egresado ITI | 20240004 | test1234 | ✅ |
| Egresado ITI | 20240005 | test1234 | ✅ |
| Egresado Merc. | 20240006 | test1234 | ✅ |
| Egresado Merc. | 20240007 | test1234 | ✅ |
| Egresado Merc. | 20240008 | test1234 | ✅ |
| Egresado Merc. | 20240009 | test1234 | ✅ |
| Egresado Merc. | 20240010 | test1234 | ✅ |
| Empresa | EMP001 | test1234 | ❌ (TechCorp) |
| Empresa | EMP002 | test1234 | ✅ (Constructora) |
| Empresa | EMP003 | test1234 | ✅ (Innovatech) |
| Admin | ADMIN01 | admin123 | ❌ |
| Admin | ADMIN02 | admin123 | ✅ |

### Egresados con Evaluaciones Completadas

| Egresado | Evaluaciones Completadas | Puntajes |
|----------|--------------------------|----------|
| Juan Carlos (1) | técnica ITI (87%), cognitiva (92%), psicométrica | 13/15 correctas |
| María Fernanda (2) | técnica ITI (73%), proyectiva | 11/15 correctas |
| Valentina (6) | técnica Merc. (93%), psicométrica, cognitiva (85%) | 14/15 correctas |
| Roberto (7) | proyectiva, cognitiva (78%) | 9/10 correctas |
| Diego (3) | cognitiva (88%) | 10/12 correctas |
| Daniela (10) | técnica Merc. (80%) | 12/15 correctas |

---

## Flujo de Trabajo

```
1. Documentar endpoint en este .md
2. Implementar endpoint
3. Commit (en español, descriptivo)
4. Probar con Bruno (actualizar colección)
5. Marcar ✅ con link al commit
6. Siguiente endpoint
```

---

## Módulos del Contrato Maestro

### Módulo 1: AUTH ✅ COMPLETADO

| # | Endpoint | Método | Auth | Estado | Commit |
|---|----------|--------|------|--------|--------|
| 1 | /auth/login | POST | No | ✅ | feat: auth login |
| 2 | /auth/logout | POST | Sí | ✅ | feat: auth logout |
| 3 | /auth/onboarding | POST | Sí | ✅ | feat: auth onboarding |
| 4 | /auth/password | PUT | Sí | ✅ | feat: auth password |

---

### Módulo 2: EGRESADO (PROFILE) ✅ COMPLETADO

| # | Endpoint | Método | Auth | Estado | Commit |
|---|----------|--------|------|--------|--------|
| 5 | /egresado/perfil | GET | Sí | ✅ | feat: GET /egresado/perfil |
| 6 | /egresado/perfil/biografia | PUT | Sí | ✅ | feat: PUT /egresado/perfil/biografia |
| 7 | /egresado/perfil/trayectoria | PUT | Sí | ✅ | feat: PUT /egresado/perfil/trayectoria |
| 8 | /egresado/perfil/habilidades | PUT | Sí | ✅ | feat: PUT /egresado/perfil/habilidades |
| 9 | /egresado/foto | POST | Sí | ✅ | feat: POST /egresado/foto |
| 10 | /egresado/stats | GET | Sí | ✅ | feat: GET /egresado/stats |

#### Detalles de pruebas:
- **GET /egresado/perfil**: ✅ Retorna perfil completo con carrera, división, contacto, trayectoria, habilidades
- **PUT /egresado/perfil/biografia**: ✅ Actualiza biografia_ia correctamente
- **PUT /egresado/perfil/trayectoria**: ✅ Actualiza JSONB de trayectoria
- **PUT /egresado/perfil/habilidades**: ✅ Actualiza JSONB de habilidades (tecnicas, blandas, idiomas)
- **POST /egresado/foto**: ✅ Valida archivo (placeholder hasta integración con Drive)
- **GET /egresado/stats**: ✅ Retorna postulaciones_activas, match_promedio, pruebas_completadas, vistas_perfil
- **Auth**: ✅ 401 sin token, 403 con rol incorrecto, 200 con egresado

---

### Módulo 4: VACANTES Y POSTULACIONES ✅ COMPLETADO

| # | Endpoint | Método | Auth | Estado |
|---|----------|--------|------|--------|
| 16 | /vacantes | GET | No | ✅ |
| 17 | /vacantes/:id | GET | No | ✅ |
| 18 | /vacantes/:id/match-detalle | GET | Sí | ✅ |
| 19 | /vacantes/:id/postular | POST | Sí | ✅ |
| 20 | /vacantes/:id/cancelar-postulacion | DELETE | Sí | ✅ |
| 21 | /egresado/postulaciones | GET | Sí | ✅ |

#### Detalles de pruebas:
- **GET /vacantes**: ✅ Paginación, filtros (ubicacion, search, division_id, solo_convenio), match opcional
- **GET /vacantes/:id**: ✅ Retorna detalle con empresa_nombre, perfil_idoneo (JSONB), analisis_ia
- **GET /vacantes/:id/match-detalle**: ✅ Radar 5 dimensiones (técnicas, inglés, experiencia, carrera, soft skills)
- **POST /vacantes/:id/postular**: ✅ Calcula match, valida duplicado, transacción
- **DELETE /vacantes/:id/cancelar-postulacion**: ✅ Solo pendiente, DELETE físico, 404 si no existe
- **GET /egresado/postulaciones**: ✅ Lista con empresa, estatus, match, fecha
- **Auth**: ✅ 401 sin token, 400 duplicado, 404 no encontrado, 400 estatus no pendiente

---

### Módulo 3: MOTOR DE EVALUACIONES (EXAM) ✅ COMPLETADO

| # | Endpoint | Método | Auth | Estado |
|---|----------|--------|------|--------|
| 11 | /evaluaciones/catalogo | GET | Sí | ✅ |
| 12 | /evaluaciones/iniciar | POST | Sí | ✅ |
| 13 | /evaluaciones/respuesta | POST | Sí | ✅ |
| 14 | /evaluaciones/finalizar | POST | Sí | ✅ |
| 15 | /evaluaciones/radar | GET | Sí | ✅ |

#### Detalles de pruebas:
- **GET /evaluaciones/catalogo**: ✅ Lista 4 tipos con estado, bloqueo (técnica 6 meses, otras para siempre)
- **POST /evaluaciones/iniciar**: ✅ Crea evaluación, preguntas RANDOM sin respuesta_correcta, validación de bloqueo
- **POST /evaluaciones/respuesta**: ✅ Guarda respuesta, calcula es_correcta, valida duplicado, valida tiempo expirado
- **POST /evaluaciones/finalizar**: ✅ Calcula puntaje (67% con 2/3 correctas), detalle_resultados con categorías
- **GET /evaluaciones/radar**: ✅ Spider chart alumno vs promedio_carrera (labels, alumno, promedio_carrera)
- **Bloqueo**: ✅ Técnica 6 meses, psico/cogni/proy una vez para siempre
- **Auth**: ✅ 401 sin token, 400 tipo inválido, 400 evaluación finalizada, 400 respuesta duplicada

#### Reglas de Bloqueo:
| Tipo | Primera vez | Después |
|------|-------------|---------|
| `tecnica` | ✅ Disponible | ⏳ 6 meses |
| `psico` | ✅ Disponible | 🔒 Para siempre |
| `cogni` | ✅ Disponible | 🔒 Para siempre |
| `proy` | ✅ Disponible | 🔒 Para siempre |

#### 11. [ ] GET /evaluaciones/catalogo
- **Auth:** Sí
- **Descripción:** Lista pruebas disponibles según carrera del usuario
- **Response:** `[{ id, nombre, tipo, minutos, completada, ultimo_puntaje }]`
- **Tablas:** `evaluaciones`, `config_pruebas`, `banco_preguntas`
- **Datos seed:** 12 evaluaciones completadas, 80 preguntas disponibles
- **Commit planeado:** `feat: endpoint catalogo evaluaciones`

#### 12. [ ] POST /evaluaciones/iniciar
- **Auth:** Sí
- **Request:** `{ "id_prueba": 1 }`
- **Response:** `{ evaluacion_id, preguntas: [{ id, pregunta, opciones }], expira_en }`
- **Tablas:** `evaluaciones`, `banco_preguntas`
- **Commit planeado:** `feat: endpoint iniciar evaluacion`

#### 13. [ ] POST /evaluaciones/respuesta
- **Auth:** Sí
- **Request:** `{ "evaluacion_id", "pregunta_id", "opcion" }`
- **Response:** `{ "status": "saved" }`
- **Tablas:** `respuestas_detalle`
- **Commit planeado:** `feat: endpoint respuesta evaluacion`

#### 14. [ ] POST /evaluaciones/finalizar
- **Auth:** Sí
- **Request:** `{ "evaluacion_id" }`
- **Response:** `{ puntaje_global, detalle_resultados, match_actualizado }`
- **Tablas:** `evaluaciones`, `respuestas_detalle`, `banco_preguntas`
- **Commit planeado:** `feat: endpoint finalizar evaluacion`

#### 15. [ ] GET /evaluaciones/radar
- **Auth:** Sí
- **Descripción:** Datos para Spider Chart
- **Response:** `{ labels, alumno, promedio_carrera }`
- **Tablas:** `evaluaciones`, `respuestas_detalle`
- **Commit planeado:** `feat: endpoint radar evaluaciones`

---

### Módulo 4: VACANTES (JOBS) ✅ COMPLETADO

#### 16. [✅] GET /vacantes
- **Auth:** No (público)
- **Params:** `page, limit, search, ubicacion, division_id, solo_convenio`
- **Response:** `{ vacantes: [{ id, titulo, empresa, estatus_convenio, es_externa, match, ubicacion }], meta }`
- **Tablas:** `vacantes`, `empresas`, `divisiones`
- **Datos seed:** 15 vacantes (8 ITI, 7 Mercadotecnia)
- **Notas:** Match calculado si usuario egresado autenticado

#### 17. [✅] GET /vacantes/:id
- **Auth:** No (público)
- **Response:** `{ id, empresa_id, empresa_nombre, titulo, descripcion, ubicacion, perfil_idoneo, analisis_ia, url_externa }`
- **Tablas:** `vacantes`, `empresas`, `divisiones`

#### 18. [✅] GET /vacantes/:id/match-detalle
- **Auth:** Sí (egresado)
- **Response:** `{ match, comparativa_radar: { labels, alumno, idoneo }, feedback_ia }`
- **Tablas:** `vacantes`, `egresados`, `postulaciones`
- **Notas:** Radar de 5 dimensiones calculadas de JSONB real

#### 19. [✅] POST /vacantes/:id/postular
- **Auth:** Sí (egresado)
- **Request:** `{}`
- **Response:** `{ id_postulacion, estatus, match }`
- **Tablas:** `postulaciones`, `vacantes`, `egresados`
- **Notas:** Match se calcula y guarda al postular. Validación de duplicado.

#### 20. [✅] DELETE /vacantes/:id/cancelar-postulacion
- **Auth:** Sí (egresado)
- **Response:** `{ success, message }`
- **Tablas:** `postulaciones`
- **Notas:** Solo permite cancelar si estatus='pendiente'. DELETE físico.

#### 21. [✅] GET /egresado/postulaciones
- **Auth:** Sí (egresado)
- **Response:** `[{ id_postulacion, vacante_titulo, empresa, estatus, match, fecha }]`
- **Tablas:** `postulaciones`, `vacantes`, `empresas`
- **Datos seed:** 25 postulaciones (distribuidas en 4 estados)

---

### Módulo 5: EMPRESA (BUSINESS)

#### 22. [ ] GET /empresa/perfil
- **Auth:** Sí (empresa)
- **Response:** `{ nombre_comercial, rfc, foto_url, estatus_convenio, contacto }`
- **Tablas:** `empresas`, `usuarios`
- **Commit planeado:** `feat: endpoint perfil empresa`

#### 23. [ ] PUT /empresa/perfil
- **Auth:** Sí (empresa)
- **Request:** `{ "nombre_comercial", "contacto" }`
- **Response:** `{ "status": "success" }`
- **Tablas:** `empresas`
- **Commit planeado:** `feat: endpoint actualizar perfil empresa`

#### 24. [ ] GET /empresa/dashboard/stats
- **Auth:** Sí (empresa)
- **Response:** `{ vacantes_activas, total_postulantes, entrevistas_pendientes }`
- **Tablas:** `vacantes`, `postulaciones`
- **Commit planeado:** `feat: endpoint dashboard empresa`

#### 25. [ ] GET /empresa/mis-vacantes
- **Auth:** Sí (empresa)
- **Response:** `[{ id, titulo, postulantes_count, fecha_pub }]`
- **Tablas:** `vacantes`, `postulaciones`
- **Commit planeado:** `feat: endpoint mis vacantes`

#### 26. [ ] POST /empresa/vacantes
- **Auth:** Sí (empresa)
- **Request:** `{ "titulo", "descripcion", "ubicacion", "perfil_idoneo" }`
- **Response:** `{ "id", "analisis_ia" }`
- **Tablas:** `vacantes`
- **Commit planeado:** `feat: endpoint crear vacante`

#### 27. [ ] GET /empresa/vacantes/:id/postulantes
- **Auth:** Sí (empresa)
- **Response:** `[{ id_postulacion, alumno_nombre, match, estatus, egresado_id }]`
- **Tablas:** `postulaciones`, `egresados`, `usuarios`
- **Commit planeado:** `feat: endpoint postulantes de vacante`

#### 28. [ ] PATCH /postulaciones/:id/estatus
- **Auth:** Sí (empresa)
- **Request:** `{ "nuevo_estatus": "revisada|aceptada|rechazada" }`
- **Response:** `{ "status": "success" }`
- **Tablas:** `postulaciones`
- **Commit planeado:** `feat: endpoint cambiar estatus postulacion`

---

### Módulo 5: EMPRESA (BUSINESS) ✅ COMPLETADO

| # | Endpoint | Método | Auth | Estado |
|---|----------|--------|------|--------|
| 22 | /empresa/perfil | GET | Sí | ✅ |
| 23 | /empresa/perfil | PUT | Sí | ✅ |
| 24 | /empresa/dashboard/stats | GET | Sí | ✅ |
| 25 | /empresa/mis-vacantes | GET | Sí | ✅ |
| 26 | /empresa/vacantes | POST | Sí | ✅ |
| 27 | /empresa/vacantes/:id/postulantes | GET | Sí | ✅ |
| 28 | /postulaciones/:id/estatus | PATCH | Sí | ✅ |

#### Detalles de pruebas:
- **GET /empresa/perfil**: ✅ Retorna nombre_comercial, rfc, estatus_convenio, contacto (JSONB)
- **PUT /empresa/perfil**: ✅ Actualiza nombre_comercial y contacto JSONB
- **GET /empresa/dashboard/stats**: ✅ vacantes_activas, total_postulantes, entrevistas_pendientes
- **GET /empresa/mis-vacantes**: ✅ Lista con COUNT de postulantes por vacante
- **POST /empresa/vacantes**: ✅ Crea vacante con perfil_idoneo JSONB, valida campos requeridos
- **GET /empresa/vacantes/:id/postulantes**: ✅ Lista postulantes ordenados por match DESC, verifica pertenencia
- **PATCH /postulaciones/:id/estatus**: ✅ Cambia estatus (pendiente/revisada/aceptada/rechazada), valida pertenencia
- **Auth**: ✅ 401 sin token, 403 con rol egresado, 404 vacante de otra empresa, 400 estatus inválido

---

### Módulo 6: ADMINISTRADOR (ADMIN)

#### 29. [ ] GET /admin/dashboard/global
- **Auth:** Sí (admin)
- **Response:** `{ total_egresados, total_empresas, total_contratados, insercion_por_carrera }`
- **Tablas:** `usuarios`, `egresados`, `empresas`, `postulaciones`
- **Commit planeado:** `feat: endpoint dashboard admin`

#### 30. [ ] GET /admin/empresas/pendientes
- **Auth:** Sí (admin)
- **Response:** `[{ id, nombre, rfc, fecha_reg }]`
- **Tablas:** `empresas`, `usuarios`
- **Datos seed:** 1 empresa con estatus `pendiente` (Constructora del Pacífico)
- **Commit planeado:** `feat: endpoint empresas pendientes`

#### 31. [ ] PATCH /admin/empresas/:id/convenio
- **Auth:** Sí (admin)
- **Request:** `{ "estatus": "activo|rechazado" }`
- **Response:** `{ "status": "success" }`
- **Tablas:** `empresas`
- **Commit planeado:** `feat: endpoint convenio empresa`

#### 32. [ ] GET /admin/banco-preguntas
- **Auth:** Sí (admin)
- **Params:** `division_id, tipo_prueba`
- **Response:** `[{ id, pregunta, carrera }]`
- **Tablas:** `banco_preguntas`, `carreras`, `divisiones`
- **Datos seed:** 80 preguntas
- **Commit planeado:** `feat: endpoint banco preguntas`

#### 33. [ ] POST /admin/banco-preguntas/generar-ia
- **Auth:** Sí (admin)
- **Request:** `{ "carrera_id", "cantidad" }`
- **Response:** `{ "status": "preguntas_generadas", "count" }`
- **Tablas:** `banco_preguntas`
- **Commit planeado:** `feat: endpoint generar preguntas IA`

---

### Módulo 7: IA Y DOCUMENTOS (SERVICES)

#### 34. [ ] POST /ia/cv/optimizar-biografia
- **Auth:** Sí
- **Request:** `{ "texto_actual": "string" }`
- **Response:** `{ "biografia_optimizada": "string" }`
- **Commit planeado:** `feat: endpoint optimizar biografia IA`

#### 35. [ ] GET /ia/cv/recomendaciones
- **Auth:** Sí (egresado)
- **Response:** `{ puntos_fuertes, puntos_debiles, cursos_sugeridos }`
- **Tablas:** `egresados`, `evaluaciones`, `vacantes`
- **Commit planeado:** `feat: endpoint recomendaciones IA`

#### 36. [ ] POST /ia/chat/asesor
- **Auth:** Sí
- **Request:** `{ "mensaje", "contexto_pantalla" }`
- **Response:** `{ "respuesta": "string" }`
- **Commit planeado:** `feat: endpoint chat asesor IA`

#### 37. [ ] GET /egresado/cv/pdf
- **Auth:** Sí (egresado)
- **Descripción:** Genera o recupera link de Drive
- **Response:** `{ pdf_url, ultima_generacion }`
- **Commit planeado:** `feat: endpoint CV PDF`

---

## Resumen de Progreso

| Módulo | Total | Completados | Pendientes |
|--------|-------|-------------|------------|
| AUTH | 4 | 4 ✅ | 0 |
| EGRESADO | 6 | 6 ✅ | 0 |
| EVALUACIONES | 5 | 0 | 5 |
| VACANTES | 6 | 0 | 6 |
| EMPRESA | 7 | 0 | 7 |
| ADMIN | 5 | 0 | 5 |
| IA/SERVICES | 4 | 0 | 4 |
| **TOTAL** | **37** | **10** | **27** |

---

## Dependencias entre Módulos

```
AUTH → EGRESADO → VACANTES → EVALUACIONES → EMPRESA → ADMIN → IA
```

**Orden propuesto de implementación:**
1. ~~AUTH~~ ✅
2. ~~EGRESADO~~ ✅
3. ~~VACANTES~~ ✅
4. EVALUACIONES (usa egresados y carreras)
5. EMPRESA (usa vacantes y postulaciones)
6. ADMIN (usa todas las tablas)
7. IA/SERVICES (depende de datos existentes)

---

## 📚 Lecciones Aprendidas y Guía de Implementación

### Checklist para cada nuevo endpoint

- [ ] Crear método `public static function` en el controller (kebab-case file)
- [ ] Registrar ruta en `routes/routes.php`
- [ ] Cargar controller en `index.php`
- [ ] Verificar middleware: `if (!Middleware::authMiddleware()) return;`
- [ ] Verificar rol: `if (!Middleware::requireRole('rol')) return;`
- [ ] Usar `getUsuarioActual()` para datos del usuario
- [ ] Extraer campos del request EXPLÍCITAMENTE (NO usar `(array) $requestData`)
- [ ] Validar campos con `validarCampos()` y verificar resultado
- [ ] Usar transacciones para writes: `beginTransaction()`, `commit()`, `rollBack()`
- [ ] Llamar `$stmt->execute()` después de `bindValue()`
- [ ] Usar `JSON_UNESCAPED_UNICODE` en `json_encode()`
- [ ] Usar `responderExito([], ...)` (NO null)
- [ ] Probar: 200 con auth correcto, 401 sin token, 403 con rol incorrecto
- [ ] Commit descriptivo en español

### Patrón de Controller (Template)

```php
public static function endpointName() {
    if (!Middleware::authMiddleware()) return;
    if (!Middleware::requireRole('rol')) return;
    
    $usuario = getUsuarioActual();
    $userId = $usuario['id'];
    
    $pdo = getPgConnection();
    
    try {
        // GET: consultar directamente
        // POST/PUT/DELETE: usar transacción
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("SQL QUERY");
        $stmt->bindValue(':param', $valor, PDO::PARAM_STR);
        $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            $pdo->rollBack();
            responderError('Recurso no encontrado', 404);
            return;
        }
        
        $pdo->commit();
        responderExito($data, 'Mensaje exitoso');
        
    } catch (Exception $e) {
        $pdo->rollBack();
        handleTransactionError($pdo, 'Error: ' . $e->getMessage(), 500);
    }
}
```

### Errores Comunes a Evitar

1. ❌ `PDO::ATTR_EMULATE_PREPARES => false` → Usa `true`
2. ❌ `json_encode($data)` sin `JSON_UNESCAPED_UNICODE`
3. ❌ `(array) $requestData` → Extrae campos explícitamente
4. ❌ `Middleware::authMiddleware();` sin verificar retorno
5. ❌ `responderExito(null, ...)` → Usa `[]`
6. ❌ `bindValue()` sin `execute()`
7. ❌ Olvidar `$pdo->commit()` después de transacción

### Flujo de Trabajo Actualizado

```
1. Implementar endpoint (controller + route + index.php)
2. Commit inicial
3. Reiniciar servidor (php -S)
4. Probar con curl:
   a. Sin auth → debe dar 401
   b. Con rol incorrecto → debe dar 403
   c. Con auth correcto → debe dar 200 con datos correctos
5. Si hay errores:
   a. Verificar logs: cat /tmp/php_server.log
   b. Revisar errores comunes (lista arriba)
   c. Corregir y hacer commit
6. Marcar ✅ en este plan
7. Siguiente endpoint
```
