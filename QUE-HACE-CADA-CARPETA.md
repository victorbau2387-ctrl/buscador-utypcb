# 🗺️ ¿Qué hace cada carpeta? Mapa del proyecto, explicado fácil

Este documento explica **cómo funciona el buscador por dentro** y **para qué sirve cada carpeta y cada
archivo** del proyecto, con palabras sencillas. No necesitas saber programar para entenderlo.

> 📌 Para instalarlo y abrirlo, sigue la [Guía paso a paso](GUIA-DE-INSTALACION.md).
> Para usar el panel, lee el [Manual de usuario del panel](docs/06-manual-administrador.md).

---

## 1. La idea en una frase

Un alumno escribe una duda en el sitio de la UTyP («q carreras ay»), el buscador **entiende** lo que
quiso decir aunque tenga faltas, **busca** la pregunta oficial que más se parece y le **muestra la
respuesta** con su fuente. El personal de la universidad **mantiene esas respuestas** desde el
**Panel del buscador**.

## 2. Las piezas, como si fuera una escuela 🏫

| Pieza | Es como… | Qué hace |
|---|---|---|
| **El botón «¿Tienes dudas?»** (widget) | La **ventanilla de informes** | Donde el alumno escribe su duda y lee la respuesta. |
| **El motor de búsqueda** | La **persona de informes** que sabe buscar | Entiende la pregunta y encuentra la respuesta correcta. |
| **La base de datos** (MariaDB) | El **archivero** con todas las fichas | Guarda las preguntas, las respuestas, las cuentas y lo que la gente pregunta. |
| **El panel** | La **oficina** del personal | Donde se escriben, corrigen y validan las respuestas. |
| **Los archivos del sitio** (`*_data.php`) | El **pizarrón de avisos** de la escuela | Calendario, carreras y noticias: el buscador los lee de ahí, siempre actualizados. |
| **PHP** | La **electricidad** del edificio | El programa que hace funcionar todo lo anterior. |

## 3. ¿Qué pasa cuando alguien pregunta? (paso a paso)

Ejemplo: un alumno escribe **«cuanto cuesta la inscrpcion»** (con falta de ortografía).

1. **La ventanilla** (`public_html/buscador/widget.js`) envía la pregunta al servidor.
2. **La puerta de entrada** (`public_html/buscador/api.php`) la recibe y revisa que todo esté bien:
   que no sea demasiado larga, que no sean demasiadas preguntas seguidas y que venga del sitio correcto.
3. **El motor** (`private/src/Search/`) la «limpia» y la entiende:
   - quita acentos, mayúsculas y signos: `cuanto cuesta la inscrpcion`;
   - quita palabras que no ayudan («la», «el», «de»…): `cuanto cuesta inscrpcion`;
   - cambia sinónimos por una palabra común: «cuánto cuesta» = **costo**;
   - corrige la falta: `inscrpcion` se parece mucho a **inscripción**.
4. **El motor compara** contra las 173 preguntas y sus 918 variantes, y le pone una **calificación**
   de 0 a 1 a cada una. La mejor: *«¿Cuánto cuesta la inscripción?»* con **1.00**.
5. **Decide qué contestar** según la calificación:
   - **0.62 o más** → da la respuesta directo.
   - **entre 0.30 y 0.62** → ofrece «¿Quisiste decir…?» con hasta 3 opciones.
   - **menos de 0.30** → dice que no tiene esa información y da el contacto del área.
6. **Guarda un registro** sin datos personales: qué se preguntó y qué se respondió, para que el
   personal vea en el panel lo que falta.
7. **La ventanilla muestra** la respuesta, su **fuente oficial** y las preguntas relacionadas.

Todo esto tarda **menos de 50 milisegundos** (0.05 segundos): mucho menos que un parpadeo. 🚀

---

## 4. Las carpetas del proyecto

```
buscador-utypcb/
├── 📄 GUIA-DE-INSTALACION.md   ← empieza aquí
├── 📄 QUE-HACE-CADA-CARPETA.md ← este documento
├── 📄 README.md
├── ⚙️ instalar-herramientas.ps1
├── ⚙️ iniciar-panel.cmd
├── 📁 conocimiento/      ← las preguntas y respuestas
├── 📁 database/          ← cómo está organizada la base de datos
├── 📁 docs/              ← los manuales
├── 📁 integracion/       ← cómo se agrega al sitio de la UTyP
├── 📁 private/           ← el cerebro (nunca visible desde internet)
├── 📁 public_html/       ← lo que sí se ve desde internet
└── 📁 tests/             ← pruebas y la página de práctica
```

