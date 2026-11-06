<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Tests\Unit;

use KawayanMedia\LeaveForms\Document\StatementOfLeave;
use KawayanMedia\LeaveForms\Model\Employee;
use KawayanMedia\LeaveForms\Model\LeaveYearRow;
use PHPUnit\Framework\TestCase;

final class StatementOfLeaveTest extends TestCase
{
    public function test_it_prints_one_row_per_year_of_service(): void
    {
        $document = new StatementOfLeave(
            employee: new Employee(firstDayOfService: '2024-07-01'),
            years: [
                2024 => ['vacation_earned' => 7.5, 'sick_earned' => 7.5, 'vacation_spent' => 0.0, 'sick_spent' => 0.0],
                2025 => ['vacation_earned' => 15.0, 'sick_earned' => 15.0, 'vacation_spent' => 2.0, 'sick_spent' => 1.0],
            ],
            asOf: '2026-03-01',
        );

        $rows = $document->rows();

        self::assertCount(3, $rows);
        self::assertSame('07/01/24  to  12/31/24', $rows[0]->period);
        self::assertSame('01/01/25  to  12/31/25', $rows[1]->period);
        self::assertSame('01/01/26  to  02/28/26', $rows[2]->period);
    }

    public function test_balances_accumulate_and_are_printed_in_full(): void
    {
        $document = new StatementOfLeave(
            employee: new Employee(firstDayOfService: '2024-07-01'),
            years: [
                2024 => ['vacation_earned' => 7.5, 'sick_earned' => 7.5, 'vacation_spent' => 2.0, 'sick_spent' => 0.0],
                2025 => ['vacation_earned' => 15.0, 'sick_earned' => 15.0, 'vacation_spent' => 1.0, 'sick_spent' => 1.0],
                2026 => ['vacation_earned' => 0.0, 'sick_earned' => 0.0, 'vacation_spent' => 0.0, 'sick_spent' => 0.0],
            ],
            asOf: '2026-03-01',
        );

        self::assertSame(19.5, $document->vacationBalance());
        self::assertSame(21.5, $document->sickBalance());
        self::assertSame(22.5, $document->vacationEarnedTotal());
        self::assertSame(22.5, $document->sickEarnedTotal());
        self::assertSame(3.0, $document->vacationSpentTotal());
        self::assertSame(1.0, $document->sickSpentTotal());
    }

    public function test_a_forwarded_balance_prints_the_note_then_the_year(): void
    {
        $document = new StatementOfLeave(
            employee: new Employee(firstDayOfService: '2005-01-01'),
            years: [
                2026 => ['vacation_earned' => 1.25, 'sick_earned' => 1.25, 'vacation_spent' => 0.0, 'sick_spent' => 0.0],
            ],
            forwarded: new LeaveYearRow(
                period: 'Balances forwarded as of 12/31/2025',
                vacationBalance: 10.0,
                sickBalance: 8.0,
            ),
            asOf: '2026-03-01',
        );

        $rows = $document->rows();

        self::assertCount(3, $rows);
        self::assertSame('Balances forwarded as of 12/31/2025', $rows[0]->period);
        self::assertSame(10.0, $rows[0]->vacationBalance);
        self::assertSame(8.0, $rows[0]->sickBalance);
        // The forwarded year still prints its own earn/spend row, then the current year follows.
        self::assertStringStartsWith('01/01/26', $rows[1]->period);
        self::assertSame(10.0, $rows[1]->vacationBalance);
        self::assertSame('01/01/26  to  02/28/26', $rows[2]->period);
        self::assertSame(11.25, $rows[2]->vacationBalance);
    }

    public function test_the_monetary_equivalent_uses_the_step_salary(): void
    {
        $document = new StatementOfLeave(
            employee: new Employee(firstDayOfService: '2026-01-01'),
            years: [2026 => ['vacation_earned' => 10.0, 'sick_earned' => 5.0]],
            stepSalary: 30000.0,
            asOf: '2026-03-01',
        );

        self::assertSame(round(15.0 * 0.0478087 * 30000, 2), round($document->grandTotal(), 2));
    }

    public function test_a_supplied_monetary_equivalent_wins(): void
    {
        $document = new StatementOfLeave(
            employee: new Employee(firstDayOfService: '2026-01-01'),
            stepSalary: 30000.0,
            monetaryEquivalent: 1234.56,
            asOf: '2026-03-01',
        );

        self::assertSame(1234.56, $document->grandTotal());
    }
}
