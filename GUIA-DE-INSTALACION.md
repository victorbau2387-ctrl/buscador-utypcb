# 🦊 Guía paso a paso: instalar y abrir el Panel del buscador

Esta guía explica **todo desde cero**, sin dar nada por sabido. Si sigues los pasos en orden, en
unos 20 minutos tendrás el **Panel del buscador de preguntas frecuentes** de la UTyP funcionando en
tu computadora.

> 💡 **Consejo:** lee cada paso completo antes de hacerlo. Si algo no sale igual, busca tu problema en
> la sección **«¿Algo salió mal?»** al final.

---

## ✅ Antes de empezar: ¿qué necesitas?

| Necesitas | ¿Cómo sé si lo tengo? |
|---|---|
| Una computadora con **Windows 10 u 11** | Abajo a la izquierda ves el botón de inicio con el logo de Windows ⊞ |
| **Internet** (solo la primera vez) | Puedes abrir páginas web |
| Unos **500 MB libres** en el disco | Casi todas las computadoras los tienen |
| **20 minutos** | 😉 |

No necesitas saber programar ni tener Claude, XAMPP u otro programa parecido. Todo lo necesario se
instala con esta guía.

---

## Paso 1 · Descargar el proyecto

1. Abre tu navegador (Chrome, Edge o el que uses).
2. Entra a esta dirección: **https://github.com/victorbau2387-ctrl/buscador-utypcb**
3. Busca el botón verde que dice **`<> Code`** y haz clic en él.
4. En el menú que aparece, haz clic en **`Download ZIP`** (descargar ZIP).
5. Se descarga un archivo llamado **`buscador-utypcb-main.zip`**. Normalmente queda en la carpeta
   **Descargas**.

> 📦 Un archivo **ZIP** es como una caja que guarda muchos archivos juntos. En el siguiente paso la
> vamos a «abrir».

---

## Paso 2 · Sacar los archivos del ZIP

1. Abre el **Explorador de archivos**: es el ícono de la **carpeta amarilla** 📁 en la barra de abajo.
   También se abre con las teclas **⊞ Windows + E**.
2. A la izquierda, haz clic en **Descargas**.
3. Busca **`buscador-utypcb-main.zip`** y haz **clic derecho** sobre él.
4. Elige **`Extraer todo…`**.
5. En la ventana que aparece, borra lo que dice en el cuadro de texto y escribe:
   ```
   C:\
   ```
6. Haz clic en **`Extraer`**.

Listo: ahora tienes la carpeta **`C:\buscador-utypcb-main`** con todo el proyecto adentro. 🎉

> ⚠️ **No trabajes desde dentro del ZIP.** Si abres el ZIP con doble clic, Windows te muestra los
> archivos pero **no funcionan desde ahí**. Siempre usa la carpeta extraída.

---

## Paso 3 · Instalar las herramientas (solo la primera vez)

El panel necesita dos programas para funcionar:

- **PHP**: el «motor» que hace que la página funcione.
- **MariaDB**: la «libreta» donde se guardan las preguntas y las cuentas.

Hay un archivo que los instala solo:

1. Abre la carpeta **`C:\buscador-utypcb-main`** en el Explorador de archivos.
2. Busca el archivo **`instalar-herramientas.ps1`** (puede verse como `instalar-herramientas`).
3. Haz **clic derecho** sobre él y elige **`Ejecutar con PowerShell`**.
   - En Windows 11, si no aparece esa opción, haz clic en **`Mostrar más opciones`** al final del
     menú y búscala ahí.
4. Se abre una **ventana azul o negra** con letras. Es normal. **No la cierres.**
5. Verás mensajes como estos (tarda entre 2 y 10 minutos, según tu internet):
   ```
   Descargando php74 ...
       Verificado (SHA-256).
   Descargando php83 ...
       Verificado (SHA-256).
   Descargando mariadb ...
       Verificado (SHA-256).
   php74 listo: 7.4.33 pdo_mysql
   php83 listo: 8.3.36 pdo_mysql
   MariaDB listo (puerto 3307, solo accesible desde esta PC).

   Listo. Ahora da doble clic en  iniciar-panel.cmd
   ```
6. Cuando diga **`Presiona Enter para cerrar esta ventana`**, presiona la tecla **Enter** ↵.

