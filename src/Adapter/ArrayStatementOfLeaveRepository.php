<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Adapter;

use KawayanMedia\LeaveForms\Contract\StatementOfLeaveRepositoryInterface;
use KawayanMedia\LeaveForms\Document\StatementOfLeave;

/**
 * In-memory repository for tests, examples and one-off scripts.
 */
final class ArrayStatementOfLeaveRepository implements StatementOfLeaveRepositoryInterface
{
    /**
     * @param array<string, StatementOfLeave|array<string, mixed>> $records
     */
    public function __construct(private array $records = [])
    {
    }

    public function find(string $employeeId): ?StatementOfLeave
    {
        $record = $this->records[$employeeId] ?? null;

        if ($record === null) {
            return null;
        }

        return $record instanceof StatementOfLeave ? $record : StatementOfLeave::fromArray($record);
    }

    /**
     * @param StatementOfLeave|array<string, mixed> $record
     */
    public function add(string $employeeId, StatementOfLeave|array $record): void
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
