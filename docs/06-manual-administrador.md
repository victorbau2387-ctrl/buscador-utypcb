# 06 · Manual de usuario del Panel del buscador

Guía para las personas que mantienen las respuestas del **buscador de preguntas frecuentes** de la
UTyP Coyuca de Benítez: personal de Servicios Escolares, Vinculación, Dirección Académica, Recursos
Financieros y el responsable técnico. No hace falta saber programar.

> El panel cambia lo que ven aspirantes y alumnos en el sitio **al instante**. Antes de marcar una
> respuesta como validada, confírmala con el área responsable.

---

## 1. Entrar al panel

1. Abre la dirección del panel:
   - En el sitio oficial (cuando esté instalado): `https://utypcb.edu.mx/buscador/admin/`
   - En una PC de pruebas: `http://127.0.0.1:8080/buscador/admin/` (ver la sección 10).
   - También se llega desde el propio buscador: botón **«¿Tienes dudas?»** → pie del panel →
     **«Acceso para personal»**.
2. Escribe tu **usuario** y tu **contraseña**. El ícono del **ojo** muestra u oculta lo que escribes.
3. Clic en **«Entrar al panel»**.

**Seguridad**

- La sesión se cierra sola después de **30 minutos sin uso**. Vuelve a entrar y sigue donde estabas.
- Después de **5 intentos fallidos** el acceso se bloquea **15 minutos**. Si no recuerdas tu
  contraseña, pídele a un superadministrador que la restablezca (sección 8). No hay recuperación por
  correo.
- Usa **«Salir»** (menú superior) al terminar, sobre todo en computadoras compartidas.

---

## 2. Roles: qué puede hacer cada cuenta

| Acción | Editor | Superadministrador |
|---|:-:|:-:|
| Ver el tablero | ✅ | ✅ |
| Crear, editar, validar y archivar preguntas | ✅ | ✅ |
| Usar el probador y revisar «Sin respuesta» | ✅ | ✅ |
| Gestionar áreas y exportar el CSV | ✅ | ✅ |
| Crear cuentas, cambiar roles, desactivar cuentas y restablecer contraseñas | — | ✅ |

Recomendación: **una cuenta por persona** (nunca compartida) y rol **Editor** para el personal de las
áreas. Deja el rol de **Superadministrador** a una o dos personas.

---

## 3. Tablero

Es la primera pantalla. Arriba están los **accesos rápidos**:

- **+ Nueva pregunta**: abre el formulario vacío.
- **Revisar sin respuesta**: lo que la gente preguntó y el buscador no supo contestar.
- **Completar pendientes**: lista las respuestas que todavía dicen `[PENDIENTE: …]`.
- **+ Crear cuenta**: solo para superadministradores.

Debajo están los indicadores de los **últimos 30 días**:

| Indicador | Qué significa |
|---|---|
| Consultas | Cuántas búsquedas se hicieron en el buscador. |
| ¿Te sirvió? positivo | Porcentaje de votos «Sí» que dieron las personas a las respuestas. |
| Tiempo medio de respuesta | Lo que tarda el buscador en contestar (debe ser menos de 2 s). |
| Preguntas validadas | Cuántas respuestas ya confirmó un área, del total. |
| Consultas por resultado | Respondidas, con sugerencias, sin respuesta o carrera no ofertada. |
| Las 10 más consultadas | Las preguntas que más ve la gente: conviene que estén validadas primero. |

---

## 4. Preguntas

Menú **Preguntas**. Muestra la lista con filtros:

- **Texto**: busca en la pregunta, la respuesta, las variantes y las palabras clave. Escribe
  `PENDIENTE` para ver las que faltan por completar.
- **Categoría**: admisión, inscripción, costos, carreras, calendario, trámites, becas, servicios,
  deportes, transparencia, contacto, institucional, titulación o estadías.
- **Estado**: borrador, validada o archivada.

Clic en una pregunta para **editarla**, o **+ Nueva pregunta** para crear una.

### 4.1 Campos del formulario

**Bloque «Pregunta»**

| Campo | Cómo llenarlo |
|---|---|
| Pregunta (como se mostrará) | La pregunta bien escrita, como título. Ej.: *¿Cuánto cuesta la inscripción?* |
| Identificador (slug) | Nombre interno único, en minúsculas y con guiones. Ej.: `inscripcion-costo`. No lo cambies en preguntas que ya existen. |
| Categoría | Una de la lista. |
| Variantes (una por línea) | **Al menos 5** formas en que la gente lo pregunta de verdad. Ver 4.2. |
| Palabras clave | Palabras sueltas que ayudan a encontrarla, separadas por espacios. Ej.: `costo inscripcion pago`. |

**Bloque «Respuesta»**

