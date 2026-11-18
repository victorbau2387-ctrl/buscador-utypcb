# 03 · Instalación e integración (hosting CWP)

Guía para instalar el buscador en el hosting institucional (CentOS Web Panel: Apache 2.4 + nginx, ModSecurity, MariaDB 10.11, PHP 7.4 que migrará a 8.x) **por FTP**, sin Git ni Composer.

Convenciones: `USUARIO` es la cuenta de CWP; `/home/USUARIO/public_html` es la raíz del sitio. Ninguna contraseña se escribe en este documento ni en el repositorio.

## 1. Requisitos

| Requisito | Cómo comprobarlo |
|---|---|
| PHP 7.4 u 8.x con `pdo_mysql`, `mbstring`, `json`, `ctype` | CWP › PHP Version / PHP Selector; o un `phpinfo()` temporal que se borra al terminar |
| MariaDB 10.11 (o MySQL 5.7+) | CWP › MySQL Manager |
| Acceso FTP/SFTP a la cuenta | CWP › FTP Accounts |
| Cron | CWP (usuario) › Cron Jobs |
| Acceso SSH o terminal del panel (para `importar.php` y `crear-admin.php`) | [PENDIENTE] confirmar si la cuenta lo tiene. Si no, ver la alternativa en el paso 7. |

No se necesita Node.js, Python, Composer, Redis ni procesos persistentes.

## 2. Estructura en el servidor

`private/` va **fuera** de `public_html` para que nada de su contenido (configuración, código, léxico, logs) sea accesible por web.

```
/home/USUARIO/
├── private-buscador/          ← contenido de la carpeta private/ del repositorio
│   ├── bin/                   importar.php, purgar.php, crear-admin.php, probar.php, evaluar.php
│   ├── cache/
│   ├── data/lexico.php
│   ├── logs/                  php-error.log (debe poder escribirse)
│   ├── src/
│   ├── config.example.php
│   └── config.php             ← se crea en el paso 4 (nunca se sube al repositorio)
├── conocimiento-buscador/     ← (opcional) preguntas.csv y areas.csv para importar
└── public_html/
    ├── footer.inc.php         ← del sitio; se le agrega UNA línea (paso 9)
    ├── calendario_data.php, carreras_data.php, noticias_data.php, …   (del sitio, no se tocan)
    └── buscador/              ← contenido de public_html/buscador/ del repositorio
        ├── api.php
        ├── _ruta_privada.php
        ├── widget.php, widget.js, widget.css
        └── admin/
```

**Ruta de `private/`.** `public_html/buscador/_ruta_privada.php` define `BF_RUTA_PRIVADA`. Su valor por defecto es la carpeta `private` al lado de `public_html` (`/home/USUARIO/private`). Si la subes como `private-buscador` (recomendado, para no chocar con otras aplicaciones), edita esa línea:

```php
define('BF_RUTA_PRIVADA', $bfEnv ? $bfEnv : '/home/USUARIO/private-buscador');
```

Y en `private-buscador/bin/purgar.php` y en el cron usa la misma ruta.

## 3. Base de datos y usuario en MariaDB (desde CWP)

1. CWP › **MySQL Manager** › *Create Database*: nombre, p. ej. `USUARIO_buscador` (CWP suele anteponer el usuario).
2. *Create User*: p. ej. `USUARIO_bfapp`, con una contraseña **aleatoria y larga** (generada por el gestor de contraseñas de la institución). Host: `localhost`.
3. Asigna al usuario **solo** esa base, con los permisos `SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, REFERENCES` (los tres últimos solo durante la instalación; después pueden retirarse).
4. Abre **phpMyAdmin**, selecciona la base, pestaña *Importar* y sube `database/schema.sql` (UTF-8). Se crean las tablas `bf_area`, `bf_faq`, `bf_variante`, `bf_consulta`, `bf_admin` y `bf_limite`.

## 4. Configuración (`private-buscador/config.php`)

1. Copia `config.example.php` como `config.php` en la misma carpeta.
2. Ajusta:

