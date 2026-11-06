<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Model;

use KawayanMedia\LeaveForms\Support\Text;

/**
 * A printed name and the position underneath it.
 */
final class Signatory
{
    public function __construct(
        public readonly string $name = '',
        public readonly string $position = '',
    ) {
    }

    /**
     * @param array<string, mixed>|self|null $value
     */
    public static function from(mixed $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        if (!is_array($value)) {
            return new self();
        }

        return new self(
            Text::blank($value['name'] ?? $value['signatory'] ?? $value[0] ?? ''),
            Text::blank($value['position'] ?? $value[1] ?? ''),
        );
    }

    public function isEmpty(): bool
    {
        return $this->name === '' && $this->position === '';
    }
}