### 📄 Archivos sueltos (en la carpeta principal)

| Archivo | Para qué sirve |
|---|---|
| `GUIA-DE-INSTALACION.md` | Cómo instalarlo y abrirlo, paso a paso. |
| `QUE-HACE-CADA-CARPETA.md` | Este documento. |
| `README.md` | La presentación del proyecto, la que se ve al abrir el repositorio en GitHub. |
| `instalar-herramientas.ps1` | **Se usa una vez.** Descarga e instala PHP y MariaDB en `dev-tools` (sección 5). |
| `iniciar-panel.cmd` | **Se usa siempre.** Enciende todo y abre el panel en el navegador. |
| `.gitignore` | Lista de archivos que **no** deben subirse a GitHub (por ejemplo, contraseñas y configuraciones reales). |
| `.gitattributes` | Le dice a Git cómo guardar los archivos para que funcionen en Windows. |

### 📁 `conocimiento/` — las preguntas y respuestas 📋

Es **lo más importante del contenido**: todo lo que el buscador sabe.

| Archivo | Para qué sirve |
|---|---|
| `preguntas.csv` | Las **173 preguntas** con sus variantes, respuestas, fuente oficial y área. Se abre en **Excel**. |
| `areas.csv` | Las **oficinas** responsables (Servicios Escolares, Vinculación…) con sus contactos oficiales. |
| `fuentes.md` | De **qué página oficial** salió cada dato y la lista de lo que falta por área (los `[PENDIENTE]`). |

> Cuando el panel arranca por primera vez, copia estas preguntas a la base de datos.

### 📁 `database/` — el plano del archivero 🗄️

| Archivo | Para qué sirve |
|---|---|
| `schema.sql` | El **plano** de la base de datos: qué «cajones» (tablas) tiene y qué guarda cada uno. No trae datos, solo la estructura. |

Las tablas que crea:

| Tabla | Guarda |
|---|---|
| `bf_faq` | Las preguntas y sus respuestas. |
| `bf_variante` | Las formas distintas de hacer cada pregunta. |
| `bf_area` | Las oficinas y sus contactos. |
| `bf_consulta` | Lo que la gente pregunta (sin datos personales), para las estadísticas y «Sin respuesta». |
| `bf_admin` | Las cuentas del panel (las contraseñas se guardan cifradas, nunca en texto). |
| `bf_limite` | Un contador para frenar a quien hace demasiadas preguntas seguidas o intenta adivinar contraseñas. |

### 📁 `docs/` — los manuales 📚

| Archivo | Para quién | Qué explica |
|---|---|---|
| `01-requerimientos-y-alcance.md` | Revisor | Qué hace y qué **no** hace el buscador, y cómo se califica. |
| `02-manual-tecnico.md` | Programadores | Cómo está construido por dentro, con diagramas. |
| `03-instalacion-e-integracion.md` | Responsable técnico | Cómo subirlo al hosting **real** de la universidad (CWP). |
| `04-base-de-conocimiento.md` | Áreas y revisor | Cómo se escriben y validan las preguntas. |
| `05-plan-de-pruebas.md` | Revisor | Cómo se prueba que funciona bien. |
| `06-manual-administrador.md` | **Personal que usa el panel** | Cómo usar el panel, paso a paso. |
| `07-aviso-privacidad-y-seguridad.md` | Todos | Qué se guarda, qué no, y cómo se protege. |

### 📁 `integracion/` — para ponerlo en el sitio real 🔌

| Archivo | Para qué sirve |
|---|---|
| `LEEME.md` | Qué archivos se agregan al sitio de la UTyP y cómo quitarlo si hace falta. |
| `footer.inc.php.example` | Muestra la **única línea** que hay que agregar al pie de página del sitio para que aparezca el botón «¿Tienes dudas?». |

### 📁 `private/` — el cerebro 🧠 (nunca se ve desde internet)

En el hosting real, esta carpeta va **fuera** de la parte pública, para que nadie pueda abrirla desde
el navegador. Aquí vive toda la lógica.

