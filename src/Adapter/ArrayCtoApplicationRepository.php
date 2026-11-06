<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Adapter;

use KawayanMedia\LeaveForms\Contract\CtoApplicationRepositoryInterface;
use KawayanMedia\LeaveForms\Document\CtoApplication;

/**
 * In-memory repository for tests, examples and one-off scripts.
 */
final class ArrayCtoApplicationRepository implements CtoApplicationRepositoryInterface
{
    /**
     * @param array<string, CtoApplication|array<string, mixed>> $records
     */
    public function __construct(private array $records = [])
    {
    }

    public function find(string $applicationId): ?CtoApplication
    {
        $record = $this->records[$applicationId] ?? null;

        if ($record === null) {
            return null;
        }

        return $record instanceof CtoApplication ? $record : CtoApplication::fromArray($record);
    }

    /**
     * @param CtoApplication|array<string, mixed> $record
     */
    public function add(string $applicationId, CtoApplication|array $record): void
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
