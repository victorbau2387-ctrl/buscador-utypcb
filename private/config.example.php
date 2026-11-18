<?php
// Copia este archivo como private/config.php y ajusta los valores.
// private/ debe quedar FUERA de public_html. config.php nunca se sube al repositorio.

return [
    'entorno' => 'produccion', // 'desarrollo' muestra errores detallados

    'db' => [
        'host'     => 'localhost',
        'puerto'   => 3306,
        'nombre'   => 'utyp_buscador',
        'usuario'  => 'utyp_buscador',
        'password' => '',
    ],

    // Ruta a la carpeta del sitio donde están calendario_data.php, carreras_data.php, etc.
    // Se leen con require_once: única fuente de verdad, no se duplican.
    'sitio' => [
        'ruta_datos' => __DIR__ . '/../public_html', // private/ va junto a public_html
        'url_base'   => 'https://utypcb.edu.mx',
    ],

    'seguridad' => [
        // Orígenes permitidos para el endpoint (sin comodín *).
        'origenes_permitidos' => ['https://utypcb.edu.mx', 'https://www.utypcb.edu.mx'],
        'max_longitud'        => 300,   // caracteres por consulta
        'limite_por_minuto'   => 20,    // consultas por visitante
        'login_intentos'      => 5,     // intentos por 15 min
        'sal_hash'            => 'CAMBIA-ESTE-VALOR-POR-UNO-ALEATORIO-LARGO',
    ],

    'privacidad' => [
        'registrar_consultas' => true,
        'dias_retencion'      => 90,
    ],

    'busqueda' => [
        'umbral_respuesta'   => 0.62, // confianza mínima para responder directo
        'umbral_sugerencias' => 0.30, // por debajo: "no tengo esa información"
        'max_sugerencias'    => 3,
        'solo_validadas'     => false, // true en producción cuando las áreas validen
    ],

    // Contacto general cuando no hay respuesta.
    'contacto_general' => [
        'nombre' => 'Servicios Escolares',
        'correo' => '',
        'telefono' => '',
        'url' => 'https://utypcb.edu.mx/contacto.php',
    ],
];
