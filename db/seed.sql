-- ============================================
-- SEED DATA - Bolsa Laboral UTC
-- Hackathon DITI 2026
-- ============================================
-- Carreras: ITI y Mercadotecnia
-- Pruebas: Técnicas, Psicométricas, Cognitivas, Proyectivas
-- ============================================
-- IMPORTANTE: Este seed requiere las tablas vacías o con datos mínimos.
-- Las tablas lookup (divisiones, carreras) se insertan primero.
-- ============================================

BEGIN;

-- ============================================
-- 0. LIMPIEZA (eliminar datos existentes de seed)
-- ============================================

DELETE FROM respuestas_detalle;
DELETE FROM banco_preguntas;
DELETE FROM evaluaciones;
DELETE FROM postulaciones;
DELETE FROM vacantes;
DELETE FROM egresados;
DELETE FROM empresas;
DELETE FROM config_pruebas;
DELETE FROM carreras;
DELETE FROM divisiones;
-- No borramos usuarios porque pueden tener FK dependientes ya creadas

-- Reset sequences to predictable values
SELECT setval('divisiones_id_seq', 1, false);
SELECT setval('carreras_id_seq', 1, false);
SELECT setval('usuarios_id_seq', 1, false);
SELECT setval('vacantes_id_seq', 1, false);
SELECT setval('postulaciones_id_seq', 1, false);
SELECT setval('evaluaciones_id_seq', 1, false);
SELECT setval('banco_preguntas_id_seq', 1, false);
SELECT setval('respuestas_detalle_id_seq', 1, false);
SELECT setval('config_pruebas_id_seq', 1, false);

-- ============================================
-- 1. DIVISIONES (lookup table)
-- ============================================

INSERT INTO divisiones (id, nombre) OVERRIDING SYSTEM VALUE VALUES
(1, 'Sistemas y Tecnología'),
(2, 'Ciencias Económico-Administrativas'),
(3, 'Ingeniería Industrial'),
(4, 'Ciencias Básicas');

-- ============================================
-- 2. CARRERAS (lookup table)
-- ============================================

INSERT INTO carreras (id, division_id, nombre, competencias_base) OVERRIDING SYSTEM VALUE VALUES
(1, 1, 'Ingeniería en Tecnologías de la Información e Innovación Digital',
'{"fundamentos": ["Programación", "Bases de datos", "Redes"], "especializacion": ["Desarrollo web", "Cloud computing", "Seguridad informática"]}'),
(2, 2, 'Licenciatura en Mercadotecnia',
'{"fundamentos": ["Investigación de mercados", "Comportamiento del consumidor", "Estadística"], "especializacion": ["Marketing digital", "Branding", "E-commerce"]}');

-- ============================================
-- 3. CONFIG_PRUEBAS
-- ============================================

INSERT INTO config_pruebas (id, tipo_prueba, duracion_minutos, cantidad_preguntas) OVERRIDING SYSTEM VALUE VALUES
(1, 'tecnica', 45, 15),
(2, 'psico', 30, 8),
(3, 'cogni', 35, 12),
(4, 'proy', 30, 10);

-- ============================================
-- 4. USUARIOS (15 total: 10 egresados, 3 empresas, 2 admin)
-- ============================================
-- IDs explícitos para poder referenciarlos en FKs

-- Egresados ITI (5) - IDs 1-5
INSERT INTO usuarios (id, matricula, password_hash, rol, primer_ingreso, fecha_registro) OVERRIDING SYSTEM VALUE VALUES
(1, '20240001', '$2y$10$gtYAjQVOakB7T4S.cBkM6.JJGbCouPFVBpks0JMZ/7HiD9t5gVmAi', 'egresado', false, NOW() - INTERVAL '90 days'),
(2, '20240002', '$2y$10$gtYAjQVOakB7T4S.cBkM6.JJGbCouPFVBpks0JMZ/7HiD9t5gVmAi', 'egresado', true, NOW() - INTERVAL '85 days'),
(3, '20240003', '$2y$10$gtYAjQVOakB7T4S.cBkM6.JJGbCouPFVBpks0JMZ/7HiD9t5gVmAi', 'egresado', true, NOW() - INTERVAL '80 days'),
(4, '20240004', '$2y$10$gtYAjQVOakB7T4S.cBkM6.JJGbCouPFVBpks0JMZ/7HiD9t5gVmAi', 'egresado', true, NOW() - INTERVAL '75 days'),
(5, '20240005', '$2y$10$gtYAjQVOakB7T4S.cBkM6.JJGbCouPFVBpks0JMZ/7HiD9t5gVmAi', 'egresado', true, NOW() - INTERVAL '70 days')
ON CONFLICT (id) DO UPDATE SET
  matricula = EXCLUDED.matricula,
  password_hash = EXCLUDED.password_hash,
  rol = EXCLUDED.rol,
  primer_ingreso = EXCLUDED.primer_ingreso;

-- Egresados Mercadotecnia (5) - IDs 6-10
INSERT INTO usuarios (id, matricula, password_hash, rol, primer_ingreso, fecha_registro) OVERRIDING SYSTEM VALUE VALUES
(6, '20240006', '$2y$10$gtYAjQVOakB7T4S.cBkM6.JJGbCouPFVBpks0JMZ/7HiD9t5gVmAi', 'egresado', true, NOW() - INTERVAL '65 days'),
(7, '20240007', '$2y$10$gtYAjQVOakB7T4S.cBkM6.JJGbCouPFVBpks0JMZ/7HiD9t5gVmAi', 'egresado', true, NOW() - INTERVAL '60 days'),
(8, '20240008', '$2y$10$gtYAjQVOakB7T4S.cBkM6.JJGbCouPFVBpks0JMZ/7HiD9t5gVmAi', 'egresado', true, NOW() - INTERVAL '55 days'),
(9, '20240009', '$2y$10$gtYAjQVOakB7T4S.cBkM6.JJGbCouPFVBpks0JMZ/7HiD9t5gVmAi', 'egresado', true, NOW() - INTERVAL '50 days'),
(10, '20240010', '$2y$10$gtYAjQVOakB7T4S.cBkM6.JJGbCouPFVBpks0JMZ/7HiD9t5gVmAi', 'egresado', true, NOW() - INTERVAL '45 days')
ON CONFLICT (id) DO UPDATE SET
  matricula = EXCLUDED.matricula,
  password_hash = EXCLUDED.password_hash,
  rol = EXCLUDED.rol,
  primer_ingreso = EXCLUDED.primer_ingreso;

-- Empresas (3) - IDs 11-13
INSERT INTO usuarios (id, matricula, password_hash, rol, primer_ingreso, fecha_registro) OVERRIDING SYSTEM VALUE VALUES
(11, 'EMP001', '$2y$10$gtYAjQVOakB7T4S.cBkM6.JJGbCouPFVBpks0JMZ/7HiD9t5gVmAi', 'empresa', false, NOW() - INTERVAL '120 days'),
(12, 'EMP002', '$2y$10$gtYAjQVOakB7T4S.cBkM6.JJGbCouPFVBpks0JMZ/7HiD9t5gVmAi', 'empresa', true, NOW() - INTERVAL '40 days'),
(13, 'EMP003', '$2y$10$gtYAjQVOakB7T4S.cBkM6.JJGbCouPFVBpks0JMZ/7HiD9t5gVmAi', 'empresa', true, NOW() - INTERVAL '35 days')
ON CONFLICT (id) DO UPDATE SET
  matricula = EXCLUDED.matricula,
  password_hash = EXCLUDED.password_hash,
  rol = EXCLUDED.rol,
  primer_ingreso = EXCLUDED.primer_ingreso;

