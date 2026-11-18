# Fuentes del borrador de la base de conocimiento

Consulta hecha el **17 de noviembre de 2026** en el sitio oficial https://utypcb.edu.mx/ y en los documentos enlazados desde ahí.
Cada fila de `preguntas.csv` lleva en `fuente_url` la página o el documento de donde sale su respuesta. Esta lista dice qué dato
se tomó de cada URL para que el revisor lo pueda comprobar.

Reglas que se siguieron:

- Solo se copió al CSV lo que aparece en el sitio. Si el dato no está publicado, la respuesta es `[PENDIENTE: qué falta (área)]`.
- Lo que el sitio ya publica en sus archivos de datos (calendario, noticias, deportes, carreras, trípticos) se responde con filas
  `tipo=dinamica`, no copiando el dato.
- Cuatro filas son **parciales**: tienen un dato publicado y una nota `[PENDIENTE: …]` para lo que falta
  (`admision-propedeutico`, `costos-cuatrimestre`, `carreras-validez`, `institucional-nuevas-instalaciones`).
- Todas las filas están en `estado=borrador`, con `validado_por` y `validado_en` vacíos.

## Páginas y documentos consultados

### https://utypcb.edu.mx/ (también `home.php`)
- Nombre: Universidad Tecnológica y Politécnica de Coyuca de Benítez (UTyPCB), Coyuca de Benítez, Guerrero.
- «Una universidad pública al servicio de la Costa Grande». Lema: «Formamos talento con garra de Coyote»; selecciones «Deportes Coyote».
- Contacto: `serviciosescolares@utypcb.edu.mx`; dirección «C. Nueva Mercado Nuevo, Centro, 40977 Coyuca de Benítez, Gro.» con enlace
  a Google Maps; Facebook `https://www.facebook.com/PolitecnicaCoyucaDeBenitez`.
- Plan de estudios: 5 programas y una tarjeta «Próximamente — Licenciatura en Enfermería: se incorporará próximamente al catálogo de programas».
- Transparencia: enlaces a `docs/poa_2025.pdf`, `docs/pe_2025_2030.pdf` y `docs/informe_q3.pdf`. **Los tres devuelven error 404**
  (fila `transparencia-poa-pe`, PENDIENTE).
- Filas: `contacto-ubicacion`, `contacto-facebook`, `contacto-general`, `institucional-nombre`, `institucional-publica`,
  `institucional-mascota`, `carrera-enfermeria-proximamente`, `transparencia-poa-pe` y las PENDIENTE de servicios/trámites
  (se apunta a la página de inicio porque el sitio no tiene página propia para esos temas).

### https://utypcb.edu.mx/convocatorias.php (igual a `convocatorias.php?id=1`)
Convocatoria de Nuevo Ingreso 2026–2027 (TSU e Ingeniería), en diez pasos:
1. Pago de ficha (psicométrico + admisión) **$500.00**, en caja de la Universidad o por transferencia: **BBVA, cuenta 0122236680,
   CLABE 012260001222366809**. Cambiar el recibo del banco o la captura impresa por el recibo oficial en **Recursos Financieros** y
   entregarlo en **Servicios Escolares**.
2. Pre-registro en `https://sescolar.com/sescolar_utpcoyuca/preregistro/admision_tsu.aspx`, recuadro «REGÍSTRATE AQUÍ».
3. Captura de datos personales y de escuela de procedencia («Ten a la mano toda tu documentación»).
4. Verificación y «Finalizar».
5. Dos días hábiles después, recuadro «SEGUIMIENTO» con la CURP: evaluación psicométrica (~2 horas; una vez abierta, concluirla),
   fecha y hora del examen, descarga de la guía de estudios.
6. Examen de admisión en la plataforma, con la CURP, botón EVALUACIÓN; duración estimada 2 a 3 horas.
   «Si no cuentas con equipo de cómputo, notifica al 781 100 3192».
7. Puntaje y retroalimentación al terminar.
8. Resultados dos días hábiles después del examen en Facebook (`http://www.facebook.com/share/1E1Q84cdzg/`).
9. Si fue aceptado: pago de inscripción **$1500.00** (mismas cuentas), cambiar el comprobante por el recibo oficial en Recursos Financieros.
10. Entrega de documentos en Servicios Escolares del **17 al 21 de agosto de 2026, de 09:00 a 16:00**.

Otros datos: «Soporte 781 100 3192»; el pre-registro «tarda menos de quince minutos»; las cifras del encabezado son
`data-contador="500"` (Ficha) y `data-contador="1500"` (Inscripción). **No hay lista de documentos requeridos** (por eso
`admision-requisitos` e `inscripcion-documentos` quedan PENDIENTE).

Filas: `admision-proceso`, `admision-preregistro`, `admision-curp`, `admision-psicometrico`, `admision-examen`,
`admision-sin-computadora`, `admision-guia`, `admision-resultados`, `admision-convocatoria`, `admision-aceptado-siguientes-pasos`,
`inscripcion-entrega-documentos`, `pagos-recibo-oficial`, `inscripcion-costo`, `costos-ficha`, `pagos-como-pagar`.

