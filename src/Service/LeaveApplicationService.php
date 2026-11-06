<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Service;

use KawayanMedia\LeaveForms\Contract\LeaveStoreInterface;
use KawayanMedia\LeaveForms\Exception\ValidationException;
use KawayanMedia\LeaveForms\Model\LeaveApplicationRequest;
use KawayanMedia\LeaveForms\Model\LeaveStatus;
use KawayanMedia\LeaveForms\Support\LeaveBalanceCalculator;
use KawayanMedia\LeaveForms\Support\LeaveDataset;
use KawayanMedia\LeaveForms\Support\LeaveDays;

/**
 * Files leave applications and moves them through the legacy approval flow:
 * 0 pending, 1 approved, 2 disapproved.
 *
 * Approval posts the spending row to the leave card and, unless disabled,
 * writes the manual log row that covers the dates in the DTR.
 */
final class LeaveApplicationService
{
    /**
     * Leave types the legacy code treated as vacation leave (they spend v_abs).
     */
    public const VACATION_TYPES = [1, 3, 21];

    /**
     * Leave types the legacy code treated as sick leave (they spend s_abs).
     */
    public const SICK_TYPES = [2];

    /**
     * Leave types that never touch either balance.
     */
    public const NON_DEDUCTIBLE_TYPES = [4, 5, 6, 24];

    public function __construct(
        private readonly LeaveStoreInterface $store,
        private readonly LeaveDays $days = new LeaveDays(),
        private readonly LeaveBalanceCalculator $calculator = new LeaveBalanceCalculator(),
        private readonly bool $writeManualLog = true,
        private readonly bool $enforceBalance = false,
    ) {
    }

    /**
     * Files an application and returns its id.
     */
    public function file(LeaveApplicationRequest $request): int|string|null
    {
        $this->validate($request);

        $row = $this->row($request);

        $duplicate = $this->store->first(LeaveDataset::APPLICATIONS, [
            'employee_id' => $request->employeeId,
            'leave_type_id' => $request->leaveTypeId,
            'month' => $row['month'],
            'multiple' => $row['multiple'],
            'year' => $row['year'],
            'approved' => LeaveStatus::PENDING,
        ]);

        if ($duplicate !== null) {
            throw ValidationException::forField(
                'dates',
                'An identical application is already waiting for approval.',
            );
        }

        return $this->store->insert(LeaveDataset::APPLICATIONS, $row);
    }

    /**
     * Approves a pending application: the leave card row and the DTR cover are
     * written in one transaction, then the status becomes approved.
     *
     * @return array<string, mixed> the updated application row
     */
    public function approve(int|string $applicationId, ?string $username = null): array
    {
        $application = $this->require($applicationId);

        if ((int) ($application['approved'] ?? 0) === LeaveStatus::APPROVED) {
            throw ValidationException::forField('approved', 'This application is already approved.');
        }

        $days = (float) ($application['days'] ?? 0);
        $leaveTypeId = (int) ($application['leave_type_id'] ?? 0);

        if ($days <= 0) {
            $days = $this->days($application);
        }

        if ($this->enforceBalance) {
            $this->assertBalance($application, $days);
        }

        return $this->store->transaction(function () use ($application, $applicationId, $days, $leaveTypeId, $username): array {
            $this->postToCard($application, $days);

            if ($this->writeManualLog) {
                $this->postManualLog($application, $username);
            }

            $values = ['approved' => LeaveStatus::APPROVED, 'days' => $days];

            if ($username !== null && $username !== '') {
                $values['approved_by'] = $username;
            }

            $this->store->update(
                LeaveDataset::APPLICATIONS,
                $values,
                ['id' => $applicationId],
            );

            return $values + $application;
        });
    }

    /**
     * Marks a pending application as disapproved.
     *
     * @return array<string, mixed> the updated application row
     */
    public function disapprove(int|string $applicationId, ?string $username = null): array
    {
        $application = $this->require($applicationId);

        if ((int) ($application['approved'] ?? 0) === LeaveStatus::APPROVED) {
            throw ValidationException::forField('approved', 'An approved application cannot be disapproved; cancel it instead.');
        }

        $values = ['approved' => LeaveStatus::DISAPPROVED];

        if ($username !== null && $username !== '') {
            $values['approved_by'] = $username;
        }

        $this->store->update(LeaveDataset::APPLICATIONS, $values, ['id' => $applicationId]);

        return $values + $application;
    }

