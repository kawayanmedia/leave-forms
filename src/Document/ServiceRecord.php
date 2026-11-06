<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Document;

use KawayanMedia\LeaveForms\Model\Employee;
use KawayanMedia\LeaveForms\Model\Signatory;
use KawayanMedia\LeaveForms\Model\WorkHistoryRow;
use KawayanMedia\LeaveForms\Support\Text;

/**
 * Employee's Service Record: every government service row of one employee.
 */
final class ServiceRecord implements PrintableDocument
{
    /**
     * @param list<WorkHistoryRow> $work
     */
    public function __construct(
        public readonly Employee $employee,
        public readonly array $work = [],
        public readonly ?Signatory $prepared = null,
        public readonly ?Signatory $certified = null,
    ) {
    }

    public function layoutKey(): string
    {
        return 'service-record';
    }

    /**
     * @param array<string, mixed> $rows
     */
    public static function fromArray(array $rows): self
    {
        $employee = $rows['employee'] ?? [];

        $work = array_map(
            static fn (mixed $row): WorkHistoryRow => $row instanceof WorkHistoryRow
                ? $row
                : WorkHistoryRow::fromArray((array) $row),
            (array) ($rows['work'] ?? []),
        );

        usort($work, static function (WorkHistoryRow $left, WorkHistoryRow $right): int {
            return Text::blank($right->dateFrom) <=> Text::blank($left->dateFrom);
        });

        return new self(
            employee: $employee instanceof Employee ? $employee : Employee::fromArray((array) $employee),
            work: array_values($work),
            prepared: isset($rows['prepared']) ? Signatory::from($rows['prepared']) : null,
            certified: isset($rows['certified']) ? Signatory::from($rows['certified']) : null,
        );
    }
}
