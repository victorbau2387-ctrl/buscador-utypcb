<?php
// Configuración para usar la API REAL desde el sitio local (MariaDB local, sin contraseñas reales).
// Uso: BF_CONFIG=tests/sitio-local/config.local.php
$base = require __DIR__ . '/../config.pruebas.php';
$base['db'] = [
    'host'     => '127.0.0.1',
    'puerto'   => 3307,
    'nombre'   => 'bf_widget',
    'usuario'  => 'root',
    'password' => '',
];
// El navegador envía la cabecera Origin también en POST del mismo sitio; hay que permitir el origen local.
$base['seguridad']['origenes_permitidos'][] = 'http://127.0.0.1:8080';
$base['seguridad']['origenes_permitidos'][] = 'http://localhost:8080';
return $base;
