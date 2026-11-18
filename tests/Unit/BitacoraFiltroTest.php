<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Validation\BitacoraFiltro;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * HU-05: filtros y resumen de la bitácora del enrutador.
 */
final class BitacoraFiltroTest extends TestCase
{
    private const PUNTOS = [1, 2, 3, 4, 5, 6, 7, 8];

    private DateTimeImmutable $hoy;

    protected function setUp(): void
    {
        $this->hoy = new DateTimeImmutable('2026-11-11 15:30:00');
    }

    public function testSinFiltrosMuestraHoyEnTodosLosPuntos(): void
    {
        $f = BitacoraFiltro::desde([], self::PUNTOS, $this->hoy);

        self::assertSame('2026-11-11', $f->fecha->format('Y-m-d'));
        self::assertNull($f->puntoId);
    }

    public function testElRangoCubreElDiaCompleto(): void
    {
        $f = BitacoraFiltro::desde(['fecha' => '2026-11-05'], self::PUNTOS, $this->hoy);

        self::assertSame('2026-11-05 00:00:00', $f->desdeSql());
        self::assertSame('2026-11-06 00:00:00', $f->hastaSql());
    }

    public function testFechaInvalidaUsaHoy(): void
    {
        foreach (['2026-02-30', '11/05/2026', 'abc', '2026-11-5'] as $mala) {
            $f = BitacoraFiltro::desde(['fecha' => $mala], self::PUNTOS, $this->hoy);
            self::assertSame('2026-11-11', $f->fecha->format('Y-m-d'), "Fecha: {$mala}");
        }
    }

    public function testFiltraPorPuntoValido(): void
    {
        self::assertSame(3, BitacoraFiltro::desde(['punto' => '3'], self::PUNTOS, $this->hoy)->puntoId);
    }

    public function testPuntoInexistenteSeIgnora(): void
    {
        self::assertNull(BitacoraFiltro::desde(['punto' => '99'], self::PUNTOS, $this->hoy)->puntoId);
    }

    public function testResumenCuentaZonasPendientesYPuntos(): void
    {
        $r = BitacoraFiltro::resumen([
            ['tipo' => 'comuna', 'latitud' => '3.45', 'punto' => 'Oficina principal (Comuna 17)'],
            ['tipo' => 'comuna', 'latitud' => null, 'punto' => 'Punto de atención Comuna 1'],
            ['tipo' => 'corregimiento', 'latitud' => null, 'punto' => 'Oficina principal (Comuna 17)'],
        ]);

        self::assertSame(3, $r['total']);
        self::assertSame(2, $r['urbanas']);
        self::assertSame(1, $r['rurales']);
        self::assertSame(2, $r['sinUbicacion']);
        self::assertSame(['Oficina principal (Comuna 17)' => 2, 'Punto de atención Comuna 1' => 1], $r['porPunto']);
    }
}
