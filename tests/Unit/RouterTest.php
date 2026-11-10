<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Controllers\HomeController;
use App\Core\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
        $this->router->get('/', [HomeController::class, 'index']);
        $this->router->get('/usuarios/{id}', [HomeController::class, 'index'], ['administrador']);
        $this->router->post('/solicitudes', [HomeController::class, 'index'], ['ventanilla']);
    }

    public function testEncuentraLaRutaRaiz(): void
    {
        $route = $this->router->match('GET', '/');

        self::assertNotNull($route);
        self::assertSame([HomeController::class, 'index'], $route['handler']);
    }

    public function testIgnoraLaBarraFinalYLaQueryString(): void
    {
        self::assertNotNull($this->router->match('POST', '/solicitudes/?x=1'));
    }

    public function testCapturaParametrosDeLaRuta(): void
    {
        $route = $this->router->match('GET', '/usuarios/42');

        self::assertNotNull($route);
        self::assertSame(['id' => '42'], $route['params']);
    }

    public function testConservaLosPerfilesAutorizados(): void
    {
        $route = $this->router->match('POST', '/solicitudes');

        self::assertNotNull($route);
        self::assertSame(['ventanilla'], $route['perfiles']);
    }

    public function testDevuelveNullSiElMetodoNoCoincide(): void
    {
        self::assertNull($this->router->match('GET', '/solicitudes'));
    }

    public function testDevuelveNullParaRutasInexistentes(): void
    {
        self::assertNull($this->router->match('GET', '/no-existe'));
    }

    public function testHeadSeTrataComoGet(): void
    {
        self::assertNotNull($this->router->match('HEAD', '/'));
    }
}
