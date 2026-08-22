# Implementación de IA — BolsaLaboral Backend

> Documentación técnica de todas las features que involucran inteligencia artificial o algoritmos inteligentes en el sistema.

## Resumen ejecutivo

| Feature | Estado | ¿Usa IA externa? | Método real |
|---------|--------|-------------------|-------------|
| Matching laboral (5 dimensiones) | ✅ Producción | No | Algoritmo determinista |
| Optimización de biografía | 🔧 Placeholder | Sí (previsto) | Plantilla + perfil |
| Recomendaciones personalizadas | ✅ Producción | No | Reglas basadas en perfil |
| Chat asesor | 🔧 Placeholder | Sí (previsto) | Respuestas por keywords |
| Generación de preguntas | 🔧 Placeholder | Sí (previsto) | No genera nada aún |
| Generación de CV PDF | 🔧 Placeholder | No | Preview JSON, sin PDF |

---

## 1. Matching Laboral — 5 Dimensiones

**Endpoint:** `GET /vacantes/:id/match-detalle`
**Estado:** ✅ Producción — algoritmo completamente funcional
**Clase:** `VacantesController` → delega a `MatchingCalculator`

### Qué hace

Calcula un porcentaje de compatibilidad (0-100%) entre el perfil de un egresado y los requisitos de una vacante, usando 5 dimensiones independientes que se promedian.

### Input

- **Del egresado:** `habilidades` (JSONB), `periodo_egreso`, `carrera`
- **De la vacante:** `perfil_idoneo` (JSONB con `habilidades_requeridas`, `nivel_ingles`, `experiencia_min`, `carrera`)

### Las 5 dimensiones

#### Dimensión 1: Habilidades técnicas (calcularMatch)

```php
// Intersección case-insensitive entre habilidades del egresado y requeridas
$coincidencias = array_intersect(
    array_map('strtolower', $tecnicas),     // ["PHP", "SQL", "Docker"] → ["php", "sql", "docker"]
    array_map('strtolower', $requeridas)    // ["PHP", "MySQL"]         → ["php", "mysql"]
);
// Score = (coincidencias / total_requeridas) × 100
// Ejemplo: 1 match de 2 requeridas = 50%
```

**Fórmula:** `round(coincidencias / requeridas × 100)`
**Edge cases:**
- Sin habilidades requeridas → 0% (evita división por cero)
- Case insensitive: "PHP" = "php"
- No penaliza habilidades extra del egresado

#### Dimensión 2: Nivel de inglés (compararNivelesIngles)

```php
$niveles = ['A1' => 1, 'A2' => 2, 'B1' => 3, 'B2' => 4, 'C1' => 5, 'C2' => 6];
// Si el egresado tiene >= el nivel requerido → 100%
// Si no → proporción (ej: A1/B1 = 1/3 = 33%)
```

**Edge cases:**
- Nivel desconocido → 0%
- Nivel igual al requerido → 100%
- Nivel superior al requerido → 100% (no bonus extra)

#### Dimensión 3: Experiencia (matchExperiencia)

```php
$rangos = [
    '0-1 años' => 1,
    '1-2 años' => 2,
    '1-3 años' => 2,   // Tratado como 1-2
    '3-5 años' => 4,
    '5+ años'  => 5,
];
// Misma lógica: si experiencia >= requerida → 100%, si no → proporción
```

**Edge cases:**
- Periodo_egreso del egresado no está en los rangos → 0%
- `0-1 años` requerido y egresado sin experiencia → 100% (todo egresado tiene al menos 0)

#### Dimensión 4: Carrera (matchCarrera)

```php
// Comparación directa por nombre (case-insensitive substring)
$matchCarrera = (stripos($carreraEgresado, $carreraRequerida) !== false) ? 100 : 0;
```

**Resultado:** 100% o 0% — sin valores intermedios.
**Edge cases:**
- "Ingeniería en Software" vs "Software" → 100% (substring)
- "Ingeniería en Software" vs "Ingeniería Civil" → 0%

#### Dimensión 5: Soft skills (calcularSoftSkills)

```php
$softSkillsComunes = [
    'trabajo en equipo', 'comunicación', 'liderazgo',
    'resolución de problemas', 'proactividad', 'adaptabilidad',
    'creatividad', 'pensamiento crítico',
    'time management', 'teamwork', 'communication', 'leadership'
];
// Cuenta cuántas soft skills del egresado están en la lista de referencia
// Score = (coincidencias / 5) × 100, máximo 100%
```

