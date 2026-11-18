# 05 · Plan de pruebas

## 1. Resumen

| Nivel | Qué verifica | Comando | Requiere |
|---|---|---|---|
| Unitarias | Texto, Indice, Carreras, Dinamicas, Buscador, Csv, base de conocimiento, funciones de seguridad | `php tests/ejecutar.php [filtro]` | Solo PHP |
| Compatibilidad | Sin funciones/sintaxis exclusivas de PHP 8; `php -l` con 7.4 | `php tests/compatibilidad.php [--php74=ruta]` | PHP 7.4 para `-l` |
| Endpoint | 405, 403, 400, 422, 429, CORS, XSS, cabeceras, privacidad del registro, tiempo | `php tests/endpoint.php` | MariaDB (sale con código 2 si no hay) |
| Evaluación | % de aciertos y tiempos con una batería de preguntas | `php private/bin/evaluar.php archivo.csv` | Solo PHP (CSV) o MariaDB (`--bd`) |
| Defensa en vivo | Preguntas del revisor, una por una | `php private/bin/evaluar.php --interactivo` | Igual que la evaluación |
| Manual | Móvil, accesibilidad, ModSecurity, panel | Listas de verificación de las secciones 6 y 7 | Navegador, dispositivos |

Sin PHPUnit ni Composer (el hosting no los tiene): `tests/lib/aserciones.php` es un mini marco con `prueba()`, `conocido()`, `igual()`, `contiene()`, `coincide()`, `entre()`, `lanza()`, etc. Códigos de salida: **0** todo bien, **1** algún fallo, **2** omitido (solo `endpoint.php`, sin MariaDB).

**Defectos conocidos.** Cuando una prueba descubre un defecto del motor que todavía no se corrige, se registra con `conocido('nombre', 'descripción', fn)`: se muestra como `[conocido]` con el caso que lo reproduce y **no** rompe la suite. Si un día pasa, aparece como `[ok*]` para que se quite la marca.

## 2. Cómo correr todo en PHP 7.4 y 8.x

En la máquina de desarrollo los PHP portátiles no están en el PATH:

```
C:\Users\Usuario\dev-tools\php74\php.exe tests\ejecutar.php
C:\Users\Usuario\dev-tools\php83\php.exe tests\ejecutar.php

C:\Users\Usuario\dev-tools\php83\php.exe tests\compatibilidad.php --php74=C:\Users\Usuario\dev-tools\php74\php.exe

C:\Users\Usuario\dev-tools\php74\php.exe tests\endpoint.php
C:\Users\Usuario\dev-tools\php83\php.exe tests\endpoint.php

C:\Users\Usuario\dev-tools\php74\php.exe private\bin\evaluar.php tests\preguntas\desarrollo.csv
C:\Users\Usuario\dev-tools\php83\php.exe private\bin\evaluar.php tests\preguntas\desarrollo.csv
```

En Linux o en el servidor basta con `php …` (o la ruta de la versión elegida en CWP, p. ej. `/opt/alt/php74/usr/bin/php`).

### 2.1 Pruebas del endpoint

`tests/endpoint.php`:

1. se conecta a MariaDB (por defecto `127.0.0.1:3307`, usuario `root`; se cambia con `--db-host`, `--db-port`, `--db-user`, `--db-pass`, `--db-nombre` o las variables `BF_DB_*`);
2. crea/recrea **solo** las tablas `bf_*` de la base `bf_pruebas` a partir de `database/schema.sql` y carga `tests/fixtures/conocimiento/*.csv`;
3. escribe una configuración temporal (origen permitido `https://utypcb.edu.mx`, límite de 8 por minuto, sal aleatoria, datos del sitio de `tests/fixtures/site_data`);
4. levanta `php -S 127.0.0.1:<puerto libre> -t public_html` con **el mismo PHP** que ejecuta la prueba;
5. hace peticiones HTTP reales y al final apaga el servidor y borra la configuración temporal.

Para probar una instalación ya desplegada (no reinicia contadores, así que la ráfaga solo exige que aparezca un 429):

```
php tests/endpoint.php --url=https://utypcb.edu.mx/buscador/api.php --origen=https://utypcb.edu.mx --limite=20
```

### 2.2 Evaluación (`private/bin/evaluar.php`)

Entrada: CSV UTF-8 `consulta,esperado[,grupo]`.

