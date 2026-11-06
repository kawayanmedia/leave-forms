<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Document;

use DateTimeImmutable;
use KawayanMedia\LeaveForms\Model\Employee;
use KawayanMedia\LeaveForms\Model\LeaveYearRow;
use KawayanMedia\LeaveForms\Model\Office;
use KawayanMedia\LeaveForms\Model\Signatory;
use KawayanMedia\LeaveForms\Support\Dates;
use KawayanMedia\LeaveForms\Support\Money;

/**
 * Statement of leave: one line per year of service with the leave earned,
 * spent and the running balance, exactly as the legacy report computed it.
 */
final class StatementOfLeave implements PrintableDocument
{
    /** @var list<LeaveYearRow>|null */
    private ?array $computedRows = null;

    /**
     * @param array<int, array{vacation_earned?: float, sick_earned?: float, vacation_spent?: float, sick_spent?: float}> $years
     */
    public function __construct(
        public readonly Employee $employee,
        public readonly Office $office = new Office(),
        public readonly array $years = [],
        public readonly ?LeaveYearRow $forwarded = null,
        public readonly float $stepSalary = 0.0,
        public readonly ?float $monetaryEquivalent = null,
        public readonly ?Signatory $prepared = null,
        public readonly ?Signatory $certified = null,
        public readonly mixed $asOf = null,
    ) {
    }

    public function layoutKey(): string
    {
        return 'statement-of-leave';
    }

    public function asOfDate(): DateTimeImmutable
    {
        return Dates::parse($this->asOf) ?? new DateTimeImmutable();
    }

    /**
     * @return list<LeaveYearRow>
     */
    public function rows(): array
    {
        if ($this->computedRows !== null) {
            return $this->computedRows;
        }

        $asOf = $this->asOfDate();
        $currentYear = (int) $asOf->format('Y');
        $service = Dates::parse($this->employee->firstDayOfService);

        $year = $service === null ? $currentYear : (int) $service->format('Y');
        $month = $service === null ? 1 : (int) $service->format('n');
        $day = $service === null ? 1 : (int) $service->format('j');

        $forwarded = $this->forwarded;
        $isForwarded = $forwarded !== null;
        $nextDay = '';

        if ($forwarded !== null && $forwarded->period !== '') {
            $year = (int) substr($forwarded->period, -4);

            // The legacy note was expected to end with the year; when it does
            // not, fall back to the current year instead of looping from 0.
            if ($year < 1900) {
                $year = $currentYear;
            } else {
                $nextDay = substr($forwarded->period, -10);
            }
        }

        $vacationBalance = $forwarded?->vacationBalance ?? 0.0;
        $sickBalance = $forwarded?->sickBalance ?? 0.0;
        $rows = [];
        $first = true;

        while ($currentYear >= $year) {
            $earned = $this->yearValues($year);
            $earned = $isForwarded && $forwarded !== null ? $this->zero() : $earned;

            $period = $this->periodFor($first, $currentYear, $year, $month, $day, $asOf, $forwarded);

            $vacationBalance += $earned['vacation_earned'] - $earned['vacation_spent'];
            $sickBalance += $earned['sick_earned'] - $earned['sick_spent'];

            $rows[] = new LeaveYearRow(
                period: $period,
                vacationEarned: $earned['vacation_earned'],
                sickEarned: $earned['sick_earned'],
                vacationSpent: $earned['vacation_spent'],
                sickSpent: $earned['sick_spent'],
                vacationBalance: $vacationBalance,
                sickBalance: $sickBalance,
            );

            if ($isForwarded) {
                $isForwarded = false;
                $earned = $this->yearValues($year);

                $vacationBalance += $earned['vacation_earned'] - $earned['vacation_spent'];
                $sickBalance += $earned['sick_earned'] - $earned['sick_spent'];

                $rows[] = new LeaveYearRow(
                    period: $this->secondPeriod($year, $nextDay),
                    vacationEarned: $earned['vacation_earned'],
                    sickEarned: $earned['sick_earned'],
                    vacationSpent: $earned['vacation_spent'],
                    sickSpent: $earned['sick_spent'],
                    vacationBalance: $vacationBalance,
                    sickBalance: $sickBalance,
                );
            }

            $year++;
            $first = false;
        }

        return $this->computedRows = $rows;
    }

    public function vacationBalance(): float
    {
        $rows = $this->rows();

        return $rows === [] ? 0.0 : $rows[count($rows) - 1]->vacationBalance;
    }

    public function sickBalance(): float
    {
        $rows = $this->rows();

        return $rows === [] ? 0.0 : $rows[count($rows) - 1]->sickBalance;
    }

