<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Config;

use InvalidArgumentException;
use KawayanMedia\LeaveForms\Support\LeaveDataset;

/**
 * Maps the datasets the leave write side touches to real table names.
 */
final class LeaveTables
{
    /**
     * @var array<string, string>
     */
    private const TABLES = [
        LeaveDataset::APPLICATIONS => 'leave_apps',
        LeaveDataset::CARD => 'leave_card',
        LeaveDataset::CTO => 'compensatory_timeoffs',
        LeaveDataset::MANUAL_LOG => 'manual_log',
        LeaveDataset::DTR => 'dtr',
        LeaveDataset::LOGS => 'logs',
    ];

    /**
     * @param array<string, string> $tables dataset => table overrides
     */
    public function __construct(private readonly array $tables = [])
    {
    }

    public static function legacy(): self
    {
        return new self();
    }

    /**
     * @param array<string, string> $tables
     */
    public static function fromArray(array $tables): self
    {
        return new self($tables);
    }

    public function table(string $dataset): string
    {
        if (!in_array($dataset, LeaveDataset::all(), true)) {
            throw new InvalidArgumentException(sprintf('Unknown leave dataset "%s".', $dataset));
        }

        return $this->tables[$dataset] ?? self::TABLES[$dataset];
    }

    /**
     * @return array<string, string> dataset => table
     */
    public function toArray(): array
    {
        return array_merge(self::TABLES, $this->tables);
    }
}
