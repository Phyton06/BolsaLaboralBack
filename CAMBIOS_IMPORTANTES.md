# Cambios Importantes - BolsaLaboralBack

> Notas técnicas para frontend y backend. Decisiones, fixes y convenciones.

---

## 📌 Convenciones del Proyecto

### Nomenclatura
- **Archivos PHP:** kebab-case (ej: `egresado-controller.php`)
- **Clases:** PascalCase (ej: `EgresadoController`)
- **Métodos:** camelCase (ej: `getProfile()`)
- **Columnas DB:** snake_case (ej: `usuario_id`, `password_hash`)
- **Rutas API:** kebab-case con slash (ej: `/egresado/perfil/biografia`)

### Estructura de Carpetas
```
BolsaLaboralBack/
├── app/
│   ├── controllers/     # Controladores (kebab-case)
│   ├── middleware/       # Middleware.php
│   ├── services/         # JwtService.php
│   └── Lib/              # helpers.php
├── config/               # database.php
├── db/                   # seed.sql, migrations/
├── public/               # index.php (entry point)
├── routes/               # routes.php
├── vendor/               # Composer dependencies
└── index.php             # Front controller
```

---

## 🔧 Configuración Técnica

### Base de Datos
- **Motor:** PostgreSQL (Supabase)
- **Pooler:** pgBouncer en modo transaction
- **PDO:** `ATTR_EMULATE_PREPARES => true` (OBLIGATORIO para pgBouncer)
- **SSL:** `sslmode=require`

### JSONB
- **IMPORTANTE:** Usar `JSON_UNESCAPED_UNICODE` en `json_encode()`
- **NO usar** `(array) $requestData` - extraer campos explícitamente
- **Cast:** `CAST(:valor AS jsonb)` en queries

### Auth
- **Header:** `Authorization: Bearer <token>`
- **Fallback:** `?token=` en query string
- **Middleware:** SIEMPRE verificar `if (!Middleware::authMiddleware()) return;`

---

## 🐛 Fixes y Lecciones Aprendidas

### 1. PDO y pgBouncer
**Problema:** `PDO::ATTR_EMULATE_PREPARES => false` causa errores con columnas JSONB en Supabase/pgBouncer.

**Solución:**
```php
PDO::ATTR_EMULATE_PREPARES => true
```

**Archivo:** `config/database.php`

### 2. JSON Unicode en PostgreSQL
**Problema:** `SQLSTATE[22P05]: unsupported Unicode escape sequence` al guardar JSONB.

**Causa:** `json_encode()` por defecto escapa caracteres unicode.

**Solución:**
```php
json_encode($data, JSON_UNESCAPED_UNICODE)
```

### 3. Flight Request Data
**Problema:** `(array) $requestData` incluye propiedades internas con null bytes que PostgreSQL rechaza.

**Solución:** Extraer campos explícitamente:
```php
$tecnicas = $requestData->tecnicas ?? [];
$blandas = $requestData->blandas ?? [];
```

**NO hacer:**
```php
$habilidades = (array) $requestData;  // ❌ Incluye null bytes
```

### 4. Middleware Pattern
**Problema:** El middleware retorna false pero el código sigue ejecutando.

**Solución:** SIEMPRE verificar el retorno:
```php
if (!Middleware::authMiddleware()) return;
if (!Middleware::requireRole('egresado')) return;
```

### 5. responderExito()
**Problema:** `responderExito(null, ...)` falla porque espera array.

**Solución:** Usar `[]` en lugar de `null`:
```php
responderExito([], 'Mensaje');  // ✅
responderExito(null, 'Mensaje'); // ❌
```

### 6. execute() en PDO
**Problema:** `bindValue()` sin `execute()` no ejecuta la consulta.

**Patrón correcto:**
```php
$stmt->bindValue(':campo', $valor, PDO::PARAM_STR);
$stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
$stmt->execute();  // ← ¡NO OLVIDAR!
```

---

## 📊 Estructura de Respuestas

