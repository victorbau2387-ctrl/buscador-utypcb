# 07 · Aviso de privacidad y seguridad

## 1. Aviso de privacidad (versión para el público)

> El buscador de preguntas frecuentes de la UTyP Coyuca de Benítez **no te pide ni guarda datos personales**. Para mejorar las respuestas guardamos, durante [PENDIENTE: 90] días, el texto de las preguntas que se hacen (sin tu IP ni ningún dato que te identifique; si escribes un correo o un teléfono, se reemplaza por "[correo]" o "[número]") y si la respuesta te fue útil. No usamos servicios externos ni inteligencia artificial de terceros: todo se procesa en el servidor de la universidad. Por favor no escribas datos personales en tus preguntas. Responsable: [PENDIENTE: área y contacto de la institución]. Aviso de privacidad integral: [PENDIENTE: URL del aviso institucional].

El texto definitivo y su ubicación en el widget los aprueba la institución.

## 2. Qué se registra y qué no

| Se registra (`bf_consulta`) | No se registra nunca |
|---|---|
| Texto de la consulta, máximo 300 caracteres, con correos → `[correo]` y teléfonos (secuencias de 9 o más caracteres entre dígitos, espacios y guiones, que empiezan y terminan en dígito) → `[número]` | Dirección IP |
| Resultado (`respondida`, `sugerencias`, `sin_respuesta`, `no_ofertada`), pregunta respondida, confianza y milisegundos | Nombre, correo, teléfono, matrícula o CURP (salvo que el visitante los escriba en un formato que no se detecte: por eso el aviso pide no escribir datos personales) |
| Si el visitante marcó la respuesta como útil | Navegador (*user agent*), cookies o identificadores de sesión del público |
| Fecha y hora | Historial que vincule varias consultas de una misma persona |

**Límite de solicitudes (`bf_limite`):** guarda `SHA-256(ámbito | SHA-256(sal_hash | IP))`, la ventana de un minuto y un contador. No guarda la IP y no se puede revertir sin la sal. Los contadores vencidos se borran solos y con `purgar.php`.

**Contexto de conversación:** viaja en cada petición desde el navegador (`contexto.carrera`); el servidor no guarda sesión del público.

**Panel:** `bf_admin` guarda usuario, nombre, hash de la contraseña (nunca la contraseña), rol, si está activo y el último acceso.

**Logs de PHP** (`private/logs/php-error.log`): solo errores técnicos (mensaje, archivo y línea), nunca la consulta del visitante.

## 3. Retención

- `privacidad.dias_retencion` (90 días por defecto, [PENDIENTE: confirmar]) define cuánto se conserva `bf_consulta`.
- `private/bin/purgar.php`, ejecutado por cron todos los días, borra las consultas más antiguas y los contadores vencidos de `bf_limite`.
- `privacidad.registrar_consultas = false` desactiva por completo el registro de consultas (el buscador sigue funcionando; el panel pierde las estadísticas y la lista de consultas sin respuesta).

## 4. Controles de seguridad

### 4.1 Superficie expuesta

En `public_html/buscador/` solo hay: el endpoint (`api.php`, 8 líneas que cargan el código privado), el widget y el panel. Todo el código, la configuración con la contraseña de la base, el léxico y los logs están en `private/`, **fuera** de `public_html`. Los scripts de `private/bin/` terminan inmediatamente si no se ejecutan desde la línea de comandos.

### 4.2 CORS restringido

- Solo se aceptan peticiones con `Origin` incluido **exactamente** en `seguridad.origenes_permitidos` (comparación estricta: `http://`, otro puerto, un subdominio o `null` → 403).
- Nunca se responde `Access-Control-Allow-Origin: *`; se devuelve el origen concreto con `Vary: Origin`.
- Métodos permitidos: `POST, OPTIONS`; cabecera permitida: `Content-Type`.
- Las peticiones sin `Origin` (el propio sitio) se aceptan.

### 4.3 Límites

| Límite | Valor por defecto | Dónde |
|---|---|---|
| Tamaño del cuerpo leído | 8 KB | `BuscarApi` |
| Longitud de la consulta | 300 caracteres (`seguridad.max_longitud`) | `BuscarApi` → 422 |
| Consultas por visitante por minuto | 20 (`seguridad.limite_por_minuto`) | `RateLimiter` → 429 |
| Intentos de inicio de sesión en el panel | 5 cada 15 minutos (`seguridad.login_intentos`) | panel (docs/06) |

### 4.4 Validación de entrada

- Solo `POST` (405 en otro caso) y cuerpo que sea un objeto JSON (400 en otro caso).
- La consulta se pasa por `strip_tags`, se normalizan los espacios y se valida su longitud con `mb_strlen` (UTF-8).
- `id`, `consulta_id` y `util` se convierten a entero; `contexto.carrera` solo se acepta si es el slug de una carrera activa.
- Todas las consultas SQL son **preparadas** (PDO con `ATTR_EMULATE_PREPARES = false`); no se concatenan valores del usuario.

### 4.5 Escape de salida

- El endpoint responde solo JSON (`Content-Type: application/json; charset=utf-8`, `X-Content-Type-Options: nosniff`, `Cache-Control: no-store`) y **no refleja** la consulta del visitante. Las pruebas comprueban que `<script>`, `<img onerror>` o `<svg onload>` no aparecen en la respuesta ni en el registro.
- El widget inserta los textos como texto (no como HTML) y las vistas del panel escapan toda salida con `Http::e()` (`htmlspecialchars` con `ENT_QUOTES | ENT_SUBSTITUTE`, UTF-8).
- Los errores internos devuelven un mensaje genérico (500); el detalle va al log.

