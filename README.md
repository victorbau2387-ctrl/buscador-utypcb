# Buscador de preguntas frecuentes · UTyP Coyuca de Benítez

> 🦊 **¿Primera vez?** Sigue la [**Guía paso a paso de instalación**](GUIA-DE-INSTALACION.md): explica todo desde cero, desde abrir la carpeta hasta entrar al panel.

Buscador de preguntas frecuentes con comprensión tolerante para el sitio de la Universidad Tecnológica y Politécnica de Coyuca de Benítez. El visitante escribe con sus palabras (con faltas, abreviaturas de chat o sin acentos) y recibe la respuesta oficial validada, sugerencias ("¿Quisiste decir…?"), el aviso de que una carrera no se ofrece, o la canalización al área responsable.

- No genera texto ni usa inteligencia artificial externa: solo devuelve respuestas registradas o datos que ya publica el sitio (calendario, carreras, noticias, deportes, trípticos).
- No guarda datos personales.
- PHP 7.4 y 8.x + MariaDB, sin Composer, Node ni procesos persistentes. Se instala por FTP en hosting CWP.

Alcance completo y criterios de aceptación: [docs/01](docs/01-requerimientos-y-alcance.md).

## Estructura

```
├── conocimiento/             Base de conocimiento editable en Excel (preguntas.csv, areas.csv)
├── database/schema.sql       Tablas bf_* para MariaDB 10.11
├── docs/                     Documentación de entrega
├── private/                  Va FUERA de public_html en el servidor
│   ├── bin/                  probar.php, evaluar.php, importar.php, purgar.php, crear-admin.php
│   ├── data/lexico.php       Abreviaturas, frases, sinónimos, palabras vacías, alias de carreras, no ofertadas
│   ├── src/                  Motor (Search/), endpoint (Api/), datos del sitio (Site/), soporte (Support/), panel (Admin/)
│   ├── config.example.php    Plantilla de private/config.php (este último nunca se sube al repositorio)
│   ├── cache/  logs/
├── public_html/buscador/     api.php (endpoint), widget.php/js/css, admin/ (panel)
└── tests/
    ├── ejecutar.php          Pruebas unitarias (mini ejecutor propio, sin PHPUnit)
    ├── endpoint.php          Pruebas de seguridad del endpoint (requiere MariaDB)
    ├── compatibilidad.php    Sintaxis exclusiva de PHP 8 + php -l con PHP 7.4
    ├── unit/                 Pruebas por componente
    ├── fixtures/             Datos simulados del sitio y base de conocimiento congelada para pruebas
    ├── preguntas/            Baterías para evaluar.php (desarrollo.csv es solo un ejemplo)
    └── resultados/           Informes generados por evaluar.php
```

## Arranque rápido local (Windows)

En la máquina de desarrollo PHP es portátil y no está en el PATH:

```
set PHP74=C:\Users\Usuario\dev-tools\php74\php.exe
set PHP83=C:\Users\Usuario\dev-tools\php83\php.exe

REM Probar el motor sin base de datos (usa conocimiento/*.csv y tests/fixtures/site_data)
%PHP83% private\bin\probar.php "cuanto cuesta la inscripcion" "info de la carrera de civil" "y el triptico?"
```

En PowerShell: `$PHP83 = 'C:\Users\Usuario\dev-tools\php83\php.exe'; & $PHP83 private\bin\probar.php "becas"`.

Con MariaDB local (p. ej. en `127.0.0.1:3307`):

