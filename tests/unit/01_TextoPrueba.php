<?php
declare(strict_types=1);

use Buscador\Search\Texto;

grupo('Texto');

prueba('normalizar: minúsculas, sin acentos ni signos', function () {
    igual('cuanto cuesta la inscripcion', Texto::normalizar('¿Cuánto CUESTA la Inscripción?!'));
    igual('nandu pinguino', Texto::normalizar('Ñandú  pingüino'));
    igual('a e i o u', Texto::normalizar('Á É Í Ó Ú'));
});

prueba('normalizar: espacios múltiples, emojis y saltos de línea', function () {
    igual('hola que tal', Texto::normalizar("  hola\n\tque   tal 😀 "));
    igual('', Texto::normalizar('¿¡?!...'));
    igual('', Texto::normalizar(''));
});

prueba('normalizar: reduce letras repetidas de chat (3 o más)', function () {
    igual('hola', Texto::normalizar('holaaaa'));
    igual('carrera', Texto::normalizar('carrera'), 'rr doble se conserva');
    igual('si', Texto::normalizar('siiiii'));
});

prueba('normalizar: conserva números', function () {
    igual('inscripcion 2026 2027', Texto::normalizar('Inscripción 2026-2027'));
});

prueba('palabras: expande abreviaturas de chat', function () {
    igual(['quiero', 'informacion', 'de', 'la', 'universidad'], Texto::palabras('Kiero info d la uni'));
    igual(['que', 'carreras', 'tienen'], Texto::palabras('q carreras tienen'));
    igual(['ingenieria', 'civil'], Texto::palabras('ing civil'));
});

prueba('palabras: reemplaza frases coloquiales', function () {
    igual(['costo', 'la', 'inscripcion'], Texto::palabras('cuánto cuesta la inscripción'));
    igual(['serviciosocial'], Texto::palabras('servicio social'));
    igual(['planestudios'], Texto::palabras('mapa curricular'));
    igual(['horario', 'abren'], Texto::palabras('a qué hora abren'));
});

prueba('palabras: texto vacío devuelve arreglo vacío', function () {
    igual([], Texto::palabras(''));
    igual([], Texto::palabras('?¿!'));
});

prueba('terminos: quita palabras vacías', function () {
    igual([], Texto::terminos('hola buenas tardes, quiero saber'));
    igual([], Texto::terminos('Kiero info d la uni'), 'todo es vacío');
});

prueba('terminos: aplica sinónimos (precio/colegiatura/cuota → costo)', function () {
    $costo = Texto::terminos('costo');
    igual($costo, Texto::terminos('precio'));
    igual($costo, Texto::terminos('colegiatura'));
    igual($costo, Texto::terminos('cuotas'));
    igual(Texto::terminos('requisitos'), Texto::terminos('papeles'));
    igual(Texto::terminos('folleto'), Texto::terminos('triptico'));
});

prueba('terminos: "cuánto cuesta" y "precio" llegan al mismo término', function () {
    igual(Texto::terminos('precio de la inscripcion'), Texto::terminos('¿Cuánto cuesta la inscripción?'));
});

prueba('terminos: tolera faltas comunes (b/v, s/c/z, qu/k, j/g, h muda, y/ll)', function () {
    igual(Texto::terminos('vacaciones'), Texto::terminos('bacaciones'));
    igual(Texto::terminos('inscripcion'), Texto::terminos('inscripsion'));
    igual(Texto::terminos('becas'), Texto::terminos('vecas'));
    igual(Texto::terminos('requisitos'), Texto::terminos('rekisitos'));
    igual(Texto::terminos('horario'), Texto::terminos('orario'));
    igual(Texto::terminos('colegio'), Texto::terminos('colejio'));
    igual(Texto::terminos('llave'), Texto::terminos('yave'));
    igual(Texto::terminos('cerveza'), Texto::terminos('servesa'));
});

prueba('terminos: los acentos no cambian el resultado', function () {
    igual(Texto::terminos('inscripcion titulacion'), Texto::terminos('Inscripción Titulación'));
});

prueba('terminos: "kiero" (chat) se trata como palabra vacía igual que "quiero"', function () {
    igual([], Texto::terminos('kiero'));
    igual([], Texto::terminos('quiero'));
});

prueba('fonetica: reglas de plegado', function () {
    igual('bakasiones', Texto::fonetica('vacaciones'));
    igual('kiero', Texto::fonetica('quiero'));
    igual('ola', Texto::fonetica('hola'));
    igual('chokolate', Texto::fonetica('chocolate'), 'ch se conserva');
    igual('yabe', Texto::fonetica('llave'));
    igual('jeminis', Texto::fonetica('geminis'));
    igual('sapato', Texto::fonetica('zapato'));
    igual('2026', Texto::fonetica('2026'), 'los números no se tocan');
    igual('kareras', Texto::fonetica('carreras'), 'letras dobles se reducen');
});

prueba('raiz: plurales y truncado a 6 letras', function () {
    igual('kosto', Texto::raiz('kostos'));
    igual('mes', Texto::raiz('meses'));
    igual('papel', Texto::raiz('papeles'));
    igual('karera', Texto::raiz('kareras'));
    igual('inskri', Texto::raiz('inskripsion'));
    igual('bus', Texto::raiz('bus'), 'palabras cortas no se recortan');
    igual('beka', Texto::raiz('bekas'));
});

prueba('raiz: singular y plural coinciden en terminos()', function () {
    igual(Texto::terminos('beca'), Texto::terminos('becas'));
    igual(Texto::terminos('carrera'), Texto::terminos('carreras'));
    igual(Texto::terminos('requisito'), Texto::terminos('requisitos'));
});

prueba('terminosConForma: conserva la palabra plegada completa para corregir', function () {
    igual([['bakasiones', 'bakasi']], Texto::terminosConForma('vacaciones'));
});

prueba('slug: genera slugs estables', function () {
    igual('ingenieria-en-energia-y-desarrollo-sostenible', Texto::slug('Ingeniería en Energía y Desarrollo Sostenible'));
    igual('ingenieria-civil', Texto::slug('  Ingeniería Civil! '));
});
