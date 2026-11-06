<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Contract;

use KawayanMedia\LeaveForms\Document\LeaveCertification;

/**
 * Loads everything the employee id document needs.
 */
interface LeaveCertificationRepositoryInterface
{
    public function find(string $employeeId): ?LeaveCertification;
}
