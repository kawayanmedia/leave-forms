<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Support;

use DateTimeImmutable;

/**
 * Working-day counting for leave applications, following the legacy
 * `leave` library: weekends and holidays are skipped, maternity and solo-parent
 * leave count them anyway, and half days count 0.5.
 */
final class LeaveDays
{
    /**
     * Legacy leave type ids that count weekends and holidays inside a range.
     *
     * @var list<int>
     */
    public const WEEKEND_INCLUSIVE_TYPES = [5, 20];

    /**
     * @param list<string> $holidays     dates in Y-m-d form
     * @param list<int>    $weekendTypes leave type ids that always count weekends
     */
    public function __construct(
        private readonly array $holidays = [],
        private readonly array $weekendTypes = self::WEEKEND_INCLUSIVE_TYPES,
    ) {
    }

    /**
     * The days a leave covers between two dates.
     *
     * @return list<string> dates in Y-m-d form, in order
     */
    public function dates(
        string $from,
        string $to,
        bool $allowWeekends = false,
        int $leaveTypeId = 0,
    ): array {
        $start = self::parse($from);
        $end = self::parse($to);
        $includeWeekends = $allowWeekends || in_array($leaveTypeId, $this->weekendTypes, true);

        $dates = [];

        for ($day = $start; $day <= $end; $day = $day->modify('+1 day')) {
            $date = $day->format('Y-m-d');

            if ($includeWeekends) {
                $dates[] = $date;
                continue;
            }

            if (self::isWeekend($date) || $this->isHoliday($date)) {
                continue;
            }

            $dates[] = $date;
        }

        return $dates;
    }

    /**
     * The number of leave days between two dates. A half day on the first or
     * last day counts 0.5 instead of 1.
     */
    public function count(
        string $from,
        string $to,
        bool $allowWeekends = false,
        int $leaveTypeId = 0,
        bool $halfDayStart = false,
        bool $halfDayEnd = false,
    ): float {
        $dates = $this->dates($from, $to, $allowWeekends, $leaveTypeId);
        $days = (float) count($dates);

        if ($days > 0 && ($halfDayStart || $halfDayEnd)) {
            $days -= 0.5;

            // A single day can never be two half days.
            if ($halfDayStart && $halfDayEnd && $days > 0) {
                $days -= 0.5;
            }
        }

        return round($days, 3);
    }

    /**
     * The legacy token format used by the File Leave screen: day numbers,
     * "25-26" ranges and "3am" / "3pm" half days, all within one month.
     *
     * @param list<string|int> $tokens
     *
     * @return array{dates: list<string>, days: float}
     */
    public function fromTokens(
        int|string $year,
        int|string $month,
        array $tokens,
        bool $allowWeekends = false,
        int $leaveTypeId = 0,
    ): array {
        $year = (int) $year;
        $month = (int) $month;
        $dates = [];
        $days = 0.0;

        foreach ($tokens as $token) {
            $token = strtolower(trim((string) $token));

            if ($token === '') {
                continue;
            }

            if (preg_match('/^(\d{1,2})\s*-\s*(\d{1,2})$/', $token, $matches) === 1) {
                $range = $this->dates(
                    self::date($year, $month, (int) $matches[1]),
                    self::date($year, $month, (int) $matches[2]),
                    $allowWeekends,
                    $leaveTypeId,
                );

                $dates = array_merge($dates, $range);
                $days += count($range);

                continue;
            }

            if (preg_match('/^(\d{1,2})(am|pm)$/', $token, $matches) === 1) {
                $date = self::date($year, $month, (int) $matches[1]);

                if ($allowWeekends || in_array($leaveTypeId, $this->weekendTypes, true) || (!self::isWeekend($date) && !$this->isHoliday($date))) {
                    $dates[] = $date;
                    $days += 0.5;
                }

                continue;
            }

            if (preg_match('/^\d{1,2}$/', $token) === 1) {
                $date = self::date($year, $month, (int) $token);

                if ($allowWeekends || in_array($leaveTypeId, $this->weekendTypes, true) || (!self::isWeekend($date) && !$this->isHoliday($date))) {
                    $dates[] = $date;
                    $days += 1.0;
                }
            }
        }

        $dates = array_values(array_unique($dates));
        sort($dates);

        return ['dates' => $dates, 'days' => round($days, 3)];
    }

    public static function isWeekend(string $date): bool
    {
        return in_array(self::parse($date)->format('N'), ['6', '7'], true);
    }

    public function isHoliday(string $date): bool
    {
        return in_array($date, $this->holidays, true);
    }

    /**
     * @return list<string>
     */
    public function holidays(): array
    {
        return $this->holidays;
    }

    public static function parse(string $date): DateTimeImmutable
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', trim($date));

        if ($parsed === false || $parsed->format('Y-m-d') !== trim($date)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid Y-m-d date.', $date));
        }

        return $parsed;
    }

    private static function date(int $year, int $month, int $day): string
    {
        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);

        if (!checkdate($month, $day, $year)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a real date.', $date));
        }

        return $date;
    }
}
