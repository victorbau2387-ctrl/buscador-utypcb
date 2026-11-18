# Integración del widget en el sitio

El buscador de preguntas frecuentes se muestra como un botón flotante
("¿Tienes dudas? Pregunta aquí") en todas las páginas que usan `footer.inc.php`.
No es una página aparte: no tiene encabezado ni menú propios.

## Archivos que se agregan (no se modifica ningún archivo existente del sitio salvo una línea)

Dentro de `public_html/buscador/`:

| Archivo | Qué es |
|---|---|
| `widget.php` | Marcado del botón y del panel; enlaza `widget.css` y `widget.js` con `?v=` (fecha de modificación) para evitar caché vieja. |
| `widget.css` | Estilos. Todas las clases llevan el prefijo `.bf-`. Solo usa las variables del sitio (`--naranja`, `--verde`, `--tinta`, `--espuma`, `--texto`, `--ff-display`, `--ff-texto`, `--sp-bloque`, `--e-suave`) con valores de respaldo. No declara fuentes. |
| `widget.js` | JavaScript vanilla, sin librerías. |
| `api.php`, `_ruta_privada.php` | Endpoint del buscador (lo entrega el núcleo). |

Y la carpeta `private/` del buscador fuera de `public_html` (ver la guía de instalación del núcleo).

## La única línea que se modifica

En `footer.inc.php`, justo antes de `</body>` (después de los scripts que ya carga el sitio):

```php
<?php require $_SERVER['DOCUMENT_ROOT'] . '/buscador/widget.php'; ?>
```

`integracion/footer.inc.php.example` muestra dónde va la línea.

## Configuración opcional

Si el buscador no queda en `/buscador/`, define estas constantes **antes** de la línea anterior
(por ejemplo en `header.inc.php`):

```php
<?php
define('BF_WIDGET_API', '/buscador/api.php'); // URL del endpoint (mismo dominio; no se abre CORS)
define('BF_WIDGET_BASE', '/buscador/');       // carpeta donde están widget.css y widget.js
// Ids del sprite de icons.inc.php, si se llaman distinto:
define('BF_WIDGET_ICONOS', ['buscar' => 'i-buscar', 'cerrar' => 'i-cerrar', 'enviar' => 'i-enviar']);
```

Iconos: si existe la función `utyp_sprite()` (icons.inc.php), el widget usa `<use href="#i-...">`.
Si el id no existe en el sprite, `widget.js` dibuja un icono de respaldo; sin `utyp_sprite()` se usa
directamente un SVG mínimo en línea.

## Cómo revertir

1. Borra (o comenta) la línea `require … /buscador/widget.php` de `footer.inc.php`. El botón desaparece
   de inmediato en todo el sitio.
2. Opcional: borra la carpeta `public_html/buscador/` y la carpeta `private/` del buscador.

No hay otros cambios que deshacer: el widget no escribe cookies ni `localStorage`; el contexto de la
conversación vive solo en la memoria de la página.

## Comprobación rápida después de integrar

- El botón aparece abajo a la derecha y abre el panel con Enter o Espacio.
- Escape cierra el panel y el foco regresa al botón.
- Al buscar "¿cuánto cuesta la inscripción?" aparece una respuesta con su fuente.
- En el teléfono (menos de 480 px de ancho) el panel ocupa toda la pantalla.
