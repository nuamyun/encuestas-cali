<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Csrf;
use PHPUnit\Framework\TestCase;

final class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testGeneraUnTokenDe64CaracteresYLoReutiliza(): void
    {
        $token = Csrf::token();

        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
        self::assertSame($token, Csrf::token());
    }

    public function testAceptaElTokenCorrecto(): void
    {
        self::assertTrue(Csrf::validate(Csrf::token()));
    }

    public function testRechazaTokensInvalidosOAusentes(): void
    {
        Csrf::token();

        self::assertFalse(Csrf::validate('token-falso'));
        self::assertFalse(Csrf::validate(null));
    }

    public function testRechazaSiNoHayTokenEnLaSesion(): void
    {
        self::assertFalse(Csrf::validate(''));
    }
}