### Éxito
```json
{
  "success": true,
  "message": "Descripción de la operación",
  "data": { ... }
}
```

### Error
```json
{
  "success": false,
  "error": "Descripción del error"
}
```

### Error con ticket
```json
{
  "success": false,
  "error": "Error interno del servidor",
  "ticket": "ERR-20260430-XXXX"
}
```

---

## 🔐 Control de Acceso

### Códigos HTTP
| Código | Significado | Cuándo usar |
|--------|-------------|-------------|
| 200 | OK | Operación exitosa |
| 400 | Bad Request | Datos inválidos o faltantes |
| 401 | Unauthorized | Token faltante o inválido |
| 403 | Forbidden | Rol incorrecto |
| 404 | Not Found | Recurso no encontrado |
| 500 | Internal Error | Error del servidor |

### Roles
| Rol | Acceso |
|-----|--------|
| egresado | /egresado/*, /evaluaciones/*, /vacantes/* |
| empresa | /empresa/*, /postulaciones/* |
| admin | /admin/*, /empresa/* (lectura) |

---

## 🗃️ Esquema de Base de Datos

### Tablas Principales
| Tabla | PK | FK | Notas |
|-------|-----|-----|-------|
| `usuarios` | `id` (bigserial) | - | `matricula` UNIQUE, `rol` CHECK |
| `egresados` | `usuario_id` | → usuarios(id), → carreras(id) | JSONB: contacto, trayectoria, habilidades |
| `empresas` | `usuario_id` | → usuarios(id) | JSONB: contacto |
| `vacantes` | `id` (bigserial) | → usuarios(id), → divisiones(id) | JSONB: perfil_idoneo |
| `postulaciones` | `id` (bigserial) | → usuarios(id), → vacantes(id) | estatus: pendiente, revisada, aceptada, rechazada |
| `evaluaciones` | `id` (bigserial) | → usuarios(id) | tipo_prueba: tecnica, psico, cogni, proy |
| `banco_preguntas` | `id` (bigserial) | → carreras(id), → divisiones(id) | JSONB: opciones |
| `respuestas_detalle` | `id` (bigserial) | → evaluaciones(id), → banco_preguntas(id) | - |
| `divisiones` | `id` (bigserial) | - | Lookup table |
| `carreras` | `id` (bigserial) | → divisiones(id) | Lookup table |

---

## 🚀 Comandos Útiles

### Iniciar servidor
```bash
php -S localhost:8080 -t public
```

### Verificar salud
```bash
curl http://localhost:8080/auth/health
```

### Login de prueba
```bash
curl -X POST http://localhost:8080/auth/login \
  -H "Content-Type: application/json" \
  -d '{"matricula": "20240001", "password": "test1234"}'
```

### Seed data
```bash
psql -h <host> -U <user> -d postgres -f db/seed.sql
```

---

## 📝 Notas para Frontend

### URLs
- **Base URL:** `http://localhost:8080` (desarrollo)
- **CORS:** Permitido desde `http://localhost:3000`

### Auth Flow
1. POST `/auth/login` → obtener `token`
2. Incluir en headers: `Authorization: Bearer <token>`
3. Si 401 → redirigir a login
4. Si 403 → mostrar "Sin permisos"

### Campos JSONB
- `contacto`: `{ telefono, correo_personal, linkedin }`
- `trayectoria`: `[{ tipo, empresa, descripcion, fecha }]`
- `habilidades`: `{ tecnicas: [], blandas: [], idiomas: [] }`
- `perfil_idoneo`: `{ carrera, experiencia_min, habilidades_requeridas, nivel_ingles }`
- `opciones` (preguntas): `{ a: "...", b: "...", c: "...", d: "..." }`

### Estados de Postulación
- `pendiente` - Recién enviada
- `revisada` - Empresa la vio
- `aceptada` - Pasó a siguiente fase
- `rechazada` - No continuará

### Estados de Vacante
- `activa` - Aceptando postulaciones
- `pausada` - Temporalmente cerrada
- `cerrada` - No acepta más
