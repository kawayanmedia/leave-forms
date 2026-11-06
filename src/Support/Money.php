<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Support;

/**
 * The precision rules the legacy documents used, in one place.
 */
final class Money
{
    /**
     * The monetisation factor every legacy computation uses:
     * days x 0.0478087 x highest salary received.
     */
    public const MONETISATION_FACTOR = 0.0478087;

    public static function monetize(float $salary, float $days): float
    {
        return $salary * $days * self::MONETISATION_FACTOR;
    }

    /** Monthly salary, totals and monetary value. */
    public static function amount(float $value): string
    {
        return number_format($value, 2);
    }

    /** Leave credits are always printed with three decimals. */
    public static function credits(float $value): string
    {
        return number_format($value, 3);
    }

    /**
     * The leave application and CTO forms prefix the salary with a peso sign.
     */
    public static function peso(float $value): string
    {
        return 'P ' . self::amount($value);
    }

    /**
     * Zero balances are printed as an empty cell in the statement of leave.
     */
    public static function creditsOrBlank(float $value): string
    {
        return $value == 0.0 ? '' : self::credits($value);
    }
}
