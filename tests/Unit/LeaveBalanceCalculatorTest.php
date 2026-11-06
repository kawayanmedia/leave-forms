<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Tests\Unit;

use KawayanMedia\LeaveForms\Support\LeaveBalanceCalculator;
use PHPUnit\Framework\TestCase;

final class LeaveBalanceCalculatorTest extends TestCase
{
    public function testEarningsAccumulate(): void
    {
        $balances = (new LeaveBalanceCalculator())->fold([
            ['v_earned' => 1.25, 's_earned' => 1.25],
            ['v_earned' => 1.25, 's_earned' => 1.25],
        ]);

        self::assertSame(2.5, $balances['vacation']);
        self::assertSame(2.5, $balances['sick']);
    }

    public function testAbsencesAndBalances(): void
    {
        $balances = (new LeaveBalanceCalculator())->fold([
            ['v_balance' => 10, 's_balance' => 8],
            ['v_abs' => 2, 's_abs' => 1],
        ]);

        self::assertSame(8.0, $balances['vacation']);
        self::assertSame(7.0, $balances['sick']);
    }

    public function testSickLeaveOverflowIsChargedToVacation(): void
    {
        $balances = (new LeaveBalanceCalculator())->fold([
            ['v_earned' => 5, 's_earned' => 0],
            ['particulars' => '2 SL', 's_abs' => 2, 'leave_type_id' => 2],
        ]);

        // With no sick credits left the legacy rule charged the whole absence
        // to vacation leave and left the sick balance at zero.
        self::assertSame(3.0, $balances['vacation']);
        self::assertSame(0.0, $balances['sick']);
    }

    public function testForwardedRowsDoNotTriggerTheConversion(): void
    {
        $balances = (new LeaveBalanceCalculator())->fold([
            ['particulars' => 'Bal. 0 VL / 0 SL', 'v_balance' => 4, 's_balance' => 0],
            ['particulars' => '1 SL', 's_abs' => 1, 'leave_type_id' => 2],
        ]);

        // The forwarded row itself is not converted, but the sick leave filed
        // afterwards still falls back to vacation leave.
        self::assertSame(3.0, $balances['vacation']);
        self::assertSame(0.0, $balances['sick']);
    }

    public function testSickLeaveBeyondTheNegativeBalanceKeepsADebt(): void
    {
        $balances = (new LeaveBalanceCalculator())->fold([
            ['particulars' => 'Bal. 10 VL / -1 SL', 'v_balance' => 10, 's_balance' => -1],
            ['particulars' => '1 SL', 's_abs' => 1],
        ]);

        // The forwarded row already carried a sick debt, so the absence
        // overflows it and the debt stays on the card while vacation pays.
        self::assertSame(9.0, $balances['vacation']);
        self::assertSame(-1.0, $balances['sick']);
    }

    public function testTerminalLeaveResetsTheBalances(): void
    {
        $balances = (new LeaveBalanceCalculator())->fold([
            ['v_earned' => 10, 's_earned' => 10],
            ['leave_type_id' => 21, 'v_abs' => 0, 's_abs' => 0],
        ]);

        self::assertSame(0.0, $balances['vacation']);
        self::assertSame(0.0, $balances['sick']);
    }

    public function testBlankRowsAreIgnored(): void
    {
        $balances = (new LeaveBalanceCalculator())->fold([
            ['v_earned' => '', 's_earned' => null],
        ]);

        self::assertSame(0.0, $balances['vacation']);
        self::assertSame(0.0, $balances['sick']);
    }
}
