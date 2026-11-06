<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Model;

use KawayanMedia\LeaveForms\Support\Text;

/**
 * The office row: its display name and, when needed, its head.
 */
final class Office
{
    public function __construct(
        public readonly string $name = '',
        public readonly string $code = '',
        public readonly string $head = '',
        public readonly string $headPosition = '',
        public readonly string $salaryGradeType = '',
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            name: Text::blank($row['office_name'] ?? $row['name'] ?? ''),
            code: Text::blank($row['office_code'] ?? ''),
            head: Text::blank($row['office_head'] ?? ''),
            headPosition: Text::blank($row['position'] ?? $row['office_head_position'] ?? ''),
            salaryGradeType: Text::blank($row['salary_grade_type'] ?? ''),
        );
    }

    public static function fromName(string $name): self
    {
        return new self(name: $name);
    }
}
