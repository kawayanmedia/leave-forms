<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Contract;

use KawayanMedia\LeaveForms\Document\StatementOfLeave;

/**
 * Loads everything the employee id document needs.
 */
interface StatementOfLeaveRepositoryInterface
{
    public function find(string $employeeId): ?StatementOfLeave;
}
