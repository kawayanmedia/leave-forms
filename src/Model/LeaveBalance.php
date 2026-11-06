<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Model;

/**
 * Vacation plus sick leave credits, the pair every leave document prints.
 */
final class LeaveBalance
{
    public function __construct(
        public readonly float $vacation = 0.0,
        public readonly float $sick = 0.0,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            vacation: (float) ($row['vacation'] ?? $row['vacation_leave'] ?? 0),
            sick: (float) ($row['sick'] ?? $row['sick_leave'] ?? 0),
        );
    }

    public function total(): float
    {
        return $this->vacation + $this->sick;
    }
}
