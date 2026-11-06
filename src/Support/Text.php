<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Support;

/**
 * FPDF works with ISO-8859-1. These helpers replace the CodeIgniter text helper
 * calls and utf8_decode() the legacy controllers used.
 */
final class Text
{
    public static function latin1(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (function_exists('mb_convert_encoding')) {
            return (string) mb_convert_encoding($value, 'ISO-8859-1', 'UTF-8');
        }

        if (function_exists('iconv')) {
            $converted = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $value);

            if ($converted !== false) {
                return $converted;
            }
        }

        return $value;
    }

    public static function titleCase(string $value): string
    {
        return ucwords(strtolower(self::latin1($value)));
    }

    public static function uppercase(string $value): string
    {
        return strtoupper(self::latin1($value));
    }

    /**
     * CodeIgniter's character_limiter().
     */
    public static function limit(string $value, int $limit = 500, string $end = '&#8230;'): string
    {
        if (strlen($value) <= $limit) {
            return $value;
        }

        $value = trim((string) preg_replace('/\s+/', ' ', str_replace(["\r\n", "\r", "\n"], ' ', $value)));

        if (strlen($value) <= $limit) {
            return $value;
        }

        $out = '';

        foreach (explode(' ', $value) as $word) {
            $out .= $word . ' ';

            if (strlen($out) >= $limit) {
                $out = trim($out);

                return strlen($out) === strlen($value) ? $out : $out . $end;
            }
        }

        return trim($out);
    }

    /**
     * Legacy cells replaced the ellipsis entity with two dots before writing.
     */
    public static function limitForPdf(string $value, int $limit): string
    {
        return str_replace('&#8230;', '..', self::limit($value, $limit));
    }

    public static function blank(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return trim((string) $value);
    }

    /**
     * Initial plus a full stop: "Santos" becomes "S.".
     */
    public static function initial(string $value): string
    {
        $value = self::blank($value);

        return $value === '' ? '' : substr($value, 0, 1) . '.';
    }
}
