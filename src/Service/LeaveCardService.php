<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Service;

use InvalidArgumentException;
use KawayanMedia\LeaveForms\Contract\LeaveStoreInterface;
use KawayanMedia\LeaveForms\Support\Calendar;
use KawayanMedia\LeaveForms\Support\LeaveDataset;

/**
 * Writes the leave card: monthly and semi-monthly earnings, forwardages,
 * spending rows, and the deletions the leave screens need.
 *
 * Earnings follow the legacy `process_leave_earnings()`:
 *   - monthly schedule: 1.25 VL and 1.25 SL on the last day of the month
 *   - semi-monthly schedule: 0.625 on the 15th and 0.625 on the last day
 * Every earning is skipped when an identical period/v_earned/s_earned/date row
 * already exists.
 */
final class LeaveCardService
{
    public const MONTHLY_EARNING = 1.25;

    public const SEMI_MONTHLY_EARNING = 0.625;

    public function __construct(private readonly LeaveStoreInterface $store)
    {
    }

    /**
     * Earns one month of leave credits on the last day of the month.
     *
     * @param float $amount credit for both VL and SL, 1.25 by default
     *
     * @return bool true when a row was written
     */
    public function earnMonth(
        string $employeeId,
        int $year,
        int $month,
        float $amount = self::MONTHLY_EARNING,
    ): bool {
        $this->assertMonth($year, $month);

        if ($amount <= 0) {
            throw new InvalidArgumentException('The earning must be greater than zero.');
        }

        return $this->earn($employeeId, Calendar::lastDay($year, $month), $amount);
    }

    /**
     * Earns the mid-month credit (0.625 by default) on the 15th.
     */
    public function earnMidMonth(
        string $employeeId,
        int $year,
        int $month,
        float $amount = self::SEMI_MONTHLY_EARNING,
    ): bool {
        $this->assertMonth($year, $month);

        if ($amount <= 0) {
            throw new InvalidArgumentException('The earning must be greater than zero.');
        }

        return $this->earn($employeeId, sprintf('%04d-%02d-15', $year, $month), $amount);
    }

    /**
     * Writes one earning row for a period date.
     *
     * @return bool false when the same earning already exists
     */
    public function earn(string $employeeId, string $period, float $amount): bool
    {
        $this->assertEmployeeId($employeeId);
        $period = Calendar::date($period);

        $exists = $this->store->first(LeaveDataset::CARD, [
            'employee_id' => $employeeId,
            'period' => $period,
            'v_earned' => $amount,
            's_earned' => $amount,
            'date' => $period,
        ]);

        if ($exists !== null) {
            return false;
        }

        $this->store->insert(LeaveDataset::CARD, [
            'employee_id' => $employeeId,
            'period' => $period,
            'v_earned' => $amount,
            's_earned' => $amount,
            'date' => $period,
            'particulars' => '',
            'leave_type_id' => 0,
            'enabled' => 1,
        ]);

        return true;
    }

    /**
     * Spends leave credits, the way approval of an application did.
     *
     * @param string $kind "vacation", "sick" or "wop" (without pay)
     * @param string $actionTaken text shown on the leave card, for example "January 10-11, 2026"
     *
     * @return int|string|null the new row id
     */
    public function spend(
        string $employeeId,
        string $kind,
        float $days,
        string $actionTaken,
        string $date,
        int $leaveTypeId = 0,
    ): int|string|null {
        $this->assertEmployeeId($employeeId);

        if ($days <= 0) {
            throw new InvalidArgumentException('The number of days must be greater than zero.');
        }

        $column = match ($kind) {
            'vacation' => 'v_abs',
            'sick' => 's_abs',
            'wop' => 'v_abs_wop',
            default => throw new InvalidArgumentException(sprintf('Unknown leave kind "%s".', $kind)),
        };

        $values = [
            'employee_id' => $employeeId,
            'particulars' => $this->trimNumber($days) . ' ' . $this->label($kind),
            'action_take' => $actionTaken,
            'date' => Calendar::date($date),
            'leave_type_id' => $leaveTypeId,
            $column => $days,
        ];

        $existing = $this->store->first(LeaveDataset::CARD, [
            'employee_id' => $employeeId,
            'action_take' => $actionTaken,
        ]);

        if ($existing !== null) {
            // Clear the absence columns this kind does not use, so a corrected
            // entry cannot be charged twice.
            $values += array_fill_keys(array_diff(['v_abs', 's_abs', 'v_abs_wop'], [$column]), null);

            $this->store->update(
                LeaveDataset::CARD,
                $values,
                ['employee_id' => $employeeId, 'action_take' => $actionTaken],
            );

            return $existing['id'] ?? null;
        }

        return $this->store->insert(LeaveDataset::CARD, $values);
    }

