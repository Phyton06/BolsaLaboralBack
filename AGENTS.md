# Code Review Rules - BolsaLaboralBack

## PHP / Flight

- Use strict typing where possible
- Prefer PDO prepared statements
- Handle exceptions properly
- Return consistent JSON responses

## Naming

- camelCase for variables/methods
- PascalCase for class names
- snake_case for database columns
- kebab-case for file names

## Security

- Never expose raw SQL errors to client
- Use parameterized queries
- Validate all inputs
- Use auth middleware for protected routes

## Architecture

- Controllers use static methods (`public static function`)
- All write operations MUST use transactions (`beginTransaction`, `commit`, `rollBack`)
- Error handling MUST use the centralized `handleTransactionError` helper
- Every endpoint MUST return JSON via `Flight::json()`

## Domain Conventions

- Estados de postulación: `pendiente`, `revisada`, `aceptada`, `rechazada`
- Estados de oferta: `activa`, `pausada`, `cerrada`
- Roles de usuario: `candidato`, `empresa`, `admin`
