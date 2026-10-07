<?php

/*
|--------------------------------------------------------------------------
| Cross-Origin Resource Sharing (CORS) Configuration
|--------------------------------------------------------------------------
|
| El SPA se sirve en el MISMO origen que la API (nginx sirve el frontend y
| proxy /api/* al backend), por lo que CORS no es necesario para el navegador
| en este escenario. Esta configuración habilita el consumo cross-origin de
| la API por parte de otros clientes (p. ej. la app Flutter).
|
| NOTA: al usar supports_credentials=true no se puede emplear "*" en
| allowed_origins; por eso se listan orígenes explícitos.
|
| Ajusta/añade dominios vía la variable de entorno CORS_ALLOWED_ORIGINS
| (lista separada por comas).
|
*/

$origins = array_values(array_filter(array_map('trim', explode(',', (string) env(
    'CORS_ALLOWED_ORIGINS',
    'https://project.business-and-growth.com,https://www.project.business-and-growth.com'
)))));

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => $origins,

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
