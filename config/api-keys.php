<?php
declare(strict_types=1);

/**
 * Configuración de API keys externas.
 * 
 * NO commitear este archivo al repositorio público.
 * Usar variables de entorno en producción.
 */

// API keys (desarrollo)
return [
    // Jooble - búsqueda de vacantes externas
    // API key: 09f982ef-1a37-4bff-960f-169051bc14d3
    'jooble' => [
        'api_key' => '09f982ef-1a37-4bff-960f-169051bc14d3',
        'base_url' => 'https://jooble.org/api',
    ],
];