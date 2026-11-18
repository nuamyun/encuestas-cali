<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Validation\SolicitudValidator;
use PHPUnit\Framework\TestCase;

/**
 * HU-03 y HU-04: reglas del registro y la georreferenciación de solicitudes.
 */
final class SolicitudValidatorTest extends TestCase
{
    /** El barrio 10 pertenece a la comuna 2; el 20 a la comuna 5. */
    public static function pertenece(int $barrio, int $cc): bool
    {
        return [$barrio, $cc] === [10, 2] || [$barrio, $cc] === [20, 5];
    }

    /**
     * @param array<string, mixed> $cambios
     * @return array{datos: array<string, mixed>, errores: array<string, string>}
     */
    private function validar(array $cambios = []): array
    {
        return SolicitudValidator::validate($cambios + [
            'nombres'                 => 'Luz Mery',
            'apellidos'               => 'Ocampo',
            'celular'                 => '315 678 9012',
            'direccion'               => 'Calle 5 # 38-25',
            'comuna_corregimiento_id' => '2',
            'barrio_id'               => '10',
            'latitud'                 => '3.4372',
            'longitud'                => '-76.5440',
        ], [self::class, 'pertenece']);
    }

    public function testSolicitudCompletaYGeorreferenciadaEsValida(): void
    {
        $r = $this->validar();

        self::assertSame([], $r['errores']);
        self::assertSame(3.4372, $r['datos']['latitud']);
        self::assertSame(-76.544, $r['datos']['longitud']);
    }

    public function testSePuedeGuardarSinGeorreferenciar(): void
    {
        $r = $this->validar(['latitud' => '', 'longitud' => '']);

        self::assertSame([], $r['errores']);
        self::assertNull($r['datos']['latitud']);
        self::assertNull($r['datos']['longitud']);
    }

    public function testNormalizaElCelularConEspaciosGuionesOIndicativo(): void
    {
        self::assertSame('3156789012', $this->validar()['datos']['celular']);
        self::assertSame('3156789012', SolicitudValidator::normalizarCelular('+57 315-678-9012'));
    }

    public function testRechazaCelularQueNoEmpiezaPor3(): void
    {
        self::assertArrayHasKey('celular', $this->validar(['celular' => '6025551234'])['errores']);
    }

    public function testRechazaCelularConMenosDe10Digitos(): void
    {
        self::assertArrayHasKey('celular', $this->validar(['celular' => '315678901'])['errores']);
    }

    public function testCamposObligatorios(): void
    {
        $r = SolicitudValidator::validate([], [self::class, 'pertenece']);

        foreach (['nombres', 'apellidos', 'celular', 'direccion', 'comuna_corregimiento_id', 'barrio_id'] as $campo) {
            self::assertArrayHasKey($campo, $r['errores'], "Falta el error de {$campo}");
        }
    }

    public function testRechazaBarrioDeOtraComuna(): void
    {
        $r = $this->validar(['comuna_corregimiento_id' => '5', 'barrio_id' => '10']);

        self::assertArrayHasKey('barrio_id', $r['errores']);
    }

    public function testRechazaCoordenadasFueraDeCali(): void
    {
        // Bogotá
        $r = $this->validar(['latitud' => '4.7110', 'longitud' => '-74.0721']);

        self::assertArrayHasKey('ubicacion', $r['errores']);
    }

    public function testRechazaSiSoloLlegaUnaCoordenada(): void
    {
        $r = $this->validar(['longitud' => '']);

        self::assertArrayHasKey('ubicacion', $r['errores']);
    }

    public function testRechazaCoordenadasNoNumericas(): void
    {
        $r = $this->validar(['latitud' => 'abc', 'longitud' => '-76.5']);

        self::assertArrayHasKey('ubicacion', $r['errores']);
    }
}
