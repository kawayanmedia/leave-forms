<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Contract;

use KawayanMedia\LeaveForms\Document\LeaveApplication;

/**
 * Loads everything the leave application id document needs.
 */
interface LeaveApplicationRepositoryInterface
{
    public function find(string $applicationId): ?LeaveApplication;
}
