<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Support;

use DateTimeImmutable;
use DateTimeInterface;
use Throwable;

/**
 * The date shapes the legacy documents print.
 */
final class Dates
{
    public const LONG_DATE = 'F d, Y';

    public const MIDDLE_MONTH = 'd-M-y';

    public const SLASH_DATE = 'm/d/Y';

    public const MONTH_DAY_YEAR = 'm-d-Y';

    public const MONTHS = [
        1 => 'January',
        2 => 'February',
        3 => 'March',
        4 => 'April',
        5 => 'May',
        6 => 'June',
        7 => 'July',
        8 => 'August',
        9 => 'September',
        10 => 'October',
        11 => 'November',
        12 => 'December',
    ];

    public static function parse(mixed $value): ?DateTimeImmutable
    {
        if ($value instanceof DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '' || $value === '0000-00-00' || strcasecmp($value, 'present') === 0) {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Throwable) {
            return null;
        }
    }

    public static function format(mixed $value, string $format): string
    {
        return self::parse($value)?->format($format) ?? '';
    }

    /**
     * Legacy convert_long_date(): "March 14, 1985".
     */
    public static function longDate(mixed $value): string
    {
        return self::format($value, self::LONG_DATE);
    }

    /**
     * Legacy convert_long_date($date, TRUE): "14-Mar-85".
     */
    public static function middleMonth(mixed $value): string
    {
        return self::format($value, self::MIDDLE_MONTH);
    }

    public static function slashDate(mixed $value): string
    {
        return self::format($value, self::SLASH_DATE);
    }

    public static function monthDayYear(mixed $value): string
    {
        return self::format($value, self::MONTH_DAY_YEAR);
    }

    /**
     * Legacy Helps::get_month_name(), which accepted "01" or 1.
     */
    public static function monthName(mixed $month): string
    {
        $number = (int) ltrim(Text::blank($month), '0');

        return self::MONTHS[$number] ?? '';
    }

    public static function isFilled(mixed $value): bool
    {
        return self::parse($value) !== null;
    }
}
