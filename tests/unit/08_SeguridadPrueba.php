<?php
declare(strict_types=1);

use Buscador\Support\Config;
use Buscador\Support\Http;

grupo('Seguridad (funciones de apoyo)');

prueba('Http::e escapa HTML, comillas y UTF-8 inválido', function () {
    igual('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', Http::e('<script>alert("x")</script>'));
    igual('O&#039;Higgins', Http::e("O'Higgins"));
    igual('', Http::e(null));
    igual("a\u{FFFD}b", Http::e("a\xC3b"), 'bytes inválidos se sustituyen');
});

prueba('huellaVisitante: hash SHA-256 con sal; no contiene la IP', function () {
    $_SERVER['REMOTE_ADDR'] = '203.0.113.7';
    $h1 = Http::huellaVisitante();
    coincide('/^[0-9a-f]{64}$/', $h1);
    noContiene('203.0.113.7', $h1);
    Config::establecer(['seguridad' => ['sal_hash' => 'otra-sal']]);
    try {
        distinto($h1, Http::huellaVisitante(), 'cambiar la sal cambia la huella');
    } finally {
        restaurarConfig();
        unset($_SERVER['REMOTE_ADDR']);
    }
});

prueba('config de ejemplo: CORS sin comodín y límites razonables', function () {
    $c = require __DIR__ . '/../../private/config.example.php';
    afirmar(!in_array('*', $c['seguridad']['origenes_permitidos'], true), 'sin *');
    foreach ($c['seguridad']['origenes_permitidos'] as $o) {
        coincide('#^https://#', $o, 'solo orígenes https');
    }
    igual(300, $c['seguridad']['max_longitud']);
    afirmar($c['seguridad']['limite_por_minuto'] > 0 && $c['seguridad']['limite_por_minuto'] <= 60, 'límite por minuto');
    igual('', $c['db']['password'], 'el ejemplo no trae contraseña');
});
