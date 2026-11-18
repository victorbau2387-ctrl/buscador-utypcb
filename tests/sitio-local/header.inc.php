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
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght,SOFT,WONK@9..144,300..900,0..100,0..1&family=Archivo:wdth,wght@62..125,400..800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        /* Variables copiadas de utypcb.edu.mx (estilos_base.inc.php, nov. 2026) para que el widget se vea como en producción. */
        :root {
            --tinta: #071a16; --tinta-2: #0d2b24; --tinta-3: #123a31;
            --verde: #0c9b63; --linea: #3fbf9a; --naranja: #ff7a00; --rojo: #d1322f;
            --espuma: #eef3f1; --espuma-2: #e2eae7; --arena: #e4ded4; --niebla: #8ea69e; --blanco: #ffffff;
            --texto: #12211d; --texto-mudo: #5c6f69;
            --grad-marea: linear-gradient(180deg, #071a16 0%, #0d2b24 100%);
            --grad-sol: linear-gradient(100deg, #ff7a00, #ffa93d);
            --ff-display: "Fraunces", Georgia, "Times New Roman", serif;
            --ff-texto: "Archivo", "Segoe UI", system-ui, -apple-system, Arial, sans-serif;
            --ff-mono: "JetBrains Mono", ui-monospace, SFMono-Regular, Consolas, monospace;
            --radio: 14px; --sombra: 0 18px 50px -20px rgba(7, 26, 22, .45);
            --sp-bloque: clamp(32px, 5vw, 64px); --gutter: clamp(20px, 4vw, 56px);
            --e-suave: cubic-bezier(.16, 1, .3, 1); --e-micro: cubic-bezier(.4, 0, .2, 1);
            --d-micro: 180ms; --d-elem: 420ms;
        }
        body { margin: 0; font-family: var(--ff-texto); color: var(--texto); background: var(--espuma); line-height: 1.55; }
        .sitio-cabecera { background: var(--grad-marea); color: var(--espuma); padding: 1rem var(--gutter); display: flex; gap: 1.5rem; align-items: center; flex-wrap: wrap; border-bottom: 3px solid var(--naranja); }
        .sitio-cabecera strong { font-family: var(--ff-display); font-size: 1.3rem; }
        .sitio-cabecera nav a { color: var(--espuma); margin-right: 1rem; text-decoration: none; opacity: .85; }
        .sitio-cabecera nav a:hover { color: var(--linea); opacity: 1; }
        main { max-width: 860px; margin: 0 auto; padding: var(--sp-bloque) var(--gutter); }
        h1 { font-family: var(--ff-display); color: var(--tinta); font-weight: 600; }
        .sitio-pie { background: var(--tinta); color: var(--niebla); padding: 2rem var(--gutter); text-align: center; font-size: .9rem; }
        .sitio-pie a { color: var(--linea); }
        code { background: var(--espuma-2); padding: .1em .3em; border-radius: 4px; font-family: var(--ff-mono); }
    </style>
</head>
<body>
<?php if (function_exists('utyp_sprite')) { utyp_sprite(); } ?>
<header class="sitio-cabecera">
    <strong>UTyP Coyuca de Benítez</strong>
    <nav><a href="#">Inicio</a><a href="#">Oferta educativa</a><a href="#">Admisión</a><a href="#">Contacto</a></nav>
</header>
