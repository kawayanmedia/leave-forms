<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Document;

use KawayanMedia\LeaveForms\Model\Employee;
use KawayanMedia\LeaveForms\Model\Signatory;
use KawayanMedia\LeaveForms\Support\Money;

/**
 * Computation of the money value of the leave credits of a retiring employee.
 */
final class MoneyValue implements PrintableDocument
{
    public function __construct(
        public readonly Employee $employee,
        public readonly float $salary = 0.0,
        public readonly float $leaveBalance = 0.0,
        public readonly ?Signatory $prepared = null,
        public readonly ?Signatory $certified = null,
        public readonly string $period = '',
        public readonly mixed $retirementDate = null,
    ) {
    }

    public function layoutKey(): string
    {
        return 'money-value';
    }

    public function totalMoney(): float
    {
        return Money::monetize($this->salary, $this->leaveBalance);
    }

    /**
     * @param array<string, mixed> $rows
     */
    public static function fromArray(array $rows): self
    {
        $employee = $rows['employee'] ?? [];

        return new self(
            employee: $employee instanceof Employee ? $employee : Employee::fromArray((array) $employee),
            salary: (float) ($rows['salary'] ?? 0),
            leaveBalance: (float) ($rows['leave_balance'] ?? 0),
            prepared: isset($rows['prepared']) ? Signatory::from($rows['prepared']) : null,
            certified: isset($rows['certified']) ? Signatory::from($rows['certified']) : null,
            period: (string) ($rows['period'] ?? ''),
            retirementDate: $rows['retirement_date'] ?? null,
        );
    }
}