| Campo | Cómo llenarlo |
|---|---|
| Tipo | **Fija (texto)**: tú escribes la respuesta. **Dinámica**: el buscador la arma en vivo con los datos del sitio (calendario, carreras, noticias, deportes, trípticos). |
| Handler (solo dinámicas) | Qué datos del sitio usa. En las fijas déjalo en «— ninguno —». |
| Respuesta | El texto que verá la persona. Claro, corto y **solo con datos oficiales**. Los saltos de línea se respetan. |
| Carrera (opcional) | Solo si la respuesta es de una carrera específica. |
| Fuente oficial (URL https://) | **Obligatoria.** La página o PDF oficial de donde sale el dato. El buscador la muestra como «Fuente». |
| Área responsable | Quién da y valida ese dato. Si el buscador no sabe algo, canaliza a esta área. |

**Bloque «Validación»**

| Campo | Cómo llenarlo |
|---|---|
| Estado | **Borrador**: aún sin confirmar (el buscador la muestra con la nota «Información en revisión por el área»). **Validada**: el área la confirmó. **Archivada**: ya no se usa (no se borra, se oculta). |
| Validada por (nombre y área) | Quién la confirmó. Ej.: *Lic. Ana Pérez, Servicios Escolares*. |
| Fecha de validación | Cuándo la confirmó. |

Para marcar **Validada**, el panel exige 5 variantes o más, la persona que validó y la fecha.

Al **guardar**, el cambio se ve de inmediato en el buscador del sitio.

### 4.2 Cómo escribir buenas variantes

Las variantes son la parte más importante para que el buscador entienda. Escríbelas **como pregunta la
gente**, no como lo diría un documento oficial:

```
cuanto cuesta inscribirse
cuanto sale la inscripcion
precio de la inscripción
cuánto se paga para entrar
k cuesta inscribirme
```

- Mezcla formas con y sin acentos, con faltas comunes («inscripsion», «veca») y con lenguaje de chat
  («q», «k», «xq»).
- No repitas la misma frase cambiando una sola palabra: busca formas **distintas**.
- Una variante por línea, sin el símbolo `|`.
- La mejor fuente de variantes es la pantalla **Sin respuesta** (sección 6).

### 4.3 Completar una respuesta [PENDIENTE]

Muchas respuestas dicen, por ejemplo, `[PENDIENTE: monto oficial — lo proporciona Recursos Financieros]`.
Para completarlas:

1. Pide el dato al área que se indica. El archivo `conocimiento/fuentes.md` trae la lista agrupada por área.
2. Reemplaza **todo** el texto `[PENDIENTE: …]` por la respuesta real.
3. Pon en **Fuente oficial** la URL donde está publicado el dato. Si el dato no está publicado en el
   sitio, pide al área que lo publique o usa la página más relacionada.
4. Si el área ya lo confirmó, marca **Validada**, con su nombre y la fecha.

**Nunca escribas un dato que no venga del área o del sitio oficial** (fechas, costos, requisitos,
teléfonos). Es la regla principal del proyecto.

### 4.4 Archivar

Si una pregunta ya no aplica (por ejemplo, un trámite que desapareció), usa **Archivar**. No se
borra: deja de aparecer en el buscador y se puede recuperar cambiando su estado.

---

## 5. Probador

Menú **Probador**. Escribe una pregunta como lo haría un alumno y verás **qué respondería el buscador**
y por qué:

- **Resultado**: respondida, sugerencias, sin respuesta o no ofertada.
- **Confianza** (0 a 1): de 0.62 en adelante responde directo; entre 0.30 y 0.62 ofrece
  «¿Quisiste decir…?»; por debajo dice que no tiene la información y canaliza al área.
- **Términos**: cómo «entendió» la pregunta (sin acentos, con faltas corregidas y sinónimos).
- **Puntajes**: las preguntas que compitieron y cuánto sacó cada una.
- **Carrera en contexto (opcional)**: simula que antes se preguntó por una carrera, como en
  «¿y cuánto cuesta?».

Úsalo **después de editar** una pregunta para comprobar que ahora sí la encuentra.

---

## 6. Sin respuesta

Menú **Sin respuesta**. Son las búsquedas reales que el buscador **no pudo contestar** o en las que
**solo dio sugerencias**, y las respuestas que la gente marcó como **«No me sirvió»**. Es la mejor
fuente para mejorar la base. Para cada una:

| Botón | Para qué |
|---|---|
| Crear pregunta a partir de esta | Si es un tema nuevo: abre el formulario con esa frase ya puesta como variante. |
| Agregar como variante de… | Si el tema **ya existe** pero se preguntó con otras palabras: eliges la pregunta y se agrega la frase como variante (puedes corregirla antes). |
| Probar | La abre en el probador. |
| Marcar revisada | Si no aplica (pruebas como «2+2», saludos o temas ajenos a la universidad). |

Los correos y teléfonos que la gente escriba aparecen **enmascarados**: el buscador no guarda datos
personales.

---

## 7. Áreas

Menú **Áreas**. Son las oficinas responsables (Servicios Escolares, Vinculación, etc.) con su correo,
teléfono y página. El buscador muestra estos datos cuando **no tiene la respuesta**, para canalizar a
la persona.

- Pon **solo datos de contacto oficiales y públicos**.
- Si un área tiene varios teléfonos, sepáralos con coma: `744 638 7225, 722 504 6523`.
- Si borras un área, sus preguntas quedan **«sin área»** y el buscador canaliza al contacto general.
  Antes de borrarla, reasigna esas preguntas a otra área.

---

## 8. Cuentas (solo superadministrador)

Menú **Cuentas**, o **+ Crear cuenta** en el tablero.

**Crear una cuenta para otra persona**

1. **+ Crear cuenta**.
2. **Usuario**: corto y sin espacios. Ej.: `ana.escolares`.
3. **Nombre completo** y **Rol**: *Editor* para el personal de las áreas.
4. **Contraseña inicial**: al menos 12 caracteres, escrita dos veces.
5. **Crear cuenta**. Comunica la contraseña a la persona **en persona o por un medio seguro** (no por
   un grupo de WhatsApp) y pídele que la cambie en **Mi cuenta** al entrar.

**Otras acciones** (en la tabla de cuentas)

- **Hacer Superadministrador / Hacer Editor**: cambia el rol.
- **Desactivar / Reactivar**: quien deja el puesto se **desactiva**; no se borra, para conservar el
  historial. Si estaba dentro, su sesión termina en la siguiente acción que haga.
- **Restablecer contraseña**: escribes una nueva para esa persona (nunca se muestra la anterior).

El panel no te deja desactivarte a ti mismo ni quedarse sin ningún superadministrador activo.

## 9. Mi cuenta

Menú **Mi cuenta**: cambia tu propia contraseña. Te pide la actual y la nueva dos veces (12 caracteres
o más). Usa una contraseña **que no uses en otros sitios**.

## 10. Exportar CSV

Menú **Exportar CSV**: descarga todas las preguntas en un archivo que **abre directo en Excel**, con
acentos correctos. Sirve para:

- Mandar a cada área la lista de sus preguntas para que las revise.
- Entregar la **base de conocimiento en formato revisable**, como pide la entrega.
- Respaldar o pasar los cambios a otra PC (sección 11).

---

## 11. Usar el panel en otra computadora (Windows)

**Una sola vez**

1. Instala **Git** (https://git-scm.com).
2. En PowerShell:
   ```powershell
   cd C:\
   git clone https://github.com/victorbau2387-ctrl/buscador-utypcb.git
   ```
   El repositorio es privado: inicia sesión con la cuenta de GitHub que tiene acceso.
3. En la carpeta `C:\buscador-utypcb`: clic derecho en **`instalar-herramientas.ps1`** →
   **Ejecutar con PowerShell**. Baja PHP y MariaDB de sus sitios oficiales, los verifica y los deja en
   `%USERPROFILE%\dev-tools`. Si dice que PHP no arranca, instala el
   *Microsoft Visual C++ Redistributable 2015-2022 (x64)* y vuelve a ejecutarlo.

**Cada vez que lo uses**

4. Doble clic en **`iniciar-panel.cmd`**.
   - La primera vez carga las preguntas de `conocimiento/preguntas.csv` y te pide **crear tu cuenta**
     (elige `superadmin`; la contraseña no se ve mientras la escribes).
   - Se abre el navegador en el panel. **Deja abierta la ventana negra** mientras lo usas; al cerrarla
     se apaga.

**Llevar tus cambios de una PC a otra**

Lo que editas en el panel se guarda en la base de datos **de esa PC**, no en GitHub. Para pasarlo:

1. En la PC donde editaste: **Exportar CSV** y guarda el archivo como
   `conocimiento\preguntas.csv` (reemplaza el anterior).
2. Sube el cambio a GitHub (`git add`, `git commit`, `git push`, o pídeselo al responsable técnico).
3. En la otra PC, en PowerShell dentro de la carpeta del proyecto:
   ```powershell
   git pull
   $env:BF_REIMPORTAR = "1"; .\iniciar-panel.cmd
   ```
   Eso vuelve a cargar el CSV actualizado. Las preguntas con el mismo identificador (slug) se
   reemplazan por las del archivo.

Las cuentas y el historial de consultas **no viajan**: cada PC tiene los suyos.

---

## 12. Problemas comunes

| Situación | Qué hacer |
|---|---|
| «Tu sesión se cerró por inactividad» | Pasaron 30 min sin uso. Vuelve a entrar. |
| «Demasiados intentos» | Espera 15 minutos o pide a un superadministrador que restablezca tu contraseña. |
| Edité una pregunta y el buscador no la encuentra | Pruébala en el **Probador**: agrega variantes con las palabras que usó la persona. |
| La respuesta sale con «Información en revisión por el área» | Está en **Borrador**. Márcala **Validada** cuando el área la confirme. |
| No veo el menú **Cuentas** | Tu cuenta es *Editor*. Pide el cambio a un superadministrador. |
| En la PC de pruebas no abre `127.0.0.1:8080` | Revisa que la ventana negra de `iniciar-panel.cmd` siga abierta. |
| Perdí mis cambios al cambiar de PC | Los cambios viven en la base de cada PC: usa **Exportar CSV** antes (sección 11). |