    /**
     * Cancels an application: pending rows are deleted, approved rows also
     * remove the leave card entry and the DTR cover they created.
     */
    public function cancel(int|string $applicationId): bool
    {
        $application = $this->require($applicationId);

        $this->store->transaction(function () use ($application, $applicationId): void {
            if ((int) ($application['approved'] ?? 0) === LeaveStatus::APPROVED) {
                $this->store->delete(LeaveDataset::CARD, [
                    'employee_id' => $application['employee_id'] ?? '',
                    'action_take' => $this->actionTaken($application),
                ]);

                $this->store->delete(LeaveDataset::MANUAL_LOG, [
                    'employee_id' => $application['employee_id'] ?? '',
                    'notes' => $this->actionTaken($application),
                ]);
            }

            $this->store->delete(LeaveDataset::APPLICATIONS, ['id' => $applicationId]);
        });

        return true;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int|string $applicationId): ?array
    {
        return $this->store->first(LeaveDataset::APPLICATIONS, ['id' => $applicationId]);
    }

    /**
     * Applications waiting for a decision, optionally limited to one office.
     *
     * @return list<array<string, mixed>>
     */
    public function pending(string $officeId = ''): array
    {
        $conditions = ['approved' => LeaveStatus::PENDING];

        if ($officeId !== '') {
            $conditions['office_id'] = $officeId;
        }

        return $this->store->rows(LeaveDataset::APPLICATIONS, $conditions, ['orderBy' => ['date_encode' => 'ASC']]);
    }

    /**
     * The VL and SL balances an employee holds, folded from the enabled leave
     * card rows the same way the legacy leave card screen did.
     *
     * @param string $asOf Y-m-d limit, empty for every row
     *
     * @return array{vacation: float, sick: float}
     */
    public function balances(string $employeeId, string $asOf = ''): array
    {
        $conditions = [
            ['employee_id', '=', $employeeId],
            ['enabled', '=', 1],
        ];

        if ($asOf !== '') {
            $conditions[] = ['date', '<=', LeaveDays::parse($asOf)->format('Y-m-d')];
        }

        return $this->calculator->fold(
            $this->store->rows(LeaveDataset::CARD, $conditions, ['orderBy' => ['id' => 'ASC']]),
        );
    }