### https://utypcb.edu.mx/calendario.php y https://utypcb.edu.mx/docs/calendario/calendario-escolar-2026-2027.png
- Ciclo 2026–2027 con tres cuatrimestres: septiembre–diciembre, enero–abril, mayo–agosto.
- Eventos (transcripción del sitio): inicio de cuatrimestre, suspensión de labores, días conmemorativos, fecha límite de pago del
  cuatrimestre inmediato, fecha límite de pago para inscripción anual, presentación de proyecto integrador, evaluaciones finales y
  extraordinarias, entrega de calificaciones y fin de cuatrimestre, reinscripciones y capacitación docente, vacaciones, receso laboral,
  II Aniversario UTyPCB, 2.ª clausura generación 2023–2026, Día del TSU, aplicación de examen de admisión, curso de inducción.
- Solo en la imagen PNG: logotipo de la SEP, **C.C.T. 12EUT0006V**, lema «Educar con pasión, para el éxito!», y en la simbología
  «Inician trámites de titulación» (marcado el 29 de octubre de 2026), «Inicia periodo de estancias para TSU» e «Inicia periodo de
  estancias para Lic. e Ing.». Estos tres últimos **no aparecen en la transcripción en texto** de `calendario.php`; las filas
  `titulacion-fechas` y `estadias-fechas` son dinámicas y solo darán fecha si el archivo de datos del calendario incluye esos eventos.
- Las fechas no se copiaron al CSV: las responden las filas dinámicas `calendario_evento` / `calendario_proximos`.

Filas: todas las de `handler=calendario_*`, `carreras-cuatrimestres`, `institucional-cct`, `carreras-validez` (parcial).

### https://utypcb.edu.mx/plan_estudios.php y `plan_estudios.php?carrera=…`
- 5 programas, cada uno con 10 niveles (cuatrimestres) y 56 materias: Ingeniería Civil; Licenciatura en Comercio Internacional y
  Aduanas; Ingeniería en Energía y Desarrollo Sostenible; Ingeniería en Tecnologías de la Información e Innovación Digital;
  Licenciatura en Ingeniería en Manejo de Recursos Naturales.
- En los cinco mapas curriculares: Inglés I–V (1.º a 5.º), **6.º cuatrimestre = Estadía del TSU**, Inglés VI–VIII (7.º a 9.º),
  **10.º cuatrimestre = Estadía de la licenciatura/ingeniería**.
- Ingeniería Civil (`?carrera=ingenieria-civil`): competencia base «Comunicar en inglés (A2–B1) de acuerdo con el MCER»;
  Proyecto Integrador I, II y III en 3.º, 5.º y 9.º cuatrimestre.
- Trípticos y «Tabla de Aranceles» (`docs/tripticos/ARANCELES.pdf`, «Información de costos, cuotas y opciones de pago»).
- Detalle por carrera (`detalle_carrera.php?carrera=…`): tríptico y ocupaciones.

Filas: `carreras-oferta`, `carrera-detalle`, `carrera-materias`, `carrera-campo-laboral`, `tripticos` (dinámicas), `carreras-duracion`,
`carreras-ingles`, `carreras-proyecto-integrador`, `estadias-que-son`, `costos-aranceles`.

### Trípticos (PDF de imagen; se leyeron visualmente)
- `https://utypcb.edu.mx/docs/tripticos/ING%20CIVIL.pdf`, `ENERGIA%20SOLAR.pdf`, `INNOVACION%20DIGITAL.pdf`, `NATURALEZA.pdf`,
  `ARANCELES.pdf` (este último es el tríptico de Comercio Internacional y Aduanas).
- «Costos de ingreso», igual en los cinco: **Ficha de examen $500.00, Inscripción $1,500.00, Cuatrimestre $1,600.00,
  Curso propedéutico $500.00, Total $4,100.00**.
- TSU en 2 años + licenciatura/ingeniería en 1 año 4 meses más = 3 años 4 meses. Energía: «Obtén dos títulos universitarios en un
  mismo plan de estudios».
- Comercio: dos TSU, «TSU en Clasificación Arancelaria y Despacho Aduanero» y «TSU en Procesos Logísticos», ambos de 2 años;
  «Modelo educativo basado en competencias profesionales».
- Contacto: «A un costado del Mercado Nuevo, Coyuca de Benítez, Guerrero»; teléfonos **744-638-7225, 722-504-6523, 781-100-3192**;
  `serviciosescolares@utypcb.edu.mx`; Facebook `www.facebook.com/UTYPCB`.

Filas: `costos-total`, `costos-cuatrimestre` (parcial), `admision-propedeutico` (parcial), `carreras-tsu`, `carreras-comercio-tsu`,
`institucional-modelo-educativo`, `contacto-general`, `contacto-facebook`.