-- Admins (2) - IDs 14-15
INSERT INTO usuarios (id, matricula, password_hash, rol, primer_ingreso, fecha_registro) OVERRIDING SYSTEM VALUE VALUES
(14, 'ADMIN01', '$2y$10$pfMJTRBuAU68QbWbf2iPeu54W6hV5frQEh6/Mwl39VucF1MBYeKrS', 'admin', false, NOW() - INTERVAL '150 days'),
(15, 'ADMIN02', '$2y$10$pfMJTRBuAU68QbWbf2iPeu54W6hV5frQEh6/Mwl39VucF1MBYeKrS', 'admin', true, NOW() - INTERVAL '30 days')
ON CONFLICT (id) DO UPDATE SET
  matricula = EXCLUDED.matricula,
  password_hash = EXCLUDED.password_hash,
  rol = EXCLUDED.rol,
  primer_ingreso = EXCLUDED.primer_ingreso;

-- Fix sequence
SELECT setval('usuarios_id_seq', (SELECT GREATEST(MAX(id), 1) FROM usuarios));

-- ============================================
-- 5. EGRESADOS (10 perfiles completos)
-- ============================================

-- ITI (carrera_id=1, division_id=1)
INSERT INTO egresados (usuario_id, nombre, apellido_paterno, apellido_materno, carrera_id, periodo_egreso, foto_drive_id, biografia_ia, contacto, trayectoria, habilidades, vistas_perfil, cv_drive_id) VALUES
(1, 'Juan Carlos', 'Hernández', 'López', 1, '2024-2025', NULL,
'Desarrollador Full Stack con experiencia en React y Node.js. Apasionado por la arquitectura de software y las soluciones escalables.',
'{"telefono": "755-101-0001", "correo_personal": "juan.hernandez@email.com", "linkedin": "linkedin.com/in/juanhernandez"}',
'[{"tipo": "practicas", "empresa": "TechCorp México", "descripcion": "Desarrollo de API REST con Node.js y PostgreSQL", "fecha": "2024-06"}, {"tipo": "proyecto", "empresa": "UTC", "descripcion": "Sistema de gestión escolar con React y Firebase", "fecha": "2024-03"}]',
'{"tecnicas": ["Python", "JavaScript", "React", "Node.js", "SQL", "AWS", "Docker", "Git"], "blandas": ["Trabajo en equipo", "Resolución de problemas", "Comunicación efectiva"], "idiomas": ["Español: Nativo", "Inglés: B2"]}',
42, NULL),

(2, 'María Fernanda', 'Gutiérrez', 'Ruiz', 1, '2024-2025', NULL,
'Especialista en ciencia de datos y machine learning. Experiencia en análisis predictivo y visualización de datos.',
'{"telefono": "755-101-0002", "correo_personal": "maria.gutierrez@email.com", "linkedin": "linkedin.com/in/mariagutierrez"}',
'[{"tipo": "practicas", "empresa": "Innovatech Solutions", "descripcion": "Análisis de datos con Python y Pandas", "fecha": "2024-07"}, {"tipo": "proyecto", "empresa": "UTC", "descripcion": "Modelo predictivo de ventas con scikit-learn", "fecha": "2024-04"}]',
'{"tecnicas": ["Python", "R", "SQL", "TensorFlow", "Pandas", "Tableau", "Machine Learning", "Estadística"], "blandas": ["Pensamiento analítico", "Comunicación de datos", "Liderazgo"], "idiomas": ["Español: Nativo", "Inglés: C1"]}',
38, NULL),

(3, 'Diego Alejandro', 'Morales', 'Vega', 1, '2024-2025', NULL,
'Desarrollador mobile con experiencia en React Native y Flutter. Enfocado en UX y rendimiento.',
'{"telefono": "755-101-0003", "correo_personal": "diego.morales@email.com", "linkedin": "linkedin.com/in/diegomorales"}',
'[{"tipo": "practicas", "empresa": "TechCorp México", "descripcion": "Desarrollo de app mobile con React Native", "fecha": "2024-05"}, {"tipo": "freelance", "empresa": "Independiente", "descripcion": "App de delivery para restaurante local", "fecha": "2024-02"}]',
'{"tecnicas": ["React Native", "Flutter", "Dart", "JavaScript", "Firebase", "REST APIs", "Git"], "blandas": ["Creatividad", "Atención al detalle", "Autogestión"], "idiomas": ["Español: Nativo", "Inglés: B1"]}',
27, NULL),

(4, 'Ana Sofía', 'Ramírez', 'Castillo', 1, '2024-2025', NULL,
'Ingeniera DevOps con experiencia en CI/CD, infraestructura como código y monitoreo de sistemas.',
'{"telefono": "755-101-0004", "correo_personal": "ana.ramirez@email.com", "linkedin": "linkedin.com/in/anaramirez"}',
'[{"tipo": "practicas", "empresa": "Innovatech Solutions", "descripcion": "Automatización de pipelines CI/CD con Jenkins", "fecha": "2024-06"}, {"tipo": "proyecto", "empresa": "UTC", "descripcion": "Infraestructura cloud con Terraform y AWS", "fecha": "2024-03"}]',
'{"tecnicas": ["AWS", "Docker", "Kubernetes", "Terraform", "Jenkins", "Linux", "Python", "Bash"], "blandas": ["Resolución de problemas", "Trabajo bajo presión", "Comunicación técnica"], "idiomas": ["Español: Nativo", "Inglés: B2"]}',
31, NULL),

(5, 'Luis Miguel', 'Torres', 'Mendoza', 1, '2024-2025', NULL,
'Desarrollador backend especializado en arquitecturas de microservicios y bases de datos distribuidas.',
'{"telefono": "755-101-0005", "correo_personal": "luis.torres@email.com", "linkedin": "linkedin.com/in/luistorres"}',
'[{"tipo": "practicas", "empresa": "TechCorp México", "descripcion": "Desarrollo de microservicios con Java Spring Boot", "fecha": "2024-07"}, {"tipo": "proyecto", "empresa": "UTC", "descripcion": "Sistema distribuido con Apache Kafka", "fecha": "2024-04"}]',
'{"tecnicas": ["Java", "Spring Boot", "PostgreSQL", "MongoDB", "Kafka", "Redis", "Docker", "Git"], "blandas": ["Pensamiento lógico", "Trabajo en equipo", "Documentación"], "idiomas": ["Español: Nativo", "Inglés: B2"]}',
19, NULL);

-- Mercadotecnia (carrera_id=2, division_id=2)
INSERT INTO egresados (usuario_id, nombre, apellido_paterno, apellido_materno, carrera_id, periodo_egreso, foto_drive_id, biografia_ia, contacto, trayectoria, habilidades, vistas_perfil, cv_drive_id) VALUES
(6, 'Valentina', 'Castillo', 'Ortiz', 2, '2024-2025', NULL,
'Especialista en marketing digital y growth hacking. Experiencia en campañas de performance y automatización.',
'{"telefono": "755-101-0006", "correo_personal": "valentina.castillo@email.com", "linkedin": "linkedin.com/in/valentinacastillo"}',
'[{"tipo": "practicas", "empresa": "Agencia Digital MX", "descripcion": "Gestión de campañas en Google Ads y Meta Ads", "fecha": "2024-06"}, {"tipo": "proyecto", "empresa": "UTC", "descripcion": "Estrategia de contenidos para startup local", "fecha": "2024-03"}]',
'{"tecnicas": ["Google Ads", "Meta Ads", "SEO", "SEM", "Google Analytics", "Email Marketing", "Copywriting", "Canva"], "blandas": ["Creatividad", "Análisis de datos", "Comunicación"], "idiomas": ["Español: Nativo", "Inglés: C1"]}',
45, NULL),

(7, 'Roberto Carlos', 'Núñez', 'Delgado', 2, '2024-2025', NULL,
'Experto en branding y posicionamiento de marca. Experiencia en estrategia de marca corporativa.',
'{"telefono": "755-101-0007", "correo_personal": "roberto.nunez@email.com", "linkedin": "linkedin.com/in/robertonunez"}',
'[{"tipo": "practicas", "empresa": "Constructora del Pacífico", "descripcion": "Rebranding corporativo y manual de marca", "fecha": "2024-05"}, {"tipo": "freelance", "empresa": "Independiente", "descripcion": "Identidad visual para 3 PyMEs locales", "fecha": "2024-02"}]',
'{"tecnicas": ["Branding", "Adobe Illustrator", "Photoshop", "Figma", "Estrategia de marca", "Investigación de mercado"], "blandas": ["Creatividad", "Liderazgo", "Presentaciones"], "idiomas": ["Español: Nativo", "Inglés: B2"]}',
33, NULL),

