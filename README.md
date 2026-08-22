# BolsaLaboral Backend

> REST API for the university job board — JWT auth, AI-powered job matching, assessments, and PDF generation.

<details>
<summary>Version en español</summary>

## BolsaLaboral Backend

> API REST para la bolsa de trabajo universitaria — autenticación JWT, match laboral por IA, evaluaciones y generación de PDF.

### Funcionalidades

- **Autenticación JWT** con roles (egresado, empresa, admin)
- **Ofertas laborales** CRUD con filtros por carrera y ubicación
- **Match laboral por IA** — calcula compatibilidad entre egresado y vacante
- **Evaluaciones técnicas** con banco de preguntas y generación por IA
- **Optimización de biografía** por IA
- **Recomendaciones profesionales** personalizadas
- **Chat asesor** para orientación
- **Generación de CV** en PDF
- **Gestión de convenios** entre universidad y empresas
- **Migraciones** con Phinx

### Stack

- PHP 8.x
- FlightPHP (micro-framework)
- PostgreSQL
- Firebase PHP-JWT
- PHPMailer
- Phinx (migrations)

### Requisitos

- PHP >= 8.0 con extensiones: PDO, pgsql, mbstring, json, openssl
- PostgreSQL >= 14
- Composer

### Instalación

```bash
composer install
```

### Configuración

Copia `.env.example` a `.env` y configura:

```env
DB_HOST=localhost
DB_PORT=5432
DB_NAME=bolsa_laboral
DB_USER=postgres
DB_PASS=tu_password
API_KEY=tu_api_key
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=tu_email
SMTP_PASS=tu_password
```

### Base de datos

```bash
# Crear la base de datos
createdb -U postgres bolsa_laboral

# Aplicar esquema
psql -U postgres -d bolsa_laboral -f config/db.sql

# Cargar datos de prueba
psql -U postgres -d bolsa_laboral -f db/seed.sql
```

### Desarrollo

```bash
php -S localhost:8080 -t public/
```

### Endpoints principales

| Método | Ruta | Descripción |
|--------|------|-------------|
| POST | `/auth/login` | Iniciar sesión |
| POST | `/auth/logout` | Cerrar sesión |
| GET | `/perfil` | Obtener perfil |
| PUT | `/perfil` | Actualizar perfil |
| GET | `/ofertas` | Listar ofertas |
| POST | `/ofertas` | Crear oferta (empresa) |
| GET | `/postulaciones` | Mis postulaciones |
| POST | `/postulaciones` | Postularse a oferta |
| GET | `/evaluaciones` | Catálogo de evaluaciones |
| POST | `/evaluaciones/iniciar` | Iniciar evaluación |
| GET | `/radar` | Radar de empleabilidad |
| POST | `/ia/optimizar-biografia` | Optimizar bio por IA |
| POST | `/ia/recomendaciones` | Recomendaciones IA |
| POST | `/ia/chat` | Chat asesor IA |
| GET | `/admin/dashboard` | Dashboard admin |

### Estructura

```
├── app/
│   ├── controllers/    # Controladores (Auth, Perfil, Ofertas, etc.)
│   ├── Lib/            # Helpers y utilidades
│   ├── middleware/      # JWT, CORS, logging
│   └── services/       # Lógica de negocio
├── config/
│   ├── database.php    # Conexión PDO
│   ├── routes.php      # Rutas Flight
│   └── db.sql          # Esquema PostgreSQL
├── db/
│   ├── migrations/     # Migraciones Phinx
│   └── seed.sql        # Datos de prueba
├── public/
│   └── index.php       # Entry point
├── .env                # Configuración (no commitear)
└── composer.json
```

### Credenciales de prueba

| Rol | Matrícula | Contraseña |
|-----|-----------|------------|
| Egresado | `20240001` | `test1234` |
| Empresa | `EMP001` | `test1234` |
| Admin | `ADMIN01` | `test1234` |

### Repositorios relacionados

