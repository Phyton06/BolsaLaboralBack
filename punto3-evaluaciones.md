Elaboración del Punto 3 – Módulo Motor de Evaluaciones (EXAM)
Objetivo del módulo
El módulo de Evaluaciones permitirá que los egresados puedan:
•	Ver las pruebas disponibles.
•	Iniciar una evaluación.
•	Responder preguntas.
•	Finalizar la evaluación.
•	Obtener resultados.
•	Mostrar estadísticas en un gráfico radar.
________________________________________
Endpoints del módulo
1. Obtener catálogo de evaluaciones
Endpoint
GET /evaluaciones/catalogo
Función
Mostrar todas las evaluaciones disponibles para el usuario.
Ejemplo de respuesta
[
  {
    "id": 1,
    "nombre": "Evaluación de Programación",
    "tipo": "tecnica",
    "minutos": 30,
    "completada": false,
    "ultimo_puntaje": 0
  }
]
Qué debe hacer el backend
•	Consultar las evaluaciones en PostgreSQL.
•	Filtrar por carrera o división si es necesario.
•	Regresar la lista en formato JSON.
________________________________________
2. Iniciar evaluación
Endpoint
POST /evaluaciones/iniciar
Request
{
  "id_prueba": 1
}
Respuesta
{
  "evaluacion_id": 10,
  "preguntas": [
    {
      "id": 1,
      "pregunta": "¿Qué es una API?",
      "opciones": {
        "a": "Base de datos",
        "b": "Interfaz de programación",
        "c": "Servidor",
        "d": "Framework"
      }
    }
  ],
  "expira_en": "2026-05-01 14:00:00"
}
Qué debe hacer el backend
•	Verificar que la prueba exista.
•	Crear un intento de evaluación.
•	Obtener preguntas desde la base de datos.
•	Regresar preguntas y tiempo límite.
________________________________________
3. Guardar respuestas
Endpoint
POST /evaluaciones/respuesta
Request
{
  "evaluacion_id": 10,
  "pregunta_id": 1,
  "opcion": "b"
}
Respuesta
{
  "status": "saved"
}
Qué debe hacer el backend
•	Guardar la respuesta del usuario.
•	Validar que la pregunta pertenezca a la evaluación.
•	Permitir actualización si cambia la respuesta.
________________________________________
4. Finalizar evaluación
Endpoint
POST /evaluaciones/finalizar
Request
{
  "evaluacion_id": 10
}
Respuesta
{
  "puntaje_global": 85,
  "detalle_resultados": {
    "logica": 90,
    "backend": 80,
    "frontend": 85
  },
  "match_actualizado": true
}
Qué debe hacer el backend
•	Revisar respuestas correctas.
•	Calcular puntaje.
•	Generar estadísticas.
•	Guardar resultados.
•	Actualizar el perfil del usuario.
________________________________________
5. Radar de habilidades
Endpoint
GET /evaluaciones/radar
Respuesta
{
  "labels": ["Backend", "Frontend", "Lógica"],
  "alumno": [80, 70, 90],
  "promedio_carrera": [75, 65, 85]
}
Función
Mostrar datos para un gráfico tipo radar o spider chart.
________________________________________
Diseño de Base de Datos
Tabla: evaluaciones
CREATE TABLE evaluaciones (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(150),
    tipo VARCHAR(50),
    minutos INT
);
________________________________________
Tabla: preguntas
CREATE TABLE preguntas (
    id SERIAL PRIMARY KEY,
    evaluacion_id INT REFERENCES evaluaciones(id),
    pregunta TEXT,
    opcion_a TEXT,
    opcion_b TEXT,
    opcion_c TEXT,
    opcion_d TEXT,
    respuesta_correcta VARCHAR(1)
);
________________________________________
Tabla: intentos_evaluacion
CREATE TABLE intentos_evaluacion (
    id SERIAL PRIMARY KEY,
    usuario_id INT,
    evaluacion_id INT,
    fecha_inicio TIMESTAMP,
    fecha_fin TIMESTAMP,
    puntaje INT
);
________________________________________
Tabla: respuestas_usuario
CREATE TABLE respuestas_usuario (
    id SERIAL PRIMARY KEY,
    intento_id INT REFERENCES intentos_evaluacion(id),
    pregunta_id INT REFERENCES preguntas(id),
    respuesta VARCHAR(1)
);
________________________________________
Ejemplo de estructura en PHP Flight
Archivo de rutas
Flight::route('GET /api/v1/evaluaciones/catalogo', function(){
    // Obtener catálogo
});

Flight::route('POST /api/v1/evaluaciones/iniciar', function(){
    // Iniciar evaluación
});

Flight::route('POST /api/v1/evaluaciones/respuesta', function(){
    // Guardar respuesta
});

Flight::route('POST /api/v1/evaluaciones/finalizar', function(){
    // Finalizar evaluación
});

Flight::route('GET /api/v1/evaluaciones/radar', function(){
    // Datos radar
});
________________________________________
Flujo general del sistema
1.	El usuario entra al catálogo.
2.	Selecciona una evaluación.
3.	El sistema crea un intento.
4.	Se muestran preguntas.
5.	El usuario responde.
6.	Las respuestas se guardan.
7.	El usuario finaliza.
8.	El sistema calcula el puntaje.
9.	Se muestran resultados y radar.
________________________________________
Recomendaciones
•	Utilizar JWT para validar usuarios.
•	Validar tiempo límite de examen.
•	Evitar respuestas duplicadas.
•	Usar transacciones en PostgreSQL.
•	Separar controladores y modelos.
•	Proteger rutas con middleware.
________________________________________
Posible estructura de carpetas
api/
│
├── routes/
│   └── evaluaciones.php
│
├── controllers/
│   └── EvaluacionController.php
│
├── models/
│   └── EvaluacionModel.php
│
├── config/
│   └── database.php
│
└── index.php
________________________________________
Tecnologías sugeridas
Backend
•	PHP Flight
•	PostgreSQL 17
•	JWT
Frontend
•	Angular 17
•	Angular Material
•	Chart.js para radar
________________________________________
Conclusión
El módulo EXAM es uno de los componentes más importantes del sistema porque permite medir habilidades y generar estadísticas útiles para las empresas y egresados. La implementación debe enfocarse en seguridad, almacenamiento correcto de respuestas y generación precisa de resultados.
