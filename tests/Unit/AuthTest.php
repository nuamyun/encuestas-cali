<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Auth;
use PHPUnit\Framework\TestCase;

/**
 * HU-02: control de acceso por perfil.
 */
final class AuthTest extends TestCase
{
    public function testRutaPublicaPermiteASinSesion(): void
    {
        self::assertSame('permitido', Auth::autorizar([], null));
    }

    public function testRutaProtegidaSinSesionPideLogin(): void
    {
        self::assertSame('login', Auth::autorizar(['administrador'], null));
    }

    public function testPerfilAutorizadoEntra(): void
    {
        self::assertSame('permitido', Auth::autorizar(['administrador'], ['perfil' => 'administrador']));
    }

    public function testOtroPerfilRecibeProhibido(): void
    {
        self::assertSame('prohibido', Auth::autorizar(['administrador'], ['perfil' => 'ventanilla']));
    }

    public function testComodinPermiteCualquierPerfilAutenticado(): void
    {
        foreach (Auth::PERFILES as $perfil) {
            self::assertSame('permitido', Auth::autorizar([Auth::CUALQUIER_PERFIL], ['perfil' => $perfil]));
        }
    }

    public function testExistenLosCincoPerfilesDeR1(): void
    {
        self::assertSame(
            ['administrador', 'ventanilla', 'encuestador', 'calidad', 'enrutador'],
            Auth::PERFILES
        );
    }
}
