/*
 * Widget del buscador de preguntas frecuentes · UTyP Coyuca de Benítez
 * JavaScript vanilla, sin dependencias.
 *
 * Seguridad: todo lo que llega del servidor se pinta con textContent / createElement.
 * Nunca se usa innerHTML con datos. Solo se enlazan URLs http(s).
 */
(function () {
    'use strict';

    var raiz = document.getElementById('bf-widget');
    if (!raiz || raiz.getAttribute('data-bf-listo') === '1') {
        return;
    }
    raiz.setAttribute('data-bf-listo', '1');

    var API = raiz.getAttribute('data-bf-api') || '/buscador/api.php';
    var MAX = parseInt(raiz.getAttribute('data-bf-max'), 10) || 300;
    var TIEMPO_LIMITE = 8000;

    var lanzador = document.getElementById('bf-lanzador');
    var panel = document.getElementById('bf-panel');
    var btnCerrar = document.getElementById('bf-cerrar');
    var form = document.getElementById('bf-form');
    var input = document.getElementById('bf-consulta');
    var btnEnviar = form.querySelector('.bf-enviar');
    var contador = document.getElementById('bf-contador');
    var contadorN = document.getElementById('bf-contador-n');
    var cuerpo = document.getElementById('bf-cuerpo');
    var inicio = document.getElementById('bf-inicio');
    var chips = document.getElementById('bf-chips');
    var historial = document.getElementById('bf-historial');
    var estado = document.getElementById('bf-estado');

    // Contexto de la conversación: solo en memoria de la página (no localStorage).
    var contexto = { carrera: null };
    var inicioCargado = false;
    var ocupado = false;

    var reducirMovimiento = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Lenis (si el sitio lo usa) no debe secuestrar el desplazamiento dentro del panel.
    panel.setAttribute('data-lenis-prevent', '');
    cuerpo.setAttribute('data-lenis-prevent', '');

    /* ------------------------------------------------------------------ */
    /* Iconos: si el sprite del sitio no tiene el id, usar trazo de respaldo */
    /* ------------------------------------------------------------------ */
    var SVG_NS = 'http://www.w3.org/2000/svg';
    var TRAZOS = {
        buscar: [['circle', { cx: 11, cy: 11, r: 7 }], ['path', { d: 'M20 20l-4.2-4.2' }]],
        cerrar: [['path', { d: 'M6 6l12 12M18 6L6 18' }]],
        enviar: [['path', { d: 'M5 12h14M13 6l6 6-6 6' }]]
    };
    Array.prototype.forEach.call(raiz.querySelectorAll('svg[data-bf-icono]'), function (svg) {
        var use = svg.querySelector('use');
        var ref = use ? (use.getAttribute('href') || use.getAttribute('xlink:href') || '') : '';
        if (ref.charAt(0) === '#' && document.getElementById(ref.slice(1))) {
            return;
        }
        var trazos = TRAZOS[svg.getAttribute('data-bf-icono')] || [];
        while (svg.firstChild) {
            svg.removeChild(svg.firstChild);
        }
        trazos.forEach(function (t) {
            var el = document.createElementNS(SVG_NS, t[0]);
            Object.keys(t[1]).forEach(function (k) { el.setAttribute(k, String(t[1][k])); });
            svg.appendChild(el);
        });
    });

    /* ------------------------------------------------------------------ */
    /* Utilidades de DOM seguras                                            */
    /* ------------------------------------------------------------------ */
    function el(etiqueta, clase, texto) {
        var n = document.createElement(etiqueta);
        if (clase) { n.className = clase; }
        if (texto !== undefined && texto !== null) { n.textContent = String(texto); }
        return n;
    }

    function texto(v) {
        return (typeof v === 'string' || typeof v === 'number') ? String(v) : '';
    }

    /** Devuelve la URL solo si empieza con http:// o https:// y es válida. */
    function urlSegura(u) {
        if (typeof u !== 'string') { return null; }
        u = u.trim();
        if (!/^https?:\/\//i.test(u)) { return null; }
        try {
            var p = new URL(u);
            return (p.protocol === 'https:' || p.protocol === 'http:') ? p.href : null;
        } catch (e) {
            return null;
        }
    }

    function enlace(url, etiqueta) {
        var seguro = urlSegura(url);
        if (!seguro) {
            return document.createTextNode(texto(etiqueta) || texto(url));
        }
        var a = el('a', null, texto(etiqueta) || seguro);
        a.href = seguro;
        a.target = '_blank';
        a.rel = 'noopener noreferrer';
        return a;
    }

    /* ------------------------------------------------------------------ */
    /* Abrir / cerrar y trampa de foco                                      */
    /* ------------------------------------------------------------------ */
    function abierto() {
        return !panel.hidden;
    }

    function abrir() {
        panel.hidden = false;
        raiz.classList.add('bf-abierto');
        lanzador.setAttribute('aria-expanded', 'true');
        input.focus();
        if (!inicioCargado) {
            cargarInicio();
        }
    }

    function cerrar() {
        panel.hidden = true;
        raiz.classList.remove('bf-abierto');
        lanzador.setAttribute('aria-expanded', 'false');
        lanzador.focus();
    }

    function enfocables() {
        var lista = panel.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])');
        return Array.prototype.filter.call(lista, function (n) {
            return n.offsetParent !== null || n === document.activeElement;
        });
    }

    lanzador.addEventListener('click', function () {
        if (abierto()) { cerrar(); } else { abrir(); }
    });
    btnCerrar.addEventListener('click', cerrar);

    panel.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape' || ev.key === 'Esc') {
            ev.preventDefault();
            cerrar();
            return;
        }
        if (ev.key !== 'Tab') { return; }
        var lista = enfocables();
        if (!lista.length) { return; }
        var primero = lista[0];
        var ultimo = lista[lista.length - 1];
        if (ev.shiftKey && (document.activeElement === primero || !panel.contains(document.activeElement))) {
            ev.preventDefault();
            ultimo.focus();
        } else if (!ev.shiftKey && (document.activeElement === ultimo || !panel.contains(document.activeElement))) {
            ev.preventDefault();
            primero.focus();
        }
    });

    // Si el foco escapa del panel abierto (clic fuera, etc.), regresa al campo de búsqueda.
    document.addEventListener('focusin', function (ev) {
        if (abierto() && !panel.contains(ev.target) && ev.target !== lanzador) {
            input.focus();
        }
    });

    /* ------------------------------------------------------------------ */
    /* Contador de caracteres                                               */
    /* ------------------------------------------------------------------ */
    function actualizarContador() {
        if (input.value.length > MAX) {
            input.value = input.value.slice(0, MAX);
        }
        var n = input.value.length;
        contadorN.textContent = String(n);
        contador.classList.toggle('bf-limite', n >= MAX);
    }
    input.addEventListener('input', actualizarContador);

    /* ------------------------------------------------------------------ */
    /* Comunicación con el endpoint                                         */
    /* ------------------------------------------------------------------ */
    function ErrorBuscador(mensaje, estadoHttp) {
        this.message = mensaje;
        this.estado = estadoHttp || 0;
    }

    function pedir(datos) {
        var control = typeof AbortController !== 'undefined' ? new AbortController() : null;
        var reloj = control ? setTimeout(function () { control.abort(); }, TIEMPO_LIMITE) : null;
        return fetch(API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(datos),
            credentials: 'same-origin',
            cache: 'no-store',
            signal: control ? control.signal : undefined
        }).then(function (resp) {
            return resp.json().catch(function () { return null; }).then(function (json) {
                if (resp.status === 429) {
                    throw new ErrorBuscador('Has hecho muchas búsquedas seguidas. Espera un minuto y vuelve a intentarlo.', 429);
                }
                if (!resp.ok || !json || json.ok !== true) {
                    var msg = json && typeof json.error === 'string' && resp.status >= 400 && resp.status < 500 && resp.status !== 403
                        ? json.error
                        : 'El buscador no está disponible por el momento. Inténtalo más tarde.';
                    throw new ErrorBuscador(msg, resp.status);
                }
                return json;
            });
        }, function (err) {
            if (err && err.name === 'AbortError') {
                throw new ErrorBuscador('La búsqueda tardó demasiado. Revisa tu conexión e inténtalo de nuevo.', 0);
            }
            throw new ErrorBuscador('No pudimos conectar con el buscador. Revisa tu conexión a internet.', 0);
        }).then(function (json) {
            if (reloj) { clearTimeout(reloj); }
            return json;
        }, function (err) {
            if (reloj) { clearTimeout(reloj); }
            throw err instanceof ErrorBuscador ? err : new ErrorBuscador('Ocurrió un problema inesperado. Inténtalo de nuevo.', 0);
        });
    }

    function guardarContexto(json) {
        if (json && json.contexto && typeof json.contexto === 'object') {
            var c = json.contexto.carrera;
            contexto = { carrera: (typeof c === 'string' && c !== '') ? c : null };
        }
    }

    function ponerOcupado(si) {
        ocupado = si;
        btnEnviar.disabled = si;
        raiz.setAttribute('aria-busy', si ? 'true' : 'false');
        estado.textContent = si ? 'Buscando…' : '';
        estado.classList.toggle('bf-buscando', si);
    }

    /* ------------------------------------------------------------------ */
    /* Sugerencias iniciales                                                */
    /* ------------------------------------------------------------------ */
    function cargarInicio() {
        inicioCargado = true;
        pedir({ accion: 'inicio' }).then(function (json) {
            var lista = Array.isArray(json.sugerencias) ? json.sugerencias : [];
            while (chips.firstChild) { chips.removeChild(chips.firstChild); }
            lista.forEach(function (s) {
                if (!s || !texto(s.pregunta)) { return; }
                var b = el('button', 'bf-chip', s.pregunta);
                b.type = 'button';
                b.addEventListener('click', function () { ver(s.id, s.pregunta); });
                chips.appendChild(b);
            });
            inicio.hidden = chips.childNodes.length === 0;
        }, function () {
            inicioCargado = false; // se reintenta la próxima vez que se abra
            inicio.hidden = true;
        });
    }

    /* ------------------------------------------------------------------ */
    /* Turnos del historial                                                 */
    /* ------------------------------------------------------------------ */
    function nuevoTurno(etiquetaUsuario) {
        inicio.hidden = true;
        var turno = el('div', 'bf-turno');
        var q = el('p', 'bf-consulta-usuario', etiquetaUsuario);
        var oculto = el('span', 'bf-sr', 'Tu búsqueda: ');
        q.insertBefore(oculto, q.firstChild);
        turno.appendChild(q);
        historial.appendChild(turno);
        mostrar(turno);
        return turno;
    }

    function mostrar(nodo) {
        var y = nodo.offsetTop - cuerpo.offsetTop - 8;
        try {
            cuerpo.scrollTo({ top: y, behavior: reducirMovimiento ? 'auto' : 'smooth' });
        } catch (e) {
            cuerpo.scrollTop = y;
        }
    }

    function botonesOpciones(lista, alElegir) {
        var cont = el('div', 'bf-opciones');
        lista.forEach(function (s) {
            if (!s || !texto(s.pregunta)) { return; }
            var b = el('button', 'bf-opcion', s.pregunta);
            b.type = 'button';
            b.addEventListener('click', function () { alElegir(s); });
            cont.appendChild(b);
        });
        return cont;
    }

    function bloqueUtil(consultaId) {
        var cont = el('div', 'bf-util');
        var pregunta = el('span', null, '¿Te sirvió?');
        var si = el('button', null, 'Sí');
        var no = el('button', null, 'No');
        si.type = 'button';
        no.type = 'button';
        cont.appendChild(pregunta);
        cont.appendChild(si);
        cont.appendChild(no);
        function votar(valor) {
            while (cont.firstChild) { cont.removeChild(cont.firstChild); }
            cont.appendChild(el('span', null, valor
                ? '¡Gracias! Nos alegra que te haya servido.'
                : 'Gracias. Usaremos tu opinión para mejorar esta respuesta.'));
            cont.setAttribute('role', 'status');
            input.focus();
            pedir({ accion: 'util', consulta_id: consultaId, util: valor ? 1 : 0 }).catch(function () { /* no es crítico */ });
        }
        si.addEventListener('click', function () { votar(true); });
        no.addEventListener('click', function () { votar(false); });
        return cont;
    }

    function tarjetaRespuesta(r, conTitulo) {
        var t = el('article', 'bf-tarjeta');
        if (conTitulo && texto(r.pregunta)) {
            t.appendChild(el('h3', 'bf-pregunta', r.pregunta));
        }
        t.appendChild(el('p', 'bf-texto', texto(r.texto)));

        var enlaces = Array.isArray(r.enlaces) ? r.enlaces : [];
        if (enlaces.length) {
            var ul = el('ul', 'bf-enlaces');
            enlaces.forEach(function (x) {
                if (!x) { return; }
                var li = el('li');
                li.appendChild(enlace(x.url, x.titulo));
                ul.appendChild(li);
            });
            t.appendChild(ul);
        }

        if (texto(r.fuente)) {
            var f = el('p', 'bf-meta', 'Fuente: ');
            f.appendChild(enlace(r.fuente, r.fuente));
            t.appendChild(f);
        }
        if (texto(r.area)) {
            t.appendChild(el('p', 'bf-meta', 'Área responsable: ' + r.area));
        }
        if (r.validada === false) {
            t.appendChild(el('p', 'bf-revision', 'Información en revisión por el área.'));
        }
        return t;
    }

    function pintarContacto(tarjeta, c) {
        if (!c || typeof c !== 'object') { return; }
        var ul = el('ul', 'bf-contacto');
        if (texto(c.nombre)) {
            var n = el('li');
            n.appendChild(el('strong', null, c.nombre));
            ul.appendChild(n);
        }
        var correo = texto(c.correo).trim();
        if (correo && /^[^\s@<>"'()]+@[^\s@<>"'()]+\.[^\s@<>"'()]+$/.test(correo)) {
            var lc = el('li', null, 'Correo: ');
            var a = el('a', null, correo);
            a.href = 'mailto:' + correo;
            lc.appendChild(a);
            ul.appendChild(lc);
        }
        var tel = texto(c.telefono).trim();
        var telLimpio = tel.replace(/[^\d+]/g, '');
        if (tel && telLimpio.replace(/\D/g, '').length >= 7) {
            var lt = el('li', null, 'Teléfono: ');
            var at = el('a', null, tel);
            at.href = 'tel:' + telLimpio;
            lt.appendChild(at);
            ul.appendChild(lt);
        }
        if (urlSegura(c.url)) {
            var lu = el('li', null, 'Sitio: ');
            lu.appendChild(enlace(c.url, c.url));
            ul.appendChild(lu);
        }
        if (ul.childNodes.length) {
            tarjeta.appendChild(el('p', 'bf-meta', 'Puedes consultar directamente con:'));
            tarjeta.appendChild(ul);
        }
    }

    function pintarResultado(turno, json) {
        var resultado = texto(json.resultado);
        var sugerencias = Array.isArray(json.sugerencias) ? json.sugerencias : [];
        var r = json.respuesta && typeof json.respuesta === 'object' ? json.respuesta : null;
        var tarjeta;

        if (resultado === 'respondida' && r) {
            tarjeta = tarjetaRespuesta(r, true);
            if (sugerencias.length) {
                var rel = el('div', 'bf-bloque');
                rel.appendChild(el('p', 'bf-subtitulo', 'Preguntas relacionadas'));
                rel.appendChild(botonesOpciones(sugerencias, function (s) { ver(s.id, s.pregunta); }));
                tarjeta.appendChild(rel);
            }
            if (typeof json.consulta_id === 'number' && json.consulta_id > 0) {
                tarjeta.appendChild(bloqueUtil(json.consulta_id));
            }
        } else if (resultado === 'no_ofertada' && r) {
            tarjeta = tarjetaRespuesta({ texto: r.texto, fuente: r.fuente, validada: r.validada }, false);
            tarjeta.classList.add('bf-sin');
        } else if (resultado === 'sugerencias' && sugerencias.length) {
            tarjeta = el('article', 'bf-tarjeta');
            tarjeta.appendChild(el('h3', 'bf-pregunta', '¿Quisiste decir…?'));
            tarjeta.appendChild(botonesOpciones(sugerencias, function (s) { ver(s.id, s.pregunta); }));
        } else {
            tarjeta = el('article', 'bf-tarjeta bf-sin');
            tarjeta.appendChild(el('h3', 'bf-pregunta', 'No tengo esa información'));
            tarjeta.appendChild(el('p', 'bf-texto', 'No encontré una respuesta para tu pregunta. Intenta escribirla de otra forma.'));
            pintarContacto(tarjeta, json.canalizar);
        }
        turno.appendChild(tarjeta);
        mostrar(turno);
    }

    function pintarError(turno, err) {
        var t = el('article', 'bf-tarjeta bf-error');
        t.setAttribute('role', 'alert');
        t.appendChild(el('p', 'bf-texto', err && err.message ? err.message : 'Ocurrió un problema. Inténtalo de nuevo.'));
        turno.appendChild(t);
        mostrar(turno);
    }

    function ejecutar(etiqueta, datos) {
        if (ocupado) { return; }
        var turno = nuevoTurno(etiqueta);
        ponerOcupado(true);
        pedir(datos).then(function (json) {
            guardarContexto(json);
            pintarResultado(turno, json);
        }, function (err) {
            pintarError(turno, err);
        }).then(function () {
            ponerOcupado(false);
        });
    }

    function buscar(consulta) {
        ejecutar(consulta, { accion: 'buscar', consulta: consulta, contexto: { carrera: contexto.carrera } });
    }

    function ver(id, pregunta) {
        var n = parseInt(id, 10);
        if (!(n > 0)) { return; }
        ejecutar(texto(pregunta) || 'Ver respuesta', { accion: 'ver', id: n, contexto: { carrera: contexto.carrera } });
        input.focus();
    }

    form.addEventListener('submit', function (ev) {
        ev.preventDefault();
        var consulta = input.value.replace(/\s+/g, ' ').trim().slice(0, MAX);
        if (!consulta) {
            input.focus();
            return;
        }
        if (ocupado) { return; }
        input.value = '';
        actualizarContador();
        buscar(consulta);
    });

    actualizarContador();
})();
