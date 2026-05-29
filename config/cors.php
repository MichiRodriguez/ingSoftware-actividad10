<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS)
    |--------------------------------------------------------------------------
    | Orígenes permitidos para acceder a la API. En desarrollo se permiten
    | los puertos del servidor Vite (5173) y del servidor Ionic (8100).
    | En producción reemplazar '*' por los dominios reales.
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173',   // Vite dev server (bovweight-web y bovweight-movil browser)
        'http://localhost:8100',   // Ionic CLI dev server
        'http://localhost:3000',   // Vue dev server alternativo
        'capacitor://localhost',   // Capacitor runtime iOS
        'http://localhost',        // Capacitor runtime Android
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
