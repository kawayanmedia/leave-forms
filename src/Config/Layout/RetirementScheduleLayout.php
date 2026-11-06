<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Config\Layout;

/**
 * Schedule of retirement pay (legacy Reports::schedule_retirement_pay).
 */
final class RetirementScheduleLayout
{
    public function __construct(
        public readonly string $orientation = 'L',
        public readonly string $unit = 'mm',
        public readonly string $pageSize = 'Legal',
        public readonly array $textColor = [89, 89, 89],
        public readonly float $periodX = 142.0,
        public readonly float $periodY = 20.5,
        public readonly float $officeY = 30.5,
        public readonly float $lguY = 35.5,
        public readonly float $partyX = 63.0,
        public readonly float $nameX = 14.0,
        public readonly float $nameY = 57.0,
        public readonly float $birthDateX = 65.0,
        public readonly float $retirementDateX = 88.0,
        public readonly float $serviceX = 114.0,
        public readonly float $serviceLineHeight = 5.0,
        public readonly float $salaryX = 145.0,
        public readonly float $salaryY = 53.0,
        public readonly float $salaryWidth = 20.0,
        public readonly float $cellHeight = 8.0,
        public readonly float $leaveX = 203.0,
        public readonly float $vacationY = 58.0,
        public readonly float $sickY = 63.0,
        public readonly float $totalY = 68.0,
        public readonly float $leaveWidth = 20.0,
        public readonly float $terminalX = 274.0,
        public readonly float $grandTotalY = 119.0,
        public readonly float $signatoryGap = 35.0,
        public readonly float $signatoryX = 13.0,
        public readonly float $signatoryWidth = 50.0,
        public readonly float $dateX = 76.0,
        public readonly float $approvedX = 147.0,
        public readonly float $approvedPositionX = 146.0,
        public readonly float $signatoryLineGap = 5.0,
        public readonly float $lowerSignatoryGap = 25.0,
        public readonly float $certifiedX = 13.0,
        public readonly float $attestedX = 88.0,
        public readonly float $availabilityX = 147.0,
        public readonly float $notedX = 223.0,
        public readonly string $font = 'Times',
        public readonly float $fontSize = 10.0,
    ) {
    }

    public static function legacy(): self
    {
        return new self();
    }

    public function signatoryY(): float
    {
        return $this->grandTotalY + $this->signatoryGap;
    }

    public function signatoryPositionY(): float
    {
        return $this->signatoryY() + $this->signatoryLineGap;
    }

    public function lowerSignatoryY(): float
    {
        return $this->signatoryPositionY() + $this->lowerSignatoryGap;
    }

    public function lowerSignatoryPositionY(): float
    {
        return $this->lowerSignatoryY() + $this->signatoryLineGap;
    }
}