1. Crea una base vacía e importa `database/schema.sql`.
2. Copia `private/config.example.php` como `private/config.php` y ajusta `db.*`, `sitio.ruta_datos` (para desarrollo: la ruta absoluta de `tests/fixtures/site_data`) y `seguridad.sal_hash`.
3. Importa la base de conocimiento: `%PHP83% private\bin\importar.php`.
4. Levanta el servidor embebido: `%PHP83% -S 127.0.0.1:8080 -t public_html` y prueba `http://127.0.0.1:8080/buscador/api.php` con POST JSON (ejemplos en [docs/02](docs/02-manual-tecnico.md#3-endpoint-post-buscadorapiphp)).

## Pruebas

```
%PHP74% tests\ejecutar.php            &  %PHP83% tests\ejecutar.php
%PHP83% tests\compatibilidad.php --php74=%PHP74%
%PHP74% tests\endpoint.php            &  %PHP83% tests\endpoint.php
```

- `ejecutar.php [filtro]` corre `tests/unit/*Prueba.php`. Código de salida 0/1. Los defectos del motor aún sin corregir aparecen como `[conocido]` con el caso que los reproduce y no rompen la suite.
- `endpoint.php` crea la base `bf_pruebas` en `127.0.0.1:3307` (configurable con `--db-*`), levanta el servidor embebido y verifica 405/403/400/422/429, CORS, XSS y cabeceras. Sin MariaDB termina con código **2** (omitido).
- `compatibilidad.php` busca `str_contains`, `match`, `enum`, `readonly`, `?->`, argumentos con nombre, promoción en constructor, etc., y ejecuta `php -l` con PHP 7.4.

## Evaluación

```
%PHP83% private\bin\evaluar.php tests\preguntas\desarrollo.csv
%PHP83% private\bin\evaluar.php tests\preguntas\aceptacion.csv --nombre=aceptacion --bd --minimo=85
%PHP83% private\bin\evaluar.php --interactivo
```

CSV `consulta,esperado[,grupo]`; genera `tests/resultados/evaluacion-<nombre>-<fecha>.md` y `.json` con aciertos directos, aciertos en sugerencia, matriz por categoría, fallos, versión de PHP y tiempos p50/p95/máx. `--interactivo` es para la defensa: el revisor escribe preguntas y califica cada respuesta. Detalles y reglas de la batería de 100 preguntas reales en [docs/05](docs/05-plan-de-pruebas.md).

## Documentación

| Documento | Contenido |
|---|---|
| [01 · Requerimientos y alcance](docs/01-requerimientos-y-alcance.md) | Qué hace y qué no, requerimientos, criterios de aceptación y cómo se miden |
| [02 · Manual técnico](docs/02-manual-tecnico.md) | Arquitectura, modelo de datos, endpoint con ejemplos reales, motor paso a paso con ejemplo trabajado |
| [03 · Instalación e integración](docs/03-instalacion-e-integracion.md) | CWP, MariaDB, configuración, FTP, importación, primera cuenta, cron, la línea de `footer.inc.php`, ModSecurity, cómo revertir |
| [04 · Base de conocimiento](docs/04-base-de-conocimiento.md) | Estado actual, formato del CSV, reglas de contenido, variantes, importar/exportar y validación por áreas |
| [05 · Plan de pruebas](docs/05-plan-de-pruebas.md) | Suite, evaluación, batería real, casos límite, móvil y accesibilidad, resultados |
| [06 · Manual de usuario del panel](docs/06-manual-administrador.md) | Entrar, roles, tablero, editar y validar preguntas, probador, sin respuesta, áreas, cuentas, exportar y uso en otra PC |
| [07 · Aviso de privacidad y seguridad](docs/07-aviso-privacidad-y-seguridad.md) | Qué se registra, retención, controles de seguridad y respuesta a incidentes |

## Usar el panel en otra PC (Windows)

1. `git clone https://github.com/victorbau2387-ctrl/buscador-utypcb.git` (repositorio privado).
2. Clic derecho en `instalar-herramientas.ps1` → **Ejecutar con PowerShell** (una sola vez: PHP 7.4, PHP 8.3 y MariaDB verificados por SHA-256 en `%USERPROFILE%\dev-tools`).
3. Doble clic en `iniciar-panel.cmd`. La primera vez carga las preguntas y pide crear tu cuenta.

Detalles en [docs/06 §11](docs/06-manual-administrador.md#11-usar-el-panel-en-otra-computadora-windows).

## Seguridad

No subas `private/config.php` ni contraseñas al repositorio. La carpeta `private/` debe quedar fuera de `public_html`. Ver [docs/07](docs/07-aviso-privacidad-y-seguridad.md).