| Clave | Valor |
|---|---|
| `entorno` | `'produccion'` (no muestra errores; los escribe en `logs/php-error.log`) |
| `db.host` / `db.puerto` | `'localhost'` / `3306` |
| `db.nombre` / `db.usuario` / `db.password` | Los del paso 3. La contraseña **solo** aquí. |
| `sitio.ruta_datos` | Ruta absoluta de la carpeta con los `*_data.php`: **`'/home/USUARIO/public_html'`**. No uses la expresión con `__DIR__` del ejemplo: sube un nivel de más (está registrado como defecto conocido en la suite). |
| `sitio.url_base` | `'https://utypcb.edu.mx'` [PENDIENTE: confirmar dominio] |
| `seguridad.origenes_permitidos` | Dominios exactos del sitio, con `https://`, p. ej. `['https://utypcb.edu.mx', 'https://www.utypcb.edu.mx']`. Nunca `*`. |
| `seguridad.sal_hash` | Valor aleatorio largo, distinto en cada instalación (ver abajo) |
| `seguridad.limite_por_minuto` | 20 (consultas por visitante por minuto) |
| `privacidad.dias_retencion` | 90 [PENDIENTE: confirmar con la institución] |
| `busqueda.solo_validadas` | `false` mientras las áreas validan; `true` cuando todas las respuestas publicadas estén validadas |
| `contacto_general` | Nombre, correo, teléfono y URL de Servicios Escolares [PENDIENTE] |

Para generar `sal_hash` (en tu computadora o por SSH):

```
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

3. Permisos: `config.php` 600 (o 640), carpetas 755, archivos 644, `logs/` y `cache/` escribibles por el usuario de PHP.

## 5. Despliegue por FTP, paso a paso

1. Descarga el paquete del repositorio (ZIP de la rama aprobada) y descomprímelo en tu computadora.
2. Borra del paquete lo que no va al servidor: `tests/`, `docs/`, `.git*`, `README.md` (opcional: se pueden conservar fuera de `public_html`).
3. Con el cliente FTP (FileZilla u otro, **en modo binario/automático y con SFTP o FTPS si el hosting lo permite**):
   1. Sube el contenido de `private/` a `/home/USUARIO/private-buscador/`.
   2. Sube `public_html/buscador/` a `/home/USUARIO/public_html/buscador/`.
   3. (Opcional) Sube `conocimiento/` a `/home/USUARIO/conocimiento-buscador/` para importarlo.
4. Crea `config.php` (paso 4) directamente en el servidor o súbelo y verifica que **no** quedó dentro de `public_html`.
5. Edita `_ruta_privada.php` si usaste `private-buscador` (paso 2).
6. Comprueba que `https://utypcb.edu.mx/buscador/api.php` abierto en el navegador responde `{"ok":false,"error":"Método no permitido."}` (405). Si ves código PHP o un error 500, revisa la versión de PHP y `private-buscador/logs/php-error.log`.
7. Comprueba que `https://utypcb.edu.mx/private-buscador/config.php` **no existe** (404): confirma que `private-buscador` quedó fuera de `public_html`.

## 6. Importar la base de conocimiento

Con SSH o la terminal del panel:

```
cd /home/USUARIO/private-buscador
php bin/importar.php --dry-run --areas=/home/USUARIO/conocimiento-buscador/areas.csv --preguntas=/home/USUARIO/conocimiento-buscador/preguntas.csv
php bin/importar.php --areas=/home/USUARIO/conocimiento-buscador/areas.csv --preguntas=/home/USUARIO/conocimiento-buscador/preguntas.csv
```

`--dry-run` solo valida y reporta errores por fila. Sin él importa todo en una transacción (todo o nada). Las preguntas se actualizan por `slug`. El detalle del formato del CSV estará en docs/04 (pendiente).

Prueba rápida después de importar:

```
php bin/evaluar.php --bd --interactivo
```

## 7. Crear la primera cuenta del panel

```
php /home/USUARIO/private-buscador/bin/crear-admin.php
```

Pide usuario, nombre, rol y la contraseña dos veces sin mostrarla. No hay cuentas ni contraseñas predefinidas.

Si la cuenta **no tiene SSH**: [PENDIENTE: confirmar con el área de sistemas]. Alternativa: generar el hash en una computadora con PHP (`php -r "echo password_hash(trim(fgets(STDIN)), PASSWORD_DEFAULT), PHP_EOL;"`, escribir la contraseña y pulsar Enter) e insertar la fila en `bf_admin` desde phpMyAdmin con `rol = 'superadmin'`. El hash nunca se comparte por correo o chat, y la contraseña se cambia desde el panel en el primer acceso.

El uso del panel se documentará en docs/06 (pendiente).

## 8. Cron de purga del historial

