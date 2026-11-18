<?php
declare(strict_types=1);

namespace Buscador\Admin;

use Buscador\Support\Db;

/** Reglas y escritura de áreas (bf_area), compartidas por el panel y el importador. */
final class Areas
{
    /** @param array<string,string> $a @return array<string,string> */
    public static function validar(array $a): array
    {
        $err = [];
        if (!preg_match('/^[a-z0-9_-]{1,40}$/', $a['clave'])) {
            $err['clave'] = 'Clave obligatoria: minúsculas, números, guion o guion bajo (máx. 40).';
        }
        if ($a['nombre'] === '' || mb_strlen($a['nombre']) > 120) {
            $err['nombre'] = 'Nombre obligatorio (máx. 120 caracteres).';
        }
        if ($a['correo'] !== '' && (filter_var($a['correo'], FILTER_VALIDATE_EMAIL) === false || strlen($a['correo']) > 120)) {
            $err['correo'] = 'Correo inválido.';
        }
        if (mb_strlen($a['telefono']) > 40) {
            $err['telefono'] = 'Máximo 40 caracteres.';
        }
        if ($a['url'] !== '' && (strncmp($a['url'], 'https://', 8) !== 0 || filter_var($a['url'], FILTER_VALIDATE_URL) === false || strlen($a['url']) > 255)) {
            $err['url'] = 'Debe ser una URL https:// válida.';
        }
        return $err;
    }

    /**
     * Inserta o actualiza por id; con $id = 0 hace upsert por clave (útil para el importador).
     * @param array<string,string> $a
     */
    public static function escribir(array $a, int $id = 0): void
    {
        $vals = [$a['clave'], $a['nombre'], self::nulo($a['correo']), self::nulo($a['telefono']), self::nulo($a['url'])];
        if ($id > 0) {
            $vals[] = $id;
            Db::ejecutar('UPDATE bf_area SET clave = ?, nombre = ?, correo = ?, telefono = ?, url = ? WHERE id = ?', $vals);
            return;
        }
        Db::ejecutar(
            'INSERT INTO bf_area (clave, nombre, correo, telefono, url) VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), correo = VALUES(correo), telefono = VALUES(telefono), url = VALUES(url)',
            $vals
        );
    }

    /** @return array<string,int> clave => id */
    public static function idsPorClave(): array
    {
        $res = [];
        foreach (Db::todos('SELECT id, clave FROM bf_area') as $f) {
            $res[(string) $f['clave']] = (int) $f['id'];
        }
        return $res;
    }

    private static function nulo(string $v): ?string
    {
        return $v === '' ? null : $v;
    }
}
