# 01 · Requerimientos y alcance

Buscador de preguntas frecuentes de la Universidad Tecnológica y Politécnica de Coyuca de Benítez (UTyP CB).

## 1. Qué es

Un **buscador de preguntas frecuentes con comprensión tolerante**. Aparece como una ventana flotante en todas las páginas del sitio institucional. El visitante escribe una pregunta con sus propias palabras y el sistema:

1. la interpreta aunque tenga faltas de ortografía, abreviaturas de chat ("q", "xq", "info", "kiero"), sin acentos o con sinónimos ("precio", "colegiatura", "cuota" → costo);
2. la compara con las preguntas registradas y sus variantes;
3. según la confianza obtenida, decide entre **cuatro resultados**:

| Resultado | Cuándo | Qué ve el visitante |
|---|---|---|
| `respondida` | confianza ≥ umbral de respuesta (0.62 por defecto) y clara ventaja sobre la segunda opción | La respuesta oficial, su fuente, el área responsable y hasta 2 preguntas relacionadas |
| `sugerencias` | confianza media (≥ 0.30) o empate | "¿Quisiste decir…?" con hasta 3 preguntas para elegir |
| `sin_respuesta` | no hay coincidencia suficiente | Lo dice con claridad y canaliza al área con su contacto oficial |
| `no_ofertada` | preguntan por una carrera que la universidad no imparte | Lo dice y muestra la lista de carreras vigentes |

Algunas respuestas son **dinámicas**: se arman en el momento con los archivos de datos que ya publica el sitio (`calendario_data.php`, `carreras_data.php`, `noticias_data.php`, `deportes_data.php`, `tripticos_data.php`). Así, el calendario, la oferta educativa, las noticias, los deportes y los trípticos nunca quedan desactualizados en el buscador: la fuente de verdad sigue siendo el sitio.

También recuerda **el contexto de la conversación** en lo inmediato: si alguien pregunta por la carrera de Ingeniería Civil y luego escribe "¿y el tríptico?", recibe el tríptico de Civil.

## 2. Qué NO hace

| No hace | Por qué |
|---|---|
| **No genera texto.** Solo devuelve respuestas escritas y validadas por las áreas, o datos que ya publica el sitio. | Evita respuestas inventadas. Si no sabe, lo dice y canaliza. |
| **No usa inteligencia artificial externa** (ni APIs de terceros, ni modelos de lenguaje, ni servicios en la nube). | Todo corre en PHP dentro del hosting institucional. No hay costos por consulta ni envío de datos a terceros. |
| **No guarda datos personales.** No registra IP, nombre, correo ni teléfono. Si el visitante escribe un correo o teléfono en su pregunta, se enmascara antes de guardar. | Ver [07 · Aviso de privacidad y seguridad](07-aviso-privacidad-y-seguridad.md). |
| No realiza trámites (inscripciones, pagos, constancias). | Solo informa y enlaza a la página oficial o al área responsable. |
| No tiene cuentas de usuario para el público. | El panel de administración es solo para el personal autorizado. |
| No requiere procesos en segundo plano, Node.js, Python ni Composer. | El hosting (CWP) no los ofrece. El índice se construye en memoria en cada consulta en pocos milisegundos. |

**Nombre con el que se presenta:** "Buscador de preguntas frecuentes". Solo podrá presentarse como "asistente inteligente" si supera el criterio A1 de la sección 4 con la batería de preguntas reales.

## 3. Requerimientos

### 3.1 Funcionales

| Clave | Requerimiento |
|---|---|
| RF-01 | El visitante puede escribir una pregunta de hasta 300 caracteres desde cualquier página del sitio. |
| RF-02 | El sistema tolera faltas de ortografía comunes (b/v, s/c/z, j/g, qu/k, y/ll, h muda), acentos ausentes, mayúsculas, letras repetidas ("holaaa") y abreviaturas de chat. |
| RF-03 | El sistema reconoce sinónimos y frases coloquiales definidos en `private/data/lexico.php`. |
| RF-04 | El sistema devuelve uno de los cuatro resultados de la sección 1 con un nivel de confianza (0 a 1). |
| RF-05 | Cada respuesta muestra su fuente oficial (URL) y el área responsable. |
| RF-06 | Las respuestas sobre calendario, carreras, noticias, deportes y trípticos se leen en vivo de los archivos de datos del sitio. |
| RF-07 | El sistema detecta la carrera mencionada (por nombre oficial o alias coloquial: "sistemas", "aduanas", "solar"…) y la conserva para la siguiente pregunta. |
| RF-08 | El sistema distingue una carrera no ofertada ("¿tienen enfermería?") de un servicio ("¿hay servicio de enfermería?"). |
| RF-09 | Cuando no hay respuesta, canaliza al área correspondiente con su contacto oficial, o a Servicios Escolares por defecto. |
| RF-10 | El visitante puede marcar si la respuesta le fue útil. |
| RF-11 | El personal autorizado administra preguntas, variantes, áreas y revisa las consultas sin respuesta desde un panel protegido (ver docs/06, pendiente). |
| RF-12 | La base de conocimiento se puede editar en CSV (Excel) e importar. |

