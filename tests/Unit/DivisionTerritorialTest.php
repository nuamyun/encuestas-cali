<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * R2: la solicitud debe referenciar la división de Cali en barrios, comunas
 * y corregimientos. Verifica los datos cargados en database/*.sql.
 */
final class DivisionTerritorialTest extends TestCase
{
    private static string $schema;
    private static string $barrios;

    public static function setUpBeforeClass(): void
    {
        self::$schema  = (string) file_get_contents(__DIR__ . '/../../database/schema.sql');
        self::$barrios = (string) file_get_contents(__DIR__ . '/../../database/barrios.sql');
    }

    public function testElEsquemaCreaLos15Corregimientos(): void
    {
        self::assertSame(15, preg_match_all("/\('corregimiento', NULL, '[^']+'\)/", self::$schema));
    }

    public function testSeCarganLos457BarriosVeredasYSectoresOficiales(): void
    {
        self::assertSame(457, preg_match_all('/^INSERT IGNORE INTO barrios/m', self::$barrios));
    }

    public function testCadaUnaDeLas22ComunasTieneBarrios(): void
    {
        preg_match_all("/tipo = 'comuna' AND numero = (\d+);/", self::$barrios, $m);
        $comunas = array_unique(array_map('intval', $m[1]));
        sort($comunas);

        self::assertSame(range(1, 22), $comunas);
    }

    public function testCadaCorregimientoTieneVeredas(): void
    {
        preg_match_all("/\('corregimiento', NULL, '([^']+)'\)/", self::$schema, $esquema);
        preg_match_all("/tipo = 'corregimiento' AND nombre = '([^']+)';/", self::$barrios, $cargados);

        self::assertSame([], array_values(array_diff($esquema[1], array_unique($cargados[1]))));
    }

    /**
     * Sin SET NAMES, MySQL lee los archivos como latin1 y las tildes quedan
     * dañadas ("Víctor" se guarda como "VÃ­ctor").
     */
    public function testCadaArchivoSqlDeclaraUtf8(): void
    {
        foreach (['schema.sql', 'seed.sql', 'barrios.sql'] as $archivo) {
            $sql = (string) file_get_contents(__DIR__ . '/../../database/' . $archivo);
            $primeraInstruccion = preg_replace('/^(\s*--[^\n]*\n|\s+)*/', '', $sql) ?? '';

            self::assertStringContainsString('SET NAMES utf8mb4;', substr($primeraInstruccion, 0, 20), "{$archivo} debe empezar con SET NAMES utf8mb4;");
        }
    }

    public function testLosPuntosDeAtencionSonLosDeR5(): void
    {
        self::assertStringContainsString('numero IN (1, 6, 7, 13, 14, 17, 20, 21)', self::$schema);
    }
}
