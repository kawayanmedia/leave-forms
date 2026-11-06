<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Config;

use DateTimeImmutable;
use KawayanMedia\LeaveForms\Support\Dates;

/**
 * Values the legacy controllers hard-coded or read from settings, kept in one
 * place so a different LGU can print the same forms.
 */
final class FormOptions
{
    public function __construct(
        public readonly string $lguCity = 'City of Puerto Princesa',
        public readonly string $lguName = '',
        public readonly string $salutation = 'Sir/Madam:',
        public readonly mixed $printedOn = null,
        public readonly mixed $retirementPeriod = null,
        public readonly bool $printAuthorityOfMayor = false,
    ) {
    }

    public function printedOn(): DateTimeImmutable
    {
        return Dates::parse($this->printedOn) ?? new DateTimeImmutable();
    }
}