- `esperado`: slug de la pregunta, `sin_respuesta` o `no_ofertada`. Varios aceptables separados por `|` (p. ej. `calendario-evaluaciones|admision-requisitos`).
- `grupo` (opcional): filas **consecutivas** con el mismo grupo forman una conversación y comparten el contexto (p. ej. "información de la carrera de civil" → "¿y el tríptico?").

Cómo cuenta:

| Caso | Cuenta como |
|---|---|
| `respondida` con un slug esperado | acierto directo |
| `sin_respuesta` / `no_ofertada` cuando eso se esperaba | acierto directo |
| `sugerencias` y la **primera** es la esperada | acierto en sugerencia (se reporta aparte) |
| Cualquier otro caso | fallo |

Base de conocimiento: si existe `private/config.php` (o `--config`) y la base responde, usa MariaDB; si no, `conocimiento/*.csv`. Se fuerza con `--bd` o `--csv`. El tiempo por consulta incluye **cargar la base, construir el índice y buscar**, igual que cada petición al endpoint.

Salida: `tests/resultados/evaluacion-<nombre>-<fecha>.md` y `.json` con % de aciertos directos, % incluyendo sugerencias, resultados devueltos, matriz por categoría, lista de fallos (consulta, esperado, obtenido, confianza), aciertos en sugerencia, avisos (slugs esperados que no existen), versión de PHP y p50/p95/máx. `--minimo=85` hace que el comando termine con código 1 si no se alcanza el porcentaje.

Modo de defensa:

```
php private/bin/evaluar.php --interactivo
php private/bin/evaluar.php preguntas-del-revisor.csv --interactivo
```

El revisor escribe una pregunta (o se recorre su CSV), el script muestra el resultado (respuesta, enlaces, sugerencias, canalización, carrera en contexto, confianza y tiempo) y pregunta: `s` correcta · `g` correcta en la primera sugerencia · `n` incorrecta · `x` no contar. `:nuevo` reinicia la conversación y `:fin` termina. Al final muestra el porcentaje y guarda un informe.

## 3. Batería de 100 preguntas reales (aceptación)

| Aspecto | Regla |
|---|---|
| **De dónde salen** | Preguntas que la comunidad **ya hizo** por canales reales: mensajes de Facebook/WhatsApp institucional, correos a Servicios Escolares, preguntas en ventanilla y en ferias vocacionales, comentarios en publicaciones. Se copian tal como se escribieron (con faltas, abreviaturas y sin acentos) y se quitan nombres, correos, teléfonos y cualquier dato personal. |
| **Quién las recopila** | Personal de la universidad ajeno al equipo de desarrollo: [PENDIENTE: nombre/cargo del responsable designado, p. ej. Servicios Escolares o el revisor]. |
| **Quién define lo esperado** | El mismo responsable, con la lista de slugs de la base de conocimiento: para cada pregunta anota el slug correcto (o varios aceptables), `sin_respuesta` si la base no debe cubrirla o `no_ofertada` si pregunta por una carrera que no se imparte. |
| **Requisito** | Las preguntas **no** deben estar copiadas en la base (ni como pregunta ni como variante). `evaluar.php` avisa si un slug esperado no existe; el responsable verifica que no haya copias textuales. |
| **Distribución sugerida** | Proporcional a lo que la gente pregunta de verdad. Incluir algunas fuera de alcance (`sin_respuesta`), algunas de carreras no ofertadas y algunas conversaciones de 2 mensajes (`grupo`). |
| **Archivo** | `tests/preguntas/aceptacion.csv`, que se entrega al equipo **solo el día de la evaluación**. |

**Por qué el equipo no debe verlas antes.** Si el equipo conoce las preguntas, puede (aun sin querer) agregar sus palabras exactas como variantes o sinónimos y el resultado mediría memoria, no comprensión. La batería oculta es la única forma de estimar cómo se comportará con preguntas nuevas del público. Para mejorar durante el desarrollo el equipo usa **otra** batería (`tests/preguntas/desarrollo.csv`) y las consultas sin respuesta del panel; los resultados de esa batería **no** son válidos para la aceptación.

Procedimiento del día:

1. El responsable entrega `aceptacion.csv` (y, si quiere, lo conserva firmado/fechado).
2. Se ejecuta, sin cambiar nada del motor ni de la base, en PHP 7.4 y en PHP 8.x:
   `php private/bin/evaluar.php tests/preguntas/aceptacion.csv --nombre=aceptacion --bd --minimo=85`
