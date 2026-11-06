<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Support;

/**
 * The tables the leave write side touches.
 */
final class LeaveDataset
{
    public const APPLICATIONS = 'applications';
    public const CARD = 'card';
    public const CTO = 'cto';
    public const MANUAL_LOG = 'manual_log';
    public const DTR = 'dtr';
    public const LOGS = 'logs';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [self::APPLICATIONS, self::CARD, self::CTO, self::MANUAL_LOG, self::DTR, self::LOGS];
    }
}
