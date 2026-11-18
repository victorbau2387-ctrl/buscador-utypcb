<?php
// Aviso de privacidad simplificado del buscador de preguntas frecuentes.
// Los textos entre corchetes [ ] los debe completar la institución antes de publicar.
declare(strict_types=1);

require __DIR__ . '/_ruta_privada.php';
require BF_RUTA_PRIVADA . '/src/bootstrap.php';

use Buscador\Support\Config;
use Buscador\Support\Http;

$dias = (int) Config::get('privacidad.dias_retencion', 90);
$registra = (bool) Config::get('privacidad.registrar_consultas', true);
$contacto = (array) Config::get('contacto_general', []);
$correo = (string) ($contacto['correo'] ?? '');
$telefono = (string) ($contacto['telefono'] ?? '');
$urlContacto = (string) ($contacto['url'] ?? '');

header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Aviso de privacidad del buscador de preguntas frecuentes</title>
<style>
  body { margin: 0; background: #f5f6f8; color: #1d232b; font: 1rem/1.6 system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif; }
  main { max-width: 44rem; margin: 0 auto; padding: 1.5rem 1rem 3rem; background: #fff; }
  h1 { font-size: 1.5rem; line-height: 1.3; }
  h2 { font-size: 1.1rem; margin-top: 1.75rem; }
  a { color: #1f5c99; }
  .pendiente { background: #fff4d6; padding: 0 .2rem; }
  .nota { color: #5a6472; font-size: .9rem; }
</style>
</head>
<body>
<main>
<h1>Aviso de privacidad simplificado del buscador de preguntas frecuentes</h1>
<p>La <strong>UTyP Coyuca de Benítez</strong> (en adelante, la Universidad), a través de
<span class="pendiente">[NOMBRE DEL RESPONSABLE / ÁREA RESPONSABLE]</span>, es responsable del tratamiento de los datos que se generan al usar
el buscador de preguntas frecuentes de este sitio.</p>

<h2>¿Qué registramos?</h2>
<?php if ($registra): ?>
<p>Cuando escribes una pregunta en el buscador guardamos únicamente:</p>
<ul>
  <li>El <strong>texto de la consulta</strong>. Antes de guardarlo, los correos electrónicos y números de teléfono que escribas se reemplazan automáticamente por <em>[correo]</em> y <em>[número]</em>.</li>
  <li>El <strong>resultado</strong> (si se respondió, se sugirieron preguntas o no hubo respuesta), la pregunta frecuente que se mostró y el nivel de coincidencia.</li>
  <li>El <strong>tiempo</strong> que tardó la respuesta, la fecha y hora, y, si la eliges, tu opinión en “¿te sirvió?”.</li>
</ul>
<?php else: ?>
<p>Actualmente el buscador <strong>no guarda</strong> el texto de las consultas.</p>
<?php endif; ?>

<h2>¿Qué NO registramos?</h2>
<ul>
  <li>Tu <strong>dirección IP en claro</strong>. Para evitar abusos se guarda una huella (hash irreversible) que no permite conocer tu IP y que solo sirve para contar solicitudes durante unos minutos.</li>
  <li>Tu <strong>nombre</strong> ni ningún dato de cuenta: el buscador no pide registrarse.</li>
  <li><strong>Cookies de rastreo</strong> ni herramientas de publicidad o analítica de terceros.</li>
</ul>
<p>Te pedimos <strong>no escribir datos personales</strong> (nombre, CURP, matrícula, domicilio) en el buscador: no los necesita para responder.</p>

<h2>¿Para qué usamos esta información?</h2>
<ul>
  <li>Mejorar las respuestas: detectar preguntas que el buscador no sabe contestar y agregarlas o corregirlas.</li>
  <li>Obtener estadísticas generales de uso (por ejemplo, las preguntas más consultadas).</li>
  <li>Proteger el servicio contra el uso automatizado o abusivo.</li>
</ul>
<p>No usamos la información para fines comerciales ni la compartimos con terceros, salvo requerimiento de una autoridad competente conforme a la ley.</p>

<h2>¿Cuánto tiempo la conservamos?</h2>
<p>Las consultas se eliminan automáticamente después de <strong><?= Http::e((string) $dias) ?> días</strong>. Los contadores contra abuso se borran a más tardar en un día.</p>

<h2>¿Quién tiene acceso?</h2>
<p>Solo el personal autorizado de la Universidad que administra el buscador, mediante una cuenta protegida con contraseña.</p>

<h2>Tus derechos y contacto</h2>
<p>Puedes ejercer tus derechos de acceso, rectificación, cancelación u oposición (derechos ARCO), o hacer cualquier pregunta sobre este aviso, con
<span class="pendiente">[NOMBRE DEL RESPONSABLE / UNIDAD DE TRANSPARENCIA]</span>
<?php if ($correo !== ''): ?>
en el correo <a href="mailto:<?= Http::e($correo) ?>"><?= Http::e($correo) ?></a><?php endif; ?>
<?php if ($telefono !== ''): ?>
, teléfono <?= Http::e($telefono) ?><?php endif; ?>
<?php if ($correo === '' && $telefono === ''): ?>
<span class="pendiente">[CORREO Y TELÉFONO DE CONTACTO]</span><?php endif; ?>.
<?php if ($urlContacto !== ''): ?>
También puedes usar la página de <a href="<?= Http::e($urlContacto) ?>">contacto</a>.
<?php endif; ?>
Como el buscador no guarda datos que te identifiquen, normalmente no es posible localizar una consulta concreta hecha por ti.</p>

<p>El aviso de privacidad integral de la Universidad está disponible en <span class="pendiente">[URL DEL AVISO DE PRIVACIDAD INTEGRAL]</span>.</p>

<p class="nota">Última actualización: <span class="pendiente">[FECHA]</span>.</p>
</main>
</body>
</html>
