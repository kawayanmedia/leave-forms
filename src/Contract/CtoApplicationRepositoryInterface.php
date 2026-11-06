<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Contract;

use KawayanMedia\LeaveForms\Document\CtoApplication;

/**
 * Loads everything the compensatory time off application id document needs.
 */
interface CtoApplicationRepositoryInterface
{
    public function find(string $applicationId): ?CtoApplication;
}
