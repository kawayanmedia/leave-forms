<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Contract;

use KawayanMedia\LeaveForms\Document\RetirementSchedule;

/**
 * Loads everything the employee id document needs.
 */
interface RetirementScheduleRepositoryInterface
{
    public function find(string $employeeId): ?RetirementSchedule;
}
