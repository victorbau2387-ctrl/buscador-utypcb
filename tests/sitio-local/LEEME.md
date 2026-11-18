# Sitio de prueba local del widget

Página que imita `header.inc.php` / `footer.inc.php` del sitio institucional para desarrollar y
probar el widget del buscador. **Solo para desarrollo**: no se sube al hosting y no está dentro de
`public_html`.

| Archivo | Para qué |
|---|---|
| `index.php` | Página simulada con las variables CSS del sitio (`--naranja`, `--verde`, …) y fuentes Fraunces/Archivo. |
| `header.inc.php`, `footer.inc.php` | Encabezado y pie simulados. El pie incluye `public_html/buscador/widget.php`, como lo hará el sitio real. |
| `router.php` | Router del servidor embebido: `/buscador/*` → `public_html/buscador/*`. |
| `api-simulada.php` | Mismo contrato que `api.php`, pero sin MariaDB: usa el motor real con `conocimiento/preguntas.csv`. |
| `config.local.php` | Configuración opcional para usar la API real con un MariaDB local (puerto 3307, base `bf_widget`). |

## Levantar el servidor

Desde la raíz del repositorio, en PowerShell:

```powershell
$env:BF_CONFIG = "tests/config.pruebas.php"
C:\Users\Usuario\dev-tools\php83\php.exe -S 127.0.0.1:8080 -t tests/sitio-local tests/sitio-local/router.php
```

En Git Bash:

```bash
BF_CONFIG=tests/config.pruebas.php /c/Users/Usuario/dev-tools/php83/php.exe -S 127.0.0.1:8080 -t tests/sitio-local tests/sitio-local/router.php
```

Para probar con PHP 7.4 cambia `php83` por `php74`.

Abre en el navegador:

- `http://127.0.0.1:8080/?api=simulada` — **recomendado**: API simulada, no necesita base de datos.
- `http://127.0.0.1:8080/?api=simulada&sprite=1` — simula `icons.inc.php` (`utyp_sprite()`); el sprite
  no trae `i-enviar` a propósito, para comprobar el icono de respaldo.
- `http://127.0.0.1:8080/?api=simulada&forzar=429` — error 429 (también `500`, `lento` = tiempo
  agotado a los 8 s, `basura` = respuesta que no es JSON).
- `http://127.0.0.1:8080/` — API real (`/buscador/api.php`). Necesita MariaDB (ver abajo).

## Usar la API real (opcional)

`api.php` necesita la base de datos (límite por minuto, registro de consultas, sugerencias de inicio).
Además, el navegador envía la cabecera `Origin` en los POST y `Http::cors()` solo acepta los orígenes
configurados, así que hay que permitir `http://127.0.0.1:8080`. `config.local.php` hace ambas cosas:

```bash
mysql -h 127.0.0.1 -P 3307 -u root -e "CREATE DATABASE bf_widget CHARACTER SET utf8mb4"
mysql -h 127.0.0.1 -P 3307 -u root bf_widget < database/schema.sql
# importa preguntas con el script de importación del núcleo, luego:
BF_CONFIG=tests/sitio-local/config.local.php /c/Users/Usuario/dev-tools/php83/php.exe -S 127.0.0.1:8080 -t tests/sitio-local tests/sitio-local/router.php
```

## Qué revisar

- Teclado: Tab hasta el botón, Enter o Espacio abre; Tab no sale del panel; Escape cierra y el foco
  vuelve al botón.
- "ingeniería civil" y luego "¿y cuánto cuesta?" (el contexto de la carrera se reenvía).
- "¿tienen medicina?" al inicio de la sesión (no ofertada), "xyz abc" (sin respuesta + contacto),
  "que es la ingenieria en energia" (sugerencias: "¿Quisiste decir…?").
- Escribe más de 300 caracteres: el campo se detiene y el contador cambia de color.
- Ventana de menos de 480 px: el panel ocupa toda la pantalla.
- Con "reducir movimiento" activado en el sistema no hay animaciones.
