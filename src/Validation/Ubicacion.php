<?php

declare(strict_types=1);

namespace App\Validation;

/**
 * Límites geográficos aproximados del municipio de Santiago de Cali
 * (zona urbana y rural). Son los mismos de las restricciones CHECK de la
 * tabla solicitudes y de public/assets/js/mapa.js.
 */
final class Ubicacion
{
    public const LAT_MIN = 3.28;
    public const LAT_MAX = 3.53;
    public const LON_MIN = -76.75;
    public const LON_MAX = -76.44;

    /** Centro del mapa: Plaza de Caycedo. */
    public const CENTRO_LAT = 3.4516;
    public const CENTRO_LON = -76.5320;

    public static function dentroDeCali(float $lat, float $lon): bool
    {
        return $lat >= self::LAT_MIN && $lat <= self::LAT_MAX
            && $lon >= self::LON_MIN && $lon <= self::LON_MAX;
    }

    /**
     * Valida un par de coordenadas que puede venir vacío.
     *
     * @return array{lat: ?float, lon: ?float, error: ?string}
     */
    public static function validar(mixed $lat, mixed $lon): array
    {
        $lat = trim((string) $lat);
        $lon = trim((string) $lon);

        if ($lat === '' && $lon === '') {
            return ['lat' => null, 'lon' => null, 'error' => null];
        }
        if (!is_numeric($lat) || !is_numeric($lon)) {
            return ['lat' => null, 'lon' => null, 'error' => 'La ubicación del mapa no es válida. Márcala de nuevo.'];
        }

        $latF = round((float) $lat, 6);
        $lonF = round((float) $lon, 6);

        if (!self::dentroDeCali($latF, $lonF)) {
            return ['lat' => null, 'lon' => null, 'error' => 'La ubicación marcada está fuera de Cali.'];
        }

        return ['lat' => $latF, 'lon' => $lonF, 'error' => null];
    }

    /**
     * @return array{latMin: float, latMax: float, lonMin: float, lonMax: float, centroLat: float, centroLon: float}
     */
    public static function config(): array
    {
        return [
            'latMin'    => self::LAT_MIN,
            'latMax'    => self::LAT_MAX,
            'lonMin'    => self::LON_MIN,
            'lonMax'    => self::LON_MAX,
            'centroLat' => self::CENTRO_LAT,
            'centroLon' => self::CENTRO_LON,
        ];
    }
}
