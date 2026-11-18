<?php

declare(strict_types=1);

namespace App\Models\Reglas;

use DateTimeImmutable;

/**
 * Filtros de la bitácora del enrutador (HU-05): día, punto de atención y
 * estado de la solicitud.
 */
final class BitacoraFiltro
{
    private function __construct(
        public readonly DateTimeImmutable $fecha,
        public readonly ?int $puntoId,
        public readonly ?string $estado,
    ) {
    }

    /**
     * Fecha inválida o vacía = hoy. Punto vacío = todos los puntos.
     * Estado vacío o desconocido = todos los estados.
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

        $punto  = (int) ($query['punto'] ?? 0);
        $estado = (string) ($query['estado'] ?? '');

        return new self(
            $fecha,
            in_array($punto, $puntosValidos, true) ? $punto : null,
            in_array($estado, EstadoSolicitud::TODOS, true) ? $estado : null,
        );
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
     * Deja solo las solicitudes del estado elegido (todas si no hay filtro).
     *
     * @param list<array<string, mixed>> $solicitudes
     * @return list<array<string, mixed>>
     */
    public function aplicarEstado(array $solicitudes): array
    {
        if ($this->estado === null) {
            return $solicitudes;
        }

        return array_values(array_filter(
            $solicitudes,
            fn (array $s): bool => ($s['estado'] ?? EstadoSolicitud::PENDIENTE) === $this->estado
        ));
    }

    /**
     * Parámetros de la URL actual, para volver a la misma vista después de
     * cambiar un estado.
     */
    public function queryString(): string
    {
        return http_build_query(array_filter([
            'fecha'  => $this->fecha->format('Y-m-d'),
            'punto'  => $this->puntoId,
            'estado' => $this->estado,
        ]));
    }

    /**
     * Totales del día para las tarjetas de resumen.
     *
     * @param list<array<string, mixed>> $solicitudes
     * @return array{total: int, urbanas: int, rurales: int, sinUbicacion: int, porEstado: array<string, int>, porPunto: array<string, int>}
     */
    public static function resumen(array $solicitudes): array
    {
        $r = [
            'total'        => count($solicitudes),
            'urbanas'      => 0,
            'rurales'      => 0,
            'sinUbicacion' => 0,
            'porEstado'    => array_fill_keys(EstadoSolicitud::TODOS, 0),
            'porPunto'     => [],
        ];

        foreach ($solicitudes as $s) {
            $s['tipo'] === 'comuna' ? $r['urbanas']++ : $r['rurales']++;
            if ($s['latitud'] === null) {
                $r['sinUbicacion']++;
            }
            $estado = $s['estado'] ?? EstadoSolicitud::PENDIENTE;
            $r['porEstado'][$estado] = ($r['porEstado'][$estado] ?? 0) + 1;
            $r['porPunto'][$s['punto']] = ($r['porPunto'][$s['punto']] ?? 0) + 1;
        }
        arsort($r['porPunto']);

        return $r;
    }
}
