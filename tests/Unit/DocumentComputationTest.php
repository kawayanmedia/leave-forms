<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Tests\Unit;

use KawayanMedia\LeaveForms\Document\CtoApplication;
use KawayanMedia\LeaveForms\Document\MoneyValue;
use KawayanMedia\LeaveForms\Document\RetirementSchedule;
use KawayanMedia\LeaveForms\Model\LeaveBalance;
use KawayanMedia\LeaveForms\Support\Money;
use PHPUnit\Framework\TestCase;

final class DocumentComputationTest extends TestCase
{
    public function test_money_value_uses_the_monetisation_factor(): void
    {
        $document = new MoneyValue(employee: new \KawayanMedia\LeaveForms\Model\Employee(), salary: 45000, leaveBalance: 27.75);

        self::assertSame(59701.11, round($document->totalMoney(), 2));
    }

    public function test_retirement_schedule_totals(): void
    {
        $document = new RetirementSchedule(
            employee: new \KawayanMedia\LeaveForms\Model\Employee(),
            balance: new LeaveBalance(15.5, 12.25),
            salary: 45000,
        );

        self::assertSame(27.75, $document->totalLeave());
        self::assertSame(round(Money::monetize(45000, 27.75), 2), round($document->terminalLeave(), 2));
    }

    public function test_cto_balance_is_balance_plus_earned_minus_spent(): void
    {
        $document = new CtoApplication(
            employee: new \KawayanMedia\LeaveForms\Model\Employee(),
            days: 2,
            balance: 4.0,
            earned: 3.0,
            spent: 1.5,
        );

        self::assertSame(5.5, $document->totalBalance());
        self::assertSame('2 days', $document->daysLabel());
    }

    public function test_one_day_is_singular(): void
    {
        $document = new CtoApplication(employee: new \KawayanMedia\LeaveForms\Model\Employee(), days: 1);

        self::assertSame('1 day', $document->daysLabel());
    }
}
