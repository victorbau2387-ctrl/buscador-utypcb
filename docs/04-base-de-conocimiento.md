# 04 · Base de conocimiento

La base de conocimiento son las preguntas frecuentes con sus variantes, su respuesta, la fuente
oficial y el área que la valida. Vive en dos lugares:

| Dónde | Para qué |
|---|---|
| `conocimiento/preguntas.csv` y `conocimiento/areas.csv` | **Formato revisable** (abre en Excel), versionado en Git. Es lo que se entrega y lo que se importa. |
| Tablas `bf_faq`, `bf_variante` y `bf_area` de MariaDB | Lo que usa el buscador en producción. Se edita desde el panel ([06](06-manual-administrador.md)). |

El paso entre los dos lugares es: CSV → base de datos con `private/bin/importar.php`, y
base de datos → CSV con **Exportar CSV** en el panel.

## 1. Estado actual (borrador)

| Tipo | Cantidad |
|---|---:|
| Respuesta oficial completa, con fuente | 45 |
| Dinámicas (se arman en vivo con los `*_data.php` del sitio) | 26 |
| Parciales (un dato oficial y una parte `[PENDIENTE]`) | 8 |
| `[PENDIENTE]`: falta el dato del área | 94 |
| **Total** | **173**, con 918 variantes |

**Ninguna está validada todavía.** Todas tienen `estado = borrador` hasta que el área responsable las
confirme por escrito. La lista de datos faltantes, agrupada por área, está en
[`conocimiento/fuentes.md`](../conocimiento/fuentes.md), junto con la URL de donde salió cada dato publicado.

Categorías cubiertas: admisión, inscripción, costos, carreras (las cinco, con su plan de estudios, vía
respuestas dinámicas), calendario, trámites, becas, servicios, deportes, transparencia, contacto,
institucional, titulación y estadías, para aspirantes y para alumnos inscritos.

## 2. Formato de `preguntas.csv`

UTF-8, separado por comas, una fila por pregunta:

| Columna | Obligatoria | Descripción |
|---|:-:|---|
| `slug` | ✅ | Identificador único en minúsculas con guiones (`inscripcion-costo`). Sirve para actualizar sin duplicar. |
| `categoria` | ✅ | Una de las 14 categorías de arriba. |
| `pregunta` | ✅ | La pregunta como se muestra (máx. 255 caracteres). |
| `variantes` | ✅ | Formas de preguntarlo, separadas por `\|`. **Mínimo 5** para poder validarla. |
| `respuesta` | ✅* | Texto de la respuesta. *En las dinámicas es una introducción opcional. |
| `tipo` | ✅ | `fija` o `dinamica`. |
| `handler` | solo dinámicas | `carreras_lista`, `carrera_detalle`, `calendario_proximos`, `calendario_evento`, `noticias_recientes`, `deportes_recientes` o `triptico_carrera`. |
| `palabras_clave` | | Palabras sueltas que ayudan a encontrarla. |
| `carrera` | | Slug de la carrera, si la respuesta es solo de una. |
| `fuente_url` | ✅ | URL oficial `https://…` de donde sale el dato. |
| `area` | | Clave del área en `areas.csv` (`escolares`, `vinculacion`…). |
| `estado` | ✅ | `borrador`, `validada` o `archivada`. |
| `validado_por` | para validar | Nombre y área de quien la confirmó. |
| `validado_en` | para validar | Fecha `AAAA-MM-DD`. |

`areas.csv`: `clave, nombre, correo, telefono, url`, con solo contactos publicados en el sitio
(teléfonos separados por coma, máx. 40 caracteres).

## 3. Reglas de contenido

1. **Nunca inventar** fechas, costos, requisitos, teléfonos ni nombres. Si el dato no está publicado,
   la respuesta es `[PENDIENTE: <qué falta> — lo proporciona <área>]`.
2. **Cada respuesta con su fuente oficial**, que el buscador muestra como enlace.
3. **Sin relleno**: no cuentan como respuesta las que solo dicen «acude a Servicios Escolares», ni la
   misma respuesta copiada por carrera. Lo que depende de la carrera se responde con las filas dinámicas.
4. **Datos que ya publica el sitio** (calendario, noticias, carreras, deportes, trípticos): filas
   dinámicas, nunca copiados al CSV, para que haya una sola fuente de verdad.
5. **Carreras no ofertadas** (Medicina, Derecho, etc.): se configuran en `private/data/lexico.php` y el
   buscador lo dice claramente. Enfermería aparece en el sitio como «Próximamente» y tiene su propia
   pregunta.

## 4. Variantes y léxico

Cada pregunta lleva al menos 5 variantes **realmente distintas**: coloquiales, de chat («q», «xq»),
sin acentos y con faltas comunes («inscripsion», «veca»). El motor además tolera por su cuenta
acentos, faltas de una o dos letras y sinónimos definidos en `private/data/lexico.php`
(por ejemplo, «cuánto sale» = costo; «compu» y «sistemas» = Tecnologías de la Información).

## 5. Importar, validar y exportar

```powershell
# Validar sin tocar la base (errores por fila: URL no https, handler inexistente, área inexistente…)
php private/bin/importar.php --dry-run

# Importar (todo o nada, en una transacción; actualiza por slug y reemplaza las variantes)
php private/bin/importar.php
```

La exportación desde el panel genera el mismo formato, con BOM para Excel. Las celdas que empiezan
con `= + - @` llevan un apóstrofo para que Excel no las tome como fórmula; el importador lo quita.

## 6. Validación por las áreas

1. Exporta el CSV desde el panel y filtra en Excel por la columna `area`.
2. Cada área revisa sus filas, completa los `[PENDIENTE]` y confirma por escrito.
3. En el panel se marca cada pregunta como **Validada**, con nombre y fecha. El panel exige 5 variantes
   o más, quién validó y la fecha.
4. En producción se recomienda `busqueda.solo_validadas = true` (en `private/config.php`) cuando la
   mayoría esté validada, para que el buscador solo use respuestas confirmadas.

## 7. Cómo crece la base

La pantalla **Sin respuesta** del panel reúne las preguntas reales que el buscador no supo contestar.
Desde ahí se convierten, con un clic, en preguntas nuevas o en variantes de las existentes.
Importante: si esas preguntas se usan para mejorar la base, **ya no sirven como prueba ciega**. La
evaluación de aceptación usa otra batería de 100 preguntas reales que el equipo no ha visto
([05](05-plan-de-pruebas.md)).
