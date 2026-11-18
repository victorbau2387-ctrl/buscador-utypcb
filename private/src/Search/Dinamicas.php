<?php
declare(strict_types=1);

namespace Buscador\Search;

use Buscador\Site\DatosSitio;

/**
 * Respuestas que se arman en vivo con los datos del sitio (tipo = 'dinamica').
 * Nunca inventan: si el dato no existe en el sitio, lo dicen y dan el enlace oficial.
 */
final class Dinamicas
{
    public const HANDLERS = [
        'carreras_lista'      => 'Lista de carreras activas (carreras_data.php)',
        'carrera_detalle'     => 'Detalle de la carrera mencionada o del contexto',
        'calendario_proximos' => 'Próximos eventos del calendario escolar',
        'calendario_evento'   => 'Próxima fecha de un evento mencionado (vacaciones, evaluaciones…)',
        'noticias_recientes'  => 'Últimas noticias publicadas',
        'deportes_recientes'  => 'Últimos encuentros deportivos',
        'triptico_carrera'    => 'Tríptico (PDF) de la carrera mencionada',
    ];

    /**
     * @param array<string,mixed> $faq
     * @return array{texto:string,enlaces:array<int,array{titulo:string,url:string}>}
     */
    public static function responder(array $faq, string $consulta, ?string $carrera): array
    {
        $h = (string) ($faq['handler'] ?? '');
        $intro = trim((string) ($faq['respuesta'] ?? ''));
        switch ($h) {
            case 'carreras_lista':
                return self::carrerasLista($intro);
            case 'carrera_detalle':
                return self::carreraDetalle($carrera, $intro);
            case 'calendario_proximos':
                return self::calendarioProximos($intro);
            case 'calendario_evento':
                return self::calendarioEvento($consulta, $intro);
            case 'noticias_recientes':
                return self::lista(DatosSitio::noticias(), $intro, 'noticias.php', 'resumen');
            case 'deportes_recientes':
                return self::lista(DatosSitio::deportes(), $intro, 'deportes.php', 'categoria');
            case 'triptico_carrera':
                return self::triptico($carrera, $intro);
        }
        return ['texto' => $intro, 'enlaces' => []];
    }

    private static function fecha(string $ymd): string
    {
        $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        $t = strtotime($ymd);
        if ($t === false) {
            return $ymd;
        }
        return (int) date('j', $t) . ' de ' . $meses[(int) date('n', $t) - 1] . ' de ' . date('Y', $t);
    }

    private static function carrerasLista(string $intro): array
    {
        $lineas = [];
        foreach (Carreras::activas() as $c) {
            $lineas[] = '• ' . $c['nombre'] . (!empty($c['tsu']) ? ' (' . $c['tsu'] . ')' : '');
        }
        if (!$lineas) {
            return ['texto' => 'Consulta la oferta educativa vigente en el sitio oficial.', 'enlaces' => [['titulo' => 'Plan de estudios', 'url' => DatosSitio::url('plan_estudios.php')]]];
        }
        return [
            'texto'   => ($intro !== '' ? $intro : 'Estas son las carreras que ofrece la universidad:') . "\n" . implode("\n", $lineas),
            'enlaces' => [['titulo' => 'Plan de estudios', 'url' => DatosSitio::url('plan_estudios.php')]],
        ];
    }

    private static function carreraDetalle(?string $slug, string $intro): array
    {
        $c = Carreras::porSlug($slug);
        if ($c === null) {
            $r = self::carrerasLista('¿De qué carrera quieres información? Estas son las disponibles:');
            return $r;
        }
        $texto = $c['nombre'];
        if (!empty($c['tsu'])) {
            $texto .= "\nIncluye: " . $c['tsu'] . '.';
        }
        if (!empty($c['nivel'])) {
            $texto .= "\nNivel: " . $c['nivel'] . '.';
        }
        if ($intro !== '') {
            $texto .= "\n" . $intro;
        }
        $enlaces = [['titulo' => 'Plan de estudios', 'url' => DatosSitio::url((string) ($c['url'] ?? 'plan_estudios.php'))]];
        $t = self::tripticoDe($c['slug']);
        if ($t) {
            $enlaces[] = $t;
        }
        return ['texto' => $texto, 'enlaces' => $enlaces];
    }

