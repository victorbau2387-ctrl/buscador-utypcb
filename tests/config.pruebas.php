<?php
// Configuración para pruebas locales: usa los datos simulados de tests/fixtures/site_data.
$base = require __DIR__ . '/../private/config.example.php';
$base['entorno'] = 'desarrollo';
$base['sitio']['ruta_datos'] = __DIR__ . '/fixtures/site_data';
return $base;
