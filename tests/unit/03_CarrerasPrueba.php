<?php
declare(strict_types=1);

use Buscador\Search\Carreras;

grupo('Carreras');

const SLUG_CIVIL    = 'ingenieria-civil';
const SLUG_COMERCIO = 'licenciatura-en-comercio-internacional-y-aduanas';
const SLUG_ENERGIA  = 'ingenieria-en-energia-y-desarrollo-sostenible';
const SLUG_ITI      = 'ingenieria-en-tecnologias-de-la-informacion-e-innovacion-digital';
const SLUG_RECURSOS = 'licenciatura-en-ingenieria-en-manejo-de-recursos-naturales';

prueba('activas: lee las 5 carreras del fixture con slug generado', function () {
    igual([SLUG_CIVIL, SLUG_COMERCIO, SLUG_ENERGIA, SLUG_ITI, SLUG_RECURSOS], array_keys(Carreras::activas()));
});

prueba('activas: las claves son palabras exclusivas (no genéricas)', function () {
    $a = Carreras::activas();
    enArreglo('civil', $a[SLUG_CIVIL]['claves']);
    enArreglo('aduanas', $a[SLUG_COMERCIO]['claves']);
    foreach ($a as $c) {
        foreach (['ingenieria', 'licenciatura', 'tecnico', 'superior', 'universitario', 'manejo'] as $generica) {
            afirmar(!in_array($generica, $c['claves'], true), "'$generica' no debe identificar a " . $c['slug']);
        }
    }
});

prueba('detectar: por palabra del nombre oficial', function () {
    igual(SLUG_CIVIL, Carreras::detectar('info de civil'));
    igual(SLUG_COMERCIO, Carreras::detectar('comercio internacional'));
    igual(SLUG_ENERGIA, Carreras::detectar('energía solar'));
    igual(SLUG_RECURSOS, Carreras::detectar('fauna silvestre'));
});

prueba('detectar: por alias coloquial (data/lexico.php)', function () {
    igual(SLUG_ITI, Carreras::detectar('la de sistemas'));
    igual(SLUG_ITI, Carreras::detectar('la de compu'), 'compu → computacion → tecnologias');
    igual(SLUG_ITI, Carreras::detectar('programacion'));
    igual(SLUG_COMERCIO, Carreras::detectar('logistica'));
    igual(SLUG_ENERGIA, Carreras::detectar('energias renovables'));
    igual(SLUG_RECURSOS, Carreras::detectar('forestal'));
    igual(SLUG_CIVIL, Carreras::detectar('construccion'));
});

prueba('detectar: sin carrera o solo palabras genéricas → null', function () {
    igual(null, Carreras::detectar('cuanto cuesta la inscripcion'));
    igual(null, Carreras::detectar('ingenieria'));
    igual(null, Carreras::detectar('licenciatura'));
    igual(null, Carreras::detectar(''));
});

prueba('detectar: "información" (incluida la abreviatura "info") no debe señalar a Tecnologías de la Información', function () {
        igual(null, Carreras::detectar('quiero informacion sobre becas'));
        igual(null, Carreras::detectar('info de becas'));
    }
);

prueba('noOfertada: carreras que no se imparten', function () {
    igual('Enfermería', Carreras::noOfertada('¿tienen enfermería?'));
    igual('Enfermería', Carreras::noOfertada('enfermeria'));
    igual('Derecho', Carreras::noOfertada('quiero estudiar derecho'));
    igual('Psicología', Carreras::noOfertada('psicologia'));
    igual('Mecatrónica', Carreras::noOfertada('hay mecatronica?'));
});

prueba('noOfertada: "servicio de enfermería" NO es una carrera', function () {
    igual(null, Carreras::noOfertada('hay servicio de enfermeria'));
    igual(null, Carreras::noOfertada('¿tienen servicio médico?'));
    igual(null, Carreras::noOfertada('la enfermeria de la escuela abre los sabados'), 'mención sin intención de estudiar');
});

prueba('noOfertada: preguntas sin carrera no ofertada → null', function () {
    igual(null, Carreras::noOfertada('cuanto cuesta la inscripcion'));
    igual(null, Carreras::noOfertada('ingenieria civil'));
});

prueba('noOfertada: "consultorio de psicología" es un servicio, no una carrera', function () {
        igual(null, Carreras::noOfertada('consultorio de psicologia'));
    }
);

prueba('porSlug', function () {
    igual('Ingeniería Civil', Carreras::porSlug(SLUG_CIVIL)['nombre']);
    igual(null, Carreras::porSlug('no-existe'));
    igual(null, Carreras::porSlug(null));
});
