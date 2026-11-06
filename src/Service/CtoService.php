<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Service;

use KawayanMedia\LeaveForms\Contract\LeaveStoreInterface;
use KawayanMedia\LeaveForms\Exception\ValidationException;
use KawayanMedia\LeaveForms\Model\CtoRequest;
use KawayanMedia\LeaveForms\Support\Calendar;
use KawayanMedia\LeaveForms\Support\LeaveDataset;
use KawayanMedia\LeaveForms\Support\LeaveDays;

/**
 * Compensatory time off applications.
 *
 * The legacy rows carry `type` ("spent" / "earned") and `status`
 * ("inactive" while pending, "active" once approved). Approval also marks the
 * dates in the DTR as "offset", which is what stops the days from being
 * counted as absences.
 */
final class CtoService
{
    public const STATUS_PENDING = 'inactive';

    public const STATUS_ACTIVE = 'active';

    public const TYPE_SPENT = 'spent';

    public const TYPE_EARNED = 'earned';

    public function __construct(
        private readonly LeaveStoreInterface $store,
        private readonly LeaveDays $days = new LeaveDays(),
    ) {
    }

    /**
     * Files a CTO application. It stays pending until {@see approve()} runs.
     *
     * @param bool $autoApprove true for the legacy case where a non-manager
     *                          filing created an already active row
     *
     * @return int|string|null the new row id
     */
    public function file(CtoRequest $request, bool $autoApprove = false): int|string|null
    {
        $dates = $this->normaliseDates($request);
        $days = $request->days ?? $this->count($dates);

        if ($days <= 0) {
            throw ValidationException::forField(
                'dates',
                'The selected dates fall on weekends or holidays, so no CTO day is counted.',
            );
        }

        $first = substr($dates[0], 0, 10);

        $id = $this->store->insert(LeaveDataset::CTO, [
            'employee_id' => $request->employeeId,
            'office_id' => $request->officeId,
            'month' => substr($first, 5, 2),
            'year' => substr($first, 0, 4),
            'days' => $days,
            'dates' => $this->multiple($dates),
            'date_file' => date('Y-m-d'),
            'type' => self::TYPE_SPENT,
            'status' => $autoApprove ? self::STATUS_ACTIVE : self::STATUS_PENDING,
        ]);

        if ($autoApprove) {
            $this->markDtr($request->employeeId, $request->officeId, $dates);
        }

        return $id;
    }

    /**
     * Approves a CTO application: the row becomes active and the dates are
     * marked "offset" in the DTR.
     *
     * @return array<string, mixed> the updated row
     */
    public function approve(int|string $ctoId, ?string $username = null): array
    {
        $row = $this->require($ctoId);

        if (($row['status'] ?? '') === self::STATUS_ACTIVE) {
            throw ValidationException::forField('status', 'This CTO application is already approved.');
        }

        $dates = $this->dates($row);

        return $this->store->transaction(function () use ($row, $ctoId, $dates, $username): array {
            $this->markDtr((string) ($row['employee_id'] ?? ''), (string) ($row['office_id'] ?? ''), $dates);

            $values = ['status' => self::STATUS_ACTIVE];

            if ($username !== null && $username !== '') {
                $values['approved_by'] = $username;
            }

            $this->store->update(LeaveDataset::CTO, $values, ['id' => $ctoId]);

            return $values + $row;
        });
    }

