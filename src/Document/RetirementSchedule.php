<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Document;

use KawayanMedia\LeaveForms\Model\Employee;
use KawayanMedia\LeaveForms\Model\LeaveBalance;
use KawayanMedia\LeaveForms\Model\Office;
use KawayanMedia\LeaveForms\Model\Signatory;
use KawayanMedia\LeaveForms\Support\Money;

/**
 * Schedule of retirement pay (landscape).
 */
final class RetirementSchedule implements PrintableDocument
{
    public function __construct(
        public readonly Employee $employee,
        public readonly Office $office = new Office(),
        public readonly LeaveBalance $balance = new LeaveBalance(),
        public readonly float $salary = 0.0,
        public readonly string $lguName = '',
        public readonly string $period = '',
        public readonly mixed $retirementDate = null,
        public readonly ?Signatory $prepared = null,
        public readonly ?Signatory $approved = null,
        public readonly ?Signatory $certified = null,
        public readonly ?Signatory $attested = null,
        public readonly ?Signatory $availability = null,
        public readonly ?Signatory $noted = null,
        public readonly mixed $issuedOn = null,
    ) {
    }

    public function layoutKey(): string
    {
        return 'retirement-schedule';
    }

    public function totalLeave(): float
    {
        return $this->balance->total();
    }

    public function terminalLeave(): float
    {
        return Money::monetize($this->salary, $this->totalLeave());
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
            salary: (float) ($rows['salary'] ?? 0),
            lguName: (string) ($rows['lgu_name'] ?? ''),
            period: (string) ($rows['period'] ?? ''),
            retirementDate: $rows['retirement_date'] ?? null,
            prepared: isset($rows['prepared']) ? Signatory::from($rows['prepared']) : null,
            approved: isset($rows['approved']) ? Signatory::from($rows['approved']) : null,
            certified: isset($rows['certified']) ? Signatory::from($rows['certified']) : null,
            attested: isset($rows['attested']) ? Signatory::from($rows['attested']) : null,
            availability: isset($rows['availability']) ? Signatory::from($rows['availability']) : null,
            noted: isset($rows['noted']) ? Signatory::from($rows['noted']) : null,
            issuedOn: $rows['issued_on'] ?? null,
        );
    }
}
