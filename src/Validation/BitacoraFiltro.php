<?php

declare(strict_types=1);

namespace App\Validation;

use DateTimeImmutable;

/**
 * Filtros de la bitácora del enrutador (HU-05): día y punto de atención.
 */
final class BitacoraFiltro
{
    private function __construct(
        public readonly DateTimeImmutable $fecha,
        public readonly ?int $puntoId,
    ) {
    }

    /**
     * Fecha inválida o vacía = hoy. Punto vacío = todos los puntos.
     *
     * @param array<string, mixed> $query
     * @param list<int> $puntosValidos
     */
    public static function desde(array $query, array $puntosValidos, ?DateTimeImmutable $hoy = null): self
    {
        $hoy   = ($hoy ?? new DateTimeImmutable('today'))->setTime(0, 0);
        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', (string) ($query['fecha'] ?? ''));

        if ($fecha === false || $fecha->format('Y-m-d') !== ($query['fecha'] ?? '')) {
            $fecha = $hoy;
        }

        $punto = (int) ($query['punto'] ?? 0);

        return new self($fecha, in_array($punto, $puntosValidos, true) ? $punto : null);
    }

    /** Inicio del día (inclusive), formato SQL. */
    public function desdeSql(): string
    {
        return $this->fecha->format('Y-m-d 00:00:00');
    }

    /** Inicio del día siguiente (exclusivo), formato SQL. */
    public function hastaSql(): string
    {
        return $this->fecha->modify('+1 day')->format('Y-m-d 00:00:00');
    }

    /**
     * Totales del día para las tarjetas de resumen.
     *
     * @param list<array<string, mixed>> $solicitudes
     * @return array{total: int, urbanas: int, rurales: int, sinUbicacion: int, porPunto: array<string, int>}
     */
    public static function resumen(array $solicitudes): array
    {
        $r = ['total' => count($solicitudes), 'urbanas' => 0, 'rurales' => 0, 'sinUbicacion' => 0, 'porPunto' => []];

        foreach ($solicitudes as $s) {
            $s['tipo'] === 'comuna' ? $r['urbanas']++ : $r['rurales']++;
            if ($s['latitud'] === null) {
                $r['sinUbicacion']++;
            }
            $r['porPunto'][$s['punto']] = ($r['porPunto'][$s['punto']] ?? 0) + 1;
        }
        arsort($r['porPunto']);

        return $r;
    }
}
