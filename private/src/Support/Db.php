<?php
declare(strict_types=1);

namespace Buscador\Support;

use PDO;

/** Conexión PDO única con consultas preparadas. */
final class Db
{
    /** @var PDO|null */
    private static $pdo;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                (string) Config::get('db.host', 'localhost'),
                (int) Config::get('db.puerto', 3306),
                (string) Config::get('db.nombre')
            );
            self::$pdo = new PDO($dsn, (string) Config::get('db.usuario'), (string) Config::get('db.password'), [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
        return self::$pdo;
    }

    /** Para pruebas: inyectar una conexión. */
    public static function usar(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    /**
     * @param array<int|string,mixed> $params
     * @return array<int,array<string,mixed>>
     */
    public static function todos(string $sql, array $params = []): array
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    /**
     * @param array<int|string,mixed> $params
     * @return array<string,mixed>|null
     */
    public static function uno(string $sql, array $params = []): ?array
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        $fila = $st->fetch();
        return $fila === false ? null : $fila;
    }

    /** @param array<int|string,mixed> $params */
    public static function ejecutar(string $sql, array $params = []): int
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        return $st->rowCount();
    }

    public static function ultimoId(): int
    {
        return (int) self::pdo()->lastInsertId();
    }
}
