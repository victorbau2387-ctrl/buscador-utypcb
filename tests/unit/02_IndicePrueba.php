<?php
declare(strict_types=1);

use Buscador\Search\Indice;
use Buscador\Search\Texto;

grupo('Indice');

/** Índice mínimo y controlado para verificar la fórmula de calificación. */
function indiceMinimo(): Indice
{
    return new Indice([
        ['id' => 1, 'pregunta' => 'alfa beta', 'variantes' => [], 'palabras_clave' => '', 'carrera' => null],
        ['id' => 2, 'pregunta' => 'gamma delta', 'variantes' => [], 'palabras_clave' => '', 'carrera' => null],
    ]);
}

prueba('calificar: consulta vacía devuelve []', function () {
    igual([], indicePrueba()->calificar([]));
});

prueba('calificar: términos desconocidos no califican ninguna pregunta', function () {
    igual([], indicePrueba()->calificar(Texto::terminos('xilofono murcielago')));
});

prueba('calificar: fórmula 0.55·cobertura + 0.45·Dice + 0.01·BM25 normalizado', function () {
    // Consulta "alfa" contra "alfa beta": cobertura 1, Dice = 2·1/(1+2) = 2/3 (idf iguales), BM25 normalizado = 1.
    $c = indiceMinimo()->calificar(['alfa']);
    igual([1], array_keys($c));
    entre(0.8599, 0.8601, $c[1], 'confianza');
});

prueba('calificar: coincidencia exacta con la pregunta da confianza máxima', function () {
    $c = indiceMinimo()->calificar(['gama', 'delta']);
    igual(2, array_key_first($c));
    entre(0.999, 1.0, $c[2]);
});

prueba('calificar: resultados ordenados de mayor a menor y en [0, 1]', function () {
    $c = indicePrueba()->calificar(Texto::terminos('fechas del calendario de examenes'));
    afirmar(count($c) >= 2, 'debe calificar varias preguntas');
    $prev = 2.0;
    foreach ($c as $v) {
        entre(0.0, 1.0, $v);
        afirmar($v <= $prev, 'orden descendente');
        $prev = $v;
    }
});

prueba('calificar: la pregunta correcta queda primero', function () {
    $ix = indicePrueba();
    igual(idPorSlug('inscripcion-costo'), array_key_first($ix->calificar(Texto::terminos('cuanto cuesta la inscripcion'))));
    igual(idPorSlug('becas-tipos'), array_key_first($ix->calificar(Texto::terminos('becas'))));
    igual(idPorSlug('calendario-vacaciones'), array_key_first($ix->calificar(Texto::terminos('cuando salimos de vacaciones'))));
});

prueba('calificar: un término desconocido reduce la confianza', function () {
    $ix = indicePrueba();
    $id = idPorSlug('becas-tipos');
    $sin = $ix->calificar(Texto::terminos('becas'))[$id];
    $con = $ix->calificar(Texto::terminos('becas xilofono'))[$id];
    afirmar($con < $sin, "con término desconocido ($con) debe ser menor que sin él ($sin)");
});

prueba('calificar: términos repetidos no inflan la confianza', function () {
    $ix = indicePrueba();
    igual($ix->calificar(['beka']), $ix->calificar(['beka', 'beka', 'beka']));
});

prueba('conoce() y faq()', function () {
    $ix = indicePrueba();
    afirmar($ix->conoce('beka'));
    afirmar(!$ix->conoce('xilofo'));
    igual('becas-tipos', $ix->faq(idPorSlug('becas-tipos'))['slug']);
    igual(null, $ix->faq(99999));
    igual(count(faqsPrueba()), count($ix->faqs()));
});

prueba('corregir: faltas de ortografía hacia el vocabulario', function () {
    $ix = indicePrueba();
    igual(Texto::terminos('inscripcion'), $ix->corregir(Texto::terminosConForma('inscrpcion')), 'letra omitida');
    igual(Texto::terminos('triptico'), $ix->corregir(Texto::terminosConForma('tripitco')), 'letras transpuestas (2 ediciones, 8 letras)');
    igual(Texto::terminos('becas'), $ix->corregir(Texto::terminosConForma('vecas')), 'b/v resuelto por plegado');
});

prueba('corregir: no toca términos conocidos, cortos ni números', function () {
    $ix = indicePrueba();
    igual(['beka'], $ix->corregir([['beka', 'beka']]));
    igual(['abc'], $ix->corregir([['abc', 'abc']]), 'menos de 4 letras');
    igual(['2026'], $ix->corregir([['2026', '2026']]));
});

prueba('corregir: exige la misma letra inicial y respeta la distancia máxima', function () {
    $ix = indicePrueba();
    igual(['peka'], $ix->corregir([['peka', 'peka']]), 'p ≠ b: no se corrige a beca');
    igual(['bxyzw'], $ix->corregir([['bxyzw', 'bxyzw']]), 'demasiado lejos de cualquier palabra');
});

prueba('rendimiento: índice de ~150 preguntas y ~750 variantes se construye y consulta en < 300 ms', function () {
    $base = faqsPrueba();
    $faqs = [];
    $id = 0;
    for ($i = 0; $i < 13; $i++) {
        foreach ($base as $f) {
            $f['id'] = ++$id;
            $f['slug'] .= '-' . $i;
            $f['pregunta'] .= ' ' . $i;
            $f['variantes'] = array_merge($f['variantes'], $f['variantes']);
            $faqs[] = $f;
        }
    }
    $t = microtime(true);
    $ix = new Indice($faqs);
    $ix->calificar($ix->corregir(Texto::terminosConForma('cuanto cuesta la inscripsion en la carrera')));
    $ms = (microtime(true) - $t) * 1000;
    afirmar(count($faqs) >= 150, 'tamaño del índice');
    afirmar($ms < 300, sprintf('tardó %.1f ms', $ms));
});
