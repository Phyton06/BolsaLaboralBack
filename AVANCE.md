# AVANCE — BolsaLaboralBack

> Proyecto para el **Hackathon DITI 2026** — Universidad Tecnológica de la Costa

---

## Módulo: Autenticación y Onboarding (AUTH) ✅ COMPLETADO

### Fecha de implementación
29 de abril de 2026

### Endpoints implementados

| Método | Ruta | Auth | Descripción |
|--------|------|------|-------------|
| `POST` | `/auth/login` | No | Iniciar sesión con matrícula y contraseña |
| `POST` | `/auth/logout` | Sí | Cerrar sesión (revocar token) |
| `POST` | `/auth/onboarding` | Sí | Completar datos de contacto en primer inicio |
| `PUT` | `/auth/password` | Sí | Cambiar contraseña |

### Detalles de cada endpoint

#### `POST /auth/login`

**Request:**
```json
{
  "matricula": "20240001",
  "password": "mi_contraseña"
}
```

**Response exitosa (200):**
```json
{
  "success": true,
  "message": "Inicio de sesión exitoso",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIs...",
    "refresh_token": "eyJhbGciOiJIUzI1NiIs...",
    "user": {
      "id": 1,
      "nombre": "Juan Pérez García",
      "rol": "egresado",
      "primer_ingreso": true
    }
  }
}
```

**Response de error (401):**
```json
{
  "success": false,
  "error": "Matricula o contraseña incorrectos"
}
```

---

#### `POST /auth/logout`

**Headers:** `Authorization: Bearer <token>`

**Response exitosa (200):**
```json
{
  "success": true,
  "message": "Sesión cerrada correctamente",
  "data": {}
}
```

---

#### `POST /auth/onboarding`

**Headers:** `Authorization: Bearer <token>`

**Request:**
```json
{
  "telefono": "+52 123 456 7890",
  "correo_personal": "usuario@email.com",
  "linkedin": "https://linkedin.com/in/usuario"
}
```

**Response exitosa (200):**
```json
{
  "success": true,
  "message": "Datos de contacto actualizados correctamente",
  "data": {
    "user": {
      "id": 1,
      "primer_ingreso": false
    }
  }
}
```

---

#### `PUT /auth/password`

**Headers:** `Authorization: Bearer <token>`

**Request:**
```json
{
  "old_password": "contraseña_vieja",
  "new_password": "contraseña_nueva"
}
```

**Response exitosa (200):**
```json
{
  "success": true,
  "message": "Contraseña actualizada correctamente",
  "data": {}
}
```

---

### Arquitectura implementada

```
BolsaLaboralBack/
├── index.php                      ← Front controller (FlightPHP + CORS)
├── composer.json                  ← Dependencias
├── phinx.php                      ← Config de migraciones
├── setup.php                      ← Script de inicialización
├── .env.example                   ← Plantilla de variables de entorno
│
├── app/
│   ├── controllers/
│   │   └── AuthController.php     ← 4 endpoints de auth
│   ├── services/
│   │   └── JwtService.php         ← Generación y validación JWT
│   ├── middleware/
│   │   └── Middleware.php         ← authMiddleware, requireRole
│   └── Lib/
│       └── helpers.php            ← Helpers centrales
│
├── config/
│   └── database.php               ← Factory PDO (Supabase)
│
├── public/
│   ├── index.php                  ← Entry point público
│   └── .htaccess                  ← Reescritura de URLs
│
├── routes/
│   └── routes.php                 ← Definición de rutas /auth/*
│
└── uploads/.gitkeep               ← Placeholder para archivos subidos
```

### Base de datos (Supabase)

Se utilizan las tablas existentes:

- **`usuarios`** — id, matricula, password_hash, rol, primer_ingreso, fecha_registro
- **`egresados`** — usuario_id, nombre, apellido_paterno, apellido_materno, contacto (jsonb)
- **`empresas`** — usuario_id, nombre_comercial, contacto (jsonb)
- **`token_blacklist`** — Para tokens revocados (ya existía en el diseño original)

### Tecnologías

- PHP 8.x con `declare(strict_types=1)` en todos los archivos
- FlightPHP como micro-framework de enrutamiento
- Firebase JWT para tokens de autenticación
- PDO con prepared statements (sin queries raw)
- Transacciones para operaciones de escritura
- Contraseñas hasheadas con `PASSWORD_BCRYPT`
- CORS configurado con variable de entorno `FRONTEND_URL`

### Próximos pasos

- [ ] Instalar dependencias: `composer install`
- [ ] Configurar `.env` con credenciales reales
- [ ] Crear usuario de prueba para validar login
- [ ] Probar los 4 endpoints con Postman o curl
- [ ] Implementar módulo de ofertas de empleo (pendiente)
