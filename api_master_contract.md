# Contrato Maestro ABSOLUTO de la API v1.0 - Hackathon DITI 2026

**Base URL:** `/api/v1`
**Autenticación:** Bearer Token (JWT) en el header `Authorization`.

---

## 1. Módulo: Autenticación y Onboarding (AUTH)

### `POST /auth/login`
- **Request:** `{ "matricula": "string", "password": "hash" }`
- **Response:** `{ "token": "jwt", "user": { "id": 1, "nombre": "string", "rol": "egresado|empresa|admin", "primer_ingreso": boolean } }`

### `POST /auth/logout`
- **Request:** `{}`
- **Response:** `{ "status": "success" }`

### `POST /auth/onboarding`
- **Descripción:** Completa datos de contacto obligatorios en el primer inicio.
- **Request:** `{ "telefono": "string", "correo_personal": "string", "linkedin": "string" }`
- **Response:** `{ "status": "success", "user": { "primer_ingreso": false } }`

### `PUT /auth/password`
- **Request:** `{ "old_password": "string", "new_password": "string" }`
- **Response:** `{ "status": "success" }`

---

## 2. Módulo: Egresado (PROFILE)

### `GET /egresado/perfil`
- **Response:** `{ "id": 1, "nombre": "string", "carrera": "string", "division": "string", "foto_url": "string", "contacto": {}, "biografia_ia": "string", "trayectoria": [], "habilidades": {} }`

### `PUT /egresado/perfil/biografia`
- **Request:** `{ "biografia": "string" }`
- **Response:** `{ "biografia_ia": "string" }`

### `PUT /egresado/perfil/trayectoria`
- **Request:** `{ "trayectoria": [ { "tipo": "string", "empresa": "string", "descripcion": "string", "fecha": "string" } ] }`
- **Response:** `{ "status": "success" }`

### `PUT /egresado/perfil/habilidades`
- **Request:** `{ "tecnicas": [], "blandas": [], "idiomas": [] }`
- **Response:** `{ "status": "success" }`

### `POST /egresado/foto`
- **Request:** `Multipart/form-data (file)`
- **Response:** `{ "foto_url": "string", "drive_id": "string" }`

### `GET /egresado/stats`
- **Response:** `{ "postulaciones_activas": 0, "match_promedio": 0, "pruebas_completadas": 0, "vistas_perfil": 0 }`

---

## 3. Módulo: Motor de Evaluaciones (EXAM)

### `GET /evaluaciones/catalogo`
- **Descripción:** Lista pruebas disponibles según la carrera del usuario.
- **Response:** `[ { "id": 1, "nombre": "string", "tipo": "tecnica|psico|cogni|proy", "minutos": 0, "completada": boolean, "ultimo_puntaje": 0 } ]`

### `POST /evaluaciones/iniciar`
- **Request:** `{ "id_prueba": 1 }`
- **Response:** `{ "evaluacion_id": 10, "preguntas": [ { "id": 1, "pregunta": "string", "opciones": { "a": "...", "b": "..." } } ], "expira_en": "timestamp" }`

### `POST /evaluaciones/respuesta`
- **Request:** `{ "evaluacion_id": 10, "pregunta_id": 1, "opcion": "a" }`
- **Response:** `{ "status": "saved" }`

### `POST /evaluaciones/finalizar`
- **Request:** `{ "evaluacion_id": 10 }`
- **Response:** `{ "puntaje_global": 0, "detalle_resultados": {}, "match_actualizado": boolean }`

### `GET /evaluaciones/radar`
- **Description:** Datos para el Spider Chart.
- **Response:** `{ "labels": [], "alumno": [], "promedio_carrera": [] }`

---

## 4. Módulo: Vacantes (JOBS)

### `GET /vacantes`
- **Params:** `page, limit, search, ubicacion, division_id, match_min, solo_convenio`
- **Response:** `{ "data": [ { "id": "1|jooble_x", "titulo": "string", "empresa": "string", "estatus_convenio": "string", "es_externa": boolean, "match": 0, "ubicacion": "string" } ], "meta": { "total": 0 } }`

