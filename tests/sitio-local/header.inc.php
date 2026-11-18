<?php
// Encabezado SIMULADO del sitio (solo para desarrollo local). Imita las variables CSS del sitio real.
$titulo = isset($titulo) ? (string) $titulo : 'UTyP Coyuca de Benítez';

// ?api=simulada usa tests/sitio-local/api-simulada.php (motor real + CSV, sin MariaDB).
// ?forzar=429|500|lento|basura fuerza errores en la API simulada.
if (isset($_GET['api']) && $_GET['api'] === 'simulada') {
    $forzar = isset($_GET['forzar']) && in_array($_GET['forzar'], ['429', '500', 'lento', 'basura'], true)
        ? '?forzar=' . $_GET['forzar'] : '';
    define('BF_WIDGET_API', '/api-simulada.php' . $forzar);
}

// ?sprite=1 simula icons.inc.php: define utyp_sprite() y un sprite SIN el id i-enviar
// (para comprobar que widget.js dibuja el icono de respaldo).
if (isset($_GET['sprite']) && $_GET['sprite'] === '1' && !function_exists('utyp_sprite')) {
    function utyp_sprite()
    {
        echo '<svg xmlns="http://www.w3.org/2000/svg" style="display:none">'
            . '<symbol id="i-buscar" viewBox="0 0 24 24"><circle cx="10" cy="10" r="6"/><path d="M21 21l-6-6"/></symbol>'
            . '<symbol id="i-cerrar" viewBox="0 0 24 24"><path d="M5 5l14 14M19 5L5 19"/></symbol>'
            . '</svg>';
    }
}
?><!doctype html>
<html lang="es-MX">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;600&family=Fraunces:opsz,wght@9..144,600&display=swap" rel="stylesheet">
    <style>
        :root {
            --naranja: #c8642a;
            --verde: #2e6b4f;
            --tinta: #1f2a2e;
            --espuma: #f6f4ef;
            --texto: #2d3236;
            --ff-display: "Fraunces", Georgia, serif;
            --ff-texto: "Archivo", system-ui, sans-serif;
            --sp-bloque: clamp(2.5rem, 6vw, 5rem);
            --e-suave: cubic-bezier(.22, .61, .36, 1);
        }
        body { margin: 0; font-family: var(--ff-texto); color: var(--texto); background: #fff; line-height: 1.55; }
        .sitio-cabecera { background: var(--tinta); color: #fff; padding: 1rem 1.25rem; display: flex; gap: 1.5rem; align-items: center; flex-wrap: wrap; }
        .sitio-cabecera strong { font-family: var(--ff-display); font-size: 1.25rem; }
        .sitio-cabecera nav a { color: #fff; margin-right: 1rem; text-decoration: none; opacity: .85; }
        main { max-width: 860px; margin: 0 auto; padding: var(--sp-bloque) 1.25rem; }
        h1 { font-family: var(--ff-display); color: var(--tinta); }
        .sitio-pie { background: var(--espuma); padding: 2rem 1.25rem; text-align: center; font-size: .9rem; }
        code { background: var(--espuma); padding: .1em .3em; border-radius: 4px; }
    </style>
</head>
<body>
<?php if (function_exists('utyp_sprite')) { utyp_sprite(); } ?>
<header class="sitio-cabecera">
    <strong>UTyP Coyuca de Benítez</strong>
    <nav><a href="#">Inicio</a><a href="#">Oferta educativa</a><a href="#">Admisión</a><a href="#">Contacto</a></nav>
</header>