(8, 'Camila', 'Ríos', 'Vargas', 2, '2024-2025', NULL,
'Analista de mercado con enfoque en inteligencia de negocios y datos de consumo.',
'{"telefono": "755-101-0008", "correo_personal": "camila.rios@email.com", "linkedin": "linkedin.com/in/camiliarios"}',
'[{"tipo": "practicas", "empresa": "Innovatech Solutions", "descripcion": "Análisis de mercado para lanzamiento de producto tech", "fecha": "2024-07"}, {"tipo": "proyecto", "empresa": "UTC", "descripcion": "Estudio de mercado para industria turística regional", "fecha": "2024-04"}]',
'{"tecnicas": ["SPSS", "Excel avanzado", "Google Analytics", "Tableau", "Power BI", "Investigación cualitativa", "Encuestas"], "blandas": ["Pensamiento analítico", "Comunicación", "Gestión de proyectos"], "idiomas": ["Español: Nativo", "Inglés: B2"]}',
28, NULL),

(9, 'Fernando Javier', 'Mejía', 'Luna', 2, '2024-2025', NULL,
'Community manager y creador de contenido. Especialista en redes sociales y engagement.',
'{"telefono": "755-101-0009", "correo_personal": "fernando.mejia@email.com", "linkedin": "linkedin.com/in/fernandomejia"}',
'[{"tipo": "practicas", "empresa": "Agencia Digital MX", "descripcion": "Gestión de redes sociales para 5 marcas", "fecha": "2024-06"}, {"tipo": "freelance", "empresa": "Independiente", "descripcion": "Creación de contenido para influencers locales", "fecha": "2024-01"}]',
'{"tecnicas": ["Redes sociales", "Copywriting", "Canva", "Adobe Premiere", "TikTok Ads", "Instagram Reels", "Hootsuite"], "blandas": ["Creatividad", "Comunicación", "Adaptabilidad"], "idiomas": ["Español: Nativo", "Inglés: B1"]}',
52, NULL),

(10, 'Daniela Patricia', 'Vega', 'Soto', 2, '2024-2025', NULL,
'Especialista en e-commerce y comercio digital. Experiencia en optimización de conversiones y marketplaces.',
'{"telefono": "755-101-0010", "correo_personal": "daniela.vega@email.com", "linkedin": "linkedin.com/in/danielavega"}',
'[{"tipo": "practicas", "empresa": "TechCorp México", "descripcion": "Optimización de tienda online con Shopify", "fecha": "2024-07"}, {"tipo": "proyecto", "empresa": "UTC", "descripcion": "Estrategia de lanzamiento para producto en Amazon", "fecha": "2024-03"}]',
'{"tecnicas": ["Shopify", "WooCommerce", "Amazon Seller", "Google Ads", "Email Marketing", "CRO", "Google Analytics"], "blandas": ["Orientación a resultados", "Análisis de datos", "Negociación"], "idiomas": ["Español: Nativo", "Inglés: C1"]}',
37, NULL);

-- ============================================
-- 6. EMPRESAS (3 registros)
-- ============================================

INSERT INTO empresas (usuario_id, nombre_comercial, rfc, foto_drive_id, estatus_convenio, calificacion_ia, contacto) VALUES
(11, 'TechCorp México', 'TCM200101XXX', NULL, 'activo', 92,
'{"telefono": "55-2345-6789", "email": "contacto@techcorp.mx", "direccion": "Av. Insurgentes Sur 1234, CDMX", "sitio_web": "techcorp.mx"}'),
(12, 'Constructora del Pacífico Sur', 'CPS210601XXX', NULL, 'pendiente', NULL,
'{"telefono": "755-345-6789", "email": "rrhh@cpsur.com", "direccion": "Blvd. Costa Chica 567, Oaxaca", "sitio_web": "cpsur.com"}'),
(13, 'Innovatech Solutions', 'ISO220315XXX', NULL, 'activo', 88,
'{"telefono": "81-8765-4321", "email": "talento@innovatech.io", "direccion": "Av. Constitución 890, Monterrey", "sitio_web": "innovatech.io"}');

-- ============================================
-- 7. VACANTES (15 registros)
-- ============================================
-- vacantes usa: empresa_id → usuarios(id), division_destino → divisiones(id)

-- Vacantes ITI (division_destino=1)
INSERT INTO vacantes (id, empresa_id, titulo, descripcion, ubicacion, division_destino, perfil_idoneo, analisis_gemini, es_externa, url_externa, fecha_publicacion) OVERRIDING SYSTEM VALUE VALUES
(1, 11, 'Desarrollador Full Stack Junior', 'Buscamos desarrollador con conocimientos en React y Node.js para unirse a nuestro equipo de desarrollo de productos digitales.', 'CDMX', 1,
'{"carrera": "Ingeniería en Tecnologías de la Información", "experiencia_min": "0-1 años", "habilidades_requeridas": ["React", "Node.js", "SQL", "Git"], "nivel_ingles": "B2"}',
'Candidato ideal: dominio de JavaScript, experiencia con frameworks modernos, capacidad de trabajar en equipo ágil.', false, NULL, NOW() - INTERVAL '20 days'),
(2, 11, 'Ingeniero de Software Backend', 'Desarrollo de APIs y microservicios para plataforma SaaS. Stack: Java, Spring Boot, PostgreSQL.', 'Guadalajara', 1,
'{"carrera": "Ingeniería en Tecnologías de la Información", "experiencia_min": "1-2 años", "habilidades_requeridas": ["Java", "Spring Boot", "PostgreSQL", "Docker"], "nivel_ingles": "B2"}',
'Se valora experiencia en arquitecturas distribuidas y patrones de diseño.', false, NULL, NOW() - INTERVAL '15 days'),
(3, 13, 'Analista de Datos', 'Análisis de datos para toma de decisiones. Uso de Python, SQL y herramientas de visualización.', 'Monterrey', 1,
'{"carrera": "Ingeniería en Tecnologías de la Información", "experiencia_min": "0-1 años", "habilidades_requeridas": ["Python", "SQL", "Pandas", "Tableau"], "nivel_ingles": "B1"}',
'Candidato con fuerte base estadística y capacidad de comunicar insights.', false, NULL, NOW() - INTERVAL '10 days'),
(4, 11, 'Administrador de Sistemas', 'Gestión de infraestructura cloud AWS, monitoreo y automatización de procesos.', 'CDMX', 1,
'{"carrera": "Ingeniería en Tecnologías de la Información", "experiencia_min": "1-3 años", "habilidades_requeridas": ["AWS", "Linux", "Docker", "Terraform"], "nivel_ingles": "B2"}',
'Certificaciones AWS son un plus. Experiencia con Kubernetes deseable.', false, NULL, NOW() - INTERVAL '5 days'),
(5, 13, 'Desarrollador Mobile React Native', 'Desarrollo de aplicaciones móviles híbridas para iOS y Android.', 'Remoto', 1,
'{"carrera": "Ingeniería en Tecnologías de la Información", "experiencia_min": "0-2 años", "habilidades_requeridas": ["React Native", "JavaScript", "Firebase", "REST APIs"], "nivel_ingles": "B2"}',
'Se valora experiencia con publicación en App Store y Google Play.', false, NULL, NOW() - INTERVAL '3 days'),
(6, 11, 'Arquitecto de Software Sr', 'Diseño de arquitecturas escalables para plataforma de e-commerce. Liderazgo técnico de equipo.', 'CDMX', 1,
'{"carrera": "Ingeniería en Tecnologías de la Información", "experiencia_min": "5+ años", "habilidades_requeridas": ["Microservicios", "AWS", "Kubernetes", "Java"], "nivel_ingles": "C1"}',
'Perfil senior con experiencia en sistemas de alto tráfico y liderazgo.', false, NULL, NOW() - INTERVAL '2 days'),
(7, 13, 'DevOps Engineer', 'Automatización de CI/CD, gestión de infraestructura como código y monitoreo de sistemas.', 'Guadalajara', 1,
'{"carrera": "Ingeniería en Tecnologías de la Información", "experiencia_min": "1-3 años", "habilidades_requeridas": ["Jenkins", "Docker", "Kubernetes", "AWS"], "nivel_ingles": "B2"}',
'Experiencia con GitOps y herramientas de observabilidad es un plus.', false, NULL, NOW() - INTERVAL '1 day'),
(8, 11, 'QA Automation Engineer', 'Automatización de pruebas para aplicaciones web y mobile. Frameworks: Selenium, Cypress.', 'CDMX', 1,
'{"carrera": "Ingeniería en Tecnologías de la Información", "experiencia_min": "1-2 años", "habilidades_requeridas": ["Selenium", "Cypress", "JavaScript", "API Testing"], "nivel_ingles": "B2"}',
'Se valora experiencia con testing de APIs y metodologías ágiles.', false, NULL, NOW() - INTERVAL '25 days');

