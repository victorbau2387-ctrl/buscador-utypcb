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
            btn.textContent = ver ? 'Ocultar' : 'Mostrar';
            btn.setAttribute('aria-pressed', ver ? 'true' : 'false');
            campo.focus();
        });
    });
})();
