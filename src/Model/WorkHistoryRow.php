<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Model;

use KawayanMedia\LeaveForms\Support\Dates;
use KawayanMedia\LeaveForms\Support\Text;

/**
 * Row of the legacy pds_work_experience table, as printed on a service record.
 */
final class WorkHistoryRow
{
    public function __construct(
        public readonly mixed $dateFrom = null,
        public readonly mixed $dateTo = null,
        public readonly string $position = '',
        public readonly string $company = '',
        public readonly string $movement = '',
        public readonly string $status = '',
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            dateFrom: $row['inclusive_date_from'] ?? null,
            dateTo: $row['inclusive_date_to'] ?? null,
            position: Text::blank($row['position'] ?? ''),
            company: Text::blank($row['company'] ?? ''),
            movement: Text::blank($row['movement'] ?? $row['movement_code'] ?? ''),
            status: Text::blank($row['status'] ?? ''),
        );
    }

    public function dateFromLabel(): string
    {
        return Dates::slashDate($this->dateFrom);
    }

    public function dateToLabel(string $presentLabel = 'Present'): string
    {
        if (Dates::isFilled($this->dateTo)) {
            return Dates::slashDate($this->dateTo);
        }

        return is_string($this->dateTo) && strcasecmp(trim($this->dateTo), 'present') === 0
            ? $presentLabel
            : '';
    }
}