### https://utypcb.edu.mx/noticias.php y notas
- `?n=examen-admision-2026-utp-coyuca` (7 jun 2026): examen 4 de julio; licenciatura en 3 años 4 meses y título de TSU en 2 años.
- `?n=ingenieria-civil-disena-construye-futuro` (27 may 2026): «Más información vía WhatsApp: 781-100-3192» → `contacto-whatsapp`.
- `?n=aspirantes-aceptados-2026-2027` (16 may 2026): teléfonos 744 638 7225 · 722 504 6523 · 781 100 3192; «a un costado del Mercado
  Nuevo, Coyuca de Benítez, Gro., arriba de Telecom» → `contacto-ubicacion`.
- `?n=jornada-transformando-guerrero-beca-50` (15 may 2026): beca del 50% en los cuatrimestres, válida los 10 cuatrimestres, para los
  primeros 200 asistentes a la jornada del sábado 23 de mayo de 2026 en la Colonia San José → `becas-transformando-guerrero`.
- `?n=gestiones-entrega-nuevos-edificios` (17 may 2026), `?n=feliz-dia-del-contador`, `?n=foro-guerrero-minero-2026` y otras de 2026:
  Rector **Mtro. Wilibaldo Delgado Carrillo** → `institucional-rector`. (Una nota de deportes de 2024 menciona a otro rector; se usó el
  de las notas de 2026.)
- `?n=historico-impulso-educacion-superior-guerrero` (13 may 2026): nuevas instalaciones con 36 aulas, módulos, auditorio audiovisual
  y laboratorios con simuladores; planes de estudio de la Dirección General de Universidades Tecnológicas y Politécnicas →
  `institucional-nuevas-instalaciones` (parcial), `carreras-validez` (parcial).
- `?n=deporte-convivencia-jornada-por-la-paz` (18 may 2026): torneo relámpago de voley; `?n=segunda-encarreratecnm-5k-10k`: rutas 5K y 10K
  → `deportes-disciplinas`.
- Nota: la noticia del examen enlaza a `sescolar.com/sescolar/preregistro/…`, distinto del enlace de la convocatoria
  (`sescolar.com/sescolar_utpcoyuca/…`). Se usó el de la convocatoria; conviene que Servicios Escolares confirme cuál es el vigente.

### https://utypcb.edu.mx/deportes.php y `deportes.php?n=…`
- Disciplinas en portada: Fútbol y Baloncesto. Encuentros: Segundo Encuentro Deportivo y Cultural de la Región 9 (Emiliano Zapata,
  Morelos, 25 sep 2025) con fútbol 7 femenil, básquetbol varonil y femenil; torneo de aniversario (19 sep 2024).
- Filas: `deportes` (dinámica), `deportes-disciplinas`.

### https://utypcb.edu.mx/transparencia.php
- «Transparencia · Armonización contable»: informes contables, presupuestarios, programáticos y anexos; ejercicios 2024, 2025 y 2026.
- Presupuesto de Ingresos 2025 y Presupuesto de Egresos 2025 (sección «Lo que entra y lo que sale»).
- «Se publican dentro de los 30 días naturales al cierre de cada trimestre»; 2024 «se publicó completo, sin desglose por trimestre»;
  «La cuenta anual 2026 se publicará al cierre del ejercicio, conforme a la Ley General de Contabilidad Gubernamental».
- «¿No encuentras un documento? … Escribir a Servicios Escolares» (`serviciosescolares@utypcb.edu.mx`).
- No hay datos de Unidad de Transparencia ni de solicitudes de acceso a la información (`transparencia-solicitud`, PENDIENTE).

## Páginas que no existen (404) al 17/11/2026
`contacto.php` (la usa `config.example.php` como `contacto_general.url`), `nosotros.php`, `becas.php`, `servicios.php`,
`directorio.php`, `robots.txt`, `sitemap.xml`, `docs/poa_2025.pdf`, `docs/pe_2025_2030.pdf`, `docs/informe_q3.pdf`.

## Datos que faltan y área que debe darlos (resumen de PENDIENTE)
- **Servicios Escolares**: documentos de admisión e inscripción, puntaje mínimo, no aceptados, bachillerato en curso, edad, cupo,
  reinscripción (proceso, adeudos, extemporánea), constancias, credencial, bajas, kárdex, calificaciones, certificado, equivalencias,
  cambio de carrera, extraordinarios, horario de atención, corrección de datos, becas, servicio médico, seguro facultativo, cafetería,
  transporte, uniforme, psicología/tutorías, titulación, cédula, servicio social, solicitudes de transparencia.
- **Recursos Financieros**: inscripción anual, reembolsos, pagos parciales, costos de trámites y de titulación, confirmación de la cuota
  cuatrimestral.
- **Dirección Académica**: modalidad, horario y turnos, sabatino, solo TSU, faltas, biblioteca, laboratorios, opciones de titulación.
- **Vinculación**: estadías (empresas, apoyo económico, requisitos), bolsa de trabajo, selecciones deportivas.
- **Rectoría / planeación**: misión y visión, historia, nuevas instalaciones, POA y Plan Estratégico (enlaces rotos).
