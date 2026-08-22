# Documentación Técnica — BolsaLaboralBack

> Proyecto para el **Hackathon DITI 2026** — Universidad Tecnológica de la Costa

Esta guía documenta la arquitectura, tecnologías y patrones de diseño del backend del sistema de Bolsa de Trabajo. Su objetivo es permitir que cualquier desarrollador (o IA) comprenda rápidamente cómo extender o mantener el sistema.

## 1. Stack Tecnológico

*   **Lenguaje:** PHP 8.x (Vanilla, estructurado tipo MVC simplificado).
*   **Framework:** FlightPHP (Micro-framework para enrutamiento).
*   **Base de Datos:** PostgreSQL como motor principal.
*   **Librerías Clave:**
    *   `vlucas/phpdotenv`: Gestión de variables de entorno (`.env`).
    *   `firebase/php-jwt`: Autenticación JWT con access tokens + refresh tokens.
    *   `phpmailer/phpmailer`: Envío de correos transaccionales.
    *   `robmorgan/phinx`: Migraciones de base de datos.
    *   `phpoffice/phpspreadsheet`: Procesamiento de archivos Excel (si aplica).

## 2. Arquitectura del Proyecto

La estructura de carpetas sigue un patrón MVC (Modelo-Vista-Controlador) adaptado:

```text
/
├── app/
│   ├── controllers/       # Lógica de Negocio (Ej: OfertaController.php)
│   ├── services/          # Lógica reutilizable y externa (EmailService, etc.)
│   ├── middleware/        # Interceptores (Auth, Validaciones, Roles)
│   └── Lib/               # Helpers y utilidades compartidas
├── config/
│   ├── database.php       # Conexiones PDO (getPgConnection)
│   └── db.sql             # Esquema de Base de Datos (PostgreSQL)
├── routes/
│   └── routes.php         # Definición de Endpoints (Flight::route)
├── migrations/            # Migraciones de Phinx
├── db/
│   └── seed.sql           # Datos semilla iniciales
├── vendor/                # Dependencias (Composer)
├── uploads/               # Archivos subidos (CVs, logos de empresa)
└── index.php              # Punto de entrada único (Front Controller)
```

## 3. Flujo de Trabajo y Patrones

### A. Enrutamiento (`routes/routes.php`)

Se utiliza **FlightPHP** para definir las rutas. Cada ruta protegida debe:

1. Verificar autenticación mediante `Middleware::authMiddleware()`.
2. Opcionalmente verificar rol mediante `Middleware::requireRole('admin')`.
3. Llamar al método correspondiente del Controlador Estático.

**Ejemplo de Ruta:**
```php
Flight::route('POST /'. $_ENV['API_VERSION_URL'] .'/postulaciones', function(){
    if (!Middleware::authMiddleware()) return;          // 1. Seguridad
    if (!Middleware::requireRole('candidato')) return;  // 2. Rol
    PostulacionController::crear();                     // 3. Controlador
});
```

### B. Controladores (`app/controllers/`)

Los controladores contienen la lógica de negocio como clases con métodos estáticos.

**Reglas de Implementación:**

1. **Validación de Entrada:** Usar `Flight::request()->data` o `php://input`. Validar siempre tipos y campos requeridos.
2. **Seguridad Transaccional:**
   * Para operaciones de escritura (`INSERT`, `UPDATE`, `DELETE`), **SIEMPRE** usar transacciones:
   * `$pdo->beginTransaction()` al inicio.
   * `$pdo->commit()` al final.
   * `$pdo->rollBack()` en caso de error.
3. **Manejo de Errores:**
   * En el bloque `catch`, utilizar el helper `handleTransactionError($pdo, $e)`.
   * Esto genera un folio de soporte (`ERR-AAAAMMDD-XXXX`) y lo registra.
4. **Respuesta:** Retornar siempre JSON (`Flight::json([...])`).

**Snippet Base para Controlador:**
```php
public static function crear() {
    try {
        $pdo = getPgConnection();
        $pdo->beginTransaction();

        $data = Flight::request()->data;

        // Validación
        if (empty($data->oferta_id) || empty($data->candidato_id)) {
            Flight::json(['success' => false, 'error' => 'Campos requeridos'], 400);
            return;
        }

        // ... Lógica de negocio ...

        $pdo->commit();
        Flight::json(['success' => true, 'data' => $resultado]);
    } catch (Exception $e) {
        self::handleTransactionError($pdo, $e);
    }
}
```

### C. Middleware (`app/middleware/`)

| Middleware | Propósito |
|------------|-----------|
| `authMiddleware()` | Valida JWT, verifica blacklist, extrae usuario |
| `requireRole($role)` | Verifica que el usuario tenga el rol necesario |
| `validateInput($rules)` | Valida campos de entrada según reglas definidas |

### D. Base de Datos

El sistema utiliza PostgreSQL con esquemas dedicados:

* **Tablas Principales:**
  * `usuarios` — Credenciales y roles (candidato, empresa, admin)
  * `candidatos` — Perfiles de aspirantes (datos personales, CV, habilidades)
  * `empresas` — Perfiles de organizaciones (nombre, giro, descripción)
  * `ofertas` — Publicaciones de empleo (título, descripción, requisitos, salario, estado)
  * `postulaciones` — Relación candidato ↔ oferta con estado y fecha
  * `token_blacklist` — Tokens JWT revocados
  * `refresh_tokens` — Refresh tokens activos
  * `password_reset_tokens` — Tokens para recuperación de contraseña
  * `tickets_error` — Registro de errores con folio de soporte

* **Auditoría:** Se recomienda implementar triggers (`fn_log_auditoria`) para registrar cambios en tablas sensibles.

## 4. Modelo de Dominio

### Estados de Postulación

```
pendiente → revisada → aceptada
                  ↘ rechazada
```

### Estados de Oferta

```
activa → pausada
       → cerrada
```

### Roles de Usuario

| Rol | Permisos |
|-----|----------|
| `candidato` | Ver ofertas, postularse, gestionar perfil, ver estado de postulaciones |
| `empresa` | Publicar ofertas, gestionar postulaciones, actualizar perfil |
| `admin` | Gestionar usuarios, ver estadísticas, moderar ofertas |

## 5. Guía para Nuevos Módulos

Si deseas crear un nuevo módulo (ej: "Notificaciones"):

1. **BD:** Crea la tabla en una migración de Phinx (`vendor/bin/phinx create Notificaciones`).
2. **Controller:** Crea `app/controllers/NotificacionController.php`.
3. **Rutas:** Registra el grupo de rutas en `routes/routes.php`.
4. **Seguridad:** Replica el patrón de Transacciones + Tickets de Error.
5. **Middleware:** Aplica `authMiddleware()` y `requireRole()` según corresponda.

## 6. Respuestas JSON Estándar

### Éxito
```json
{
    "success": true,
    "message": "Operación realizada correctamente",
    "data": { }
}
```

### Error
```json
{
    "success": false,
    "error": "Descripción del error",
    "ticket": "ERR-20260115-0042"
}
```

### Paginación
```json
{
    "success": true,
    "data": [ ],
    "pagination": {
        "total": 150,
        "page": 1,
        "per_page": 20,
        "total_pages": 8
    }
}
```

---

**Nota para IAs:** Al generar código, priorizar siempre la estabilidad (try-catch) y la integridad de datos (transacciones) sobre la brevedad. Seguir las convenciones de naming definidas en AGENTS.md.
