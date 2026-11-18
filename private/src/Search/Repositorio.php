<?php
declare(strict_types=1);

namespace Buscador\Search;

use Buscador\Support\Config;
use Buscador\Support\Db;

/** Acceso de solo lectura a las preguntas frecuentes para el buscador. */
final class Repositorio
{
    /** @return array<int,array<string,mixed>> */
    public static function faqsActivas(): array
    {
        $soloValidadas = (bool) Config::get('busqueda.solo_validadas', false);
        $faqs = Db::todos(
            "SELECT f.id, f.slug, f.categoria, f.pregunta, f.respuesta, f.tipo, f.handler, f.palabras_clave,
                    f.carrera, f.fuente_url, f.estado, f.validado_por,
                    a.nombre AS area_nombre, a.correo AS area_correo, a.telefono AS area_telefono, a.url AS area_url
             FROM bf_faq f LEFT JOIN bf_area a ON a.id = f.area_id
             WHERE f.estado " . ($soloValidadas ? "= 'validada'" : "<> 'archivada'")
        );
        $porId = [];
        foreach ($faqs as $f) {
            $f['variantes'] = [];
            $porId[(int) $f['id']] = $f;
        }
        if ($porId) {
            foreach (Db::todos('SELECT faq_id, texto FROM bf_variante') as $v) {
                $id = (int) $v['faq_id'];
                if (isset($porId[$id])) {
                    $porId[$id]['variantes'][] = $v['texto'];
                }
            }
        }
        return array_values($porId);
    }
}
