<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Contract;

use KawayanMedia\LeaveForms\Document\ServiceRecord;

/**
 * Loads everything the employee id document needs.
 */
interface ServiceRecordRepositoryInterface
{
    public function find(string $employeeId): ?ServiceRecord;
}
