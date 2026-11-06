<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Adapter;

use KawayanMedia\LeaveForms\Contract\LeaveCertificationRepositoryInterface;
use KawayanMedia\LeaveForms\Document\LeaveCertification;

/**
 * In-memory repository for tests, examples and one-off scripts.
 */
final class ArrayLeaveCertificationRepository implements LeaveCertificationRepositoryInterface
{
    /**
     * @param array<string, LeaveCertification|array<string, mixed>> $records
     */
    public function __construct(private array $records = [])
    {
    }

    public function find(string $employeeId): ?LeaveCertification
    {
        $record = $this->records[$employeeId] ?? null;

        if ($record === null) {
            return null;
        }

        return $record instanceof LeaveCertification ? $record : LeaveCertification::fromArray($record);
    }

    /**
     * @param LeaveCertification|array<string, mixed> $record
     */
    public function add(string $employeeId, LeaveCertification|array $record): void
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