    private function validate(LeaveApplicationRequest $request): void
    {
        $errors = [];

        if (trim($request->employeeId) === '') {
            $errors['employee_id'] = 'An employee id is required.';
        }

        if ($request->leaveTypeId <= 0) {
            $errors['leave_type_id'] = 'A leave type is required.';
        }

        if ($request->fromDate === '' || $request->toDate === '') {
            $errors['dates'] = 'Both the start and the end date are required.';
        }

        if ($errors === []) {
            $from = LeaveDays::parse($request->fromDate);
            $to = LeaveDays::parse($request->toDate);

            if ($to < $from) {
                $errors['dates'] = 'The end date cannot be before the start date.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withErrors($errors);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function row(LeaveApplicationRequest $request): array
    {
        $from = LeaveDays::parse($request->fromDate);
        $to = LeaveDays::parse($request->toDate);

        $days = $request->days ?? $this->days->count(
            $request->fromDate,
            $request->toDate,
            $request->allowWeekends,
            $request->leaveTypeId,
            $request->halfDayStart,
            $request->halfDayEnd,
        );

        if ($days <= 0) {
            throw ValidationException::forField(
                'days',
                'The selected dates fall on weekends or holidays, so no leave day is counted.',
            );
        }

        $multiple = $from->format('j');
        $month = $from->format('m');
        $year = $from->format('Y');

        $row = [
            'employee_id' => $request->employeeId,
            'office_id' => $request->officeId,
            'multiple' => $multiple,
            'month' => $month,
            'year' => $year,
            'month5' => '',
            'year5' => '',
            'multiple5' => '',
            'leave_type_id' => $request->leaveTypeId,
            'special_priv_id' => $request->specialPrivilegeId,
            'days' => $days,
            'mone' => $request->mone,
            'date_encode' => date('Y-m-d'),
            'allow_sat_sun' => $request->allowWeekends ? 1 : 0,
            'username' => $request->username,
            'approved' => LeaveStatus::PENDING,
        ];

        if ($month !== $to->format('m') || $year !== $to->format('Y')) {
            $row['month5'] = $to->format('m');
            $row['year5'] = $to->format('Y');
            $row['multiple5'] = $to->format('j');
        } elseif ($multiple !== $to->format('j')) {
            $row['multiple'] = $multiple . '-' . $to->format('j');
        }

        return $row;
    }

    /**
     * @param array<string, mixed> $application
     */
    private function days(array $application): float
    {
        return $this->days->count(
            $this->startDate($application),
            $this->endDate($application),
            (int) ($application['allow_sat_sun'] ?? 0) === 1,
            (int) ($application['leave_type_id'] ?? 0),
        );
    }

    private function date(string $year, string $month, string $day): string
    {
        $day = trim($day);

        // Half days are stored as "3am" or "3pm"; they are still one date.
        if (preg_match('/^(\d{1,2})(am|pm)$/', strtolower($day), $matches) === 1) {
            $day = $matches[1];
        }

        return sprintf('%04d-%02d-%02d', (int) $year, (int) $month, (int) $day);
    }

    /**
     * @param array<string, mixed> $application
     */
    private function postToCard(array $application, float $days): void
    {
        $leaveTypeId = (int) ($application['leave_type_id'] ?? 0);

        if (in_array($leaveTypeId, self::NON_DEDUCTIBLE_TYPES, true)) {
            return;
        }

        $actionTaken = $this->actionTaken($application);
        $employeeId = (string) ($application['employee_id'] ?? '');

        $values = [
            'employee_id' => $employeeId,
            'particulars' => $this->particulars($application, $days),
            'action_take' => $actionTaken,
            'date' => $this->cardDate($application),
            'max_leave_date' => $this->cardDate($application),
            'leave_type_id' => $leaveTypeId,
            'manual_log_id' => $application['manual_log_id'] ?? null,
        ];

        if (in_array($leaveTypeId, self::SICK_TYPES, true)) {
            $values['s_abs'] = $days;
        } else {
            $values['v_abs'] = $days;
        }

        if (($application['special_priv_id'] ?? '') !== '') {
            $values['special_priv_id'] = $application['special_priv_id'];
        }

        $existing = $this->store->first(LeaveDataset::CARD, [
            'employee_id' => $employeeId,
            'action_take' => $actionTaken,
        ]);

        if ($existing !== null) {
            $unused = in_array($leaveTypeId, self::SICK_TYPES, true) ? 'v_abs' : 's_abs';

            $this->store->update(
                LeaveDataset::CARD,
                $values + [$unused => null],
                ['employee_id' => $employeeId, 'action_take' => $actionTaken],
            );

            return;
        }

        $this->store->insert(LeaveDataset::CARD, $values);
    }

    /**
     * @param array<string, mixed> $application
     */
    private function postManualLog(array $application, ?string $username): void
    {
        $dates = $this->coverDates($application);

        if ($dates === []) {
            return;
        }

        $employeeId = (string) ($application['employee_id'] ?? '');
        $notes = $this->actionTaken($application);

        $existing = $this->store->first(LeaveDataset::MANUAL_LOG, [
            'employee_id' => $employeeId,
            'notes' => $notes,
        ]);

        if ($existing !== null) {
            return;
        }

        $this->store->insert(LeaveDataset::MANUAL_LOG, [
            'employee_id' => $employeeId,
            'office_id' => $application['office_id'] ?? null,
            'cover_if_ob_or_leave' => $dates[0],
            'cover_if_ob_or_leave2' => $dates[count($dates) - 1],
            'multiple' => 0,
            'log_type' => 2,
            'notes' => $notes,
            'username' => $username,
        ]);
    }

    /**
     * @param array<string, mixed> $application
     *
     * @return list<string>
     */
    private function coverDates(array $application): array
    {
        return $this->days->dates(
            $this->startDate($application),
            $this->endDate($application),
            (int) ($application['allow_sat_sun'] ?? 0) === 1,
            (int) ($application['leave_type_id'] ?? 0),
        );
    }

    /**
     * @param array<string, mixed> $application
     */
    private function actionTaken(array $application): string
    {
        $from = LeaveDays::parse($this->startDate($application));
        $to = LeaveDays::parse($this->endDate($application));

        $fromDay = $from->format('j');
        $toDay = $to->format('j');

        $days = $fromDay === $toDay ? $fromDay : $fromDay . '-' . $toDay;

        return sprintf('%s %s, %s', $from->format('F'), $days, $from->format('Y'));
    }

    /**
     * @param array<string, mixed> $application
     */
    private function cardDate(array $application): string
    {
        return $this->startDate($application);
    }

    /**
     * @param array<string, mixed> $application
     */
    private function particulars(array $application, float $days): string
    {
        $leaveTypeId = (int) ($application['leave_type_id'] ?? 0);

        $label = match (true) {
            in_array($leaveTypeId, self::SICK_TYPES, true) => 'SL',
            in_array($leaveTypeId, self::NON_DEDUCTIBLE_TYPES, true) => 'SPL',
            default => 'VL',
        };

        return $this->trimNumber($days) . ' ' . $label;
    }

    /**
     * @param array<string, mixed> $application
     */
    private function assertBalance(array $application, float $days): void
    {
        $leaveTypeId = (int) ($application['leave_type_id'] ?? 0);

        if (in_array($leaveTypeId, self::NON_DEDUCTIBLE_TYPES, true)) {
            return;
        }

        $balances = $this->balances((string) ($application['employee_id'] ?? ''));
        $available = in_array($leaveTypeId, self::SICK_TYPES, true)
            ? $balances['sick']
            : $balances['vacation'];

        if ($available < $days) {
            throw ValidationException::forField(
                'days',
                sprintf('Only %s leave day(s) are left but %s were filed.', $this->trimNumber($available), $this->trimNumber($days)),
            );
        }
    }

    private function trimNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
    }

    /**
     * The last date of an application, honouring a second range or a
     * "12-14" day range.
     *
     * @param array<string, mixed> $application
     */
    private function endDate(array $application): string
    {
        if ($this->filled($application, 'multiple5')) {
            return $this->date(
                $this->pick($application, 'year5', 'year'),
                $this->pick($application, 'month5', 'month'),
                (string) $application['multiple5'],
            );
        }

        $multiple = (string) ($application['multiple'] ?? '');

        if (str_contains($multiple, '-')) {
            return $this->date(
                (string) ($application['year'] ?? ''),
                (string) ($application['month'] ?? ''),
                substr($multiple, (int) strpos($multiple, '-') + 1),
            );
        }

        return $this->startDate($application);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function filled(array $row, string $key): bool
    {
        return trim((string) ($row[$key] ?? '')) !== '';
    }

    /**
     * Reads a column, falling back to another one when it is missing or blank.
     *
     * @param array<string, mixed> $row
     */
    private function pick(array $row, string $key, string $fallback): string
    {
        $value = trim((string) ($row[$key] ?? ''));

        return $value !== '' ? $value : trim((string) ($row[$fallback] ?? ''));
    }

    /**
     * The first date of an application, honouring a "12-14" day range.
     *
     * @param array<string, mixed> $application
     */
    private function startDate(array $application): string
    {
        $multiple = (string) $application['multiple'];

        if (str_contains($multiple, '-')) {
            $multiple = substr($multiple, 0, (int) strpos($multiple, '-'));
        }

        return $this->date(
            (string) ($application['year'] ?? ''),
            (string) ($application['month'] ?? ''),
            $multiple,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function require(int|string $applicationId): array
    {
        $application = $this->store->first(LeaveDataset::APPLICATIONS, ['id' => $applicationId]);

        if ($application === null) {
            throw ValidationException::forField(
                'id',
                sprintf('No leave application found with id %s.', (string) $applicationId),
            );
        }

        return $application;
    }
}
