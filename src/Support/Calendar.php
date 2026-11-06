<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Support;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Month arithmetic the leave card needs: validating a date, and the last day of
 * a month.
 */
final class Calendar
{
    public static function date(string $date): string
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', trim($date));

        if ($parsed === false || $parsed->format('Y-m-d') !== trim($date)) {
            throw new InvalidArgumentException(sprintf('"%s" is not a valid Y-m-d date.', $date));
        }

        return $parsed->format('Y-m-d');
    }

    public static function lastDay(int $year, int $month): string
    {
        return sprintf('%04d-%02d-%02d', $year, $month, self::daysInMonth($year, $month));
    }

    public static function daysInMonth(int $year, int $month): int
    {
        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException(sprintf('Month %d is out of range.', $month));
        }

        return (int) (new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month)))->format('t');
    }

    public static function monthName(int $month): string
    {
        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException(sprintf('Month %d is out of range.', $month));
        }

        return DateTimeImmutable::createFromFormat('!m', (string) $month)->format('F');
    }
}