> 🔒 **¿Es seguro?** Sí. Descarga los programas de sus páginas oficiales y revisa que no estén
> alterados («Verificado (SHA-256)» significa que pasó esa revisión). No cambia nada de Windows: todo
> queda en la carpeta `C:\Users\TU-USUARIO\dev-tools`.

**¿Saliste con un mensaje amarillo que dice «no arranca» y menciona «Visual C++»?** Te falta un
complemento de Microsoft que PHP necesita:

1. Entra a **https://aka.ms/vs/17/release/vc_redist.x64.exe**. Se descarga un archivo.
2. Ábrelo con doble clic, marca **«Acepto los términos»** y haz clic en **Instalar**.
3. Vuelve a hacer el **Paso 3** desde el principio.

---

## Paso 4 · Abrir el panel

1. En la carpeta **`C:\buscador-utypcb-main`**, busca **`iniciar-panel.cmd`** (puede verse como
   `iniciar-panel`, con un ícono de engranes ⚙️).
2. Dale **doble clic**.
3. Si aparece una ventana azul que dice **«Windows protegió su PC»**:
   - Haz clic en **`Más información`**.
   - Luego en **`Ejecutar de todas formas`**.

   (Windows avisa así con cualquier archivo descargado de internet que no conoce. Este es el tuyo.)
4. Se abre una **ventana negra**. Esa ventana **es el servidor** del panel:
   **⚠️ déjala abierta mientras uses el panel.** Si la cierras, el panel se apaga.

### La primera vez: crea tu cuenta

La primera vez, la ventana negra te hace unas preguntas. Contéstalas escribiendo y presionando
**Enter** ↵ después de cada una:

| Te pregunta | Qué escribir | Ejemplo |
|---|---|---|
| Usuario | Un nombre corto, sin espacios | `maria.lopez` |
| Nombre | Tu nombre completo | `María López` |
| Rol (dice `[superadmin/editor]`) | Escribe **`superadmin`** | `superadmin` |
| Contraseña | Una contraseña de **12 letras o más** | (no la escribas en ningún otro lado) |
| Repite la contraseña | La misma otra vez | |

> 🙈 **Al escribir la contraseña verás asteriscos `****` en lugar de las letras** (en algunas
> computadoras no se ve nada). Es a propósito, para que nadie la vea. Escríbela con calma y presiona
> **Enter**.

> 📝 **Apunta tu usuario y contraseña en un lugar seguro.** Si los olvidas, otra persona con cuenta
> de superadministrador te los puede restablecer.

### Entrar

5. Se abre solo tu navegador en la página **«Panel del buscador de preguntas frecuentes»**.
   Si no se abre, abre el navegador y escribe en la barra de arriba:
   ```
   http://127.0.0.1:8080/buscador/admin/
   ```
6. Escribe tu **usuario** y tu **contraseña**. Con el **ojito** 👁 puedes ver lo que escribiste.
7. Haz clic en **`Entrar al panel`**.

**¡Ya estás dentro!** 🎉 Para aprender a usar el panel (editar preguntas, crear cuentas para otras
personas, etc.) lee el **[Manual de usuario del panel](docs/06-manual-administrador.md)**.

---

## Las siguientes veces (ya instalado)

Solo necesitas **un paso**:

1. Doble clic en **`iniciar-panel.cmd`**.
2. Deja la ventana negra abierta y entra con tu usuario y contraseña.

Las preguntas y los cambios que hagas en el panel **se guardan**, aunque apagues la computadora.

## Cómo apagarlo

Cuando termines, **cierra la ventana negra** con la ❌ de arriba a la derecha, o haz clic en ella y
presiona **Ctrl + C**. Así se apaga el panel.

---

## 🧭 Extra: ¿cómo se abre CMD o PowerShell?

Algunas instrucciones te piden «escribir un comando». Para eso se usa una ventana de comandos:

1. Presiona la tecla **⊞ Windows** (o haz clic en el botón de inicio).
2. Escribe **`PowerShell`** (o **`cmd`**).
3. Haz clic en **«Windows PowerShell»** (o en **«Símbolo del sistema»**).
4. Se abre una ventana donde puedes escribir. Para **ir a la carpeta del proyecto**, escribe esto y
   presiona **Enter**:
   ```
   cd C:\buscador-utypcb-main
   ```
5. Para abrir el panel desde ahí, escribe:
   ```
   .\iniciar-panel.cmd
   ```