| Carpeta o archivo | Para qué sirve |
|---|---|
| `config.example.php` | **Ejemplo** de configuración: base de datos, límites y contactos. En el hosting se copia como `config.php` con los datos reales (ese archivo **nunca** se sube a GitHub). |
| `data/lexico.php` | El **diccionario** del buscador: sinónimos («cuánto sale» = costo), abreviaturas de chat («q» = que), palabras que se ignoran y carreras que **no** se ofrecen. Se puede editar sin tocar el motor. |
| `cache/` y `logs/` | Carpetas vacías donde el programa guarda archivos temporales y el registro de errores. |

**`private/src/` — el código del programa**, dividido en partes:

| Carpeta | Qué hay | Archivos principales |
|---|---|---|
| `Search/` 🔍 | **El motor de búsqueda** | `Texto.php` limpia y entiende las palabras (acentos, faltas, sinónimos) · `Indice.php` califica qué tan parecida es cada pregunta · `Buscador.php` decide qué contestar · `Carreras.php` detecta de qué carrera se habla y las no ofertadas · `Dinamicas.php` arma respuestas con el calendario, las noticias y las carreras del sitio · `Repositorio.php` lee las preguntas de la base · `Csv.php` lee los archivos CSV |
| `Admin/` 🔐 | **El panel** | `Auth.php` inicio de sesión y seguridad · `App.php` decide qué pantalla mostrar · `Vista.php` arma el diseño de las páginas · `Faqs.php`, `Areas.php`, `Cuentas.php` guardan y validan los datos · `Importador.php` y `Exportador.php` pasan de CSV a base de datos y viceversa |
| `Admin/Pantalla/` 🖥️ | **Cada pantalla del panel** | `Acceso.php` (login) · `Tablero.php` · `Preguntas.php` · `Probador.php` · `SinRespuesta.php` · `Areas.php` · `CuentasPantalla.php` · `Exportar.php` |
| `Api/` 🚪 | **La puerta de entrada** del buscador | `BuscarApi.php` recibe las preguntas del botón, revisa que sean seguras y devuelve la respuesta |
| `Site/` 🏫 | **Lectura del sitio de la UTyP** | `DatosSitio.php` lee `calendario_data.php`, `carreras_data.php`, etc. del sitio real, sin copiarlos |
| `Support/` 🧰 | **Herramientas de apoyo** | `Db.php` conexión a la base de datos · `Config.php` lee la configuración · `Http.php` respuestas seguras · `RateLimiter.php` frena a quien abusa |
| `bootstrap.php` | **El interruptor general** | Prepara todo lo anterior cada vez que alguien usa el buscador o el panel |

**`private/bin/` — comandos para el responsable técnico** (se usan desde la ventana de comandos):

| Archivo | Para qué sirve |
|---|---|
| `crear-admin.php` | Crea una cuenta del panel (lo usa `iniciar-panel.cmd` la primera vez). |
| `importar.php` | Pasa las preguntas del CSV a la base de datos. Con `--dry-run` solo revisa errores, sin cambiar nada. |
| `evaluar.php` | **Califica** al buscador con una lista de preguntas y saca el porcentaje de aciertos. Con `--interactivo` sirve para la **defensa** frente al revisor. |
| `probar.php` | Prueba rápida del motor desde la ventana de comandos, sin abrir el navegador. |
| `purgar.php` | Borra las consultas viejas (más de 90 días) para cuidar la privacidad. En el hosting se programa para que corra solo cada noche. |

### 📁 `public_html/buscador/` — lo que sí se ve desde internet 🌐

| Archivo | Para qué sirve |
|---|---|
| `widget.php` | El **botón «¿Tienes dudas?»** y su ventanita. Es lo que se agrega al pie del sitio. |
| `widget.js` | Lo que hace que el botón **funcione**: abre la ventanita, manda la pregunta y muestra la respuesta. |
| `widget.css` | El **diseño** del botón: los colores verde y naranja de la UTyP y las fuentes del sitio. |
| `api.php` | La dirección a la que el botón manda las preguntas (solo pasa el mensaje a `private/`). |
| `privacidad.php` | El **aviso de privacidad** que se ve desde el pie del buscador. |
| `_ruta_privada.php` | Le dice a los archivos públicos **dónde** está la carpeta `private/`. |
| `admin/index.php` | La entrada al **panel**. |
| `admin/admin.css` | El **diseño** del panel y del login. |
| `admin/admin.js` | El **ojito** 👁 para mostrar u ocultar la contraseña. |

### 📁 `tests/` — pruebas y página de práctica 🧪

