<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Adapter;

use KawayanMedia\LeaveForms\Contract\MoneyValueRepositoryInterface;
use KawayanMedia\LeaveForms\Document\MoneyValue;

/**
 * In-memory repository for tests, examples and one-off scripts.
 */
final class ArrayMoneyValueRepository implements MoneyValueRepositoryInterface
{
    /**
     * @param array<string, MoneyValue|array<string, mixed>> $records
     */
    public function __construct(private array $records = [])
    {
    }

    public function find(string $employeeId): ?MoneyValue
    {
        $record = $this->records[$employeeId] ?? null;

        if ($record === null) {
            return null;
        }

        return $record instanceof MoneyValue ? $record : MoneyValue::fromArray($record);
    }

    /**
     * @param MoneyValue|array<string, mixed> $record
     */
    public function add(string $employeeId, MoneyValue|array $record): void
    {
        $this->records[$employeeId] = $record;
    }

    public function has(string $employeeId): bool
    {
        return array_key_exists($employeeId, $this->records);
    }

    /**
     * @return list<string>
     */
    public function ids(): array
    {
        return array_keys($this->records);
    }
}