- [BolsaLaboralFront](https://github.com/phyton06/BolsaLaboralFront) — Frontend Angular 17

### Licencia

Proyecto académico — Universidad Tecnológica de la Costa

</details>

## Features

- **JWT authentication** with role-based access (egresado, empresa, admin)
- **Job listings** CRUD with career and location filters
- **AI job matching** — calculates graduate-to-vacancy compatibility
- **Technical assessments** with question bank and AI-generated questions
- **AI biography optimization** for graduate profiles
- **Personalized career recommendations**
- **Advisor chat** for professional guidance
- **PDF CV generation**
- **University-company agreement management**
- **Database migrations** with Phinx

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Language | PHP 8.x |
| Framework | FlightPHP |
| Database | PostgreSQL |
| Auth | Firebase PHP-JWT |
| Email | PHPMailer |
| Migrations | Phinx |

## Prerequisites

- PHP >= 8.0 with extensions: PDO, pgsql, mbstring, json, openssl
- PostgreSQL >= 14
- Composer

## Getting Started

```bash
# Install dependencies
composer install

# Configure environment
cp .env.example .env
# Edit .env with your database credentials

# Create database and load schema
createdb -U postgres bolsa_laboral
psql -U postgres -d bolsa_laboral -f config/db.sql
psql -U postgres -d bolsa_laboral -f db/seed.sql

# Start development server
php -S localhost:8080 -t public/
```

## Testing

```bash
composer test          # run all tests
composer test:unit     # unit tests only
```

| Suite | Tests | Assertions | What it covers |
|-------|-------|------------|----------------|
| MatchingCalculator | 33 | 47 | 5-dimension matching, edge cases, feedback |
| JwtService | 9 | 16 | Token generation, expiry, validation |
| Helpers | 12 | 14 | Folio format, input validation |
| Middleware | 8 | 8 | Token extraction, role checking |

**CI:** GitHub Actions runs tests on PHP 8.1/8.2/8.3 with PostgreSQL on every push and PR.

## Engineering Metrics

| Metric | Value |
|--------|-------|
| API health endpoint | ~190ms (warm) |
| API login (JWT) | ~550ms |
| Docker image | ~600MB (php:8.4-cli + extensions) |
| Deployment | Render free tier, auto-deploy from main |

## Environment Variables

```env
DB_HOST=localhost
DB_PORT=5432
DB_NAME=bolsa_laboral
DB_USER=postgres
DB_PASS=your_password
API_KEY=your_api_key
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your_email
SMTP_PASS=your_password
```

## API Endpoints

| Method | Route | Description |
|--------|-------|-------------|
| POST | `/auth/login` | Sign in |
| POST | `/auth/logout` | Sign out |
| GET | `/perfil` | Get profile |
| PUT | `/perfil` | Update profile |
| GET | `/ofertas` | List job offers |
| POST | `/ofertas` | Create offer (empresa) |
| GET | `/postulaciones` | My applications |
| POST | `/postulaciones` | Apply to offer |
| GET | `/evaluaciones` | Assessment catalog |
| POST | `/evaluaciones/iniciar` | Start assessment |
| GET | `/radar` | Employability radar |
| POST | `/ia/optimizar-biografia` | AI biography optimization |
| POST | `/ia/recomendaciones` | AI recommendations |
| POST | `/ia/chat` | AI advisor chat |
| GET | `/admin/dashboard` | Admin dashboard |

## Database

Schema file: [`config/db.sql`](config/db.sql)

Key tables:
- `usuarios` — users (egresados, empresas, admin)
- `vacantes` — job listings
- `postulaciones` — applications
- `evaluaciones` — assessments
- `banco_preguntas` — question bank
- `egresados` — graduate profiles
- `empresas` — company profiles

## Test Credentials

| Role | Matrícula | Password |
|------|-----------|----------|
| Egresado | `20240001` | `test1234` |
| Empresa | `EMP001` | `test1234` |
| Admin | `ADMIN01` | `test1234` |

## Project Structure

```
├── app/
│   ├── controllers/    # Controllers (Auth, Perfil, Ofertas, etc.)
│   ├── Lib/            # Helpers and utilities
│   ├── middleware/      # JWT, CORS, logging
│   └── services/       # Business logic
├── config/
│   ├── database.php    # PDO connection
│   ├── routes.php      # Flight routes
│   └── db.sql          # PostgreSQL schema
├── db/
│   ├── migrations/     # Phinx migrations
│   └── seed.sql        # Test data
├── public/
│   └── index.php       # Entry point
├── .env                # Configuration (do not commit)
└── composer.json
```

## Related Repos

- [BolsaLaboralFront](https://github.com/phyton06/BolsaLaboralFront) — Angular 17 frontend

## License

Academic project — Universidad Tecnológica de la Costa

## Architecture

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   Client    │────▶│  FlightPHP  │────▶│  PostgreSQL │
│   (Angular) │     │   (Router)  │     │  (Supabase) │
└─────────────┘     └──────┬──────┘     └─────────────┘
                           │
                    ┌──────┴──────┐
                    │  Services   │
                    │  (Business) │
                    └─────────────┘
```

- **Controllers** — Route handlers (Auth, Profile, Offers, Applications, Assessments, Admin, IA)
- **Services** — Business logic (JWT, AI matching, PDF generation, email)
- **Middleware** — JWT auth, CORS, logging
- **Migrations** — Phinx database migrations
- **[docs/](docs/)** — AI implementation details, security guide, API contract, technical docs
