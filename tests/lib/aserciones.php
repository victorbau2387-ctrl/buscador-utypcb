<?php
// Mini marco de pruebas sin dependencias (el hosting no tiene Composer ni PHPUnit).
// Compatible con PHP 7.4 y 8.x.
declare(strict_types=1);

final class FalloAsercion extends \Exception
{
}

final class Pruebas
{
    /** @var array<int,array{nombre:string,grupo:string,fn:callable,conocido:?string}> */
    public static $casos = [];
    /** @var string */
    public static $grupo = '';
    /** @var int */
    public static $aserciones = 0;
}

/** Agrupa las pruebas que se registren a continuación (normalmente, una por archivo). */
function grupo(string $nombre): void
{
    Pruebas::$grupo = $nombre;
}

/** Registra una prueba normal. */
function prueba(string $nombre, callable $fn): void
{
    Pruebas::$casos[] = ['nombre' => $nombre, 'grupo' => Pruebas::$grupo, 'fn' => $fn, 'conocido' => null];
}

/**
 * Registra una prueba que documenta un defecto CONOCIDO del motor (todavía sin corregir).
 * Si falla, se informa como "conocido" y no rompe la suite. Si pasa, se avisa para quitar la marca.
 */
function conocido(string $nombre, string $defecto, callable $fn): void
{
    Pruebas::$casos[] = ['nombre' => $nombre, 'grupo' => Pruebas::$grupo, 'fn' => $fn, 'conocido' => $defecto];
}

function exportar($v): string
{
    if (is_string($v)) {
        return '"' . (mb_strlen($v) > 160 ? mb_substr($v, 0, 160) . '…' : $v) . '"';
    }
    $s = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($s === false) {
        $s = var_export($v, true);
    }
    return mb_strlen($s) > 300 ? mb_substr($s, 0, 300) . '…' : $s;
}

function afirmar(bool $condicion, string $mensaje = 'la condición no se cumple'): void
{
    Pruebas::$aserciones++;
    if (!$condicion) {
        throw new FalloAsercion($mensaje);
    }
}

/** Igualdad estricta (===). */
function igual($esperado, $obtenido, string $mensaje = ''): void
{
    Pruebas::$aserciones++;
    if ($esperado !== $obtenido) {
        throw new FalloAsercion(($mensaje !== '' ? $mensaje . ': ' : '') . 'se esperaba ' . exportar($esperado) . ', se obtuvo ' . exportar($obtenido));
    }
}

function distinto($noEsperado, $obtenido, string $mensaje = ''): void
{
    Pruebas::$aserciones++;
    if ($noEsperado === $obtenido) {
        throw new FalloAsercion(($mensaje !== '' ? $mensaje . ': ' : '') . 'no debía ser ' . exportar($obtenido));
    }
}

function contiene(string $aguja, string $pajar, string $mensaje = ''): void
{
    Pruebas::$aserciones++;
    if (strpos($pajar, $aguja) === false) {
        throw new FalloAsercion(($mensaje !== '' ? $mensaje . ': ' : '') . exportar($aguja) . ' no aparece en ' . exportar($pajar));
    }
}

function noContiene(string $aguja, string $pajar, string $mensaje = ''): void
{
    Pruebas::$aserciones++;
    if (strpos($pajar, $aguja) !== false) {
        throw new FalloAsercion(($mensaje !== '' ? $mensaje . ': ' : '') . exportar($aguja) . ' no debía aparecer en ' . exportar($pajar));
    }
}

function coincide(string $regex, string $texto, string $mensaje = ''): void
{
    Pruebas::$aserciones++;
    if (!preg_match($regex, $texto)) {
        throw new FalloAsercion(($mensaje !== '' ? $mensaje . ': ' : '') . exportar($texto) . ' no coincide con ' . $regex);
    }
}

/** @param array<mixed> $arreglo */
function enArreglo($valor, array $arreglo, string $mensaje = ''): void
{
    Pruebas::$aserciones++;
    if (!in_array($valor, $arreglo, true)) {
        throw new FalloAsercion(($mensaje !== '' ? $mensaje . ': ' : '') . exportar($valor) . ' no está en ' . exportar($arreglo));
    }
}

function entre(float $min, float $max, float $valor, string $mensaje = ''): void
{
    Pruebas::$aserciones++;
    if ($valor < $min || $valor > $max) {
        throw new FalloAsercion(($mensaje !== '' ? $mensaje . ': ' : '') . $valor . ' fuera de [' . $min . ', ' . $max . ']');
    }
}

function lanza(string $claseExcepcion, callable $fn, string $mensaje = ''): void
{
    Pruebas::$aserciones++;
    try {
        $fn();
    } catch (\Throwable $e) {
        if ($e instanceof $claseExcepcion) {
            return;
        }
        throw new FalloAsercion(($mensaje !== '' ? $mensaje . ': ' : '') . 'se lanzó ' . get_class($e) . ' en lugar de ' . $claseExcepcion);
    }
    throw new FalloAsercion(($mensaje !== '' ? $mensaje . ': ' : '') . 'no se lanzó ' . $claseExcepcion);
}

/**
 * Ejecuta las pruebas registradas y devuelve el código de salida (0 = todo bien, 1 = hay fallos).
 * @param string $filtro solo ejecuta pruebas cuyo grupo o nombre contenga el texto (sin distinguir mayúsculas)
 */
function ejecutarPruebas(string $filtro = ''): int
{
    $ok = $fallos = $conocidos = $corregidos = 0;
    $detalles = [];
    $grupoActual = null;
    $inicio = microtime(true);
    foreach (Pruebas::$casos as $c) {
        if ($filtro !== '' && stripos($c['grupo'] . ' ' . $c['nombre'], $filtro) === false) {
            continue;
        }
        if ($c['grupo'] !== $grupoActual) {
            $grupoActual = $c['grupo'];
            echo "\n" . $grupoActual . "\n";
        }
        $error = null;
        try {
            ($c['fn'])();
        } catch (FalloAsercion $e) {
            $error = $e->getMessage();
        } catch (\Throwable $e) {
            $error = get_class($e) . ': ' . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')';
        }
        if ($c['conocido'] !== null) {
            if ($error !== null) {
                $conocidos++;
                echo '  [conocido] ' . $c['nombre'] . "\n              defecto: " . $c['conocido'] . "\n              detalle: " . $error . "\n";
            } else {
                $corregidos++;
                echo '  [ok*]      ' . $c['nombre'] . "  (estaba marcada como defecto conocido y ya pasa: quita la marca)\n";
            }
            continue;
        }
        if ($error === null) {
            $ok++;
            echo '  [ok]       ' . $c['nombre'] . "\n";
        } else {
            $fallos++;
            echo '  [FALLA]    ' . $c['nombre'] . "\n              " . $error . "\n";
            $detalles[] = $c['grupo'] . ' > ' . $c['nombre'] . ': ' . $error;
        }
    }
    $ms = (int) round((microtime(true) - $inicio) * 1000);
    echo "\n" . str_repeat('-', 70) . "\n";
    printf(
        "PHP %s | %d correctas, %d fallidas, %d defectos conocidos, %d conocidos ya corregidos | %d aserciones | %d ms\n",
        PHP_VERSION,
        $ok,
        $fallos,
        $conocidos,
        $corregidos,
        Pruebas::$aserciones,
        $ms
    );
    if ($detalles) {
        echo "\nFallos:\n  - " . implode("\n  - ", $detalles) . "\n";
    }
    echo $fallos === 0 ? "RESULTADO: OK\n" : "RESULTADO: HAY FALLOS\n";
    return $fallos === 0 ? 0 : 1;
}