-- Vacantes Mercadotecnia (division_destino=2)
INSERT INTO vacantes (id, empresa_id, titulo, descripcion, ubicacion, division_destino, perfil_idoneo, analisis_gemini, es_externa, url_externa, fecha_publicacion) OVERRIDING SYSTEM VALUE VALUES
(9, 12, 'Especialista en Marketing Digital', 'Gestión de campañas de performance en Google Ads y Meta Ads. Optimización de ROI.', 'CDMX', 2,
'{"carrera": "Licenciatura en Mercadotecnia", "experiencia_min": "1-2 años", "habilidades_requeridas": ["Google Ads", "Meta Ads", "Google Analytics", "SEO"], "nivel_ingles": "B2"}',
'Candidato orientado a resultados con experiencia comprobable en campañas digitales.', false, NULL, NOW() - INTERVAL '18 days'),
(10, 13, 'Community Manager', 'Gestión de redes sociales corporativas. Creación de contenido y estrategias de engagement.', 'Remoto', 2,
'{"carrera": "Licenciatura en Mercadotecnia", "experiencia_min": "0-1 años", "habilidades_requeridas": ["Redes sociales", "Copywriting", "Canva", "Hootsuite"], "nivel_ingles": "B1"}',
'Creatividad y conocimiento de tendencias digitales son esenciales.', false, NULL, NOW() - INTERVAL '12 days'),
(11, 12, 'Analista de Mercado', 'Investigación de mercados, análisis de competencia y elaboración de informes estratégicos.', 'Guadalajara', 2,
'{"carrera": "Licenciatura en Mercadotecnia", "experiencia_min": "1-2 años", "habilidades_requeridas": ["SPSS", "Excel avanzado", "Power BI", "Investigación de mercado"], "nivel_ingles": "B2"}',
'Se valora experiencia en estudios cuantitativos y cualitativos.', false, NULL, NOW() - INTERVAL '8 days'),
(12, 13, 'Brand Manager', 'Desarrollo de estrategia de marca, posicionamiento y gestión de identidad corporativa.', 'CDMX', 2,
'{"carrera": "Licenciatura en Mercadotecnia", "experiencia_min": "3-5 años", "habilidades_requeridas": ["Branding", "Estrategia de marca", "Adobe Creative Suite", "Investigación de mercado"], "nivel_ingles": "C1"}',
'Perfil senior con experiencia en gestión de marcas reconocidas.', false, NULL, NOW() - INTERVAL '6 days'),
(13, 12, 'SEO/SEM Specialist', 'Optimización de motores de búsqueda y gestión de campañas de pago por click.', 'Monterrey', 2,
'{"carrera": "Licenciatura en Mercadotecnia", "experiencia_min": "1-3 años", "habilidades_requeridas": ["SEO técnico", "Google Ads", "Ahrefs", "Google Search Console"], "nivel_ingles": "B2"}',
'Certificaciones de Google Ads y Analytics son requeridas.', false, NULL, NOW() - INTERVAL '4 days'),
(14, 13, 'Content Strategist', 'Diseño de estrategia de contenidos para blog, redes y email marketing.', 'Remoto', 2,
'{"carrera": "Licenciatura en Mercadotecnia", "experiencia_min": "1-2 años", "habilidades_requeridas": ["Copywriting", "SEO", "Email Marketing", "WordPress"], "nivel_ingles": "B2"}',
'Habilidad de escritura impecable y conocimiento de SEO son esenciales.', false, NULL, NOW() - INTERVAL '2 days'),
(15, 12, 'Growth Marketing Manager', 'Liderar estrategia de crecimiento: adquisición, retención y monetización.', 'CDMX', 2,
'{"carrera": "Licenciatura en Mercadotecnia", "experiencia_min": "3-5 años", "habilidades_requeridas": ["Growth hacking", "Analytics", "A/B testing", "Automatización"], "nivel_ingles": "C1"}',
'Perfil estratégico con experiencia en startups o empresas de alto crecimiento.', false, NULL, NOW() - INTERVAL '1 day');

SELECT setval('vacantes_id_seq', (SELECT MAX(id) FROM vacantes));

-- ============================================
-- 8. POSTULACIONES (25 registros)
-- ============================================
-- postulaciones usa: egresado_id → usuarios(id), vacante_id → vacantes(id)

INSERT INTO postulaciones (egresado_id, vacante_id, match_porcentaje, estatus, fecha) OVERRIDING SYSTEM VALUE VALUES
-- ITI -> Vacantes ITI
(1, 1, 92, 'aceptada', NOW() - INTERVAL '15 days'),
(1, 2, 85, 'revisada', NOW() - INTERVAL '10 days'),
(2, 3, 78, 'pendiente', NOW() - INTERVAL '9 days'),
(2, 1, 68, 'rechazada', NOW() - INTERVAL '20 days'),
(3, 5, 90, 'aceptada', NOW() - INTERVAL '6 days'),
(3, 7, 82, 'revisada', NOW() - INTERVAL '5 days'),
(4, 7, 75, 'pendiente', NOW() - INTERVAL '2 days'),
(4, 4, 60, 'rechazada', NOW() - INTERVAL '3 days'),
(5, 2, 88, 'aceptada', NOW() - INTERVAL '13 days'),
(5, 6, 79, 'revisada', NOW() - INTERVAL '7 days'),
-- Mercadotecnia -> Vacantes Mercadotecnia
(6, 9, 85, 'pendiente', NOW() - INTERVAL '1 day'),
(6, 14, 91, 'aceptada', NOW() - INTERVAL '3 days'),
(7, 12, 83, 'revisada', NOW() - INTERVAL '2 days'),
(7, 9, 62, 'rechazada', NOW() - INTERVAL '9 days'),
(8, 11, 87, 'aceptada', NOW() - INTERVAL '1 day'),
(8, 10, 74, 'pendiente', NOW() - INTERVAL '18 hours'),
(9, 10, 89, 'aceptada', NOW() - INTERVAL '4 days'),
(9, 14, 76, 'revisada', NOW() - INTERVAL '6 days'),
(10, 15, 93, 'pendiente', NOW() - INTERVAL '19 hours'),
(10, 12, 86, 'aceptada', NOW() - INTERVAL '9 days'),
-- Cross-aplicaciones
(1, 3, 55, 'pendiente', NOW() - INTERVAL '1 day'),
(6, 15, 48, 'revisada', NOW() - INTERVAL '3 days'),
(2, 5, 42, 'rechazada', NOW() - INTERVAL '7 days'),
(8, 13, 71, 'pendiente', NOW() - INTERVAL '4 days'),
(5, 8, 58, 'rechazada', NOW() - INTERVAL '15 days');

SELECT setval('postulaciones_id_seq', (SELECT MAX(id) FROM postulaciones));