Nada de esta carpeta se sube al hosting real: sirve para comprobar que todo funciona y para practicar
en tu computadora.

| Carpeta o archivo | Para qué sirve |
|---|---|
| `ejecutar.php` | Corre las **103 pruebas automáticas** del motor. Si todas pasan, dice `RESULTADO: OK`. |
| `endpoint.php` | Prueba la **seguridad** de la puerta de entrada (19 pruebas: que rechace ataques, abusos, etc.). |
| `compatibilidad.php` | Revisa que el código funcione en **PHP 7.4 y 8.x**, como pide el hosting. |
| `config.pruebas.php` | Configuración especial para las pruebas. |
| `unit/` | Las pruebas, una por cada parte del motor (`01_TextoPrueba.php`, `05_BuscadorPrueba.php`…). |
| `lib/` | Herramientas que usan las pruebas. |
| `fixtures/` | **Datos de práctica**: copias del calendario, carreras y noticias para probar sin el sitio real. |
| `preguntas/desarrollo.csv` | 102 preguntas de práctica para afinar el buscador. **No** cuentan para la calificación. |
| `preguntas/reales.csv` | Aquí van las **100 preguntas reales** de aspirantes y alumnos para la calificación oficial (todavía vacío). |
| `preguntas/LEEME.md` | Por qué las preguntas reales no se deben ver antes de evaluar. |
| `resultados/` | Donde `evaluar.php` guarda sus informes. |

**`tests/sitio-local/` — el sitio de práctica en tu computadora:**

| Archivo | Para qué sirve |
|---|---|
| `iniciar.cmd` | Enciende todo (base de datos + servidor). `iniciar-panel.cmd` lo usa por dentro. |
| `index.php` + `replica.php` | Muestran la **portada real de utypcb.edu.mx con el botón** agregado, para ver cómo quedará (necesita internet). |
| `pruebas.php` | Página sencilla para que los programadores prueben errores y modos especiales. |
| `router.php` | Dirige cada dirección del navegador al archivo correcto en tu computadora. |
| `tunel.cmd` | Crea un **enlace temporal** para mostrarlo por internet a otra persona. |
| `config.local.php`, `config.tunel.php` | Configuraciones para tu computadora y para el túnel. |
| `api-simulada.php` | Una puerta de entrada «de mentiritas» que funciona **sin base de datos**, para probar el botón. |
| `header.inc.php`, `footer.inc.php` | Encabezado y pie de página simulados. |
| `LEEME.md` | Instrucciones técnicas del sitio de práctica. |

---

## 5. Carpetas que se crean en tu computadora (no están en GitHub)

`instalar-herramientas.ps1` crea esta carpeta en tu usuario de Windows
(`C:\Users\TU-USUARIO\dev-tools`):

| Carpeta | Qué es |
|---|---|
| `php74/` | **PHP 7.4**, la versión que usa hoy el hosting de la universidad. |
| `php83/` | **PHP 8.3**, la versión a la que se va a migrar. El panel usa esta. |
| `mariadb/` | El programa de la **base de datos** MariaDB. |
| `mariadb-data/` | ⭐ **Tus datos**: preguntas, cambios del panel, cuentas y consultas. Si la borras, se pierden; usa **Exportar CSV** antes. |
| `dl/` | Los archivos descargados (ZIP). Se pueden borrar después de instalar. |

Y en la carpeta del proyecto puede aparecer `tests/sitio-local/.cache/`, con la copia guardada de la
portada de la UTyP para la vista previa. Se puede borrar sin problema.

---

## 6. ¿Qué toco para…?

| Quiero… | Dónde |
|---|---|
| Cambiar una respuesta o agregar una pregunta | En el **panel** → Preguntas (lo más fácil), o en `conocimiento/preguntas.csv` |
| Agregar un sinónimo («chamba» = trabajo) | `private/data/lexico.php` |
| Cambiar los colores del botón | `public_html/buscador/widget.css` |
| Cambiar los colores del panel | `public_html/buscador/admin/admin.css` |
| Cambiar qué tan «exigente» es el buscador | `private/config.php` → `umbral_respuesta` y `umbral_sugerencias` |
| Ver cómo entiende una pregunta | Panel → **Probador** |
| Saber si algo se rompió | `tests/ejecutar.php` (debe decir `RESULTADO: OK`) |
| Instalarlo en el sitio real | `docs/03-instalacion-e-integracion.md` |
