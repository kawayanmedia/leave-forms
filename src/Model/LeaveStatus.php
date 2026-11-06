<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Model;

/**
 * The legacy `leave_apps.approved` values.
 */
final class LeaveStatus
{
    public const PENDING = 0;
    public const APPROVED = 1;
    public const DISAPPROVED = 2;

    public static function label(int|string $status): string
    {
        return match ((int) $status) {
            self::APPROVED => 'Approved',
            self::DISAPPROVED => 'Disapproved',
            default => 'Pending',
        };
    }
}