-- ============================================
-- 9. EVALUACIONES (ligadas a egresado)
-- ============================================
-- El esquema real: evaluaciones tiene egresado_id, tipo_prueba, puntaje_global, etc.
-- Las evaluaciones representan pruebas completadas por un egresado específico.

INSERT INTO evaluaciones (egresado_id, tipo_prueba, puntaje_global, detalle_resultados, es_base, fecha_inicio, fecha_fin) OVERRIDING SYSTEM VALUE VALUES
-- Juan Carlos (1) - completó técnica, cognitiva, psico
(1, 'tecnica', 87, '{"correctas": 13, "incorrectas": 2, "categorias": {"algoritmos": 8, "bases_datos": 5}}', true, NOW() - INTERVAL '30 days', NOW() - INTERVAL '30 days' + INTERVAL '42 minutes'),
(1, 'cogni', 92, '{"correctas": 11, "incorrectas": 1, "razonamiento": 95, "numerico": 89}', true, NOW() - INTERVAL '29 days', NOW() - INTERVAL '29 days' + INTERVAL '30 minutes'),
(1, 'psico', NULL, '{"personalidad": "extrovertido", "trabajo_equipo": 4, "presion": 5}', true, NOW() - INTERVAL '28 days', NOW() - INTERVAL '28 days' + INTERVAL '25 minutes'),

-- María Fernanda (2) - completó técnica ITI, proyectiva
(2, 'tecnica', 73, '{"correctas": 11, "incorrectas": 4, "categorias": {"algoritmos": 6, "bases_datos": 5}}', true, NOW() - INTERVAL '27 days', NOW() - INTERVAL '27 days' + INTERVAL '40 minutes'),
(2, 'proy', NULL, '{"estabilidad": 4, "liderazgo": 3, "adaptabilidad": 5}', true, NOW() - INTERVAL '26 days', NOW() - INTERVAL '26 days' + INTERVAL '28 minutes'),

-- Valentina (6) - Mercadotecnia: completó técnica, psico
(6, 'tecnica', 93, '{"correctas": 14, "incorrectas": 1, "categorias": {"metricas": 8, "campanas": 6}}', true, NOW() - INTERVAL '25 days', NOW() - INTERVAL '25 days' + INTERVAL '43 minutes'),
(6, 'psico', NULL, '{"personalidad": "lider", "trabajo_equipo": 5, "presion": 4}', true, NOW() - INTERVAL '24 days', NOW() - INTERVAL '24 days' + INTERVAL '22 minutes'),
(6, 'cogni', 85, '{"correctas": 10, "incorrectas": 2, "razonamiento": 83, "numerico": 87}', true, NOW() - INTERVAL '23 days', NOW() - INTERVAL '23 days' + INTERVAL '32 minutes'),

-- Roberto (7) - Mercadotecnia: completó proyectiva, cognitiva
(7, 'proy', NULL, '{"estabilidad": 3, "liderazgo": 4, "adaptabilidad": 4}', true, NOW() - INTERVAL '22 days', NOW() - INTERVAL '22 days' + INTERVAL '26 minutes'),
(7, 'cogni', 78, '{"correctas": 9, "incorrectas": 3, "razonamiento": 75, "numerico": 81}', true, NOW() - INTERVAL '21 days', NOW() - INTERVAL '21 days' + INTERVAL '33 minutes'),

-- Diego (3) - solo cognitiva
(3, 'cogni', 88, '{"correctas": 10, "incorrectas": 2, "razonamiento": 90, "numerico": 86}', true, NOW() - INTERVAL '20 days', NOW() - INTERVAL '20 days' + INTERVAL '31 minutes'),

-- Daniela (10) - técnica mercadotecnia
(10, 'tecnica', 80, '{"correctas": 12, "incorrectas": 3, "categorias": {"metricas": 7, "campanas": 5}}', true, NOW() - INTERVAL '19 days', NOW() - INTERVAL '19 days' + INTERVAL '44 minutes');

SELECT setval('evaluaciones_id_seq', (SELECT MAX(id) FROM evaluaciones));

-- ============================================
-- 10. BANCO_PREGUNTAS (60 preguntas cuantitativas)
-- ============================================
-- banco_preguntas usa: division_id, carrera_id, tipo_prueba
-- carrera_id=1 → ITI, carrera_id=2 → Mercadotecnia
-- division_id=1 → Sistemas, division_id=2 → Económico-Administrativas

-- Técnicas ITI (carrera_id=1, division_id=1, tipo_prueba='tecnica')
INSERT INTO banco_preguntas (division_id, carrera_id, tipo_prueba, pregunta, opciones, respuesta_correcta, activo) OVERRIDING SYSTEM VALUE VALUES
(1, 1, 'tecnica', '¿Cuál es la complejidad temporal de un algoritmo de búsqueda binaria en un arreglo ordenado de n elementos?',
'{"a": "O(n)", "b": "O(log n)", "c": "O(n²)", "d": "O(1)"}', 'b', true),

(1, 1, 'tecnica', 'Si un servidor procesa 1,000 requests por segundo, ¿cuántos requests procesa en 5 minutos?',
'{"a": "50,000", "b": "100,000", "c": "300,000", "d": "600,000"}', 'c', true),

(1, 1, 'tecnica', '¿Cuántas comparaciones como máximo necesita un Merge Sort para ordenar un arreglo de 8 elementos?',
'{"a": "8", "b": "16", "c": "24", "d": "32"}', 'c', true),

(1, 1, 'tecnica', 'Si una función recursiva tiene profundidad de recursión de 10 y en cada nivel hace 3 llamadas, ¿cuántas llamadas totales se ejecutan (incluyendo la primera)?',
'{"a": "30", "b": "59,049", "c": "88,573", "d": "3^10"}', 'c', true),

(1, 1, 'tecnica', '¿Cuál es el número máximo de nodos en un árbol binario de altura 4 (raíz en nivel 0)?',
'{"a": "15", "b": "16", "c": "31", "d": "32"}', 'c', true),

(1, 1, 'tecnica', 'Si una cola (queue) tiene 20 elementos y se realizan 8 dequeues y 12 enqueues, ¿cuántos elementos quedan?',
'{"a": "16", "b": "24", "c": "28", "d": "40"}', 'b', true),

(1, 1, 'tecnica', '¿Cuántas permutaciones existen para un arreglo de 5 elementos distintos?',
'{"a": "25", "b": "120", "c": "720", "d": "3,125"}', 'b', true),

(1, 1, 'tecnica', 'Si un algoritmo tiene complejidad O(n log n) y n=1024, ¿cuántas operaciones aproximadas realiza?',
'{"a": "1,024", "b": "10,240", "c": "102,400", "d": "1,048,576"}', 'b', true),

(1, 1, 'tecnica', 'En una tabla hash con 100 espacios y 75 elementos insertados, ¿cuál es el factor de carga?',
'{"a": "0.25", "b": "0.50", "c": "0.75", "d": "1.33"}', 'c', true),

(1, 1, 'tecnica', '¿Cuántas aristas tiene un grafo completo de 6 nodos?',
'{"a": "6", "b": "12", "c": "15", "d": "30"}', 'c', true),

(1, 1, 'tecnica', 'Si un proceso tiene un tiempo de ejecución de 250ms y se optimiza reduciendo un 40% su complejidad, ¿cuál es el nuevo tiempo?',
'{"a": "100ms", "b": "150ms", "c": "175ms", "d": "200ms"}', 'b', true),

(1, 1, 'tecnica', '¿Cuántos bits se necesitan para representar 256 valores distintos?',
'{"a": "4", "b": "8", "c": "16", "d": "256"}', 'b', true),

(1, 1, 'tecnica', 'Si un arreglo de 1,000 elementos se busca secuencialmente, ¿cuántas comparaciones se hacen en promedio para encontrar un elemento?',
'{"a": "250", "b": "500", "c": "750", "d": "1,000"}', 'b', true),

(1, 1, 'tecnica', '¿Cuál es el valor de 2^16?',
'{"a": "32,768", "b": "65,536", "c": "131,072", "d": "256,000"}', 'b', true),

