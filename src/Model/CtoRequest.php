<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Model;

/**
 * A compensatory time off application: the days are always "spent" days and
 * approval marks them in the DTR.
 */
final class CtoRequest
{
    /**
     * @param list<string> $dates dates in Y-m-d form, or "Y-m-d am" / "Y-m-d pm" for a half day
     */
    public function __construct(
        public readonly string $employeeId,
        public readonly array $dates,
        public readonly string $officeId = '',
        public readonly ?float $days = null,
        public readonly bool $allowWeekends = false,
        public readonly string $username = '',
    ) {
    }

    /**
     * @param array<string, mixed> $rows
     */
    public static function fromArray(array $rows): self
    {
        $dates = $rows['dates'] ?? [];
        $multiple = $rows['multiple'] ?? null;

        if ($multiple !== null && $dates === []) {
            $dates = is_array($multiple) ? $multiple : explode(',', (string) $multiple);
        }

        return new self(
            employeeId: (string) ($rows['employee_id'] ?? ''),
            dates: array_values(array_filter(array_map('trim', array_map('strval', (array) $dates)))),
            officeId: (string) ($rows['office_id'] ?? ''),
            days: isset($rows['days']) && $rows['days'] !== '' ? (float) $rows['days'] : null,
            allowWeekends: (bool) ($rows['allow_weekends'] ?? $rows['allow_sat_sun'] ?? false),
            username: (string) ($rows['username'] ?? ''),
        );
    }
}
