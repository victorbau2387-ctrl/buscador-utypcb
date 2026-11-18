<?php
// Página de prueba local del widget. Ver tests/sitio-local/LEEME.md.
$titulo = 'Sitio local de prueba · Buscador UTyP';
require __DIR__ . '/header.inc.php';
?>
<main>
    <h1>Página simulada del sitio</h1>
    <p>Esta página imita <code>header.inc.php</code> y <code>footer.inc.php</code> del sitio institucional
        para probar el widget del buscador. El botón flotante aparece abajo a la derecha.</p>
    <p>Modos:</p>
    <ul>
        <li><a href="?">API real</a> (<code>/buscador/api.php</code>, requiere MariaDB)</li>
        <li><a href="?api=simulada">API simulada</a> (motor real con el CSV, sin base de datos)</li>
        <li><a href="?api=simulada&amp;sprite=1">API simulada + sprite de iconos</a></li>
        <li>Errores: <a href="?api=simulada&amp;forzar=429">429</a> ·
            <a href="?api=simulada&amp;forzar=500">500</a> ·
            <a href="?api=simulada&amp;forzar=lento">tiempo agotado</a> ·
            <a href="?api=simulada&amp;forzar=basura">respuesta no JSON</a></li>
    </ul>
    <p>Prueba: “¿cuánto cuesta la inscripción?”, “ingeniería civil” seguido de “¿y de qué trata?”,
        “¿tienen medicina?”, “xyz abc”.</p>
    <?php for ($i = 1; $i <= 6; $i++) : ?>
        <p>Texto de relleno <?= $i ?> para comprobar que el botón flotante no estorba al desplazarse por la página.
            Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore.</p>
    <?php endfor; ?>
</main>
<?php require __DIR__ . '/footer.inc.php';
