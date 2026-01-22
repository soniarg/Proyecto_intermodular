<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    /*
     * ✅ CONFIGURACIÓN SEGURA
     * Permitimos explícitamente a tu Frontend (Vue) conectarse.
     * Incluyo localhost, 127.0.0.1 y los puertos típicos de Vite (5173 y 5174).
     */
    'allowed_origins' => [
        'http://localhost:5173',
        'http://localhost:5174',
        'http://127.0.0.1:5173',
        'http://127.0.0.1:5174',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    /*
     * ✅ IMPORTANTE
     * Lo ponemos en 'true' para permitir el envío de cookies y tokens de autenticación.
     * Esto es obligatorio si no usamos el comodín '*'.
     */
    'supports_credentials' => true,

];