(1, 1, 'tecnica', 'Si una API tiene un rate limit de 100 requests/minuto y tu app hace 5 requests/segundo, ¿cuántos segundos puedes operar antes de ser bloqueado?',
'{"a": "10", "b": "20", "c": "30", "d": "60"}', 'b', true),

-- Técnicas ITI - Bases de Datos y Redes (carrera_id=1, division_id=1, tipo_prueba='tecnica')
(1, 1, 'tecnica', 'Si una tabla tiene 500 registros y otra tiene 300, y haces INNER JOIN con 50 coincidencias, ¿cuántos registros devuelve el resultado?',
'{"a": "50", "b": "300", "c": "500", "d": "800"}', 'a', true),

(1, 1, 'tecnica', 'Si un servidor tiene un uptime del 99.9%, ¿cuántos minutos de downtime tiene aproximadamente al mes (30 días)?',
'{"a": "4.38", "b": "43.8", "c": "86.4", "d": "144"}', 'b', true),

(1, 1, 'tecnica', '¿Cuántas combinaciones posibles tiene una contraseña de 4 dígitos numéricos (0-9)?',
'{"a": "1,000", "b": "4,096", "c": "10,000", "d": "40,000"}', 'c', true),

(1, 1, 'tecnica', 'Si una base de datos tiene 1,000,000 de registros y un índice B-tree tiene factor de ramificación 100, ¿cuántos niveles como máximo tiene el árbol?',
'{"a": "2", "b": "3", "c": "4", "d": "5"}', 'b', true),

(1, 1, 'tecnica', 'Si una consulta SQL tarda 200ms sin índice y 15ms con índice, ¿cuál es la mejora porcentual?',
'{"a": "85%", "b": "90%", "c": "92.5%", "d": "95%"}', 'c', true),

(1, 1, 'tecnica', '¿Cuántos bytes tiene un paquete TCP con payload de 1,460 bytes, encabezado TCP de 20 bytes e IP de 20 bytes?',
'{"a": "1,460", "b": "1,480", "c": "1,500", "d": "1,520"}', 'c', true),

(1, 1, 'tecnica', 'Si una API REST devuelve 50 registros por página y hay 1,237 registros totales, ¿cuántas páginas se necesitan?',
'{"a": "24", "b": "25", "c": "26", "d": "27"}', 'b', true),

(1, 1, 'tecnica', '¿Cuántas direcciones IP utilizables tiene una red con máscara 255.255.255.0?',
'{"a": "128", "b": "254", "c": "256", "d": "512"}', 'b', true),

(1, 1, 'tecnica', 'Si un backup completo tarda 2 horas y un incremental tarda 15 minutos, y se hacen 6 incrementales por semana más 1 completo, ¿cuánto tiempo total de backup semanal hay?',
'{"a": "2h 15min", "b": "2h 30min", "c": "3h 30min", "d": "4h 30min"}', 'c', true),

(1, 1, 'tecnica', '¿Cuál es el throughput en Mbps de una conexión que transfiere 500 MB en 20 segundos?',
'{"a": "100", "b": "200", "c": "400", "d": "800"}', 'b', true),

-- Técnicas Mercadotecnia - Métricas y KPIs (carrera_id=2, division_id=2, tipo_prueba='tecnica')
(2, 2, 'tecnica', 'Si una campaña tiene un CTR del 2.5% y 10,000 impresiones, ¿cuántos clicks tuvo?',
'{"a": "25", "b": "250", "c": "2,500", "d": "25,000"}', 'b', true),

(2, 2, 'tecnica', '¿Cuál es el ROI de una inversión de $5,000 que generó $7,500 en ventas?',
'{"a": "50%", "b": "100%", "c": "150%", "d": "200%"}', 'a', true),

(2, 2, 'tecnica', 'Si gastaste $2,000 en ads y obtuviste 40 conversiones, ¿cuál es el CPA (Costo por Adquisición)?',
'{"a": "$20", "b": "$40", "c": "$50", "d": "$80"}', 'c', true),

(2, 2, 'tecnica', 'Si tu tasa de conversión es del 3% y tuviste 500 visitas, ¿cuántas conversiones obtuviste?',
'{"a": "15", "b": "30", "c": "50", "d": "150"}', 'a', true),

(2, 2, 'tecnica', 'Si asignas 40% del presupuesto a Google Ads y tu presupuesto total es $10,000, ¿cuánto se asigna a Google Ads?',
'{"a": "$2,000", "b": "$4,000", "c": "$6,000", "d": "$8,000"}', 'b', true),

(2, 2, 'tecnica', 'Si un email marketing tiene 5,000 enviados, 2,500 abiertos y 125 clicks, ¿cuál es la tasa de click-through sobre aperturas?',
'{"a": "2.5%", "b": "5%", "c": "10%", "d": "25%"}', 'b', true),

(2, 2, 'tecnica', 'Si el costo por click (CPC) es de $3.50 y tu presupuesto diario es $350, ¿cuántos clicks puedes comprar?',
'{"a": "50", "b": "100", "c": "150", "d": "200"}', 'b', true),

(2, 2, 'tecnica', 'Si una landing page tiene 800 visitantes y 48 conversiones, ¿cuál es la tasa de conversión?',
'{"a": "4%", "b": "6%", "c": "8%", "d": "10%"}', 'b', true),

(2, 2, 'tecnica', 'Si el CPM (costo por mil impresiones) es de $5 y quieres 50,000 impresiones, ¿cuánto cuesta?',
'{"a": "$125", "b": "$250", "c": "$500", "d": "$1,000"}', 'b', true),

(2, 2, 'tecnica', 'Si tu tasa de rebote es del 45% y recibiste 2,000 visitas, ¿cuántos usuarios abandonaron sin interactuar?',
'{"a": "450", "b": "900", "c": "1,100", "d": "1,550"}', 'b', true),

(2, 2, 'tecnica', 'Si una campaña genera 200 leads y 30 se convierten en clientes, ¿cuál es la tasa de conversión de leads?',
'{"a": "10%", "b": "15%", "c": "20%", "d": "25%"}', 'b', true),

(2, 2, 'tecnica', 'Si el valor de vida del cliente (LTV) es de $500 y el CAC es de $125, ¿cuál es la relación LTV/CAC?',
'{"a": "2:1", "b": "3:1", "c": "4:1", "d": "5:1"}', 'c', true),

(2, 2, 'tecnica', 'Si una publicación en redes sociales tiene 1,200 likes de 15,000 seguidores, ¿cuál es la tasa de engagement?',
'{"a": "6%", "b": "8%", "c": "10%", "d": "12%"}', 'b', true),

(2, 2, 'tecnica', 'Si el costo de adquisición de un cliente es $80 y genera $320 en ingresos, ¿cuál es el margen de ganancia porcentual sobre la inversión?',
'{"a": "200%", "b": "300%", "c": "400%", "d": "500%"}', 'b', true),

(2, 2, 'tecnica', 'Si una campaña de influencers tiene 50,000 reach, 5% engagement y 2% conversión sobre engagement, ¿cuántas conversiones se esperan?',
'{"a": "25", "b": "50", "c": "100", "d": "250"}', 'b', true),

-- Técnicas Mercadotecnia - Campañas y Presupuesto (carrera_id=2, division_id=2, tipo_prueba='tecnica')
(2, 2, 'tecnica', 'Si el presupuesto mensual de marketing es $60,000 y se distribuye: 30% digital, 25% eventos, 20% tradicional, 25% otros, ¿cuánto es para digital?',
'{"a": "$12,000", "b": "$15,000", "c": "$18,000", "d": "$24,000"}', 'c', true),

(2, 2, 'tecnica', 'Si una campaña de email tiene 10,000 suscriptores, 40% open rate y 10% de los que abren hacen click, ¿cuántos clicks hay?',
'{"a": "100", "b": "200", "c": "400", "d": "1,000"}', 'c', true),

(2, 2, 'tecnica', 'Si el ticket promedio es de $250 y quieres alcanzar $50,000 en ventas, ¿cuántas ventas necesitas?',
'{"a": "100", "b": "150", "c": "200", "d": "250"}', 'c', true),