### 3.2 No funcionales

| Clave | Requerimiento |
|---|---|
| RNF-01 | Compatible con PHP 7.4 y 8.x (sin `str_contains`, `match`, enumeraciones, argumentos con nombre, etc.). |
| RNF-02 | MariaDB 10.11 (o MySQL 5.7+) con `utf8mb4`. |
| RNF-03 | Funciona en hosting CWP (Apache 2.4 + nginx, ModSecurity) instalado por FTP, sin Git, sin Composer y sin procesos persistentes. Solo usa cron para la purga del historial. |
| RNF-04 | Tiempo de respuesta menor a 2 segundos por consulta (medido en el servidor). |
| RNF-05 | La carpeta `private/` queda fuera de `public_html`. En la parte pública solo están el endpoint, el widget y el panel. |
| RNF-06 | Endpoint solo por POST con JSON, CORS restringido a los dominios institucionales, validación de entrada, límite de solicitudes por minuto y salida JSON con `nosniff`. |
| RNF-07 | El widget es usable en móvil y accesible con teclado y lector de pantalla. |
| RNF-08 | No se guardan datos personales. El historial de consultas se purga automáticamente (90 días por defecto). |
| RNF-09 | Las pruebas y la evaluación se pueden repetir con un comando, en PHP 7.4 y 8.x. |

## 4. Criterios de aceptación del revisor y cómo se mide cada uno

| Clave | Criterio | Cómo se mide | Herramienta |
|---|---|---|---|
| **A1** | **≥ 85 % de aciertos** con **100 preguntas reales** que **no estén** en la base de conocimiento | Se ejecuta la batería de aceptación y se toma el porcentaje de **aciertos directos**: respondida con la pregunta correcta, o `sin_respuesta` / `no_ofertada` cuando así se esperaba. Los aciertos en sugerencia se informan aparte y **no** cuentan para A1. | `php private/bin/evaluar.php tests/preguntas/aceptacion.csv --nombre=aceptacion` |
| **A2** | Tiempo de respuesta **< 2 s** | El informe de evaluación da p50, p95 y máximo por consulta (incluye cargar la base, construir el índice y buscar). Se exige **máximo < 2 s**. En el endpoint real también se mide el tiempo HTTP completo. | `evaluar.php` · `tests/endpoint.php` |
| **A3** | La evaluación se puede **repetir en vivo** con preguntas que da el revisor durante la defensa | Modo interactivo: el revisor escribe cada pregunta, ve el resultado y dice si es correcto. Al final se muestra el porcentaje y se guarda un informe. | `php private/bin/evaluar.php --interactivo` |
| A4 | Compatible con PHP 7.4 y 8.x | La suite completa pasa con ambos ejecutables, y `compatibilidad.php` no encuentra sintaxis exclusiva de PHP 8 y `php -l` 7.4 no da errores. | `tests/ejecutar.php` · `tests/compatibilidad.php` |
| A5 | Seguridad del endpoint | GET → 405, origen ajeno → 403, JSON inválido → 400, consulta vacía o > 300 caracteres → 422, ráfaga → 429, XSS no reflejado, `Content-Type: application/json` y `nosniff`. | `tests/endpoint.php` |
| A6 | Sin datos personales | Revisión del esquema (`bf_consulta` no tiene columnas de identificación) y prueba de que correos y teléfonos se enmascaran al registrar. | `tests/endpoint.php` · docs/07 |
| A7 | Instalable en CWP sin herramientas extra | Se sigue [03 · Instalación](03-instalacion-e-integracion.md) paso a paso en el hosting. | Lista de verificación de docs/03 |
| A8 | Móvil y accesibilidad | Lista de verificación de [05 · Plan de pruebas](05-plan-de-pruebas.md#6-pruebas-en-móvil-y-accesibilidad). | Manual |

**Regla de presentación:** mientras A1 y A2 no se cumplan con la batería real, el sistema se presenta y se documenta como "buscador de preguntas frecuentes".

## 5. Supuestos y datos pendientes

| Dato | Estado |
|---|---|
| Montos, requisitos, becas, horarios y contactos oficiales de cada área | [PENDIENTE] Las áreas deben validarlos. Mientras tanto las respuestas quedan en estado `borrador`. |
| Lista definitiva de carreras **no** ofertadas (`no_ofertadas` en `lexico.php`) | [PENDIENTE] Confirmar con Servicios Escolares. |
| Correo y teléfono de contacto general (`contacto_general` en la configuración) | [PENDIENTE] |
| Dominio(s) exactos desde los que se incrusta el widget (`origenes_permitidos`) | [PENDIENTE] Confirmar si se usa `www.` y otros subdominios. |
| Responsable institucional de recopilar las 100 preguntas reales | [PENDIENTE] Ver docs/05, sección 3. |
| Días de retención del historial de consultas | 90 días por defecto. [PENDIENTE] Confirmar con la institución. |