3. Se anexan los dos informes `.md` a la sección 8.
4. Criterio: **≥ 85 % de aciertos directos** y **tiempo máximo < 2 s**. Los aciertos en sugerencia se informan pero no cuentan.

## 4. Casos límite

Cubiertos por la suite automática (unitarias y endpoint):

| Caso | Esperado | Dónde |
|---|---|---|
| Consulta vacía, solo espacios, solo signos o solo etiquetas HTML | 422 en el endpoint; `sin_respuesta` en el motor | endpoint · Buscador |
| 300 caracteres multibyte (ñ) / 301 caracteres | 200 / 422 | endpoint |
| Cuerpo JSON > 8 KB, JSON inválido, JSON escalar, acción desconocida | 400, nunca 500 | endpoint |
| Origen parecido (subdominio malicioso, http en vez de https, otro puerto, `null`) | 403 | endpoint |
| Solo saludos ("hola buenas tardes", "gracias") | `sin_respuesta` con contacto | Buscador |
| Texto sin sentido ("asdfgh qwerty") | `sin_respuesta` | Buscador |
| `<script>`, `<img onerror>`, `<svg onload>` | No se refleja; no se guarda con etiquetas | Buscador · endpoint |
| Correo o teléfono en la consulta | Se guarda como `[correo]` / `[número]` | endpoint |
| Faltas: bacaciones, inscripsion, vecas, rekisitos, orario, yave | Mismo término que la forma correcta | Texto · Indice |
| Abreviaturas: q, k, info, d, uni, kiero, ing, compu | Expandidas | Texto |
| Letras repetidas "holaaaa", "siiiii" | Reducidas | Texto |
| Empate cercano bajo 0.85 ("examen") | `sugerencias` | Buscador |
| "¿tienen enfermería?" vs "¿hay servicio de enfermería?" | `no_ofertada` vs respuesta de servicio médico | Carreras · Buscador |
| Contexto inválido (slug inexistente, número, arreglo) | Se ignora | Buscador |
| Fechas: solo eventos futuros respecto a hoy; fallback si no hay | Comprobado contra el fixture con la fecha del día | Dinamicas |
| CSV con BOM, CRLF, comillas, comas y saltos de línea en campos | Se leen bien | Csv |
| Ráfaga de solicitudes | 429 exactamente en la solicitud límite + 1 | endpoint |

Para revisar a mano en el sitio: pregunta muy larga pegada desde Word, emojis, mayúsculas sostenidas, consultas en inglés ("tuition cost"), números sueltos ("2026"), y preguntar justo después de que cambia el calendario (evento de hoy).

## 5. Rendimiento

- Suite: índice sintético de ≥ 150 preguntas y ~750 variantes construido y consultado en < 300 ms; cada búsqueda del fixture < 100 ms.
- Endpoint: tiempo HTTP completo < 2 s y `ms` del motor < 2000.
- Evaluación: p50/p95/máx por consulta en cada informe.

## 6. Pruebas en móvil y accesibilidad

Lista de verificación (marcar en cada dispositivo/navegador; anotar modelo y versión):

**Dispositivos mínimos:** Android con Chrome, iPhone con Safari, computadora con Chrome/Edge y con Firefox.

| # | Verificación | Android | iOS | Escritorio |
|---|---|---|---|---|
| M1 | El botón flotante no tapa el menú, el pie ni botones del sitio | ☐ | ☐ | ☐ |
| M2 | A 320 px de ancho la ventana ocupa la pantalla sin desplazamiento horizontal | ☐ | ☐ | ☐ |
| M3 | Al abrir el teclado del teléfono, el campo de texto y la última respuesta siguen visibles | ☐ | ☐ | — |
| M4 | El texto no se ve menor a 16 px en el campo (evita el zoom automático de iOS) | ☐ | ☐ | — |
| M5 | Áreas táctiles de al menos 44×44 px (botones, sugerencias, enlaces) | ☐ | ☐ | — |
| M6 | Los enlaces (PDF, plan de estudios) abren correctamente y se puede volver | ☐ | ☐ | ☐ |
| M7 | Funciona con conexión lenta (DevTools › 3G lenta): muestra estado "buscando" y error claro sin conexión | ☐ | ☐ | ☐ |
| M8 | Girar el teléfono no pierde la conversación | ☐ | ☐ | — |
| A1 | Se abre, se usa y se cierra **solo con teclado** (Tab, Shift+Tab, Enter, Escape); el foco es visible | — | — | ☐ |
| A2 | Al abrir, el foco va al campo de texto; al cerrar, regresa al botón flotante | — | — | ☐ |
| A3 | Lector de pantalla (NVDA/Narrador en Windows, VoiceOver en iOS, TalkBack en Android): el botón tiene nombre, la ventana se anuncia como diálogo y las respuestas nuevas se leen (región `aria-live`) | ☐ | ☐ | ☐ |
| A4 | Contraste de texto ≥ 4.5:1 (y ≥ 3:1 en iconos y bordes) | ☐ | ☐ | ☐ |
| A5 | Con zoom del navegador al 200 % todo sigue usable | — | — | ☐ |
| A6 | Respeta "reducir movimiento" del sistema (sin animaciones bruscas) | ☐ | ☐ | ☐ |
| A7 | El idioma del contenido está marcado como español (`lang="es"`) | — | — | ☐ |
| A8 | Los mensajes de error ("demasiadas consultas", "sin conexión") se anuncian y no dependen solo del color | ☐ | ☐ | ☐ |

