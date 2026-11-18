<?php
declare(strict_types=1);

namespace Buscador\Search;

use Buscador\Site\DatosSitio;
use Buscador\Support\Config;

/**
 * Orquesta una búsqueda: normaliza, corrige, detecta carrera, califica y decide.
 * No toca la base de datos (recibe las preguntas ya cargadas), así se puede probar sin MariaDB.
 *
 * Resultados posibles:
 *   respondida    confianza alta: muestra la respuesta + preguntas relacionadas
 *   sugerencias   confianza media: "¿Quisiste decir…?" con hasta 3 preguntas
 *   sin_respuesta no hay coincidencia: lo dice y canaliza al área con su contacto oficial
 *   no_ofertada   preguntan por una carrera que la universidad no ofrece
 */
final class Buscador
{
    /** @var Indice */
    private $indice;

    public function __construct(Indice $indice)
    {
        $this->indice = $indice;
    }

    /**
     * @param array<string,mixed> $contexto ['carrera' => slug|null] de la respuesta anterior
     * @return array<string,mixed>
     */
    public function buscar(string $consulta, array $contexto = []): array
    {
        $inicio = microtime(true);
        $umbralR = (float) Config::get('busqueda.umbral_respuesta', 0.62);
        $umbralS = (float) Config::get('busqueda.umbral_sugerencias', 0.30);
        $maxSug  = (int) Config::get('busqueda.max_sugerencias', 3);

        $carrera = Carreras::detectar($consulta);
        $terminos = $this->indice->corregir(Texto::terminosConForma($consulta));

        // Contexto entre mensajes: "¿y cuánto cuesta?" después de preguntar por una carrera.
        $ctxCarrera = isset($contexto['carrera']) && is_string($contexto['carrera']) && Carreras::porSlug($contexto['carrera'])
            ? $contexto['carrera'] : null;
        $usaContexto = false;
        // Una carrera no ofertada nunca es "seguimiento" de la anterior ("tienen medicina" tras hablar de Civil).
        $noOfertada = $carrera === null ? Carreras::noOfertada($consulta) : null;
        if ($carrera === null && $noOfertada === null && $ctxCarrera !== null && $this->esSeguimiento($consulta, $terminos)) {
            $carrera = $ctxCarrera;
            $usaContexto = true;
        }

        $base = [
            'resultado'   => 'sin_respuesta',
            'respuesta'   => null,
            'sugerencias' => [],
            'canalizar'   => null,
            'contexto'    => ['carrera' => $carrera],
            'confianza'   => 0.0,
            'faq_id'      => null,
            'diagnostico' => ['terminos' => $terminos, 'carrera' => $carrera, 'uso_contexto' => $usaContexto],
        ];

        if ($noOfertada !== null) {
            $lista = array_map(static function ($c) {
                return '• ' . $c['nombre'];
            }, array_values(Carreras::activas()));
            $base['resultado'] = 'no_ofertada';
            $base['contexto'] = ['carrera' => null];
            $base['confianza'] = 1.0;
            $base['respuesta'] = [
                'id'       => null,
                'pregunta' => '¿Ofrecen ' . $noOfertada . '?',
                'texto'    => 'Por ahora la universidad no ofrece la carrera de ' . $noOfertada . '.'
                    . ($lista ? "\nEstas son las carreras disponibles:\n" . implode("\n", $lista) : ''),
                'fuente'   => DatosSitio::url('plan_estudios.php'),
                'area'     => null,
                'validada' => true,
                'enlaces'  => [],
            ];
            return $this->fin($base, $inicio);
        }

        if (!$terminos) {
            $base['canalizar'] = $this->contactoGeneral();
            return $this->fin($base, $inicio);
        }

        $puntajes = $this->ajustarPorCarrera($this->indice->calificar($terminos), $carrera);
        $ids = array_keys($puntajes);
        $mejorId = $ids[0] ?? null;
        $mejor = $mejorId !== null ? $puntajes[$mejorId] : 0.0;
        $segundo = isset($ids[1]) ? $puntajes[$ids[1]] : 0.0;
        $base['confianza'] = round($mejor, 3);

        if ($mejorId !== null && $mejor >= $umbralR && ($mejor - $segundo >= 0.04 || $mejor >= 0.85)) {
            $base['resultado'] = 'respondida';
            $base['faq_id'] = $mejorId;
            $base['respuesta'] = $this->armarRespuesta($mejorId, $consulta, $carrera);
            foreach (array_slice($ids, 1, 2) as $id) {
                if ($puntajes[$id] >= $umbralS) {
                    $base['sugerencias'][] = $this->sugerencia($id, $puntajes[$id]);
                }
            }
        } elseif ($mejorId !== null && $mejor >= $umbralS) {
            $base['resultado'] = 'sugerencias';
            foreach (array_slice($ids, 0, $maxSug) as $id) {
                if ($puntajes[$id] >= $umbralS * 0.8) {
                    $base['sugerencias'][] = $this->sugerencia($id, $puntajes[$id]);
                }
            }
        } else {
            $base['canalizar'] = $mejorId !== null && $mejor >= 0.15
                ? $this->contactoDe($this->indice->faq($mejorId) ?? [])
                : $this->contactoGeneral();
        }
        return $this->fin($base, $inicio);
    }