    /**
     * Removes a pending CTO application.
     */
    public function cancel(int|string $ctoId): bool
    {
        $row = $this->require($ctoId);

        if (($row['status'] ?? '') === self::STATUS_ACTIVE) {
            throw ValidationException::forField('status', 'An approved CTO application cannot be cancelled.');
        }

        $this->store->delete(LeaveDataset::CTO, ['id' => $ctoId]);

        return true;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int|string $ctoId): ?array
    {
        return $this->store->first(LeaveDataset::CTO, ['id' => $ctoId]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function pending(string $officeId = ''): array
    {
        $conditions = ['status' => self::STATUS_PENDING];

        if ($officeId !== '') {
            $conditions['office_id'] = $officeId;
        }

        return $this->store->rows(LeaveDataset::CTO, $conditions, ['orderBy' => ['id' => 'ASC']]);
    }

    /**
     * The number of CTO days earned minus spent, from the active rows.
     */
    public function balance(string $employeeId): float
    {
        $balance = 0.0;

        foreach ($this->store->rows(LeaveDataset::CTO, ['employee_id' => $employeeId, 'status' => self::STATUS_ACTIVE]) as $row) {
            $days = (float) ($row['days'] ?? 0);
            $balance += ($row['type'] ?? '') === self::TYPE_EARNED ? $days : -$days;
        }

        return round($balance, 3);
    }

    /**
     * Counts CTO days, skipping weekends and holidays. A half day counts 0.5.
     *
     * @param list<string> $dates full dates or half days ("Y-m-d am")
     */
    private function count(array $dates): float
    {
        $days = 0.0;

        foreach ($dates as $date) {
            $day = substr($date, 0, 10);
            $half = strlen($date) > 10 ? 0.5 : 1.0;

            $days += $this->days->count($day, $day) > 0 ? $half : 0.0;
        }

        return $days;
    }

    /**
     * @param list<string> $dates
     */
    private function multiple(array $dates): string
    {
        $tokens = [];

        foreach ($dates as $date) {
            $day = (int) substr($date, 8, 2);

            if (strlen($date) > 10) {
                $tokens[] = $day . trim(substr($date, 10));
                continue;
            }

            $tokens[] = (string) $day;
        }

        return implode('-', $tokens);
    }

    /**
     * Marks the CTO dates in the DTR: a whole day replaces the row with
     * "offset" in all four columns, a half day fills the AM or PM pair.
     *
     * @param list<string> $dates
     */
    private function markDtr(string $employeeId, string $officeId, array $dates): void
    {
        if ($employeeId === '') {
            throw ValidationException::forField('employee_id', 'An employee id is required.');
        }

        foreach ($dates as $date) {
            if (strlen($date) > 10) {
                $day = substr($date, 0, 10);
                $meridiem = strtolower(trim(substr($date, 10)));

                $columns = $meridiem === 'am'
                    ? ['am_login' => 'offset', 'am_logout' => 'offset']
                    : ['pm_login' => 'offset', 'pm_logout' => 'offset'];

                $this->writeDtrRow($employeeId, $officeId, $day, $columns);

                continue;
            }

            $this->store->delete(LeaveDataset::DTR, ['employee_id' => $employeeId, 'log_date' => $date]);

            $this->store->insert(LeaveDataset::DTR, [
                'employee_id' => $employeeId,
                'office_id' => $officeId,
                'log_date' => $date,
                'am_login' => 'offset',
                'am_logout' => 'offset',
                'pm_login' => 'offset',
                'pm_logout' => 'offset',
            ]);
        }
    }

    /**
     * @param array<string, string> $columns
     */
    private function writeDtrRow(string $employeeId, string $officeId, string $date, array $columns): void
    {
        $existing = $this->store->first(LeaveDataset::DTR, ['employee_id' => $employeeId, 'log_date' => $date]);

        if ($existing !== null) {
            $this->store->update(LeaveDataset::DTR, $columns, ['employee_id' => $employeeId, 'log_date' => $date]);

            return;
        }

        $this->store->insert(LeaveDataset::DTR, $columns + [
            'employee_id' => $employeeId,
            'office_id' => $officeId,
            'log_date' => $date,
        ]);
    }

    /**
     * @param list<string> $dates
     */
    private function normaliseDates(CtoRequest $request): array
    {
        if (trim($request->employeeId) === '') {
            throw ValidationException::forField('employee_id', 'An employee id is required.');
        }

        if ($request->dates === []) {
            throw ValidationException::forField('dates', 'At least one CTO date is required.');
        }

        $normalised = [];

        foreach ($request->dates as $date) {
            $value = trim($date);
            $half = '';

            if (preg_match('/^(.*)\s+(am|pm)$/i', $value, $matches) === 1) {
                $value = trim($matches[1]);
                $half = ' ' . strtolower($matches[2]);
            }

            $normalised[] = Calendar::date($value) . $half;
        }

        return $normalised;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return list<string>
     */
    private function dates(array $row): array
    {
        $first = sprintf(
            '%04d-%02d-01',
            (int) ($row['year'] ?? 0),
            (int) ($row['month'] ?? 0),
        );

        $dates = [];

        foreach (explode('-', (string) ($row['dates'] ?? '')) as $token) {
            $token = trim($token);

            foreach (['am', 'pm'] as $meridiem) {
                if (str_ends_with($token, $meridiem)) {
                    $dates[] = substr($first, 0, 8) . str_pad((string) (int) substr($token, 0, -2), 2, '0', STR_PAD_LEFT) . ' ' . $meridiem;
                    continue 2;
                }
            }

            if ($token !== '' && ctype_digit($token)) {
                $dates[] = substr($first, 0, 8) . str_pad($token, 2, '0', STR_PAD_LEFT);
            }
        }

        return $dates;
    }

    /**
     * @return array<string, mixed>
     */
    private function require(int|string $ctoId): array
    {
        $row = $this->store->first(LeaveDataset::CTO, ['id' => $ctoId]);

        if ($row === null) {
            throw ValidationException::forField('id', sprintf('No CTO application found with id %s.', (string) $ctoId));
        }

        return $row;
    }
}
