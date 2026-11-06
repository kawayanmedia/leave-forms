<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Contract;

use KawayanMedia\LeaveForms\Document\MoneyValue;

/**
 * Loads everything the employee id document needs.
 */
interface MoneyValueRepositoryInterface
{
    public function find(string $employeeId): ?MoneyValue;
}
