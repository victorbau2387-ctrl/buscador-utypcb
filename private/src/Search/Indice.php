<?php
declare(strict_types=1);

namespace Buscador\Search;

/**
 * Índice en memoria sobre las preguntas frecuentes.
 * Con ~150 preguntas y ~750 variantes se construye en pocos milisegundos,
 * así que no requiere procesos en segundo plano (el hosting no los permite).
 */
final class Indice
{
    private const PESO_PREGUNTA = 2.0;
    private const PESO_VARIANTE = 1.0;
    private const PESO_CLAVE    = 1.5;
    private const K1 = 1.2;
    private const B  = 0.75;

    /** @var array<int,array<string,mixed>> faq por id */
    private $faqs = [];
    /** @var array<int,array<string,float>> frecuencia ponderada de términos por faq */
    private $tf = [];
    /** @var array<int,float> */
    private $longitud = [];
    /** @var array<int,array<int,array<string,bool>>> frases (pregunta y variantes) como conjuntos de términos */
    private $frases = [];
    /** @var array<string,float> */
    private $idf = [];
    /** @var float */
    private $longitudMedia = 1.0;
    /** @var array<string,string> palabra plegada completa => raíz (para corregir faltas) */
    private $formas = [];

    /**
     * @param array<int,array<string,mixed>> $faqs filas con id, pregunta, palabras_clave, variantes (string[]), carrera, ...
     */
    public function __construct(array $faqs)
    {
        $df = [];
        foreach ($faqs as $faq) {
            $id = (int) $faq['id'];
            $this->faqs[$id] = $faq;
            $tf = [];
            $frases = [];

            $this->registrarFormas((string) $faq['pregunta'] . ' ' . implode(' ', (array) ($faq['variantes'] ?? [])) . ' ' . (string) ($faq['palabras_clave'] ?? ''));
            $tp = Texto::terminos((string) $faq['pregunta']);
            $this->sumar($tf, $tp, self::PESO_PREGUNTA);
            $frases[] = array_fill_keys($tp, true);

            foreach ((array) ($faq['variantes'] ?? []) as $v) {
                $tv = Texto::terminos((string) $v);
                $this->sumar($tf, $tv, self::PESO_VARIANTE);
                if ($tv) {
                    $frases[] = array_fill_keys($tv, true);
                }
            }
            $tk = Texto::terminos(str_replace(',', ' ', (string) ($faq['palabras_clave'] ?? '')));
            $this->sumar($tf, $tk, self::PESO_CLAVE);
            if ($tk) {
                $frases[] = array_fill_keys(array_merge($tp, $tk), true);
            }

            $this->tf[$id] = $tf;
            $this->longitud[$id] = array_sum($tf);
            $this->frases[$id] = $frases;
            foreach (array_keys($tf) as $t) {
                $df[$t] = ($df[$t] ?? 0) + 1;
            }
        }
        $n = max(1, count($this->faqs));
        foreach ($df as $t => $d) {
            $this->idf[$t] = log(1 + ($n - $d + 0.5) / ($d + 0.5));
        }
        $this->longitudMedia = $this->longitud ? array_sum($this->longitud) / count($this->longitud) : 1.0;
    }

    /**
     * @param array<string,float> $tf
     * @param string[] $terminos
     */
    private function sumar(array &$tf, array $terminos, float $peso): void
    {
        foreach ($terminos as $t) {
            $tf[$t] = ($tf[$t] ?? 0.0) + $peso;
        }
    }

    /** @return array<string,mixed>|null */
    public function faq(int $id): ?array
    {
        return $this->faqs[$id] ?? null;
    }

    /** @return array<int,array<string,mixed>> */
    public function faqs(): array
    {
        return $this->faqs;
    }

    public function conoce(string $termino): bool
    {
        return isset($this->idf[$termino]);
    }

    private function registrarFormas(string $texto): void
    {
        foreach (Texto::terminosConForma(str_replace(',', ' ', $texto)) as $par) {
            $this->formas[$par[0]] = $par[1];
        }
    }

