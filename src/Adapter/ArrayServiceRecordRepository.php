<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Adapter;

use KawayanMedia\LeaveForms\Contract\ServiceRecordRepositoryInterface;
use KawayanMedia\LeaveForms\Document\ServiceRecord;

/**
 * In-memory repository for tests, examples and one-off scripts.
 */
final class ArrayServiceRecordRepository implements ServiceRecordRepositoryInterface
{
    /**
     * @param array<string, ServiceRecord|array<string, mixed>> $records
     */
    public function __construct(private array $records = [])
    {
    }

    public function find(string $employeeId): ?ServiceRecord
    {
        $record = $this->records[$employeeId] ?? null;

        if ($record === null) {
            return null;
        }

        return $record instanceof ServiceRecord ? $record : ServiceRecord::fromArray($record);
    }

    /**
     * @param ServiceRecord|array<string, mixed> $record
     */
    public function add(string $employeeId, ServiceRecord|array $record): void
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
