<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Model;

use KawayanMedia\LeaveForms\Support\LeaveDays;

/**
 * A leave application as the caller describes it: who, what type, and from when
 * to when.
 *
 * The day count is worked out from the dates unless it is supplied, which is
 * what the legacy screen did for hospital leave and for entered day counts.
 */
final class LeaveApplicationRequest
{
    public function __construct(
        public readonly string $employeeId,
        public readonly int $leaveTypeId,
        public readonly string $fromDate,
        public readonly string $toDate,
        public readonly string $officeId = '',
        public readonly ?float $days = null,
        public readonly bool $allowWeekends = false,
        public readonly bool $halfDayStart = false,
        public readonly bool $halfDayEnd = false,
        public readonly string $specialPrivilegeId = '',
        public readonly string $username = '',
        public readonly string $mone = '',
    ) {
    }

    /**
     * @param array<string, mixed> $rows
     */
    public static function fromArray(array $rows): self
    {
        return new self(
            employeeId: (string) ($rows['employee_id'] ?? ''),
            leaveTypeId: (int) ($rows['leave_type_id'] ?? 0),
            fromDate: (string) ($rows['from_date'] ?? $rows['fromDate'] ?? ''),
            toDate: (string) ($rows['to_date'] ?? $rows['toDate'] ?? ''),
            officeId: (string) ($rows['office_id'] ?? ''),
            days: isset($rows['days']) && $rows['days'] !== '' ? (float) $rows['days'] : null,
            allowWeekends: (bool) ($rows['allow_weekends'] ?? $rows['allow_sat_sun'] ?? false),
            halfDayStart: (bool) ($rows['half_day_start'] ?? false),
            halfDayEnd: (bool) ($rows['half_day_end'] ?? false),
            specialPrivilegeId: (string) ($rows['special_privilege_id'] ?? $rows['special_priv_id'] ?? ''),
            username: (string) ($rows['username'] ?? ''),
            mone: (string) ($rows['mone'] ?? ''),
        );
    }

    /**
     * The "action taken" text the legacy screens wrote on the leave card, for
     * example "January 10-11, 2026".
     */
    public function actionTaken(): string
    {
        $from = LeaveDays::parse($this->fromDate);
        $to = LeaveDays::parse($this->toDate);
        $month = $from->format('F');
        $fromDay = $from->format('j');
        $toDay = $to->format('j');

        $days = $fromDay === $toDay ? $fromDay : $fromDay . '-' . $toDay;

        return sprintf('%s %s, %s', $month, $days, $from->format('Y'));
    }
}
