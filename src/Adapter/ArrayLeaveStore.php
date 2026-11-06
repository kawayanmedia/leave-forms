<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Adapter;

use KawayanMedia\LeaveForms\Contract\LeaveStoreInterface;
use KawayanMedia\Persistence\Support\Predicate;

/**
 * Array backed leave store for tests, examples and dry runs.
 *
 * It assigns an auto-increment `id` to every inserted row that has none, so the
 * services can hand back the id of a filed application just like a database
 * would.
 */
final class ArrayLeaveStore implements LeaveStoreInterface
{
    /**
     * @var array<string, list<array<string, mixed>>>
     */
    private array $rows = [];

    /**
     * @var array<string, int>
     */
    private array $sequences = [];

    /**
     * @param array<string, list<array<string, mixed>>> $data dataset => rows
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $dataset => $rows) {
            $this->rows[$dataset] = array_values($rows);
        }
    }

    public function rows(string $dataset, array $conditions = [], array $options = []): array
    {
        $rows = $this->rows[$dataset] ?? [];

        if ($conditions !== []) {
            $rows = array_values(array_filter(
                $rows,
                static fn (array $row): bool => Predicate::matches($conditions, $row),
            ));
        }

        $orderBy = $options['orderBy'] ?? [];

        if ($orderBy !== []) {
            usort($rows, static function (array $left, array $right) use ($orderBy): int {
                foreach ($orderBy as $column => $direction) {
                    $result = Predicate::compare($left[$column] ?? null, $right[$column] ?? null);

                    if ($result !== 0) {
                        return strtoupper((string) $direction) === 'DESC' ? -$result : $result;
                    }
                }

                return 0;
            });
        }

        $offset = (int) ($options['offset'] ?? 0);
        $limit = $options['limit'] ?? null;

        if ($offset > 0 || $limit !== null) {
            $rows = array_slice($rows, $offset, $limit === null ? null : (int) $limit);
        }

        return array_values($rows);
    }

    public function first(string $dataset, array $conditions = []): ?array
    {
        return $this->rows($dataset, $conditions, ['limit' => 1])[0] ?? null;
    }

    public function insert(string $dataset, array $values): int|string|null
    {
        if (!array_key_exists('id', $values)) {
            $values = ['id' => $this->nextId($dataset)] + $values;
        }

        $this->rows[$dataset][] = $values;

        return $values['id'];
    }

    public function update(string $dataset, array $values, array $conditions = []): int
    {
        $affected = 0;

        foreach ($this->rows[$dataset] ?? [] as $index => $row) {
            if (!Predicate::matches($conditions, $row)) {
                continue;
            }

            $this->rows[$dataset][$index] = array_merge($row, $values);
            $affected++;
        }

        return $affected;
    }

    public function delete(string $dataset, array $conditions = []): int
    {
        $kept = [];
        $affected = 0;

        foreach ($this->rows[$dataset] ?? [] as $row) {
            if (Predicate::matches($conditions, $row)) {
                $affected++;
                continue;
            }

            $kept[] = $row;
        }

        $this->rows[$dataset] = $kept;

        return $affected;
    }

    public function transaction(callable $operation): mixed
    {
        $rows = $this->rows;
        $sequences = $this->sequences;

        try {
            return $operation($this);
        } catch (\Throwable $exception) {
            $this->rows = $rows;
            $this->sequences = $sequences;

            throw $exception;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function dump(string $dataset): array
    {
        return $this->rows[$dataset] ?? [];
    }

    private function nextId(string $dataset): int
    {
        $current = $this->sequences[$dataset] ?? 0;

        foreach ($this->rows[$dataset] ?? [] as $row) {
            $current = max($current, (int) ($row['id'] ?? 0));
        }

        $current++;
        $this->sequences[$dataset] = $current;

        return $current;
    }
}