    /**
     * Records a forwarded balance, the legacy "Bal." row.
     *
     * @return int|string|null the new row id
     */
    public function forward(
        string $employeeId,
        float $vacation,
        float $sick,
        string $date,
        string $note = '',
    ): int|string|null {
        $this->assertEmployeeId($employeeId);

        return $this->store->insert(LeaveDataset::CARD, [
            'employee_id' => $employeeId,
            'particulars' => 'Bal. ' . $this->trimNumber($vacation) . ' VL / ' . $this->trimNumber($sick) . ' SL',
            'action_take' => $note,
            'date' => Calendar::date($date),
            'v_balance' => $vacation,
            's_balance' => $sick,
            'enabled' => 1,
            'leave_type_id' => 0,
        ]);
    }

    /**
     * Deletes every row carrying a forwarded balance, the way
     * `Leave_card::delete_balance_forwarded()` did before re-forwarding.
     */
    public function deleteForwarded(string $employeeId): int
    {
        $this->assertEmployeeId($employeeId);

        return $this->store->delete(LeaveDataset::CARD, [
            ['employee_id', '=', $employeeId],
            ['particulars', 'like', 'bal%'],
        ]);
    }

    /**
     * Deletes every card row dated on or before a cutoff.
     */
    public function deleteThrough(string $employeeId, string $cutoff): int
    {
        $this->assertEmployeeId($employeeId);

        return $this->store->delete(LeaveDataset::CARD, [
            ['employee_id', '=', $employeeId],
            ['date', '<=', Calendar::date($cutoff)],
        ]);
    }

    /**
     * Deletes the earnings of a period range, the way
     * `Leave_card::delete_earning()` did.
     */
    public function deleteEarnings(string $employeeId, string $from, string $to): int
    {
        $this->assertEmployeeId($employeeId);

        return $this->store->delete(LeaveDataset::CARD, [
            ['employee_id', '=', $employeeId],
            ['period', '!=', ''],
            ['period', 'between', Calendar::date($from), Calendar::date($to)],
        ]);
    }

    /**
     * Enables or disables the undertime rows dated on a given day, matching
     * `Leave_card::set_enabled()`.
     */
    public function setUndertimeEnabled(string $date, bool $enabled = true): int
    {
        return $this->store->update(
            LeaveDataset::CARD,
            ['enabled' => $enabled ? 1 : 0],
            [
                ['particulars', 'like', 'UT%'],
                ['date', '=', Calendar::date($date)],
            ],
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function entries(string $employeeId, string $asOf = ''): array
    {
        $conditions = [
            ['employee_id', '=', $employeeId],
            ['enabled', '=', 1],
        ];

        if ($asOf !== '') {
            $conditions[] = ['date', '<=', Calendar::date($asOf)];
        }

        return $this->store->rows(LeaveDataset::CARD, $conditions, ['orderBy' => ['id' => 'ASC']]);
    }

    private function label(string $kind): string
    {
        return match ($kind) {
            'vacation' => 'VL',
            'sick' => 'SL',
            default => 'WOP',
        };
    }

    private function trimNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
    }

    private function assertEmployeeId(string $employeeId): void
    {
        if (trim($employeeId) === '') {
            throw new InvalidArgumentException('An employee id is required.');
        }
    }

    private function assertMonth(int $year, int $month): void
    {
        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException(sprintf('Month %d is out of range.', $month));
        }

        if ($year < 1900 || $year > 2999) {
            throw new InvalidArgumentException(sprintf('Year %d is out of range.', $year));
        }
    }
}