    /** Respuesta completa de una pregunta (cuando el usuario elige una sugerencia). */
    public function ver(int $id, array $contexto = []): ?array
    {
        if ($this->indice->faq($id) === null) {
            return null;
        }
        $carrera = isset($contexto['carrera']) && is_string($contexto['carrera']) && Carreras::porSlug($contexto['carrera']) ? $contexto['carrera'] : null;
        $faq = $this->indice->faq($id);
        if (!empty($faq['carrera'])) {
            $carrera = (string) $faq['carrera'];
        }
        return [
            'resultado'   => 'respondida',
            'respuesta'   => $this->armarRespuesta($id, (string) $faq['pregunta'], $carrera),
            'sugerencias' => [],
            'canalizar'   => null,
            'contexto'    => ['carrera' => $carrera],
            'confianza'   => 1.0,
            'faq_id'      => $id,
        ];
    }

    /** @param string[] $terminos */
    private function esSeguimiento(string $consulta, array $terminos): bool
    {
        $n = Texto::normalizar($consulta);
        return count($terminos) <= 3 || strpos($n . ' ', 'y ') === 0 || strpos($n, ' esa ') !== false || strpos($n, ' esta ') !== false;
    }

    /**
     * @param array<int,float> $puntajes
     * @return array<int,float>
     */
    private function ajustarPorCarrera(array $puntajes, ?string $carrera): array
    {
        foreach ($puntajes as $id => $p) {
            $faq = $this->indice->faq($id);
            $fc = $faq['carrera'] ?? null;
            if ($carrera !== null && $fc === $carrera) {
                $puntajes[$id] = min(1.0, $p + 0.15);
            } elseif ($fc !== null && $fc !== '' && $fc !== $carrera) {
                $puntajes[$id] = $p * ($carrera === null ? 0.85 : 0.5);
            } elseif ($carrera !== null && ($faq['handler'] ?? '') === 'carrera_detalle') {
                $puntajes[$id] = min(1.0, $p + 0.10);
            }
        }
        arsort($puntajes);
        return $puntajes;
    }

    /** @return array<string,mixed> */
    private function armarRespuesta(int $id, string $consulta, ?string $carrera): array
    {
        $faq = (array) $this->indice->faq($id);
        $texto = (string) $faq['respuesta'];
        $enlaces = [];
        if (($faq['tipo'] ?? 'fija') === 'dinamica') {
            $d = Dinamicas::responder($faq, $consulta, $carrera);
            $texto = $d['texto'];
            $enlaces = $d['enlaces'];
        }
        return [
            'id'       => $id,
            'pregunta' => (string) $faq['pregunta'],
            'texto'    => $texto,
            'fuente'   => (string) ($faq['fuente_url'] ?? ''),
            'area'     => $faq['area_nombre'] ?? null,
            'validada' => ($faq['estado'] ?? '') === 'validada',
            'enlaces'  => $enlaces,
        ];
    }

    /** @return array<string,mixed> */
    private function sugerencia(int $id, float $conf): array
    {
        $faq = (array) $this->indice->faq($id);
        return ['id' => $id, 'pregunta' => (string) $faq['pregunta'], 'confianza' => round($conf, 3)];
    }

    /** @param array<string,mixed> $faq */
    private function contactoDe(array $faq): array
    {
        if (!empty($faq['area_nombre'])) {
            return [
                'nombre'   => (string) $faq['area_nombre'],
                'correo'   => (string) ($faq['area_correo'] ?? ''),
                'telefono' => (string) ($faq['area_telefono'] ?? ''),
                'url'      => (string) ($faq['area_url'] ?? ''),
            ];
        }
        return $this->contactoGeneral();
    }

    /** @return array<string,string> */
    private function contactoGeneral(): array
    {
        $c = (array) Config::get('contacto_general', []);
        return [
            'nombre'   => (string) ($c['nombre'] ?? 'Servicios Escolares'),
            'correo'   => (string) ($c['correo'] ?? ''),
            'telefono' => (string) ($c['telefono'] ?? ''),
            'url'      => (string) ($c['url'] ?? ''),
        ];
    }

    /** @param array<string,mixed> $r */
    private function fin(array $r, float $inicio): array
    {
        $r['ms'] = (int) round((microtime(true) - $inicio) * 1000);
        return $r;
    }
}
