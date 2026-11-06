<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Tests\Unit;

use KawayanMedia\LeaveForms\Support\LeaveDays;
use PHPUnit\Framework\TestCase;

final class LeaveDaysTest extends TestCase
{
    public function testWeekendsAreSkipped(): void
    {
        $days = new LeaveDays();

        // 2026-01-09 is a Friday, 2026-01-12 a Monday.
        self::assertSame(['2026-01-09', '2026-01-12'], $days->dates('2026-01-09', '2026-01-12'));
        self::assertSame(2.0, $days->count('2026-01-09', '2026-01-12'));
    }

    public function testHolidaysAreSkipped(): void
    {
        $days = new LeaveDays(['2026-01-12']);

        self::assertSame(['2026-01-09'], $days->dates('2026-01-09', '2026-01-12'));
        self::assertTrue($days->isHoliday('2026-01-12'));
    }

    public function testWeekendsCanBeAllowed(): void
    {
        $days = new LeaveDays();

        self::assertSame(4.0, $days->count('2026-01-09', '2026-01-12', true));
    }

    public function testMaternityCountsEveryDay(): void
    {
        $days = new LeaveDays();

        self::assertSame(4.0, $days->count('2026-01-09', '2026-01-12', false, 5));
        self::assertSame(4.0, $days->count('2026-01-09', '2026-01-12', false, 20));
        self::assertSame(2.0, $days->count('2026-01-09', '2026-01-12', false, 1));
    }

    public function testHalfDays(): void
    {
        $days = new LeaveDays();

        self::assertSame(0.5, $days->count('2026-01-12', '2026-01-12', false, 1, true));
        self::assertSame(2.5, $days->count('2026-01-12', '2026-01-14', false, 1, false, true));
        self::assertSame(2.0, $days->count('2026-01-12', '2026-01-14', false, 1, true, true));
    }

    public function testLegacyTokens(): void
    {
        $days = new LeaveDays();
        $result = $days->fromTokens(2026, 1, ['5', '6-7']);

        self::assertSame(3.0, $result['days']);
        self::assertSame(['2026-01-05', '2026-01-06', '2026-01-07'], $result['dates']);
    }

    public function testLegacyHalfDayTokens(): void
    {
        $days = new LeaveDays();
        $result = $days->fromTokens(2026, 1, ['5am', '6pm']);

        self::assertSame(1.0, $result['days']);
        self::assertSame(['2026-01-05', '2026-01-06'], $result['dates']);
    }

    public function testLegacyTokensSkipWeekends(): void
    {
        $days = new LeaveDays();
        $result = $days->fromTokens(2026, 1, ['10', '11']);

        self::assertSame(0.0, $result['days']);
        self::assertSame([], $result['dates']);
    }

    public function testInvalidDatesAreRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new LeaveDays())->count('2026-02-30', '2026-03-02');
    }

    public function testWeekendDetection(): void
    {
        self::assertTrue(LeaveDays::isWeekend('2026-01-10'));
        self::assertFalse(LeaveDays::isWeekend('2026-01-12'));
    }
}
