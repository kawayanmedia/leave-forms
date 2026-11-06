<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Config\Layout;

/**
 * Computation of money value (legacy Reports::money_value).
 */
final class MoneyValueLayout
{
    /**
     * @param array<int, int> $textColor legacy grey
     */
    public function __construct(
        public readonly string $orientation = 'P',
        public readonly string $unit = 'mm',
        public readonly string $pageSize = 'Letter',
        public readonly array $textColor = [89, 89, 89],
        public readonly float $nameX = 83.0,
        public readonly float $nameY = 30.0,
        public readonly float $nameWidth = 50.0,
        public readonly float $cellHeight = 8.0,
        public readonly float $periodGap = 6.0,
        public readonly float $detailsX = 114.0,
        public readonly float $detailsGap = 20.5,
        public readonly float $lineGap = 6.0,
        public readonly float $retirementGap = 11.0,
        public readonly float $balanceGap = 5.5,
        public readonly float $calculationGap = 18.0,
        public readonly float $calculationSalaryX = 27.0,
        public readonly float $calculationLeaveX = 58.0,
        public readonly float $calculationWidth = 50.0,
        public readonly float $totalX = 167.0,
        public readonly float $totalWidth = 15.0,
        public readonly float $signatoryGap = 44.0,
        public readonly float $preparedX = 50.0,
        public readonly float $certifiedX = 136.0,
        public readonly float $signatoryWidth = 50.0,
        public readonly float $signatoryLineGap = 6.0,
        public readonly string $font = 'Times',
        public readonly float $fontSize = 12.0,
    ) {
    }

    public static function legacy(): self
    {
        return new self();
    }

    public function periodY(): float
    {
        return $this->nameY + $this->periodGap;
    }

    public function birthDateY(): float
    {
        return $this->periodY() + $this->detailsGap;
    }

    public function serviceDateY(): float
    {
        return $this->birthDateY() + $this->lineGap;
    }

    public function retirementDateY(): float
    {
        return $this->serviceDateY() + $this->retirementGap;
    }

    public function salaryY(): float
    {
        return $this->retirementDateY() + $this->lineGap;
    }

    public function leaveBalanceY(): float
    {
        return $this->salaryY() + $this->balanceGap;
    }

    public function calculationY(): float
    {
        return $this->leaveBalanceY() + $this->calculationGap;
    }

    public function signatoryY(): float
    {
        return $this->calculationY() + $this->signatoryGap;
    }

    public function signatoryPositionY(): float
    {
        return $this->signatoryY() + $this->signatoryLineGap;
    }
}