(2, 2, 'tecnica', 'Si una campaña de Facebook Ads tiene $500 de presupuesto, CPC de $0.50 y CTR del 2%, ¿cuántas impresiones se necesitan?',
'{"a": "10,000", "b": "25,000", "c": "50,000", "d": "100,000"}', 'c', true),

(2, 2, 'tecnica', 'Si el margen de ganancia es del 35% y quieres una ganancia neta de $7,000, ¿cuánto debes vender?',
'{"a": "$14,000", "b": "$20,000", "c": "$24,500", "d": "$35,000"}', 'b', true),

(2, 2, 'tecnica', 'Si una empresa tiene 1,000 clientes y pierde 50 al mes, ¿cuál es la tasa de churn mensual?',
'{"a": "2%", "b": "5%", "c": "10%", "d": "15%"}', 'b', true),

(2, 2, 'tecnica', 'Si el costo de producción de un producto es $120 y se vende a $200, ¿cuál es el margen de ganancia?',
'{"a": "20%", "b": "40%", "c": "60%", "d": "66.6%"}', 'b', true),

(2, 2, 'tecnica', 'Si una campaña de Google Ads tiene $1,000 de presupuesto y el CPC promedio sube de $2 a $2.50, ¿cuántos clicks pierdes?',
'{"a": "50", "b": "100", "c": "150", "d": "200"}', 'b', true),

(2, 2, 'tecnica', 'Si el 60% de tus ventas vienen de clientes recurrentes y tienes 500 ventas totales, ¿cuántas son de clientes nuevos?',
'{"a": "100", "b": "200", "c": "300", "d": "400"}', 'b', true),

(2, 2, 'tecnica', 'Si una promoción reduce el precio de $500 a $375, ¿cuál es el porcentaje de descuento?',
'{"a": "20%", "b": "25%", "c": "30%", "d": "35%"}', 'b', true),

-- Psicométricas - Personalidad (sin carrera específica, division_id=NULL, tipo_prueba='psico')
(NULL, NULL, 'psico', 'Del 1 al 5, ¿qué tan cómodo te sientes trabajando bajo presión?',
'{"a": "1 - Muy incómodo", "b": "2 - Algo incómodo", "c": "3 - Neutral", "d": "4 - Cómodo", "e": "5 - Muy cómodo"}', NULL, true),

(NULL, NULL, 'psico', 'Del 1 al 5, ¿con qué frecuencia tomas la iniciativa en proyectos grupales?',
'{"a": "1 - Nunca", "b": "2 - Rara vez", "c": "3 - A veces", "d": "4 - Frecuentemente", "e": "5 - Siempre"}', NULL, true),

(NULL, NULL, 'psico', 'Del 1 al 5, ¿qué tan importante es para ti recibir retroalimentación sobre tu trabajo?',
'{"a": "1 - No importante", "b": "2 - Poco importante", "c": "3 - Moderadamente", "d": "4 - Importante", "e": "5 - Muy importante"}', NULL, true),

(NULL, NULL, 'psico', 'Cuando enfrentas un problema nuevo, ¿cuántas alternativas sueles considerar antes de decidir?',
'{"a": "1", "b": "2", "c": "3", "d": "4", "e": "5 o más"}', NULL, true),

(NULL, NULL, 'psico', 'Del 1 al 5, ¿qué tan bien manejas los conflictos interpersonales en el trabajo?',
'{"a": "1 - Muy mal", "b": "2 - Mal", "c": "3 - Regular", "d": "4 - Bien", "e": "5 - Muy bien"}', NULL, true),

(NULL, NULL, 'psico', '¿Cuántas horas al día puedes mantener concentración intensa en una tarea?',
'{"a": "1-2", "b": "2-3", "c": "3-4", "d": "4-5", "e": "5+"}', NULL, true),

(NULL, NULL, 'psico', 'Del 1 al 5, ¿qué tan adaptable eres a cambios inesperados en el entorno laboral?',
'{"a": "1 - Nada adaptable", "b": "2 - Poco adaptable", "c": "3 - Moderadamente", "d": "4 - Adaptable", "e": "5 - Muy adaptable"}', NULL, true),

(NULL, NULL, 'psico', '¿Con cuántas personas te sientes cómodo colaborando simultáneamente en un proyecto?',
'{"a": "1-2", "b": "3-4", "c": "5-6", "d": "7-8", "e": "9+"}', NULL, true),

-- Cognitivas - Razonamiento Lógico y Numérico (sin carrera específica, tipo_prueba='cogni')
(NULL, NULL, 'cogni', '¿Cuál número sigue en la secuencia: 2, 6, 18, 54, ...?',
'{"a": "108", "b": "162", "c": "216", "d": "324"}', 'b', true),

(NULL, NULL, 'cogni', 'Si un tren viaja a 60 km/h, ¿cuántos kilómetros recorre en 2.5 horas?',
'{"a": "120 km", "b": "150 km", "c": "180 km", "d": "200 km"}', 'b', true),

(NULL, NULL, 'cogni', '¿Cuál es el 15% de 240?',
'{"a": "24", "b": "36", "c": "48", "d": "60"}', 'b', true),

(NULL, NULL, 'cogni', 'Si 3 trabajadores completan un proyecto en 6 días, ¿cuántos días tardarían 6 trabajadores al mismo ritmo?',
'{"a": "2", "b": "3", "c": "4", "d": "12"}', 'b', true),

(NULL, NULL, 'cogni', '¿Cuál es el siguiente número en la secuencia: 1, 1, 2, 3, 5, 8, ...?',
'{"a": "11", "b": "13", "c": "15", "d": "16"}', 'b', true),

(NULL, NULL, 'cogni', 'Si un producto cuesta $800 y tiene un descuento del 25%, ¿cuál es el precio final?',
'{"a": "$200", "b": "$550", "c": "$600", "d": "$650"}', 'c', true),

(NULL, NULL, 'cogni', '¿Cuántos grados tiene un ángulo interno de un hexágono regular?',
'{"a": "108°", "b": "120°", "c": "135°", "d": "150°"}', 'b', true),

(NULL, NULL, 'cogni', 'Si A es mayor que B, B es mayor que C, y C es mayor que D, ¿cuántos de los cuatro son menores que B?',
'{"a": "1", "b": "2", "c": "3", "d": "4"}', 'b', true),

(NULL, NULL, 'cogni', '¿Cuál es el resultado de: (12 × 3) + (8 ÷ 2)?',
'{"a": "36", "b": "40", "c": "44", "d": "48"}', 'b', true),

(NULL, NULL, 'cogni', 'Si una caja tiene 48 manzanas y sacas 1/4 el lunes y 1/3 del restante el martes, ¿cuántas quedan?',
'{"a": "18", "b": "20", "c": "24", "d": "36"}', 'c', true),

(NULL, NULL, 'cogni', '¿Cuántas caras tiene un cubo?',
'{"a": "4", "b": "6", "c": "8", "d": "12"}', 'b', true),

(NULL, NULL, 'cogni', 'Si 5 máquinas producen 5 artículos en 5 minutos, ¿cuántas máquinas se necesitan para producir 100 artículos en 100 minutos?',
'{"a": "5", "b": "20", "c": "50", "d": "100"}', 'a', true),

-- Proyectivas - Estabilidad Emocional y Liderazgo (sin carrera específica, tipo_prueba='proy')
(NULL, NULL, 'proy', 'En un equipo de 5 personas con deadline en 3 días y un bug crítico, ¿cuántas horas extra considerarías razonable invertir?',
'{"a": "0", "b": "2-4", "c": "4-6", "d": "6-8", "e": "8+"}', 'c', true),

(NULL, NULL, 'proy', 'Si tu supervisor te da feedback negativo frente a todo el equipo, ¿qué harías?',
'{"a": "1 - Responder defensivamente", "b": "2 - Guardar silencio y enojarte", "c": "3 - Escuchar y pedir hablar en privado después", "d": "4 - Aceptar públicamente y mejorar", "e": "5 - Agradecer el feedback y pedir ejemplos específicos"}', 'e', true),

