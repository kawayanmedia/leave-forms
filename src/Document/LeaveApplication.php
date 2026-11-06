<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Document;

use KawayanMedia\LeaveForms\Model\Employee;
use KawayanMedia\LeaveForms\Model\LeaveBalance;
use KawayanMedia\LeaveForms\Model\Office;
use KawayanMedia\LeaveForms\Model\Signatory;

/**
 * Application for leave (the base CS form, not the LGU variants).
 */
final class LeaveApplication implements PrintableDocument
{
    public function __construct(
        public readonly Employee $employee,
        public readonly Office $office = new Office(),
        public readonly LeaveBalance $balance = new LeaveBalance(),
        public readonly string $applicationId = '',
        public readonly int $leaveTypeId = 0,
        public readonly string $leaveTypeName = '',
        public readonly float $days = 0.0,
        public readonly string $month = '',
        public readonly string $fromDay = '',
        public readonly string $year = '',
        public readonly string $secondMonth = '',
        public readonly string $secondFromDay = '',
        public readonly string $secondYear = '',
        public readonly float $salary = 0.0,
        public readonly mixed $filedOn = null,
        public readonly mixed $lastEarned = null,
        public readonly ?Signatory $certifiedBy = null,
        public readonly string $authorityOfMayor = '',
    ) {
    }

    public function layoutKey(): string
    {
        return 'leave-application';
    }

    public function totalBalance(): float
    {
        return $this->balance->total();
    }

    public function daysLabel(): string
    {
        return $this->days . ' ' . ($this->days > 1 ? 'days' : 'day');
    }

    public function hasSecondRange(): bool
    {
        return $this->secondFromDay !== '';
    }

    /**
     * @param array<string, mixed> $rows
     */
    public static function fromArray(array $rows): self
    {
        $employee = $rows['employee'] ?? [];
        $office = $rows['office'] ?? [];
        $balance = $rows['balance'] ?? [];

        return new self(
            employee: $employee instanceof Employee ? $employee : Employee::fromArray((array) $employee),
            office: $office instanceof Office ? $office : Office::fromArray((array) $office),
            balance: $balance instanceof LeaveBalance ? $balance : LeaveBalance::fromArray((array) $balance),
            applicationId: (string) ($rows['application_id'] ?? $rows['leave_apps_id'] ?? ''),
            leaveTypeId: (int) ($rows['leave_type_id'] ?? 0),
            leaveTypeName: (string) ($rows['leave_type'] ?? $rows['leave_name'] ?? ''),
            days: (float) ($rows['days'] ?? 0),
            month: (string) ($rows['month'] ?? ''),
            fromDay: (string) ($rows['from_day'] ?? $rows['multiple'] ?? ''),
            year: (string) ($rows['year'] ?? ''),
            secondMonth: (string) ($rows['second_month'] ?? $rows['month5'] ?? ''),
            secondFromDay: (string) ($rows['second_from_day'] ?? $rows['multiple5'] ?? ''),
            secondYear: (string) ($rows['second_year'] ?? $rows['year5'] ?? ''),
            salary: (float) ($rows['salary'] ?? 0),
            filedOn: $rows['filed_on'] ?? $rows['date_encode'] ?? null,
            lastEarned: $rows['last_earned'] ?? null,
            certifiedBy: isset($rows['certified_by']) ? Signatory::from($rows['certified_by']) : null,
            authorityOfMayor: (string) ($rows['authority_of_mayor'] ?? ''),
        );
    }
}