**Edge cases:**
- Sin soft skills → 0%
- 5+ skills comunes → 100% (tope)
- Skills no estándar no cuentan (solo las de la lista)

### Score final

```php
$matchCalculado = round(($tecnico + $ingles + $experiencia + $carrera + $softSkills) / 5);
```

**Promedio simple de las 5 dimensiones.** Si ya existe postulación, usa el match guardado (evita recalcular).

### Feedback textual (generarFeedback)

Genera un string con recomendaciones basado en umbrales:
- Match ≥ 80%: "coincide muy bien"
- Match 60-79%: "buena coincidencia, áreas de oportunidad"
- Match < 60%: "no coincide completamente"
- Si habilidades técnicas < 60%: "reforzar habilidades técnicas"
- Si inglés < 60%: "nivel de inglés es alto"
- Si carrera = 0%: "carrera diferente, habilidades transferibles"

---

## 2. Optimización de Biografía

**Endpoint:** `POST /ia/cv/optimizar-biografia`
**Estado:** 🔧 Placeholder — genera texto con plantilla, no usa IA externa

### Qué hace actualmente

```php
// 1. Obtiene perfil del egresado (carrera, habilidades, trayectoria)
// 2. Construye string con plantilla:
$biografia = "Profesional en {$carrera} con dominio de " . implode(', ', $tecnicas);
$biografia .= ". Experiencia comprobada en {$ultimaExperiencia}";
$biografia .= ". Orientado a resultados con capacidad de aprendizaje continuo...";
```

### Input esperado (cuando IA esté conectada)

```json
{
  "texto_actual": "Soy desarrollador PHP con 2 años de experiencia"
}
```

### Output actual

```json
{
  "biografia_optimizada": "Profesional en Ingeniería en Software con dominio de PHP, SQL, Docker. Experiencia comprobada en desarrollo web. Orientado a resultados...",
  "longitud_original": 52,
  "longitud_optimizada": 143
}
```

### Para integrar IA real (roadmap)

1. Conectar con API de OpenAI/Claude
2. Prompt: "Reescribe esta biografía profesional para un CV de egresado universitario. Sé conciso, usa verbos de acción, destaca habilidades técnicas."
3. Enviar: `{ texto_actual, carrera, habilidades, trayectoria }` como contexto
4. Guardar respuesta en `egresados.biografia_ia`

---

## 3. Recomendaciones Personalizadas

**Endpoint:** `GET /ia/cv/recomendaciones`
**Estado:** ✅ Producción — algoritmo basado en reglas

### Qué hace

Analiza el perfil completo del egresado y genera 3 listas:

1. **Puntos fuertes** — basado en:
   - 3+ habilidades técnicas → "Dominio técnico en N tecnologías"
   - Puntaje técnico ≥ 80% → "Excelente puntaje técnico"
   - Puntaje cognitivo ≥ 80% → "Alto rendimiento cognitivo"
   - Inglés B2+ → "Nivel de inglés B2 o superior"
   - Sin datos → "Perfil en construcción"

2. **Puntos débiles** — basado en:
   - Sin foto → afecta visibilidad
   - Sin CV → genera CV automático
   - Sin prueba técnica → mejora match
   - Sin habilidades técnicas
   - Sin experiencia/proyectos

3. **Cursos sugeridos** — basado en:
   - Puntaje técnico < 70% → "Refuerzo técnico en [carrera]"
   - Puntaje cognitivo < 70% → "Desarrollo de habilidades cognitivas"
   - Postulaciones con match < 50% → "Mejora tu perfil para vacantes específicas"
   - Sin brechas → "Mantén tu perfil actualizado"

### Datos que recibe

- `egresados.habilidades` (JSONB)
- `egresados.trayectoria` (JSONB)
- `egresados.cv_drive_id`, `foto_drive_id` (null check)
- `evaluaciones` (puntajes por tipo)
- `postulaciones` (match_porcentaje, estatus)

### Cómo evita resultados absurdos

- Sin habilidades → punto débil claro, no inventa fortalezas
- Sin evaluaciones → "Perfil en construcción"
- Sin postulaciones → cursos genéricos de mantenimiento
- Solo usa datos reales de la DB, nunca inventa

---

## 4. Chat Asesor

**Endpoint:** `POST /ia/chat/asesor`
**Estado:** 🔧 Placeholder — match por keywords, no usa IA

### Qué hace actualmente