CWP (usuario) › **Cron Jobs** › agregar, todos los días a las 03:15:

```
15 3 * * * /usr/local/bin/php /home/USUARIO/private-buscador/bin/purgar.php
```

Ajusta la ruta del PHP de línea de comandos a la versión elegida en CWP (`which php` por SSH; p. ej. `/opt/alt/php74/usr/bin/php`). Para probarlo a mano: `php bin/purgar.php --verbose`. Borra las consultas con más de `privacidad.dias_retencion` días y los contadores vencidos de `bf_limite`.

## 9. Integración con el sitio: la única línea en `footer.inc.php`

Haz una copia de respaldo de `footer.inc.php` y agrega, justo antes de `</body>` (o al final del archivo si `</body>` está en otra plantilla), **esta única línea**:

```php
<?php require $_SERVER['DOCUMENT_ROOT'] . '/buscador/widget.php'; ?>
```

El widget no deja variables sueltas, no se duplica si el pie se incluye dos veces y usa por defecto `/buscador/api.php` en el mismo dominio (sin CORS). No hay que tocar ningún otro archivo del sitio.

## 10. Prueba con ModSecurity

ModSecurity (normalmente con el conjunto de reglas OWASP CRS) puede bloquear peticiones POST con JSON que contengan palabras que parezcan ataques. Después de instalar:

1. Abre el sitio, abre el buscador y haz 5 preguntas normales ("¿cuánto cuesta la inscripción?", "becas", "calendario", "¿tienen enfermería?", "la de sistemas").
2. Haz preguntas con caracteres especiales: comillas, `¿?`, acentos, "select", "script", "union", "<b>hola</b>".
3. Si alguna devuelve **403 con una página HTML** (no JSON) o el widget dice que el servicio no está disponible:
   - En CWP › **ModSecurity** (o *Security* › *Mod Security*) revisa el registro de auditoría (`/usr/local/apache/logs/modsec_audit.log` o el que indique CWP) buscando la URL `/buscador/api.php` y anota el **ID de la regla** (`[id "9xxxxx"]`).
   - Comprueba que la petición lleva `Content-Type: application/json` (el widget la envía así); sin ella algunas reglas analizan el cuerpo como formulario.
   - Las reglas que más suelen saltar con texto libre en español son las de inyección SQL o XSS por palabras sueltas. **No desactives ModSecurity para todo el sitio.** Pide al administrador del servidor una exclusión **solo para la ruta** `/buscador/api.php` y **solo para el ID de regla** detectado (p. ej. `SecRuleRemoveById` dentro de un `<LocationMatch "^/buscador/api\.php$">`).
   - El endpoint ya valida, limita la longitud, quita etiquetas, usa consultas preparadas y no refleja la consulta, por lo que la exclusión puntual no abre un hueco.
4. Repite la prueba. Revisa también que el **panel** (formularios con CSRF) funcione: crear y editar una pregunta con acentos y comillas.
5. nginx de CWP actúa como proxy frente a Apache: si las respuestas JSON llegan cortadas o con caché, verifica que nginx no cachee `/buscador/` (el endpoint envía `Cache-Control: no-store`).

Prueba automática contra el servidor ya instalado (desde una computadora con PHP; no reinicia contadores ni necesita la base):

```
php tests/endpoint.php --url=https://utypcb.edu.mx/buscador/api.php --origen=https://utypcb.edu.mx --limite=20
```

## 11. Revertir la instalación por completo

1. **Quita la línea** del paso 9 de `footer.inc.php` (o restaura la copia de respaldo). El sitio vuelve a estar exactamente como antes.
2. Elimina la tarea de cron del paso 8.
3. Borra por FTP `/home/USUARIO/public_html/buscador/`.
4. (Opcional) Exporta la base desde phpMyAdmin si la institución quiere conservar la base de conocimiento; el historial de consultas no contiene datos personales, pero puede purgarse antes con `TRUNCATE bf_consulta;`.
5. En CWP › MySQL Manager borra la base y el usuario creados en el paso 3.
6. Borra `/home/USUARIO/private-buscador/` y `/home/USUARIO/conocimiento-buscador/` (incluye `config.php` con la contraseña y `logs/`).
7. Si se pidió una exclusión de ModSecurity para `/buscador/api.php`, solicita que se retire.

No quedan archivos, tablas ni tareas del buscador en el servidor, y los `*_data.php` del sitio nunca se modificaron.