    public function vacationEarnedTotal(): float
    {
        return array_sum(array_map(static fn (LeaveYearRow $row): float => $row->vacationEarned, $this->rows()));
    }

    public function sickEarnedTotal(): float
    {
        return array_sum(array_map(static fn (LeaveYearRow $row): float => $row->sickEarned, $this->rows()));
    }

    public function vacationSpentTotal(): float
    {
        return array_sum(array_map(static fn (LeaveYearRow $row): float => $row->vacationSpent, $this->rows()));
    }

    public function sickSpentTotal(): float
    {
        return array_sum(array_map(static fn (LeaveYearRow $row): float => $row->sickSpent, $this->rows()));
    }

    /**
     * Legacy Salary_grade::monetary_equivalent(): leave days x 0.0478087 x the
     * highest salary received (the step amount of the employee's salary grade).
     */
    public function grandTotal(): float
    {
        if ($this->monetaryEquivalent !== null) {
            return $this->monetaryEquivalent;
        }

        $days = $this->sickBalance() + $this->vacationBalance();

        return $this->stepSalary === 0.0 ? 0.0 : Money::MONETISATION_FACTOR * $this->stepSalary * $days;
    }

    /**
     * @param array<string, mixed> $rows
     */
    public static function fromArray(array $rows): self
    {
        $employee = $rows['employee'] ?? [];
        $office = $rows['office'] ?? [];
        $forwarded = $rows['forwarded'] ?? null;

        return new self(
            employee: $employee instanceof Employee ? $employee : Employee::fromArray((array) $employee),
            office: $office instanceof Office ? $office : Office::fromArray((array) $office),
            years: (array) ($rows['years'] ?? []),
            forwarded: $forwarded === null ? null : new LeaveYearRow(
                period: (string) ($forwarded['note'] ?? $forwarded['forwarded_note'] ?? ''),
                vacationBalance: (float) ($forwarded['vacation'] ?? $forwarded['forwarded_vacation'] ?? 0),
                sickBalance: (float) ($forwarded['sick'] ?? $forwarded['forwarded_sick'] ?? 0),
            ),
            stepSalary: (float) ($rows['step_salary'] ?? 0),
            monetaryEquivalent: isset($rows['monetary_equivalent']) ? (float) $rows['monetary_equivalent'] : null,
            prepared: isset($rows['prepared']) ? Signatory::from($rows['prepared']) : null,
            certified: isset($rows['certified']) ? Signatory::from($rows['certified']) : null,
            asOf: $rows['as_of'] ?? null,
        );
    }

    /**
     * @return array{vacation_earned: float, sick_earned: float, vacation_spent: float, sick_spent: float}
     */
    private function yearValues(int $year): array
    {
        $values = $this->years[$year] ?? [];

        return [
            'vacation_earned' => (float) ($values['vacation_earned'] ?? 0),
            'sick_earned' => (float) ($values['sick_earned'] ?? 0),
            'vacation_spent' => (float) ($values['vacation_spent'] ?? 0),
            'sick_spent' => (float) ($values['sick_spent'] ?? 0),
        ];
    }

    /**
     * @return array{vacation_earned: float, sick_earned: float, vacation_spent: float, sick_spent: float}
     */
    private function zero(): array
    {
        return ['vacation_earned' => 0.0, 'sick_earned' => 0.0, 'vacation_spent' => 0.0, 'sick_spent' => 0.0];
    }

    private function periodFor(
        bool $first,
        int $currentYear,
        int $year,
        int $month,
        int $day,
        DateTimeImmutable $asOf,
        ?LeaveYearRow $forwarded,
    ): string {
        $shortYear = substr((string) $year, -2);

        if ($first) {
            if ($forwarded !== null) {
                return $forwarded->period;
            }

            return sprintf('%02d/%02d/%s  to  12/31/%s', $month, $day, $shortYear, $shortYear);
        }

        if ($currentYear === $year) {
            $lastMonthEnd = $asOf->modify('first day of this month')->modify('-1 day');

            return '01/01/' . $shortYear . '  to  ' . $lastMonthEnd->format('m/d/y');
        }

        return sprintf('01/01/%s  to  12/31/%s', $shortYear, $shortYear);
    }

    private function secondPeriod(int $year, string $nextDay): string
    {
        $shortYear = substr((string) $year, -2);
        $from = Dates::parse($nextDay);

        if ($from === null) {
            return sprintf('01/01/%s  to  12/31/%s', $shortYear, $shortYear);
        }

        return $from->modify('+1 day')->format('m/d/y') . '  to  12/31/' . $shortYear;
    }
}
