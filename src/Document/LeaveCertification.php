<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Document;

use KawayanMedia\LeaveForms\Model\Employee;
use KawayanMedia\LeaveForms\Model\LeaveBalance;
use KawayanMedia\LeaveForms\Model\Office;
use KawayanMedia\LeaveForms\Model\Signatory;

/**
 * Certificate of leave credits for one employee.
 */
final class LeaveCertification implements PrintableDocument
{
    public function __construct(
        public readonly Employee $employee,
        public readonly Office $office = new Office(),
        public readonly LeaveBalance $balance = new LeaveBalance(),
        public readonly ?Signatory $noticedBy = null,
        public readonly ?Signatory $notedBy = null,
        public readonly mixed $requestedOn = null,
        public readonly mixed $issuedOn = null,
    ) {
    }

    public function layoutKey(): string
    {
        return 'leave-certification';
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
            noticedBy: isset($rows['noticed_by']) ? Signatory::from($rows['noticed_by']) : null,
            notedBy: isset($rows['noted_by']) ? Signatory::from($rows['noted_by']) : null,
            requestedOn: $rows['requested_on'] ?? null,
            issuedOn: $rows['issued_on'] ?? null,
        );
    }
}
