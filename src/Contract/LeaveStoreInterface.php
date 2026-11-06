<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Contract;

/**
 * Persistence seam of the leave write side.
 *
 * Datasets are named with {@see \KawayanMedia\LeaveForms\Support\LeaveDataset}
 * and resolved to tables by {@see \KawayanMedia\LeaveForms\Config\LeaveTables}.
 * Conditions use the shapes of kawayanmedia/persistence: a column => value map,
 * [column, operator, value] tuples or Condition objects.
 */
interface LeaveStoreInterface
{
    /**
     * @param array<mixed>                                                               $conditions
     * @param array{orderBy?: array<string, string>, limit?: int|null, offset?: int|null} $options
     *
     * @return list<array<string, mixed>>
     */
    public function rows(string $dataset, array $conditions = [], array $options = []): array;

    /**
     * @param array<mixed> $conditions
     *
     * @return array<string, mixed>|null
     */
    public function first(string $dataset, array $conditions = []): ?array;

    /**
     * @param array<string, mixed> $values
     *
     * @return int|string|null the new row id when the adapter can report one
     */
    public function insert(string $dataset, array $values): int|string|null;

    /**
     * @param array<string, mixed> $values
     * @param array<mixed>         $conditions
     *
     * @return int affected rows
     */
    public function update(string $dataset, array $values, array $conditions = []): int;

    /**
     * @param array<mixed> $conditions
     *
     * @return int affected rows
     */
    public function delete(string $dataset, array $conditions = []): int;

    /**
     * @template T
     *
     * @param callable(self): T $operation
     *
     * @return T
     */
    public function transaction(callable $operation): mixed;
}
