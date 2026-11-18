<?php
declare(strict_types=1);

namespace Buscador\Support;

/**
 * Límite de solicitudes por ventana fija, guardado en bf_limite.
 * `ventana` es el timestamp Unix de INICIO de la ventana (múltiplo de su duración), así conviven
 * ventanas de distinta duración (60 s del buscador, 900 s del login) y la limpieza es uniforme.
 */
final class RateLimiter
{
    /** Registra un intento y devuelve true si sigue dentro del límite. */
    public static function permitir(string $ambito, string $huella, int $maximo, int $segundos): bool
    {
        $clave = hash('sha256', $ambito . '|' . $huella);
        $ventana = intdiv(time(), $segundos) * $segundos;
        Db::ejecutar(
            'INSERT INTO bf_limite (clave, ventana, conteo) VALUES (?, ?, 1)
             ON DUPLICATE KEY UPDATE conteo = conteo + 1',
            [$clave, $ventana]
        );
        $fila = Db::uno('SELECT conteo FROM bf_limite WHERE clave = ? AND ventana = ?', [$clave, $ventana]);
        if (mt_rand(1, 50) === 1) {
            self::limpiar();
        }
        return $fila !== null && (int) $fila['conteo'] <= $maximo;
    }

    public static function reiniciar(string $ambito, string $huella, int $segundos): void
    {
        Db::ejecutar(
            'DELETE FROM bf_limite WHERE clave = ? AND ventana = ?',
            [hash('sha256', $ambito . '|' . $huella), intdiv(time(), $segundos) * $segundos]
        );
    }

    /** Borra contadores de ventanas que empezaron hace más de una hora (ninguna dura tanto). */
    public static function limpiar(): int
    {
        return Db::ejecutar('DELETE FROM bf_limite WHERE ventana < ?', [time() - 3600]);
    }
}