### 4.6 Sesiones del panel

Lineamientos que debe cumplir el panel (su implementación se documentará en docs/06):

- Cookie de sesión con `HttpOnly`, `Secure` (solo HTTPS) y `SameSite=Strict` (o `Lax`); nombre propio para no compartirla con otras aplicaciones del dominio.
- `session_regenerate_id(true)` al iniciar sesión; cierre por inactividad; botón de cerrar sesión que destruye la sesión.
- Solo cuentas activas; roles `superadmin` y `editor`.

### 4.7 CSRF

Todo formulario o acción que modifique datos en el panel lleva un **token CSRF** aleatorio por sesión, verificado con `hash_equals`, y solo se acepta por `POST`. El endpoint público no usa cookies ni sesión, así que no es susceptible de CSRF; además, CORS bloquea a orígenes ajenos.

### 4.8 Contraseñas

- Se guardan con `password_hash()` (bcrypt/argon2 según la versión de PHP) y se verifican con `password_verify()`; `password_needs_rehash()` permite actualizar el algoritmo tras migrar a PHP 8.
- No existen cuentas ni contraseñas predefinidas: la primera se crea con `crear-admin.php`, que no muestra la contraseña al escribirla.
- La contraseña de la base de datos vive **solo** en `private/config.php` (permisos 600/640), que no se sube al repositorio (`.gitignore`). Ningún documento contiene contraseñas.
- `sal_hash` es aleatoria y distinta en cada instalación.

### 4.9 ModSecurity

Se mantiene activo. Si bloquea consultas legítimas, se solicita una exclusión **por ID de regla y solo para `/buscador/api.php`**, nunca la desactivación global (procedimiento en [docs/03, sección 10](03-instalacion-e-integracion.md#10-prueba-con-modsecurity)).

### 4.10 Limitaciones conocidas

- La acción `util` no exige autenticación: cualquiera que conozca un `consulta_id` puede marcarlo una vez (solo si aún no tiene valor). El impacto se limita a la estadística de utilidad.
- La huella del límite de solicitudes depende de la IP que ve PHP: detrás del proxy nginx de CWP debe comprobarse que `REMOTE_ADDR` es la IP del visitante y no la del proxy; si fuera la del proxy, todos los visitantes compartirían el mismo contador. [PENDIENTE: verificar en el hosting]
- El enmascarado de datos personales cubre correos y números de 9 o más caracteres; otros datos (nombres, matrículas cortas) no se detectan.

## 5. Respuesta a incidentes

### 5.1 Se filtró una credencial (contraseña de la base, cuenta del panel o `config.php`)

1. **Contener:** si se sospecha uso indebido, quita temporalmente la línea del widget de `footer.inc.php` y renombra `public_html/buscador/admin/` para cerrar el panel.
2. **Rotar la contraseña de MariaDB:** CWP › MySQL Manager › cambiar la contraseña del usuario de la aplicación por una nueva aleatoria; actualizar `db.password` en `private/config.php`. Revisar en MySQL Manager que el usuario solo tenga acceso desde `localhost` y solo a su base.
3. **Rotar cuentas del panel:** restablecer la contraseña de cada cuenta con `crear-admin.php` (también restablece contraseñas existentes) y desactivar las que no se usen. Si se filtró una cuenta concreta, desactivarla primero.
4. **Rotar `sal_hash`:** generar una nueva (`php -r "echo bin2hex(random_bytes(32));"`). Esto invalida las huellas del límite de solicitudes (no afecta a nada más).
5. **Cerrar sesiones activas del panel:** borrar los archivos de sesión del usuario de PHP o cambiar el nombre de la cookie de sesión del panel.
6. **Revisar y purgar:** revisar en el panel o en phpMyAdmin cambios recientes en `bf_faq`, `bf_variante`, `bf_area` y `bf_admin` (`actualizado_en`, `ultimo_acceso`) y restaurar desde respaldo si hubo alteraciones. Si el historial de consultas pudo exponerse, purgarlo: `TRUNCATE bf_consulta;` (o `php bin/purgar.php` con una retención menor).
7. **Revisar logs:** `private/logs/php-error.log`, logs de acceso de Apache/nginx y de ModSecurity del periodo.
8. **Credenciales de FTP/CWP:** si la filtración vino por ahí, cambiarlas también y revisar que no haya archivos nuevos o modificados en `public_html` y `private-buscador`.
9. **Reactivar** el widget y el panel, y **registrar** el incidente (fecha, alcance, acciones) con el área responsable de la institución [PENDIENTE: contacto].

### 5.2 Abuso del endpoint (muchas peticiones, contenido ofensivo)

- Bajar `seguridad.limite_por_minuto`, revisar que el límite se aplique por visitante (sección 4.10) y, si persiste, bloquear la IP en el firewall de CWP.
- Las consultas ofensivas se pueden borrar desde el panel o con `DELETE FROM bf_consulta WHERE id = …`; no afectan las respuestas, que son fijas y validadas.

### 5.3 Respuesta incorrecta publicada

- Archivar o corregir la pregunta en el panel (efecto inmediato: el índice se construye en cada consulta).
- Con `busqueda.solo_validadas = true` solo se publican respuestas validadas por las áreas.