(NULL, NULL, 'proy', '¿Cuántas veces al mes consideras apropiado solicitar feedback sobre tu desempeño?',
'{"a": "0", "b": "1", "c": "2", "d": "3-4", "e": "Semanal"}', 'c', true),

(NULL, NULL, 'proy', 'Si descubres que un colega está cometiendo errores que afectan al equipo, ¿cuál es tu nivel de intervención del 1 al 5?',
'{"a": "1 - No intervenir", "b": "2 - Informar al supervisor", "c": "3 - Hablar con el colega directamente", "d": "4 - Ofrecer ayuda sin señalar errores", "e": "5 - Proponer capacitación al equipo"}', 'd', true),

(NULL, NULL, 'proy', '¿Cuántos proyectos simultáneos puedes manejar efectivamente sin perder calidad?',
'{"a": "1", "b": "2", "c": "3", "d": "4", "e": "5+"}', 'c', true),

(NULL, NULL, 'proy', 'Si te asignan una tarea que no dominas, ¿cuál es tu primer paso?',
'{"a": "1 - Rechazar la tarea", "b": "2 - Intentar solo sin ayuda", "c": "3 - Investigar y luego pedir orientación", "d": "4 - Pedir capacitación antes de empezar", "e": "5 - Buscar un mentor y crear un plan de aprendizaje"}', 'e', true),

(NULL, NULL, 'proy', '¿Cada cuánto tiempo consideras necesario tomar un descanso durante una jornada de 8 horas?',
'{"a": "Cada hora", "b": "Cada 2 horas", "c": "Cada 3 horas", "d": "Solo al comer", "e": "Solo al terminar"}', 'b', true),

(NULL, NULL, 'proy', 'Si un proyecto falla después de meses de trabajo, ¿cuántos días consideras razonable para procesar emocionalmente antes de retomar?',
'{"a": "0", "b": "1-2", "c": "3-5", "d": "1 semana", "e": "2+ semanas"}', 'b', true),

(NULL, NULL, 'proy', '¿Qué tan dispuesto estás a cambiar de rol si el equipo lo necesita? (1 = nada dispuesto, 5 = completamente)',
'{"a": "1", "b": "2", "c": "3", "d": "4", "e": "5"}', NULL, true),

(NULL, NULL, 'proy', 'Si tu equipo tiene 3 prioridades conflictivas, ¿cuál es tu estrategia?',
'{"a": "1 - Hacer todo al mismo tiempo", "b": "2 - Elegir la más fácil primero", "c": "3 - Discutir con el equipo y priorizar por impacto", "d": "4 - Pedir al supervisor que decida", "e": "5 - Crear matriz de urgencia vs importancia y presentar opciones"}', 'e', true);

SELECT setval('banco_preguntas_id_seq', (SELECT MAX(id) FROM banco_preguntas));

-- ============================================
-- 11. RESPUESTAS_DETALLE
-- ============================================
-- respuestas_detalle: evaluacion_id, pregunta_id, respuesta_dada, es_correcta

-- Evaluación 1 (Juan Carlos, técnica ITI, id=1) - preguntas 1-15
INSERT INTO respuestas_detalle (evaluacion_id, pregunta_id, respuesta_dada, es_correcta) OVERRIDING SYSTEM VALUE VALUES
(1, 1, 'b', true), (1, 2, 'c', true), (1, 3, 'c', true), (1, 4, 'c', true), (1, 5, 'c', true),
(1, 6, 'b', true), (1, 7, 'b', true), (1, 8, 'b', true), (1, 9, 'c', true), (1, 10, 'c', true),
(1, 11, 'b', true), (1, 12, 'b', true), (1, 13, 'b', true), (1, 14, 'a', false), (1, 15, 'b', true);

-- Evaluación 2 (María Fernanda, cogni, id=2) - preguntas 51-62
INSERT INTO respuestas_detalle (evaluacion_id, pregunta_id, respuesta_dada, es_correcta) OVERRIDING SYSTEM VALUE VALUES
(2, 51, 'b', true), (2, 52, 'b', true), (2, 53, 'b', true), (2, 54, 'b', true), (2, 55, 'b', true),
(2, 56, 'c', true), (2, 57, 'b', true), (2, 58, 'b', true), (2, 59, 'b', true), (2, 60, 'c', true),
(2, 61, 'b', true), (2, 62, 'a', true);

-- Evaluación 3 (Valentina, técnica Mercadotecnia, id=6) - preguntas 26-40 (subset)
INSERT INTO respuestas_detalle (evaluacion_id, pregunta_id, respuesta_dada, es_correcta) OVERRIDING SYSTEM VALUE VALUES
(6, 26, 'b', true), (6, 27, 'a', true), (6, 28, 'c', true), (6, 29, 'a', true), (6, 30, 'b', true),
(6, 31, 'b', true), (6, 32, 'b', true), (6, 33, 'b', true), (6, 34, 'b', true), (6, 35, 'b', true),
(6, 36, 'b', true), (6, 37, 'c', true), (6, 38, 'b', true), (6, 39, 'd', false), (6, 40, 'b', true);

-- Evaluación 4 (Roberto, proyectiva, id=9) - preguntas 63-72
INSERT INTO respuestas_detalle (evaluacion_id, pregunta_id, respuesta_dada, es_correcta) OVERRIDING SYSTEM VALUE VALUES
(9, 63, 'c', true), (9, 64, 'e', true), (9, 65, 'c', true), (9, 66, 'd', true), (9, 67, 'c', true),
(9, 68, 'e', true), (9, 69, 'b', true), (9, 70, 'b', true), (9, 71, 'd', false), (9, 72, 'e', true);

-- Evaluación 5 (Diego, cogni, id=11) - preguntas 51-62 (subset)
INSERT INTO respuestas_detalle (evaluacion_id, pregunta_id, respuesta_dada, es_correcta) OVERRIDING SYSTEM VALUE VALUES
(11, 51, 'b', true), (11, 52, 'b', true), (11, 53, 'b', true), (11, 54, 'b', true), (11, 55, 'a', false),
(11, 56, 'c', true), (11, 57, 'b', true), (11, 58, 'b', true), (11, 59, 'b', true), (11, 60, 'c', true),
(11, 61, 'b', true), (11, 62, 'a', true);

-- Evaluación 6 (Daniela, técnica Mercadotecnia, id=12) - preguntas 41-50 (subset)
INSERT INTO respuestas_detalle (evaluacion_id, pregunta_id, respuesta_dada, es_correcta) OVERRIDING SYSTEM VALUE VALUES
(12, 41, 'c', true), (12, 42, 'c', true), (12, 43, 'c', true), (12, 44, 'c', true), (12, 45, 'b', true),
(12, 46, 'b', true), (12, 47, 'b', true), (12, 48, 'b', true), (12, 49, 'b', true), (12, 50, 'b', true),
(12, 26, 'b', true), (12, 27, 'a', true), (12, 28, 'a', false);

-- ============================================
-- VERIFICACIÓN DE INTEGRIDAD
-- ============================================

SELECT 'divisiones' as tabla, COUNT(*) FROM divisiones
UNION ALL SELECT 'carreras', COUNT(*) FROM carreras
UNION ALL SELECT 'config_pruebas', COUNT(*) FROM config_pruebas
UNION ALL SELECT 'usuarios', COUNT(*) FROM usuarios
UNION ALL SELECT 'egresados', COUNT(*) FROM egresados
UNION ALL SELECT 'empresas', COUNT(*) FROM empresas
UNION ALL SELECT 'vacantes', COUNT(*) FROM vacantes
UNION ALL SELECT 'postulaciones', COUNT(*) FROM postulaciones
UNION ALL SELECT 'evaluaciones', COUNT(*) FROM evaluaciones
UNION ALL SELECT 'banco_preguntas', COUNT(*) FROM banco_preguntas
UNION ALL SELECT 'respuestas_detalle', COUNT(*) FROM respuestas_detalle;

COMMIT;
