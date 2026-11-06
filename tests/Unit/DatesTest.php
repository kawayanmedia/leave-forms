<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Tests\Unit;

use KawayanMedia\LeaveForms\Support\Dates;
use PHPUnit\Framework\TestCase;

final class DatesTest extends TestCase
{
    public function test_the_three_legacy_date_shapes(): void
    {
        self::assertSame('March 14, 1985', Dates::longDate('1985-03-14'));
        self::assertSame('14-Mar-85', Dates::middleMonth('1985-03-14'));
        self::assertSame('03/14/1985', Dates::slashDate('1985-03-14'));
    }

    public function test_empty_and_present_values_print_blank(): void
    {
        self::assertSame('', Dates::longDate(null));
        self::assertSame('', Dates::longDate(''));
        self::assertSame('', Dates::longDate('Present'));
        self::assertFalse(Dates::isFilled('0000-00-00'));
    }

    public function test_the_month_name_helper_accepts_legacy_zero_padded_months(): void
    {
        self::assertSame('November', Dates::monthName('11'));
        self::assertSame('January', Dates::monthName('01'));
        self::assertSame('January', Dates::monthName(1));
        self::assertSame('', Dates::monthName('13'));
    }

    public function test_date_math(): void
    {
        $start = Dates::parse('2005-07-01');
        $end = Dates::parse('2030-03-14');

        $interval = $start->diff($end);

        self::assertSame(24, $interval->y);
        self::assertSame(8, $interval->m);
        self::assertSame(13, $interval->d);
    }
}
