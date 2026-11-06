<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Tests\Unit;

use KawayanMedia\LeaveForms\Support\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function test_the_legacy_precisions(): void
    {
        self::assertSame('45,000.00', Money::amount(45000));
        self::assertSame('27.750', Money::credits(27.75));
        self::assertSame('P 45,000.00', Money::peso(45000));
    }

    public function test_monetisation_matches_the_legacy_formula(): void
    {
        self::assertSame(59701.11, round(Money::monetize(45000, 27.75), 2));
        self::assertSame(0.0, Money::monetize(0, 10));
    }

    public function test_zero_credits_can_be_printed_blank(): void
    {
        self::assertSame('', Money::creditsOrBlank(0.0));
        self::assertSame('1.250', Money::creditsOrBlank(1.25));
    }
}
