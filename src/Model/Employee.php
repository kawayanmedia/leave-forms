<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Model;

use KawayanMedia\LeaveForms\Support\Text;

/**
 * The subset of the employee row every document prints.
 */
final class Employee
{
    public function __construct(
        public readonly string $id = '',
        public readonly string $salutation = '',
        public readonly string $firstName = '',
        public readonly string $middleName = '',
        public readonly string $lastName = '',
        public readonly string $position = '',
        public readonly string $officeId = '',
        public readonly string $detailedOfficeId = '',
        public readonly string $salaryGrade = '',
        public readonly string $step = '',
        public readonly mixed $birthDate = null,
        public readonly mixed $firstDayOfService = null,
        public readonly ?string $officeHead = null,
        public readonly ?string $officeHeadPosition = null,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            id: Text::blank($row['id'] ?? $row['employee_id'] ?? ''),
            salutation: Text::blank($row['salut'] ?? ''),
            firstName: Text::blank($row['fname'] ?? ''),
            middleName: Text::blank($row['mname'] ?? ''),
            lastName: Text::blank($row['lname'] ?? ''),
            position: Text::blank($row['position'] ?? ''),
            officeId: Text::blank($row['office_id'] ?? ''),
            detailedOfficeId: Text::blank($row['detailed_office_id'] ?? ''),
            salaryGrade: Text::blank($row['salary_grade'] ?? ''),
            step: Text::blank($row['step'] ?? ''),
            birthDate: $row['birth_date'] ?? null,
            firstDayOfService: $row['first_day_of_service'] ?? null,
            officeHead: isset($row['office_head']) ? Text::blank($row['office_head']) : null,
            officeHeadPosition: isset($row['office_head_position']) ? Text::blank($row['office_head_position']) : null,
        );
    }

    /**
     * Legacy service record: "Dela Cruz, Juan Santos".
     */
    public function lastFirst(): string
    {
        return trim($this->lastName . ', ' . $this->firstName . ' ' . $this->middleName);
    }

    /**
     * Legacy name-with-salutation block: "Mr. Juan Santos Dela Cruz".
     */
    public function salutationName(): string
    {
        return trim(implode(' ', array_filter([
            $this->salutation,
            Text::titleCase($this->firstName),
            Text::titleCase($this->middleName),
            Text::titleCase($this->lastName),
        ], static fn (string $part): bool => $part !== '')));
    }

    /**
     * Legacy money value / retirement schedule: "Juan S. Dela Cruz".
     */
    public function middleInitialName(): string
    {
        $parts = array_filter([$this->firstName, Text::initial($this->middleName), $this->lastName]);

        return trim(implode(' ', $parts));
    }

    /**
     * Applicant block on the leave and CTO forms.
     */
    public function firstMiddleLast(): string
    {
        return trim(implode(' ', array_filter([
            Text::titleCase($this->firstName),
            Text::titleCase($this->middleName),
            Text::titleCase($this->lastName),
        ], static fn (string $part): bool => $part !== '')));
    }

    public function middleInitial(): string
    {
        return Text::initial($this->middleName);
    }
}
