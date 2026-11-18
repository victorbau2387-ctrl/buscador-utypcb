<?php
declare(strict_types=1);

use Buscador\Search\Csv;

grupo('Csv');

const ENCABEZADO_FAQ = "slug,categoria,pregunta,variantes,respuesta,tipo,handler,palabras_clave,carrera,fuente_url,area,estado,validado_por,validado_en\n";

prueba('leer: quita el BOM de Excel del primer encabezado', function () {
    $f = archivoTemporal("\xEF\xBB\xBFclave,nombre\nx,Equis\n");
    $filas = Csv::leer($f);
    igual([['clave' => 'x', 'nombre' => 'Equis']], $filas);
});

prueba('leer: comillas, comas y saltos de línea dentro de un campo', function () {
    $f = archivoTemporal("a,b\n\"uno, dos\",\"línea 1\nlínea 2\"\n\"dice \"\"hola\"\"\",z\n");
    $filas = Csv::leer($f);
    igual(2, count($filas));
    igual('uno, dos', $filas[0]['a']);
    igual("línea 1\nlínea 2", $filas[0]['b']);
    igual('dice "hola"', $filas[1]['a']);
});

prueba('leer: finales de línea de Windows (CRLF), espacios y filas vacías', function () {
    $f = archivoTemporal("a,b\r\n  x  , y \r\n\r\n,\r\nz,w\r\n");
    igual([['a' => 'x', 'b' => 'y'], ['a' => 'z', 'b' => 'w']], Csv::leer($f));
});

prueba('leer: columnas faltantes quedan vacías', function () {
    $f = archivoTemporal("a,b,c\n1\n");
    igual([['a' => '1', 'b' => '', 'c' => '']], Csv::leer($f));
});

prueba('leer: archivo vacío y archivo inexistente', function () {
    igual([], Csv::leer(archivoTemporal('')));
    lanza(\Throwable::class, function () {
        @Csv::leer(sys_get_temp_dir() . '/no-existe-' . mt_rand() . '.csv');
    });
});

prueba('leer: una barra invertida al final de un campo entre comillas no debe unir filas', function () {
        $f = archivoTemporal("a,b\n\"C:\\carpeta\\\",x\ny,z\n");
        $filas = Csv::leer($f);
        igual(2, count($filas));
        igual('C:\\carpeta\\', $filas[0]['a']);
    }
);

prueba('faqsEnMemoria: variantes separadas por "|" (recortadas, sin vacías)', function () {
    $f = archivoTemporal(ENCABEZADO_FAQ . "uno,cat,¿Pregunta?, a | b ||  c |,Resp,,,clave,,https://x.mx/,,,,\n");
    $faqs = Csv::faqsEnMemoria($f);
    igual(['a', 'b', 'c'], $faqs[0]['variantes']);
});

prueba('faqsEnMemoria: valores por defecto, ids consecutivos y archivadas excluidas', function () {
    $f = archivoTemporal(ENCABEZADO_FAQ
        . "uno,cat,P1,,R1,,,,,https://x.mx/,,,,\n"
        . "dos,cat,P2,,R2,,,,,https://x.mx/,,archivada,,\n"
        . "tres,cat,P3,,R3,dinamica,noticias_recientes,,ingenieria-civil,https://x.mx/,,validada,Área,2026-01-01\n");
    $faqs = Csv::faqsEnMemoria($f);
    igual(['uno', 'tres'], array_column($faqs, 'slug'));
    igual([1, 2], array_column($faqs, 'id'));
    igual('fija', $faqs[0]['tipo']);
    igual('borrador', $faqs[0]['estado']);
    igual(null, $faqs[0]['handler']);
    igual(null, $faqs[0]['carrera']);
    igual('dinamica', $faqs[1]['tipo']);
    igual('noticias_recientes', $faqs[1]['handler']);
    igual('ingenieria-civil', $faqs[1]['carrera']);
    igual('validada', $faqs[1]['estado']);
});

prueba('faqsEnMemoria: une el área por clave (con BOM en areas.csv)', function () {
    $areas = archivoTemporal("\xEF\xBB\xBFclave,nombre,correo,telefono,url\nesc,Servicios Escolares,esc@ejemplo.invalid,000,https://x.mx/\n");
    $f = archivoTemporal(ENCABEZADO_FAQ . "uno,cat,P1,,R1,,,,,https://x.mx/,esc,,,\ndos,cat,P2,,R2,,,,,https://x.mx/,otra,,,\n");
    $faqs = Csv::faqsEnMemoria($f, $areas);
    igual('Servicios Escolares', $faqs[0]['area_nombre']);
    igual('esc@ejemplo.invalid', $faqs[0]['area_correo']);
    igual(null, $faqs[1]['area_nombre'], 'área inexistente');
});

prueba('faqsEnMemoria: el fixture de pruebas tiene las columnas esperadas', function () {
    $fh = fopen(__DIR__ . '/../fixtures/conocimiento/preguntas.csv', 'rb');
    $enc = fgetcsv($fh, 0, ',', '"', '\\');
    fclose($fh);
    igual(Csv::COLUMNAS_FAQ, $enc);
});
