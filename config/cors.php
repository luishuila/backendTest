<?php

return [
    // Descomenta esta línea para probar en local (solo permitirá el origen de tu Angular):
    // 'allowed_origins' => ['http://localhost:4200'],

    // Descomenta esta línea para aplicar CORS a todas las rutas durante pruebas locales:
    // 'paths' => ['*'],
     
  'paths' => [], 
              
  'allowed_methods' => ['*'],
  'allowed_origins'=> ['*'],

  'allowed_origins_patterns' => [],
  'allowed_headers' => ['*'],
  'exposed_headers' => ['*'],
  'max_age' => 0,
  'supports_credentials' => false,
];