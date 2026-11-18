<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Reglas\EstadoSolicitud;
use PHPUnit\Framework\TestCase;

/**
 * Bitácora: el enrutador marca las solicitudes como pendiente, lista o rechazada.
 */
final class EstadoSolicitudTest extends TestCase
{
    public function testExistenLosTresEstados(): void
    {
        self::assertSame(['pendiente', 'lista', 'rechazada'], EstadoSolicitud::TODOS);
    }

    public function testMarcarListaNoPideMotivo(): void
    {
        $r = EstadoSolicitud::validarCambio(['estado' => 'lista']);

        self::assertNull($r['error']);
        self::assertSame('lista', $r['estado']);
        self::assertNull($r['motivo']);
    }

    public function testRechazarSinMotivoEsInvalido(): void
    {
        self::assertNotNull(EstadoSolicitud::validarCambio(['estado' => 'rechazada'])['error']);
        self::assertNotNull(EstadoSolicitud::validarCambio(['estado' => 'rechazada', 'motivo' => '  no  '])['error']);
    }

    public function testRechazarConMotivoGuardaElMotivoLimpio(): void
    {
        $r = EstadoSolicitud::validarCambio(['estado' => 'rechazada', 'motivo' => "  Dirección   fuera\nde cobertura "]);

        self::assertNull($r['error']);
        self::assertSame('Dirección fuera de cobertura', $r['motivo']);
    }

    public function testVolverAPendienteBorraElMotivo(): void
    {
        $r = EstadoSolicitud::validarCambio(['estado' => 'pendiente', 'motivo' => 'motivo anterior']);

        self::assertSame('pendiente', $r['estado']);
        self::assertNull($r['motivo']);
    }

    public function testRechazaEstadosDesconocidos(): void
    {
        self::assertNotNull(EstadoSolicitud::validarCambio(['estado' => 'aprobada'])['error']);
        self::assertNotNull(EstadoSolicitud::validarCambio([])['error']);
    }

    public function testElEsquemaUsaLosMismosEstados(): void
    {
        $schema = (string) file_get_contents(__DIR__ . '/../../database/schema.sql');

        self::assertStringContainsString("ENUM('pendiente','lista','rechazada') NOT NULL DEFAULT 'pendiente'", $schema);
    }
}
