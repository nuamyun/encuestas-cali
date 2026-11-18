<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Validation\Ubicacion;
use PHPUnit\Framework\TestCase;

/**
 * HU-04: límites geográficos de Cali.
 */
final class UbicacionTest extends TestCase
{
    public function testPuntosUrbanosYRuralesDeCaliEstanDentro(): void
    {
        self::assertTrue(Ubicacion::dentroDeCali(3.4516, -76.5320), 'Centro');
        self::assertTrue(Ubicacion::dentroDeCali(3.3760, -76.5400), 'Sur (Comuna 17)');
        self::assertTrue(Ubicacion::dentroDeCali(3.4300, -76.6400), 'Ladera occidental (zona rural)');
    }

    public function testCiudadesVecinasYLejanasEstanFuera(): void
    {
        self::assertFalse(Ubicacion::dentroDeCali(4.7110, -74.0721), 'Bogotá');
        self::assertFalse(Ubicacion::dentroDeCali(3.5800, -76.4900), 'Al norte, hacia Yumbo');
        self::assertFalse(Ubicacion::dentroDeCali(3.2600, -76.5400), 'Al sur, hacia Jamundí');
    }

    public function testLimitesCoincidenConElCheckDeLaBaseDeDatos(): void
    {
        $schema = (string) file_get_contents(__DIR__ . '/../../database/schema.sql');

        self::assertStringContainsString('latitud  BETWEEN ' . Ubicacion::LAT_MIN . ' AND ' . Ubicacion::LAT_MAX, $schema);
        self::assertStringContainsString('longitud BETWEEN ' . Ubicacion::LON_MIN . ' AND ' . Ubicacion::LON_MAX, $schema);
    }

    public function testRedondeaASeisDecimales(): void
    {
        $r = Ubicacion::validar('3.45161234567', '-76.53209876');

        self::assertSame(3.451612, $r['lat']);
        self::assertSame(-76.532099, $r['lon']);
    }
}
