<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Adapter;

use KawayanMedia\LeaveForms\Config\LeaveTables;
use KawayanMedia\LeaveForms\Contract\LeaveStoreInterface;
use KawayanMedia\Persistence\Contract\DatabaseInterface;
use KawayanMedia\Persistence\Contract\LastInsertIdAwareInterface;

/**
 * Stores leave data in any database reachable through {@see DatabaseInterface}.
 *
 * ```php
 * $store = new PdoLeaveStore(PdoDatabase::fromDsn('mysql:host=localhost;dbname=ihrmis', 'root', 'secret'));
 * (new LeaveApplicationService($store))->file($request);
 * ```
 */
final class PdoLeaveStore implements LeaveStoreInterface
{
    public function __construct(
        private readonly DatabaseInterface $database,
        private readonly LeaveTables $tables = new LeaveTables(),
    ) {
    }

    public function rows(string $dataset, array $conditions = [], array $options = []): array
    {
        return $this->database->select($this->tables->table($dataset), ['*'], $conditions, $options);
    }

    public function first(string $dataset, array $conditions = []): ?array
    {
        return $this->rows($dataset, $conditions, ['limit' => 1])[0] ?? null;
    }

    public function insert(string $dataset, array $values): int|string|null
    {
        $this->database->insert($this->tables->table($dataset), $values);

        return $this->database instanceof LastInsertIdAwareInterface
            ? $this->database->lastInsertId()
            : null;
    }

    public function update(string $dataset, array $values, array $conditions = []): int
    {
        return $this->database->update($this->tables->table($dataset), $values, $conditions);
    }

    public function delete(string $dataset, array $conditions = []): int
    {
        return $this->database->delete($this->tables->table($dataset), $conditions);
    }

    public function transaction(callable $operation): mixed
    {
        return $this->database->transaction(fn (): mixed => $operation($this));
    }

    public function tables(): LeaveTables
    {
        return $this->tables;
    }
}
