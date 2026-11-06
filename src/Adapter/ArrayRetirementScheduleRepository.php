<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Adapter;

use KawayanMedia\LeaveForms\Contract\RetirementScheduleRepositoryInterface;
use KawayanMedia\LeaveForms\Document\RetirementSchedule;

/**
 * In-memory repository for tests, examples and one-off scripts.
 */
final class ArrayRetirementScheduleRepository implements RetirementScheduleRepositoryInterface
{
    /**
     * @param array<string, RetirementSchedule|array<string, mixed>> $records
     */
    public function __construct(private array $records = [])
    {
    }

    public function find(string $employeeId): ?RetirementSchedule
    {
        $record = $this->records[$employeeId] ?? null;

        if ($record === null) {
            return null;
        }

        return $record instanceof RetirementSchedule ? $record : RetirementSchedule::fromArray($record);
    }

    /**
     * @param RetirementSchedule|array<string, mixed> $record
     */
    public function add(string $employeeId, RetirementSchedule|array $record): void
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