## 7. Pruebas manuales en el hosting

| # | Verificación | ☐ |
|---|---|---|
| H1 | `/buscador/api.php` por GET responde 405 en JSON | ☐ |
| H2 | `/private-buscador/config.php` no es accesible por web | ☐ |
| H3 | El widget aparece en todas las páginas que usan `footer.inc.php` y en ninguna se duplica | ☐ |
| H4 | Preguntas con acentos, comillas, "select", "script", "<b>": ModSecurity no las bloquea (ver docs/03, sección 10) | ☐ |
| H5 | Las respuestas dinámicas muestran el calendario, carreras, noticias, deportes y trípticos reales del sitio | ☐ |
| H6 | El panel: inicio de sesión, bloqueo tras intentos fallidos, crear/editar con acentos, cerrar sesión | ☐ |
| H7 | El cron de `purgar.php` corre (revisar con `--verbose` a mano y al día siguiente en CWP) | ☐ |
| H8 | Cambio de PHP 7.4 → 8.x en CWP: repetir H1, H3, H5, H6 | ☐ |

## 8. Resultados

### 8.1 Suite automática (máquina de desarrollo, 17/11/2026)

| Prueba | PHP 7.4.33 | PHP 8.3.36 |
|---|---|---|
| `tests/ejecutar.php` | 97 correctas, 0 fallidas, 6 defectos conocidos | 97 correctas, 0 fallidas, 6 defectos conocidos |
| `tests/endpoint.php` (MariaDB local) | 18 correctas, 0 fallidas, 1 defecto conocido | 18 correctas, 0 fallidas, 1 defecto conocido |
| `tests/compatibilidad.php` | — | 0 hallazgos; `php -l` 7.4 sin errores en los 39 archivos |

### 8.2 Evaluación de desarrollo — **NO válida para aceptación**

Batería: `tests/preguntas/desarrollo.csv` (10 preguntas de ejemplo escritas por el equipo, solo para probar el script). Base: `conocimiento/preguntas.csv` de ese momento (12 preguntas).

| Medida | PHP 7.4.33 | PHP 8.3.36 |
|---|---|---|
| Aciertos directos | 8/10 (80.0 %) | 8/10 (80.0 %) |
| Aciertos incluyendo sugerencias | 10/10 (100.0 %) | 10/10 (100.0 %) |
| Tiempo p50 / p95 / máx | 1.6 / 4.5 / 4.5 ms | 1.6 / 4.5 / 4.5 ms |

### 8.3 Evaluación de aceptación (100 preguntas reales)

Se llena con la salida de `evaluar.php` el día de la evaluación (sección 3). No se anticipan resultados.

| Medida | PHP 7.4 | PHP 8.x |
|---|---|---|
| Fecha | | |
| Archivo y responsable de la batería | | |
| Preguntas en la base | | |
| Aciertos directos | | |
| Aciertos incluyendo sugerencias | | |
| Tiempo p50 / p95 / máx | | |
| ¿Cumple ≥ 85 % y < 2 s? | | |
| Informes anexos (`tests/resultados/…`) | | |

### 8.4 Evaluación en vivo durante la defensa

| Medida | Valor |
|---|---|
| Fecha / PHP | |
| Preguntas del revisor | |
| Correctas directas | |
| Incluyendo sugerencias | |
| Informe (`evaluacion-interactiva-…`) | |
