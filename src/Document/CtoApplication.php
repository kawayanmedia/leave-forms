<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Document;

use KawayanMedia\LeaveForms\Model\Employee;
use KawayanMedia\LeaveForms\Model\Office;
use KawayanMedia\LeaveForms\Model\Signatory;

/**
 * Application for compensatory time off. The form is printed twice per page,
 * so the renderer draws this document twice.
 */
final class CtoApplication implements PrintableDocument
{
    public function __construct(
        public readonly Employee $employee,
        public readonly Office $office = new Office(),
        public readonly string $applicationId = '',
        public readonly float $days = 0.0,
        public readonly string $month = '',
        public readonly string $dates = '',
        public readonly string $year = '',
        public readonly float $salary = 0.0,
        public readonly float $balance = 0.0,
        public readonly float $earned = 0.0,
        public readonly float $spent = 0.0,
        public readonly mixed $filedOn = null,
        public readonly ?Signatory $officeHead = null,
        public readonly ?Signatory $certifiedBy = null,
    ) {
    }

    public function layoutKey(): string
    {
        return 'cto-application';
    }

    public function totalBalance(): float
    {
        return ($this->balance + $this->earned) - $this->spent;
    }

    public function daysLabel(): string
    {
        return $this->days . ' ' . ($this->days > 1 ? 'days' : 'day');
    }

    /**
     * @param array<string, mixed> $rows
     */
    public static function fromArray(array $rows): self
    {
        $employee = $rows['employee'] ?? [];
        $office = $rows['office'] ?? [];
        $head = $rows['office_head'] ?? null;

        return new self(
            employee: $employee instanceof Employee ? $employee : Employee::fromArray((array) $employee),
            office: $office instanceof Office ? $office : Office::fromArray((array) $office),
            applicationId: (string) ($rows['application_id'] ?? $rows['id'] ?? ''),
            days: (float) ($rows['days'] ?? 0),
            month: (string) ($rows['month'] ?? ''),
            dates: (string) ($rows['dates'] ?? ''),
            year: (string) ($rows['year'] ?? ''),
            salary: (float) ($rows['salary'] ?? 0),
            balance: (float) ($rows['balance'] ?? 0),
            earned: (float) ($rows['earned'] ?? 0),
            spent: (float) ($rows['spent'] ?? 0),
            filedOn: $rows['filed_on'] ?? $rows['date_file'] ?? null,
            officeHead: $head === null ? null : Signatory::from($head),
            certifiedBy: isset($rows['certified_by']) ? Signatory::from($rows['certified_by']) : null,
        );
    }
}