    private static function calendarioProximos(string $intro): array
    {
        $hoy = date('Y-m-d');
        $prox = array_slice(array_values(array_filter(DatosSitio::eventos(), static function ($e) use ($hoy) {
            return (string) ($e['fecha'] ?? '') >= $hoy;
        })), 0, 5);
        $enlace = [['titulo' => 'Calendario escolar', 'url' => DatosSitio::url('calendario.php')]];
        if (!$prox) {
            return ['texto' => 'No hay próximos eventos publicados en el calendario.', 'enlaces' => $enlace];
        }
        $lineas = array_map(static function ($e) {
            return '• ' . self::fecha((string) $e['fecha']) . ': ' . $e['titulo'];
        }, $prox);
        return ['texto' => ($intro !== '' ? $intro : 'Próximas fechas del calendario escolar:') . "\n" . implode("\n", $lineas), 'enlaces' => $enlace];
    }

    private static function calendarioEvento(string $consulta, string $intro): array
    {
        $q = array_diff(Texto::terminos($consulta), Texto::terminos('calendario fecha cuando'));
        $hoy = date('Y-m-d');
        $enlace = [['titulo' => 'Calendario escolar', 'url' => DatosSitio::url('calendario.php')]];
        $mejor = null;
        $mejorPuntos = 0;
        foreach (DatosSitio::eventos() as $e) {
            if ((string) ($e['fecha'] ?? '') < $hoy) {
                continue;
            }
            $te = Texto::terminos((string) ($e['titulo'] ?? '') . ' ' . ($e['tipo'] ?? ''));
            $p = count(array_intersect($q, $te));
            if ($p > $mejorPuntos) {
                $mejor = $e;
                $mejorPuntos = $p;
            }
        }
        if ($mejor === null) {
            return self::calendarioProximos('No encontré ese evento en el calendario publicado. Estas son las próximas fechas:');
        }
        return ['texto' => trim($intro . "\n" . $mejor['titulo'] . ': ' . self::fecha((string) $mejor['fecha']) . '.'), 'enlaces' => $enlace];
    }

    /** @param array<int,array<string,mixed>> $items */
    private static function lista(array $items, string $intro, string $pagina, string $campoExtra): array
    {
        $enlaces = [['titulo' => 'Ver todo', 'url' => DatosSitio::url($pagina)]];
        if (!$items) {
            return ['texto' => 'Por ahora no hay publicaciones.', 'enlaces' => $enlaces];
        }
        $lineas = array_map(static function ($n) use ($campoExtra) {
            $extra = !empty($n[$campoExtra]) ? ' — ' . $n[$campoExtra] : '';
            return '• ' . self::fecha((string) ($n['fecha'] ?? '')) . ': ' . ($n['titulo'] ?? '') . $extra;
        }, array_slice($items, 0, 3));
        return ['texto' => ($intro !== '' ? $intro : 'Lo más reciente:') . "\n" . implode("\n", $lineas), 'enlaces' => $enlaces];
    }

    /** @return array{titulo:string,url:string}|null */
    private static function tripticoDe(string $slug): ?array
    {
        $c = Carreras::porSlug($slug);
        foreach (DatosSitio::tripticos() as $t) {
            $coincide = (isset($t['carrera']) && ($t['carrera'] === $slug || strpos($slug, (string) $t['carrera']) !== false))
                || ($c !== null && isset($t['titulo']) && strpos(Texto::normalizar($c['nombre']), Texto::normalizar((string) $t['titulo'])) !== false);
            if ($coincide && !empty($t['archivo'])) {
                return ['titulo' => 'Tríptico: ' . ($t['titulo'] ?? 'PDF'), 'url' => DatosSitio::url((string) $t['archivo'])];
            }
        }
        return null;
    }

    private static function triptico(?string $slug, string $intro): array
    {
        if ($slug !== null && ($t = self::tripticoDe($slug))) {
            return ['texto' => $intro !== '' ? $intro : 'Aquí está el tríptico de la carrera:', 'enlaces' => [$t]];
        }
        $enlaces = [];
        foreach (DatosSitio::tripticos() as $t) {
            if (!empty($t['archivo'])) {
                $enlaces[] = ['titulo' => (string) ($t['titulo'] ?? 'Tríptico'), 'url' => DatosSitio::url((string) $t['archivo'])];
            }
        }
        return ['texto' => 'Estos son los trípticos disponibles:', 'enlaces' => $enlaces];
    }
}
