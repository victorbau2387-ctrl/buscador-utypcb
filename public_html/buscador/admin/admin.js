// Panel del buscador: mejoras progresivas (sin dependencias). Sin JS, todo sigue funcionando.
(function () {
    'use strict';
    // Botón "Mostrar / Ocultar" para campos de contraseña marcados con data-ver-clave.
    document.querySelectorAll('[data-ver-clave]').forEach(function (btn) {
        var campo = document.getElementById(btn.getAttribute('data-ver-clave'));
        if (!campo) { return; }
        btn.hidden = false;
        btn.addEventListener('click', function () {
            var ver = campo.type === 'password';
            campo.type = ver ? 'text' : 'password';
            var etiqueta = ver ? 'Ocultar contraseña' : 'Mostrar contraseña';
            btn.setAttribute('aria-pressed', ver ? 'true' : 'false');
            btn.setAttribute('aria-label', etiqueta);
            btn.setAttribute('title', etiqueta);
            campo.focus();
        });
    });
})();
