# Evaluación del buscador: desarrollo

| Dato | Valor |
|---|---|
| Fecha | 2026-11-17 21:04:37 CST |
| Archivo de preguntas | `tests/preguntas/desarrollo.csv` |
| Versión de PHP | 7.4.33 (Windows 10.0) |
| Base de conocimiento | CSV conocimiento/preguntas.csv (127 preguntas) |
| Configuración | `tests/sitio-local/config.local.php` |
| Umbrales | respuesta 0.62 · sugerencias 0.30 · máx. sugerencias 3 |

## Resumen

| Medida | Valor |
|---|---|
| Consultas evaluadas | 68 |
| Aciertos directos | 56 (82.4 %) |
| Aciertos en sugerencia (primera opción) | 6 (8.8 %) |
| Aciertos incluyendo sugerencias | 62 (91.2 %) |
| Fallos | 6 (8.8 %) |
| Tiempo por consulta p50 / p95 / máx | 21.8 / 22.7 / 27.9 ms |
| Cumple «≥ 85 % de aciertos directos y < 2 s» | no aplica (se requieren al menos 100 preguntas reales) |

El tiempo incluye cargar la base de conocimiento, construir el índice y buscar (lo mismo que hace cada petición al endpoint), sin la latencia de red.

Un acierto directo es: respondida con un slug esperado, o `sin_respuesta` / `no_ofertada` cuando eso se esperaba. Un acierto en sugerencia es cuando el motor ofrece sugerencias y la primera es la esperada.

### Resultados devueltos

| Resultado | Consultas |
|---|---|
| respondida | 52 |
| sugerencias | 11 |
| sin_respuesta | 1 |
| no_ofertada | 4 |

## Matriz por categoría

| Categoría | Total | Directos | En sugerencia | Fallos | % directos | % con sugerencias |
|---|---|---|---|---|---|---|
| admision | 13 | 12 | 0 | 1 | 92.3 % | 92.3 % |
| becas | 2 | 0 | 2 | 0 | 0.0 % | 100.0 % |
| calendario | 6 | 5 | 1 | 0 | 83.3 % | 100.0 % |
| carreras | 11 | 9 | 0 | 2 | 81.8 % | 81.8 % |
| contacto | 3 | 3 | 0 | 0 | 100.0 % | 100.0 % |
| costos | 6 | 5 | 1 | 0 | 83.3 % | 100.0 % |
| deportes | 1 | 1 | 0 | 0 | 100.0 % | 100.0 % |
| estadias | 3 | 3 | 0 | 0 | 100.0 % | 100.0 % |
| inscripcion | 4 | 3 | 1 | 0 | 75.0 % | 100.0 % |
| institucional | 1 | 1 | 0 | 0 | 100.0 % | 100.0 % |
| no_ofertada | 4 | 4 | 0 | 0 | 100.0 % | 100.0 % |
| servicios | 3 | 3 | 0 | 0 | 100.0 % | 100.0 % |
| sin_respuesta | 3 | 1 | 0 | 2 | 33.3 % | 33.3 % |
| titulacion | 1 | 1 | 0 | 0 | 100.0 % | 100.0 % |
| tramites | 6 | 4 | 1 | 1 | 66.7 % | 83.3 % |
| transparencia | 1 | 1 | 0 | 0 | 100.0 % | 100.0 % |

## Fallos (6)

| Fila | Consulta | Grupo | Esperado | Obtenido | Confianza |
|---|---|---|---|---|---|
| 19 | sigo en el cobach puedo presentar examen |  | admision-bachillerato-en-curso | sugerencias: admision-examen, admision-bachillerato-en-curso, tramite-extraordinario | 0.545 |
| 28 | en que puedo trabajar si estudio comercio internacional |  | carrera-campo-laboral | sugerencias: carreras-comercio-tsu, carrera-campo-laboral, servicios-wifi | 0.547 |
| 32 | las carreras son semestrales |  | carreras-cuatrimestres | sugerencias: carreras-duracion, tramite-baja-temporal, carrera-materias | 0.777 |
| 51 | me quiero pasar de civil a energia |  | tramite-cambio-carrera | carrera-detalle | 0.639 |
| 68 | quien gano el clasico america chivas |  | sin_respuesta | sugerencias: institucional-rector, deportes-unirse, titulacion-cedula | 0.302 |
| 69 | cuanto cuesta un boleto de avion a tijuana |  | sin_respuesta | sugerencias: costos-cuatrimestre, costos-total, inscripcion-costo | 0.306 |

## Aciertos en sugerencia (6)

| Fila | Consulta | Esperado | Obtenido | Confianza |
|---|---|---|---|---|
| 5 | mi mama quiere saber cuanto es todo lo que hay que pagar para entrar | costos-total | sugerencias: costos-total, institucional-nombre, costos-aranceles | 0.598 |
| 8 | ya hice el deposito que sigue con el comprobante | pagos-recibo-oficial | sugerencias: pagos-recibo-oficial, pagos-como-pagar, admision-aceptado-siguientes-pasos | 0.354 |
| 23 | que dias reciben los documentos de los aceptados | inscripcion-entrega-documentos | sugerencias: inscripcion-entrega-documentos, inscripcion-documentos, titulacion-fechas | 0.667 |
| 41 | cuando son las vacaciones de fin de año | calendario-vacaciones | sugerencias: calendario-vacaciones, calendario-fin-cuatrimestre, carreras-cuatrimestres | 0.539 |
| 52 | hay becas para los alumnos | becas-tipos | sugerencias: becas-tipos, becas-requisitos, becas-benito-juarez | 0.617 |
| 53 | que paso con la beca del 50 por ciento | becas-transformando-guerrero | sugerencias: becas-transformando-guerrero, admision-proceso, reinscripcion-proceso | 0.591 |
