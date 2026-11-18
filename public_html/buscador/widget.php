<?php
/**
 * Widget del buscador de preguntas frecuentes.
 *
 * Se incluye desde footer.inc.php con UNA línea:
 *   <?php require $_SERVER['DOCUMENT_ROOT'] . '/buscador/widget.php'; ?>
 *
 * Configuración opcional (definir ANTES del require, por ejemplo en header.inc.php):
 *   define('BF_WIDGET_API', '/buscador/api.php');   // URL del endpoint (misma web, sin CORS)
 *   define('BF_WIDGET_BASE', '/buscador/');         // carpeta pública de widget.css / widget.js
 *   define('BF_WIDGET_ICONOS', ['buscar' => 'i-buscar', 'cerrar' => 'i-cerrar', 'enviar' => 'i-enviar']);
 *   define('BF_WIDGET_ADMIN', false);  // oculta el enlace "Acceso para personal" (por defecto: BASE . 'admin/')
 *
 * Compatible con PHP 7.4 y 8.x. Todo se ejecuta dentro de una función anónima
 * para no dejar variables sueltas en el ámbito de footer.inc.php.
 */
if (defined('BF_WIDGET_INCLUIDO')) {
    return; // evita duplicarlo si el footer se incluye dos veces
}
define('BF_WIDGET_INCLUIDO', true);

(static function () {
    $e = static function ($texto) {
        return htmlspecialchars((string) $texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    };

    $api = defined('BF_WIDGET_API') ? (string) BF_WIDGET_API : '/buscador/api.php';
    $base = defined('BF_WIDGET_BASE') ? (string) BF_WIDGET_BASE : '/buscador/';
    if ($base === '' || substr($base, -1) !== '/') {
        $base .= '/';
    }
    // Enlace al panel (login) en el pie del widget. false lo oculta.
    $admin = defined('BF_WIDGET_ADMIN') ? BF_WIDGET_ADMIN : $base . 'admin/';

    // Versión para invalidar caché: fecha de modificación de cada archivo.
    $ver = static function ($archivo) {
        $ruta = __DIR__ . '/' . $archivo;
        return is_file($ruta) ? (string) filemtime($ruta) : '1';
    };

    // Iconos: sprite del sitio (icons.inc.php) si existe; si no, SVG inline mínimo.
    $ids = ['buscar' => 'i-buscar', 'cerrar' => 'i-cerrar', 'enviar' => 'i-enviar'];
    if (defined('BF_WIDGET_ICONOS') && is_array(BF_WIDGET_ICONOS)) {
        $ids = array_merge($ids, BF_WIDGET_ICONOS);
    }
    $trazos = [
        'buscar' => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-4.2-4.2"/>',
        'cerrar' => '<path d="M6 6l12 12M18 6L6 18"/>',
        'enviar' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
    ];
    $conSprite = function_exists('utyp_sprite');
    $icono = static function ($nombre) use ($ids, $trazos, $conSprite, $e) {
        $attrs = 'class="bf-icono" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" focusable="false"'
            . ' fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"';
        if ($conSprite) {
            // data-bf-icono permite a widget.js usar el trazo de respaldo si el id no existe en el sprite.
            return '<svg ' . $attrs . ' data-bf-icono="' . $e($nombre) . '"><use href="#' . $e($ids[$nombre]) . '"></use></svg>';
        }
        return '<svg ' . $attrs . '>' . $trazos[$nombre] . '</svg>';
    };
    ?>
<link rel="stylesheet" href="<?= $e($base . 'widget.css?v=' . $ver('widget.css')) ?>">
<div class="bf-widget" id="bf-widget" data-bf-api="<?= $e($api) ?>" data-bf-max="300">
    <button type="button" class="bf-lanzador" id="bf-lanzador" aria-expanded="false" aria-controls="bf-panel" aria-haspopup="dialog">
        <?= $icono('buscar') ?>
        <span class="bf-lanzador-texto">¿Tienes dudas? Pregunta aquí</span>
    </button>

    <div class="bf-panel" id="bf-panel" role="dialog" aria-modal="true" aria-labelledby="bf-titulo" aria-describedby="bf-descripcion" hidden>
        <div class="bf-cabecera">
            <div>
                <h2 class="bf-titulo" id="bf-titulo">Preguntas frecuentes</h2>
                <p class="bf-descripcion" id="bf-descripcion">Busca información oficial de la universidad.</p>
            </div>
            <button type="button" class="bf-cerrar" id="bf-cerrar" aria-label="Cerrar buscador">
                <?= $icono('cerrar') ?>
            </button>
        </div>

        <form class="bf-form" id="bf-form" role="search" novalidate>
            <label class="bf-etiqueta" for="bf-consulta">Escribe tu pregunta</label>
            <div class="bf-campo">
                <input type="search" id="bf-consulta" class="bf-input" name="consulta" maxlength="300"
                       autocomplete="off" enterkeyhint="search" placeholder="Ej. ¿Cuánto cuesta la inscripción?"
                       aria-describedby="bf-contador">
                <button type="submit" class="bf-enviar" aria-label="Buscar">
                    <?= $icono('enviar') ?>
                </button>
            </div>
            <p class="bf-contador" id="bf-contador" aria-live="off"><span id="bf-contador-n">0</span>/300</p>
        </form>

        <div class="bf-cuerpo" id="bf-cuerpo">
            <div class="bf-inicio" id="bf-inicio">
                <p class="bf-subtitulo">Preguntas comunes</p>
                <div class="bf-chips" id="bf-chips"></div>
            </div>
            <div class="bf-historial" id="bf-historial" aria-live="polite" aria-relevant="additions" aria-atomic="false"></div>
            <p class="bf-estado" id="bf-estado" role="status" aria-live="polite"></p>
        </div>

        <div class="bf-pie">
            <p>Respuestas tomadas de información oficial de la universidad.</p>
            <p class="bf-pie-enlaces">
                <a href="<?= $e($base . 'privacidad.php') ?>" target="_blank" rel="noopener">Aviso de privacidad</a>
                <?php if ($admin) : ?>
                    <span aria-hidden="true">·</span>
                    <a href="<?= $e((string) $admin) ?>" class="bf-acceso" target="_blank" rel="noopener">
                        <svg class="bf-icono" width="14" height="14" viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                        Acceso para personal
                    </a>
                <?php endif; ?>
            </p>
        </div>
    </div>
</div>
<script src="<?= $e($base . 'widget.js?v=' . $ver('widget.js')) ?>" defer></script>
<?php
})();
