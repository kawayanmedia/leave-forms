<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Model;

/**
 * One period line of the statement of leave.
 */
final class LeaveYearRow
{
    public function __construct(
        public readonly string $period = '',
        public readonly float $vacationEarned = 0.0,
        public readonly float $sickEarned = 0.0,
        public readonly float $vacationSpent = 0.0,
        public readonly float $sickSpent = 0.0,
        public readonly float $vacationBalance = 0.0,
        public readonly float $sickBalance = 0.0,
    ) {
    }
}