    /**
     * Corrige términos desconocidos con la palabra más parecida del vocabulario (distancia de edición
     * sobre la palabra plegada completa: 1 edición hasta 6 letras, 2 a partir de 7; misma letra inicial).
     * @param array<int,array{0:string,1:string}> $pares salida de Texto::terminosConForma()
     * @return string[] raíces
     */
    public function corregir(array $pares): array
    {
        $res = [];
        foreach ($pares as $par) {
            list($forma, $t) = $par;
            if (isset($this->idf[$t]) || strlen($forma) < 4 || ctype_digit($forma)) {
                $res[] = $t;
                continue;
            }
            $max = strlen($forma) >= 7 ? 2 : 1;
            $mejor = null;
            $mejorDist = $max + 1;
            foreach ($this->formas as $f => $raiz) {
                $f = (string) $f; // claves numéricas ("2026") llegan como int
                if ($f[0] !== $forma[0] || abs(strlen($f) - strlen($forma)) > $max || !isset($this->idf[$raiz])) {
                    continue;
                }
                $d = levenshtein($forma, $f);
                if ($d < $mejorDist) {
                    $mejor = $raiz;
                    $mejorDist = $d;
                }
            }
            $res[] = $mejor !== null ? $mejor : $t;
        }
        return $res;
    }

    private function idf(string $t): float
    {
        if (isset($this->idf[$t])) {
            return $this->idf[$t];
        }
        // Término desconocido: penaliza la cobertura, pero menos que uno muy específico.
        return $this->idf ? 0.5 * array_sum($this->idf) / count($this->idf) : 1.0;
    }

    /**
     * Califica todas las preguntas contra la consulta.
     * confianza (0..1) = 0.55·cobertura + 0.45·mejor frase
     *   cobertura   = peso idf de los términos de la consulta presentes en la faq / peso total de la consulta
     *   mejor frase = coeficiente de Dice ponderado por idf contra la variante más parecida
     * @param string[] $terminos
     * @return array<int,float> id => confianza, ordenado de mayor a menor
     */
    public function calificar(array $terminos): array
    {
        $q = array_values(array_unique($terminos));
        if (!$q) {
            return [];
        }
        $pesoQ = 0.0;
        foreach ($q as $t) {
            $pesoQ += $this->idf($t);
        }
        $bm25 = [];
        $conf = [];
        foreach ($this->tf as $id => $tf) {
            $cubierto = 0.0;
            $s = 0.0;
            foreach ($q as $t) {
                if (!isset($tf[$t])) {
                    continue;
                }
                $idf = $this->idf[$t];
                $cubierto += $idf;
                $f = $tf[$t];
                $s += $idf * ($f * (self::K1 + 1)) / ($f + self::K1 * (1 - self::B + self::B * $this->longitud[$id] / $this->longitudMedia));
            }
            if ($cubierto <= 0) {
                continue;
            }
            $mejorFrase = 0.0;
            foreach ($this->frases[$id] as $frase) {
                $comun = 0.0;
                $pesoF = 0.0;
                foreach ($frase as $t => $_) {
                    $w = $this->idf[$t] ?? 0.0;
                    $pesoF += $w;
                    if (in_array($t, $q, true)) {
                        $comun += $w;
                    }
                }
                $dice = ($pesoQ + $pesoF) > 0 ? 2 * $comun / ($pesoQ + $pesoF) : 0.0;
                if ($dice > $mejorFrase) {
                    $mejorFrase = $dice;
                }
            }
            $conf[$id] = 0.55 * ($cubierto / $pesoQ) + 0.45 * $mejorFrase;
            $bm25[$id] = $s;
        }
        // Desempate por BM25 ANTES de recortar a 1.0 (si no, dos filas en 1.0 empatan al azar).
        $maxBm = $bm25 ? max($bm25) : 1.0;
        $orden = [];
        foreach ($conf as $id => $c) {
            $orden[$id] = $c + 0.01 * ($bm25[$id] / ($maxBm ?: 1.0));
        }
        arsort($orden);
        $res = [];
        foreach ($orden as $id => $c) {
            $res[$id] = min(1.0, $c);
        }
        return $res;
    }
}
