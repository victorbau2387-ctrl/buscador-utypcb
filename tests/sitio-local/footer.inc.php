<?php // Pie SIMULADO del sitio (solo para desarrollo local). ?>
<footer class="sitio-pie">
    Sitio de prueba local · no se publica.
</footer>
<?php
// En el sitio real la línea es:  require $_SERVER['DOCUMENT_ROOT'] . '/buscador/widget.php';
// Aquí el DOCUMENT_ROOT es tests/sitio-local, por eso se apunta a public_html con __DIR__.
require __DIR__ . '/../../public_html/buscador/widget.php';
?>
</body>
</html>
