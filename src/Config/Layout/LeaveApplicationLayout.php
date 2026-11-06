<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Config\Layout;

/**
 * Application for leave (legacy Reports::leave_apps, base version).
 *
 * The template already carries the form text; this layout only places the
 * answers, the checkbox ticks and the signatures.
 */
final class LeaveApplicationLayout
{
    /**
     * @param list<int> $firstRowLeaveTypes   leave type ids ticked on the first row
     * @param list<int> $secondRowLeaveTypes  leave type ids ticked on the second row
     * @param list<int> $unnamedLeaveTypes    ids whose pre-printed label is left alone
     */
    public function __construct(
        public readonly string $orientation = 'P',
        public readonly string $unit = 'mm',
        public readonly string $pageSize = 'A4',
        public readonly string $trackingPrefix = '',
        public readonly float $trackingX = 158.0,
        public readonly float $trackingY = 10.5,
        public readonly float $employeeIdGap = 9.0,
        public readonly float $partyGap = 20.0,
        public readonly float $officeX = 25.0,
        public readonly float $lastNameX = 90.0,
        public readonly float $firstNameX = 145.0,
        public readonly float $middleNameX = 187.0,
        public readonly float $filingGap = 13.0,
        public readonly float $filingX = 25.0,
        public readonly float $positionX = 70.0,
        public readonly float $salaryX = 170.0,
        public readonly float $choiceGap = 14.0,
        public readonly float $firstTickGap = 2.0,
        public readonly float $secondTickGap = 27.0,
        public readonly float $tickX = 28.0,
        public readonly float $leaveNameGap = 18.0,
        public readonly float $leaveNameX = 32.0,
        public readonly float $daysX = 35.0,
        public readonly float $daysY = 128.0,
        public readonly float $rangeGap = 4.0,
        public readonly float $rangeX = 60.0,
        public readonly float $lastEarnGap = 39.0,
        public readonly float $lastEarnX = 35.0,
        public readonly float $balanceGap = 18.0,
        public readonly float $vacationBalanceX = 25.0,
        public readonly float $sickBalanceX = 54.0,
        public readonly float $totalBalanceX = 80.0,
        public readonly float $certificationX = 35.0,
        public readonly float $certificationY = 205.0,
        public readonly float $certificationPositionY = 211.0,
        public readonly float $signatureWidth = 65.0,
        public readonly float $signatureHeight = 5.0,
        public readonly float $applicantX = 129.0,
        public readonly float $applicantY = 139.0,
        public readonly float $officeHeadY = 206.0,
        public readonly float $authorityX = 70.0,
        public readonly float $authorityY = 260.0,
        public readonly array $firstRowLeaveTypes = [1, 3, 4, 5, 6, 7, 9, 10, 12],
        public readonly array $secondRowLeaveTypes = [2, 11, 20],
        public readonly array $unnamedLeaveTypes = [1, 2],
        public readonly string $font = 'Arial',
        public readonly float $fontSize = 12.0,
        public readonly float $leaveNameFontSize = 10.0,
        public readonly float $certificationPositionFontSize = 11.0,
    ) {
    }

    public static function legacy(): self
    {
        return new self();
    }

    public function employeeIdY(): float
    {
        return $this->trackingY + $this->employeeIdGap;
    }

    public function partyY(): float
    {
        return $this->employeeIdY() + $this->partyGap;
    }

    public function filingY(): float
    {
        return $this->partyY() + $this->filingGap;
    }

    public function firstTickY(): float
    {
        return $this->filingY() + $this->choiceGap + $this->firstTickGap;
    }

    public function firstLeaveNameY(): float
    {
        return $this->firstTickY() + $this->leaveNameGap;
    }

    public function secondTickY(): float
    {
        return $this->filingY() + $this->choiceGap + $this->secondTickGap;
    }

    public function secondLeaveNameY(): float
    {
        return $this->secondTickY() + $this->leaveNameGap;
    }

    public function rangeY(): float
    {
        return $this->daysY + $this->rangeGap;
    }

    public function lastEarnY(): float
    {
        return $this->rangeY() + $this->lastEarnGap;
    }

    public function balanceY(): float
    {
        return $this->lastEarnY() + $this->balanceGap;
    }
}