```php
// 1. Recibe mensaje del usuario + contexto de pantalla
// 2. Busca keywords en el mensaje:
$keywords = ['vacante', 'empleo', 'trabajo'] → respuesta sobre vacantes
$keywords = ['evaluaci', 'prueba', 'examen'] → respuesta sobre evaluaciones
$keywords = ['cv', 'curriculum'] → respuesta sobre CV
// 3. Si no matchea → respuesta genérica
```

### Input

```json
{
  "mensaje": "¿Cómo puedo buscar vacantes?",
  "contexto_pantalla": "inicio"
}
```

### Output

```json
{
  "respuesta": "Puedes buscar vacantes en la sección de 'Empleos'...",
  "contexto": "inicio"
}
```

### Para integrar IA real (roadmap)

1. Conectar con API de LLM
2. System prompt: "Eres el asesor de Bolsa Laboral UTC. Ayuda a egresados con vacantes, evaluaciones, CV y postulaciones."
3. Contexto: enviar perfil del egresado + vacantes disponibles
4. Mantener las 5 categorías como fallback si la IA falla

---

## 5. Generación de Preguntas por IA

**Endpoint:** `POST /admin/banco-preguntas/generar-ia`
**Estado:** 🔧 Placeholder — no genera nada

### Qué hace actualmente

```php
// 1. Valida carrera_id, cantidad (1-50), tipo_prueba
// 2. Verifica que la carrera exista
// 3. Retorna respuesta simulada:
$generadas[] = [
    'carrera' => $carrera['nombre'],
    'tipo_prueba' => $tipoPrueba,
    'estado' => 'pendiente_de_revision',
];
// NO inserta en banco_preguntas
```

### Para integrar IA real (roadmap)

1. Prompt: "Genera N preguntas de opción múltiple sobre [carrera] para prueba [tipo]. Formato JSON."
2. Insertar en `banco_preguntas` con `activo = false` (requiere revisión admin)
3. Validar que preguntas sean únicas (anti-duplicados)

---

## 6. Generación de CV PDF

**Endpoint:** `GET /egresado/cv/pdf`
**Estado:** 🔧 Placeholder — retorna preview JSON, no genera PDF

### Qué hace actualmente

1. Si `cv_drive_id` existe → retorna link de Google Drive
2. Si no → construye array con todos los datos del egresado y retorna como `preview`
3. NO genera archivo PDF

### Output

```json
{
  "pdf_url": null,
  "preview": {
    "nombre": "Juan Pérez",
    "carrera": "Ingeniería en Software",
    "biografia": "...",
    "habilidades_tecnicas": ["PHP", "SQL"],
    "trayectoria": [...]
  },
  "nota": "La generación de PDF requiere integración con Google Drive API"
}
```

### Para integrar PDF real (roadmap)

1. Usar Dompdf o TCPDF para generar PDF desde PHP
2. O integrar con Google Drive API para subir y compartir
3. Guardar `cv_drive_id` en DB después de generar

---

## Decisiones de diseño

### ¿Por qué algoritmo determinista para matching?

- **Transparencia:** El egresado puede entender por qué tiene X% de match
- **Explicabilidad en entrevista:** "Son 5 dimensiones con pesos iguales, cada una compara X contra Y"
- **Sin llamadas externas:** Respuesta instantánea, sin costo de API
- **Sin sesgos de modelo:** El matching es reproducible y auditable

### ¿Por qué placeholders para las demás features?

- El proyecto es un prototipo funcional (Hackathon DITI 2026)
- Las features de IA requieren API keys y costo por uso
- La arquitectura está preparada para integrar (interfaces claras)
- Los placeholders demuestran la funcionalidad sin dependencias externas

### Cómo manejo datos faltantes

- **Habilidades vacías:** score = 0%, punto débil en recomendaciones
- **Sin evaluaciones:** radar vacío, recomendación "completar evaluaciones"
- **Sin perfil_idoneo:** 400 "Perfil idóneo no disponible"
- **Carrera no encontrada:** score = 0% en dimensión carrera
- **Nivel inglés desconocido:** A1 por defecto (nivel más bajo)

### Cómo evito resultados absurdos

- Promedio de 5 dimensiones: un solo score alto no infla el total
- Sin perfil_idoneo → error explícito, no calcula
- Feedback contextual: si match < 60%, dice "aún puedes aplicar"
- Recomendaciones basadas en umbrales claros (70%, 80%, 50%)
