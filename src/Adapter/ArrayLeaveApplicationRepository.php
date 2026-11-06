<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Adapter;

use KawayanMedia\LeaveForms\Contract\LeaveApplicationRepositoryInterface;
use KawayanMedia\LeaveForms\Document\LeaveApplication;

/**
 * In-memory repository for tests, examples and one-off scripts.
 */
final class ArrayLeaveApplicationRepository implements LeaveApplicationRepositoryInterface
{
    /**
     * @param array<string, LeaveApplication|array<string, mixed>> $records
     */
    public function __construct(private array $records = [])
    {
    }

    public function find(string $applicationId): ?LeaveApplication
    {
        $record = $this->records[$applicationId] ?? null;

        if ($record === null) {
            return null;
        }

        return $record instanceof LeaveApplication ? $record : LeaveApplication::fromArray($record);
    }

    /**
     * @param LeaveApplication|array<string, mixed> $record
     */
    public function add(string $applicationId, LeaveApplication|array $record): void
    {
        $this->records[$applicationId] = $record;
    }

    public function has(string $applicationId): bool
    {
        return array_key_exists($applicationId, $this->records);
    }

    /**
     * @return list<string>
     */
    public function ids(): array
    {
        return array_keys($this->records);
    }
}