> ✍️ **Truco:** en estas ventanas puedes **pegar** texto con **clic derecho** o con **Ctrl + V**.

## 🧭 Extra: ver los códigos del programa

1. Descarga e instala **Visual Studio Code** desde **https://code.visualstudio.com**: botón azul de
   descarga, luego **Siguiente, Siguiente… Instalar**.
2. Ábrelo y ve al menú **`Archivo` → `Abrir carpeta…`** y elige **`C:\buscador-utypcb-main`**.
3. A la izquierda verás todas las carpetas:

| Carpeta | Qué hay adentro |
|---|---|
| `private/src/Search/` | 🧠 El «cerebro» del buscador: cómo entiende las preguntas |
| `private/src/Admin/` | 🔐 El panel: login, preguntas y cuentas |
| `public_html/buscador/` | 🎨 El botón «¿Tienes dudas?» y su diseño |
| `conocimiento/preguntas.csv` | 📋 Todas las preguntas y respuestas (también se abre en Excel) |
| `database/schema.sql` | 🗄️ Cómo está organizada la base de datos |
| `docs/` | 📚 Los manuales |

---

## 🆘 ¿Algo salió mal?

| Lo que pasa | Cómo arreglarlo |
|---|---|
| No aparece **«Ejecutar con PowerShell»** | Windows 11: clic derecho → **«Mostrar más opciones»**. O abre PowerShell (ver *Extra*) y escribe: `cd C:\buscador-utypcb-main` y después `powershell -ExecutionPolicy Bypass -File .\instalar-herramientas.ps1` |
| Sale en rojo **«la ejecución de scripts está deshabilitada»** | Usa el comando de la fila de arriba (`-ExecutionPolicy Bypass`). Solo aplica a esa vez y no cambia tu Windows. |
| Dice **«ERROR … descargar»** | Revisa que tengas internet y vuelve a ejecutar el instalador. Lo que ya se descargó no se repite. |
| Mensaje amarillo de **«Visual C++»** | Instala el complemento de Microsoft (ver final del **Paso 3**). |
| **«No encuentro …php.exe»** al abrir `iniciar-panel.cmd` | Falta el **Paso 3**. Hazlo y vuelve a intentar. |
| **«Windows protegió su PC»** | **Más información → Ejecutar de todas formas**. |
| Aparece una ventana del **Firewall de Windows** preguntando por `php` o `mysqld` | Puedes darle **Cancelar**: el panel solo se usa dentro de tu computadora y funciona igual. |
| La página dice **«No se puede acceder a este sitio»** | La ventana negra está cerrada. Vuelve a abrir `iniciar-panel.cmd` y déjala abierta. |
| La ventana negra dice **«Address already in use»** o **«Failed to listen»** | Ya hay otro panel abierto. Busca la otra ventana negra en la barra de tareas y úsala (o ciérrala y vuelve a abrir). También puede ser que reiniciar la computadora lo arregle. |
| Me equivoqué al crear mi cuenta | Abre PowerShell en la carpeta del proyecto y escribe:<br>`$env:BF_CONFIG = "tests/sitio-local/config.local.php"`<br>`& "$env:USERPROFILE\dev-tools\php83\php.exe" private\bin\crear-admin.php`<br>Si pones el mismo usuario, te ofrece cambiarle la contraseña. |
| **«Demasiados intentos»** al entrar | Espera 15 minutos. Es una protección contra quien intenta adivinar contraseñas. |
| **«Tu sesión se cerró por inactividad»** | Pasaron 30 minutos sin usar el panel. Vuelve a entrar. |
| El antivirus bloquea algo | Los programas vienen de php.net y mariadb.org y están verificados. Permite la carpeta `C:\Users\TU-USUARIO\dev-tools` en tu antivirus. |

Si nada de esto lo arregla, toma una **captura de pantalla** (tecla **⊞ Windows + Shift + S**) del
mensaje y mándasela a la persona encargada del proyecto.

---

## 🗑️ Desinstalar

1. Borra la carpeta **`C:\buscador-utypcb-main`**.
2. Borra la carpeta **`C:\Users\TU-USUARIO\dev-tools`**. Ahí están PHP, MariaDB y la base de datos
   con tus cuentas y cambios; si quieres conservar tus preguntas, antes usa **Exportar CSV** en el panel.

No queda nada más instalado en Windows.
