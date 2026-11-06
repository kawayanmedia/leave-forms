<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Exception;

use InvalidArgumentException;

/**
 * Raised when leave input cannot be written: a missing employee, an impossible
 * date range, an unknown leave type or an application that is already approved.
 */
final class ValidationException extends InvalidArgumentException
{
    /**
     * @var array<string, string>
     */
    private array $errors = [];

    public static function forField(string $field, string $message): self
    {
        $exception = new self($message);
        $exception->errors[$field] = $message;

        return $exception;
    }

    /**
     * @param array<string, string> $errors field => message
     */
    public static function withErrors(array $errors): self
    {
        $exception = new self(implode(' ', $errors));
        $exception->errors = $errors;

        return $exception;
    }

    /**
     * @return array<string, string> field => message
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
