<?php
// Ejecutor de pruebas unitarias (sin PHPUnit ni Composer).
//
//   php tests/ejecutar.php            todas las pruebas de tests/unit/
//   php tests/ejecutar.php Buscador   solo las que contengan "Buscador" en el grupo o el nombre
//
// Código de salida: 0 si todo pasa, 1 si alguna prueba falla.
// Las pruebas marcadas como "conocido" documentan defectos del motor aún sin corregir: se informan, no rompen la suite.
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit;
}

putenv('BF_CONFIG=' . __DIR__ . '/config.pruebas.php');
require __DIR__ . '/../private/src/bootstrap.php';
require __DIR__ . '/lib/aserciones.php';
require __DIR__ . '/lib/apoyo.php';

$archivos = glob(__DIR__ . '/unit/*Prueba.php') ?: [];
sort($archivos);
foreach ($archivos as $archivo) {
    require $archivo;
}

exit(ejecutarPruebas((string) ($argv[1] ?? '')));
