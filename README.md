# BolsaLaboralBack

> **Proyecto desarrollado para el Hackathon DITI 2026 — Universidad Tecnológica de la Costa**

## Descripción

API backend para el sistema de **Bolsa de Trabajo** de la Universidad Tecnológica de la Costa. Permite la gestión de ofertas de empleo, registro de candidatos, postulaciones y seguimiento del proceso de selección.

### Entidades principales

| Entidad | Descripción |
|---------|-------------|
| **Ofertas** | Publicaciones de empleo con requisitos, descripción, empresa y estado |
| **Candidatos** | Aspirantes que buscan empleo, con perfil profesional y documentos |
| **Postulaciones** | Relación candidato ↔ oferta con estados de seguimiento |
| **Empresas** | Organizaciones que publican ofertas de empleo |

### Stack Tecnológico

- **PHP 8.x** con micro-framework **FlightPHP**
- **PostgreSQL** como motor de base de datos
- **JWT** para autenticación (Firebase JWT)
- **Phinx** para migraciones de base de datos
- **PHPMailer** para envío de correos transaccionales
- **vlucas/phpdotenv** para gestión de variables de entorno

## Instalación

1. Clona el repositorio:
   ```bash
   git clone <URL_DEL_REPO>
   cd BolsaLaboralBack
   ```

2. Instala las dependencias:
   ```bash
   composer install
   ```

3. Ejecuta el script de configuración:
   ```bash
   php setup.php
   ```

4. Edita el archivo `.env` con tus valores reales (basado en `.env.example`).

5. Ejecuta las migraciones de base de datos:
   ```bash
   vendor/bin/phinx migrate
   ```

6. Ejecuta la API:
   ```bash
   php -S localhost:8080 -t public
   ```

## Uso

- **URL base de la API:** `http://localhost:8080/api/v1`
- Si accedes desde otro dispositivo de la red, usa la IP local de tu PC en vez de `localhost`.

---

## Endpoints Principales

### Autenticación

- **POST /api/v1/login** — Login de usuario. Devuelve JWT access token + refresh token.
  ```json
  { "email": "usuario@email.com", "password": "tu_password" }
  ```

- **POST /api/v1/register** — Registro de nuevo candidato o empresa.

- **POST /api/v1/refresh-token** — Renueva el access token.

- **POST /api/v1/revoke-refresh-token** — Revoca un refresh token.

- **POST /api/v1/blacklist-token** — Añade un JWT a la blacklist.

- **POST /api/v1/validate-token** — Valida un token JWT sin requerir sesión.

- **POST /api/v1/request-password-reset** — Solicita recuperación de contraseña por email.

- **POST /api/v1/reset-password** — Cambia contraseña con token de recuperación.

### Ofertas de Empleo

- **GET /api/v1/ofertas** — Lista ofertas disponibles (pública o filtrada).
- **GET /api/v1/ofertas/{id}** — Detalle de una oferta.
- **POST /api/v1/ofertas** — Crea una nueva oferta (empresa autenticada).
- **PUT /api/v1/ofertas/{id}** — Actualiza una oferta.
- **DELETE /api/v1/ofertas/{id}** — Elimina/desactiva una oferta.
- **GET /api/v1/mis-ofertas** — Ofertas publicadas por la empresa autenticada.

### Postulaciones

- **POST /api/v1/postulaciones** — El candidato se postula a una oferta.
- **GET /api/v1/mis-postulaciones** — Postulaciones del candidato autenticado.
- **GET /api/v1/ofertas/{id}/postulaciones** — Postulaciones de una oferta (empresa).
- **PUT /api/v1/postulaciones/{id}/estado** — Cambia el estado de una postulación (pendiente → revisada → aceptada → rechazada).

### Candidatos

- **GET /api/v1/mi-perfil** — Perfil del candidato autenticado.
- **PUT /api/v1/mi-perfil** — Actualiza perfil del candidato.
- **POST /api/v1/mi-perfil/cv** — Sube archivo CV.

### Empresas

- **GET /api/v1/mi-empresa** — Perfil de la empresa autenticada.
- **PUT /api/v1/mi-empresa** — Actualiza perfil de la empresa.

### Administración

- **GET /api/v1/admin/usuarios** — Lista de usuarios (admin).
- **PUT /api/v1/admin/usuarios/{id}/rol** — Cambia rol de usuario.
- **GET /api/v1/admin/estadisticas** — Estadísticas del sistema.

---

## Notas sobre el uso de tokens

- El token JWT se envía en el header `Authorization: Bearer <token>` o como parámetro GET `?token=<token>`.
- El refresh token tiene vigencia de **1 mes**, se reutiliza mientras esté vigente y no revocado.
- Si un token está en la blacklist, será rechazado en cualquier endpoint protegido.

---

## Variables de Entorno

Crea un archivo `.env` en la raíz del proyecto basado en `.env.example`:

```env
API_VERSION_URL=api/v1
API_KEY=tu_clave_secreta_para_jwt
DB_HOST=localhost
DB_PORT=5432
DB_NAME=bolsa_laboral
DB_USER=postgres
DB_PASS=tu_password_postgres

# SMTP (recuperación de contraseña y notificaciones)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=tu-email@gmail.com
SMTP_PASSWORD=tu-password-de-app
SMTP_FROM_EMAIL=noreply@tudominio.com

# URL del frontend
FRONTEND_URL=http://localhost:3000

# Logo para emails (opcional)
LOGO_URL=https://tudominio.com/logo-bolsalaboral.png
LOGO_ALT=BolsaLaboral Logo
LOGO_WIDTH=60px
LOGO_HEIGHT=60px
```

---

## Migraciones

El proyecto utiliza **Phinx** para versionar la base de datos:

```bash
# Ejecutar migraciones pendientes
vendor/bin/phinx migrate

# Crear una nueva migración
vendor/bin/phinx create NuevaMigracion

# Revertir última migración
vendor/bin/phinx rollback
```

---

## Créditos

Desarrollado para el **Hackathon DITI 2026** — **Universidad Tecnológica de la Costa**.

Basado en la arquitectura y patrones de **SIEstBackend** (Armando Elias Frias Garcia).
