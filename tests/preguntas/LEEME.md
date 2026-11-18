# Preguntas de prueba del buscador

Hay dos archivos, con el mismo formato (`consulta,esperado`):

- `esperado` es el `slug` de la pregunta frecuente que debe responder, o `sin_respuesta` (el buscador debe decir que no tiene
  esa información y canalizar), o `no_ofertada` (se pregunta por una carrera que la universidad no ofrece).

## desarrollo.csv — para afinar

Unas 70 consultas escritas por el equipo, redactadas distinto a las variantes de `conocimiento/preguntas.csv`. Sirven para
ajustar el léxico (`private/data/lexico.php`), las variantes y los umbrales mientras se trabaja. Como el equipo las ve y
ajusta el motor contra ellas, **su porcentaje de aciertos no mide la calidad real** del buscador: con el tiempo el motor
termina "aprendiendo" estas frases.

Cada consulta debe probarse por separado: `private/bin/probar.php` con varias consultas en una sola llamada arrastra la carrera
detectada de una consulta a la siguiente (simula una conversación).

## reales.csv — evaluación de aceptación

La evaluación de aceptación **debe hacerse con `reales.csv`**: preguntas **reales** de aspirantes y alumnos, tal como
llegaron por correo, WhatsApp, Facebook o en ventanilla, recopiladas por **Servicios Escolares**.

Reglas para que la medición sea válida:

1. Servicios Escolares copia las preguntas tal cual (con sus faltas de ortografía y abreviaturas), quita nombres, teléfonos,
   matrículas y cualquier dato personal, y anota en `esperado` el slug que debería responder (o `sin_respuesta` / `no_ofertada`).
2. **Nadie del equipo que ajusta el motor, el léxico o la base de conocimiento debe ver estas preguntas** mientras trabaja.
   Si las viera, dejarían de medir cómo responde el buscador a preguntas nuevas.
3. La evaluación la corre una persona ajena al ajuste (o Servicios Escolares) y solo se comparte el resultado global y los tipos
   de fallo, no las preguntas.
4. Conviene juntar al menos 100 preguntas de varios canales y fechas, incluyendo preguntas que el sitio no responde.
5. Cuando se use un lote para corregir el motor, ese lote pasa a `desarrollo.csv` y se junta un lote nuevo para la siguiente
   evaluación.

Por ahora `reales.csv` solo tiene el encabezado.
