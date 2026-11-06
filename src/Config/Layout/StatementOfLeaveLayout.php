<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Config\Layout;

/**
 * Statement of leave (legacy Reports::statement_leave).
 */
final class StatementOfLeaveLayout
{
    public function __construct(
        public readonly string $orientation = 'P',
        public readonly string $unit = 'mm',
        public readonly string $pageSize = 'Legal',
        public readonly float $nameX = 26.0,
        public readonly float $nameY = 47.0,
        public readonly float $officeY = 52.0,
        public readonly float $serviceDateX = 161.0,
        public readonly float $headerX = 10.0,
        public readonly float $headerY = 57.0,
        public readonly float $headerHeight = 10.0,
        public readonly float $subHeaderHeight = 5.0,
        public readonly float $periodWidth = 44.0,
        public readonly float $groupWidth = 40.0,
        public readonly float $columnWidth = 20.0,
        public readonly float $remarksWidth = 30.0,
        public readonly float $firstRowGap = 10.1,
        public readonly float $rowPitch = 5.1,
        public readonly float $periodX = 12.0,
        public readonly array $valueX = [
            'vacation_earned' => 54.0,
            'sick_earned' => 75.0,
            'vacation_spent' => 95.0,
            'sick_spent' => 115.0,
            'vacation_balance' => 135.0,
            'sick_balance' => 153.0,
        ],
        public readonly float $totalsGap = 5.1,
        public readonly float $combinedGap = 5.1,
        public readonly float $signatoryGap = 15.0,
        public readonly float $signatoryX = 10.0,
        public readonly float $signatoryWidth = 90.0,
        public readonly float $signatoryHeight = 5.0,
        public readonly float $signatoryNameGap = 10.0,
        public readonly string $font = 'Times',
        public readonly float $fontSize = 12.0,
    ) {
    }

    public static function legacy(): self
    {
        return new self();
    }

    public function subHeaderY(): float
    {
        return $this->headerY + $this->subHeaderHeight + $this->subHeaderHeight;
    }

    public function firstRowY(): float
    {
        return $this->subHeaderY() + $this->subHeaderHeight + $this->firstRowGap;
    }

    public function totalsY(int $rows): float
    {
        return $this->firstRowY() + ($rows * $this->rowPitch) + $this->totalsGap;
    }

    public function combinedY(int $rows): float
    {
        return $this->totalsY($rows) + $this->combinedGap;
    }

    public function signatoryY(int $rows): float
    {
        return $this->combinedY($rows) + $this->signatoryGap;
    }

    public function signatoryNameY(int $rows): float
    {
        return $this->signatoryY($rows) + $this->signatoryHeight + $this->signatoryNameGap;
    }
}
