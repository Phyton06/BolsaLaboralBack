# Manual de Instalación — BolsaLaboralBack

Este documento detalla los pasos para clonar, configurar y ejecutar el sistema de Bolsa de Trabajo.

## 1. Clonación e Instalación

### 1.1 Clonar Repositorio

```bash
git clone <URL_DEL_REPO>
cd BolsaLaboralBack
```

### 1.2 Instalar Dependencias PHP

El proyecto utiliza **Composer** para gestionar librerías. Ejecuta:

```bash
composer install
```

### 1.3 Habilitar Extensiones PHP

Edita tu archivo `php.ini` y asegúrate de que las siguientes extensiones estén habilitadas (sin `;` al inicio):

```ini
extension=gd
extension=zip
extension=pdo_pgsql
```

*Reinicia tu servidor web o terminal después de guardar cambios.*

---

## 2. Configuración de Entorno (.env)

El sistema **NO** guarda contraseñas en el código. Debes crear un archivo `.env` en la raíz del proyecto.

**Opción rápida:**
```bash
cp .env.example .env
```

Luego edita `.env` con tus valores reales.

**Variables Requeridas:**

```ini
# --- API ---
API_VERSION_URL=api/v1
API_KEY=tu_clave_secreta_para_jwt

# --- POSTGRESQL (Base de datos principal) ---
DB_HOST=localhost
DB_PORT=5432
DB_NAME=bolsa_laboral
DB_USER=postgres
DB_PASS=tu_password_postgres

# --- MAIL SERVER (Notificaciones SMTP) ---
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=tu_correo@gmail.com
SMTP_PASSWORD=tu_app_password
SMTP_FROM_EMAIL=tu_correo@gmail.com
SMTP_FROM_NAME="Bolsa de Trabajo UTC"

# --- URL del Frontend ---
FRONTEND_URL=http://localhost:3000

# --- Logo para emails (opcional) ---
LOGO_URL=
LOGO_ALT=BolsaLaboral Logo
LOGO_WIDTH=60px
LOGO_HEIGHT=60px
```

> **Nota de Seguridad**: Nunca subas el archivo `.env` al repositorio (ya está en `.gitignore`).

---

## 3. Configuración de Base de Datos

### 3.1 Crear la Base de Datos

```sql
CREATE DATABASE bolsa_laboral;
```

### 3.2 Ejecutar Migraciones con Phinx

```bash
# Ejecutar todas las migraciones pendientes
vendor/bin/phinx migrate

# Verificar estado de migraciones
vendor/bin/phinx status
```

### 3.3 Cargar Datos Semilla (opcional)

```bash
psql -U postgres -d bolsa_laboral -f db/seed.sql
```

---

## 4. Ejecutar el Servidor

### Desarrollo (servidor embebido de PHP)

```bash
php -S localhost:8080 -t public
```

### Producción (Apache / Nginx)

Configura el DocumentRoot para apuntar a la carpeta `public/` del proyecto.

**Ejemplo Apache (.htaccess ya incluido):**
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

---

## 5. Verificar Instalación

### Test de conexión a la API

```bash
curl http://localhost:8080/api/v1/health
```

Deberías recibir:
```json
{ "status": "ok", "service": "BolsaLaboralBack" }
```

### Test de base de datos

```bash
vendor/bin/phinx status
```

Deberías ver las migraciones ejecutadas con status `[up]`.

---

## 6. Scripts Disponibles

| Comando | Propósito |
|---------|-----------|
| `vendor/bin/phinx migrate` | Ejecuta migraciones pendientes |
| `vendor/bin/phinx create NombreMigracion` | Crea nueva migración |
| `vendor/bin/phinx rollback` | Revierte la última migración |
| `php setup.php` | Script de configuración inicial |

---

## 7. Solución de Problemas Comunes

### Error "Driver not found"

Verifica que `pdo_pgsql` esté habilitado en `php.ini`:
```bash
php -m | grep pgsql
```

### Error de Parsing del Archivo .env

Si encuentras:
```
Fatal error: Uncaught Dotenv\Exception\InvalidFileException: Failed to parse dotenv file
```

**Solución:**
1. Copia el archivo `.env.example` a `.env`: `cp .env.example .env`
2. Asegúrate de que:
   * No hay espacios alrededor del signo `=`
   * Los valores no están entre corchetes `[]`
   * No hay caracteres especiales sin comillas
   * Cada variable está en una línea separada

### Error SMTP

Verifica que `SMTP_PASSWORD` sea una "App Password" si usas Gmail con 2FA.

### Error de conexión a PostgreSQL

1. Verifica que el servicio de PostgreSQL esté corriendo:
   ```bash
   sudo systemctl status postgresql
   ```
2. Verifica las credenciales en `.env`
3. Prueba la conexión manual:
   ```bash
   psql -U postgres -d bolsa_laboral -c "SELECT 1"
   ```

### Migraciones fallidas

```bash
# Ver estado
vendor/bin/phinx status

# Forzar rollback si quedó en estado inconsistente
vendor/bin/phinx rollback -t 0

# Re-ejecutar
vendor/bin/phinx migrate
```

---

## 8. Estructura de Migraciones

Las migraciones se almacenan en `db/migrations/` y siguen el formato de Phinx:

```
db/migrations/
├── 20260115000001_create_usuarios.php
├── 20260115000002_create_candidatos.php
├── 20260115000003_create_empresas.php
├── 20260115000004_create_ofertas.php
├── 20260115000005_create_postulaciones.php
└── 20260115000006_create_token_tables.php
```
