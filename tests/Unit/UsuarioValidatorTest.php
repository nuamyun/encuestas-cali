<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Validation\UsuarioValidator;
use PHPUnit\Framework\TestCase;

/**
 * HU-01: reglas para crear usuarios.
 */
final class UsuarioValidatorTest extends TestCase
{
    private const PERFILES = [1 => 'administrador', 2 => 'ventanilla', 3 => 'encuestador', 4 => 'calidad', 5 => 'enrutador'];
    private const PUNTOS   = [1, 2, 3, 4, 5, 6, 7, 8];

    /**
     * @param array<string, mixed> $cambios
     * @return array<string, mixed>
     */
    private function entrada(array $cambios = []): array
    {
        return $cambios + [
            'documento'         => '1144123456',
            'nombres'           => 'María José',
            'apellidos'         => 'Peña Ruiz',
            'correo'            => 'Maria.Pena@Encuestas.test ',
            'password'          => 'Segura2026',
            'perfil_id'         => '3',
            'punto_atencion_id' => '',
        ];
    }

    public function testDatosValidosNoTienenErrores(): void
    {
        $r = UsuarioValidator::validate($this->entrada(), self::PERFILES, self::PUNTOS);

        self::assertSame([], $r['errores']);
    }

    public function testNormalizaElCorreoAMinusculasSinEspacios(): void
    {
        $r = UsuarioValidator::validate($this->entrada(), self::PERFILES, self::PUNTOS);

        self::assertSame('maria.pena@encuestas.test', $r['datos']['correo']);
    }

    public function testRechazaDocumentoConLetrasOMuyCorto(): void
    {
        self::assertArrayHasKey('documento', UsuarioValidator::validate($this->entrada(['documento' => '12a45']), self::PERFILES, self::PUNTOS)['errores']);
        self::assertArrayHasKey('documento', UsuarioValidator::validate($this->entrada(['documento' => '1234']), self::PERFILES, self::PUNTOS)['errores']);
    }

    public function testRechazaCorreoInvalido(): void
    {
        $r = UsuarioValidator::validate($this->entrada(['correo' => 'no-es-correo']), self::PERFILES, self::PUNTOS);

        self::assertArrayHasKey('correo', $r['errores']);
    }

    public function testRechazaNombresConNumeros(): void
    {
        $r = UsuarioValidator::validate($this->entrada(['nombres' => 'Juan 2']), self::PERFILES, self::PUNTOS);

        self::assertArrayHasKey('nombres', $r['errores']);
    }

    public function testRechazaContrasenaCorta(): void
    {
        $r = UsuarioValidator::validate($this->entrada(['password' => '1234567']), self::PERFILES, self::PUNTOS);

        self::assertArrayHasKey('password', $r['errores']);
    }

    public function testRechazaPerfilInexistente(): void
    {
        $r = UsuarioValidator::validate($this->entrada(['perfil_id' => '99']), self::PERFILES, self::PUNTOS);

        self::assertArrayHasKey('perfil_id', $r['errores']);
    }

    public function testVentanillaSinPuntoDeAtencionEsInvalido(): void
    {
        $r = UsuarioValidator::validate($this->entrada(['perfil_id' => '2']), self::PERFILES, self::PUNTOS);

        self::assertArrayHasKey('punto_atencion_id', $r['errores']);
    }

    public function testVentanillaConPuntoValidoEsAceptada(): void
    {
        $r = UsuarioValidator::validate($this->entrada(['perfil_id' => '2', 'punto_atencion_id' => '8']), self::PERFILES, self::PUNTOS);

        self::assertSame([], $r['errores']);
        self::assertSame(8, $r['datos']['punto_atencion_id']);
    }

    public function testOtrosPerfilesNoGuardanPuntoDeAtencion(): void
    {
        $r = UsuarioValidator::validate($this->entrada(['perfil_id' => '5', 'punto_atencion_id' => '3']), self::PERFILES, self::PUNTOS);

        self::assertNull($r['datos']['punto_atencion_id']);
    }
}