### `GET /vacantes/:id`
- **Response:** `{ "id": 1, "empresa_id": 1, "titulo": "string", "descripcion": "string", "ubicacion": "string", "perfil_idoneo": {}, "analisis_ia": "string", "url_externa": "string" }`

### `GET /vacantes/:id/match-detalle`
- **Response:** `{ "match": 85, "comparativa_radar": { "alumno": [], "idoneo": [] }, "feedback_ia": "string" }`

### `POST /vacantes/:id/postular`
- **Request:** `{}`
- **Response:** `{ "id_postulacion": 1, "status": "postulado" }`

### `DELETE /vacantes/:id/cancelar-postulacion`
- **Request:** `{}`
- **Response:** `{ "status": "success" }`

### `GET /egresado/postulaciones`
- **Response:** `[ { "id_postulacion": 1, "vacante_titulo": "string", "empresa": "string", "estatus": "revisión|entrevista|...", "fecha": "string" } ]`

---

## 5. Módulo: Empresa (BUSINESS)

### `GET /empresa/perfil`
- **Response:** `{ "nombre_comercial": "string", "rfc": "string", "foto_url": "string", "estatus_convenio": "string", "contacto": {} }`

### `PUT /empresa/perfil`
- **Request:** `{ "nombre_comercial": "string", "contacto": {} }`
- **Response:** `{ "status": "success" }`

### `GET /empresa/dashboard/stats`
- **Response:** `{ "vacantes_activas": 0, "total_postulantes": 0, "entrevistas_pendientes": 0 }`

### `GET /empresa/mis-vacantes`
- **Response:** `[ { "id": 1, "titulo": "string", "postulantes_count": 0, "fecha_pub": "string" } ]`

### `POST /empresa/vacantes`
- **Request:** `{ "titulo": "string", "descripcion": "string", "ubicacion": "string", "perfil_idoneo": {} }`
- **Response:** `{ "id": 1, "analisis_ia": "string" }`

### `GET /empresa/vacantes/:id/postulantes`
- **Response:** `[ { "id_postulacion": 1, "alumno_nombre": "string", "match": 90, "estatus": "string", "egresado_id": 1 } ]`

### `PATCH /postulaciones/:id/estatus`
- **Request:** `{ "nuevo_estatus": "entrevista|contratado|rechazado" }`
- **Response:** `{ "status": "success" }`

---

## 6. Módulo: Administrador (ADMIN)

### `GET /admin/dashboard/global`
- **Response:** `{ "total_egresados": 0, "total_empresas": 0, "total_contratados": 0, "insercion_por_carrera": {} }`

### `GET /admin/empresas/pendientes`
- **Response:** `[ { "id": 1, "nombre": "string", "rfc": "string", "fecha_reg": "string" } ]`

### `PATCH /admin/empresas/:id/convenio`
- **Request:** `{ "estatus": "activo|rechazado" }`
- **Response:** `{ "status": "success" }`

### `GET /admin/banco-preguntas`
- **Params:** `division_id, tipo_prueba`
- **Response:** `[ { "id": 1, "pregunta": "string", "carrera": "string" } ]`

### `POST /admin/banco-preguntas/generar-ia`
- **Request:** `{ "carrera_id": 1, "cantidad": 10 }`
- **Response:** `{ "status": "preguntas_generadas", "count": 10 }`

---

## 7. Módulo: IA y Documentos (SERVICES)

### `POST /ia/cv/optimizar-biografia`
- **Request:** `{ "texto_actual": "string" }`
- **Response:** `{ "biografia_optimizada": "string" }`

### `GET /ia/cv/recomendaciones`
- **Response:** `{ "puntos_fuertes": [], "puntos_debiles": [], "cursos_sugeridos": [] }`

### `POST /ia/chat/asesor`
- **Request:** `{ "mensaje": "string", "contexto_pantalla": "string" }`
- **Response:** `{ "respuesta": "string" }`

### `GET /egresado/cv/pdf`
- **Description:** Genera o recupera el link de Drive.
- **Response:** `{ "pdf_url": "string", "ultima_generacion": "timestamp" }`
