<?php
// Demo temporal por túnel (cloudflared). Todos los visitantes llegan con la IP del túnel,
// así que comparten el límite por minuto: se sube para que una prueba con varias personas no dé 429.
// Solo para demostraciones; en producción se usa private/config.php.
$base = require __DIR__ . '/config.local.php';
$base['seguridad']['limite_por_minuto'] = 120;
return $base;
