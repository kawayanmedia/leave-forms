<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Config\Layout;

/**
 * Application for compensatory time off (legacy Reports::cto_apps).
 *
 * The template carries two copies of the form, so every coordinate is printed
 * twice: once at $copyOffset 0 and once at $copyOffset.
 */
final class CtoApplicationLayout
{
    public function __construct(
        public readonly string $orientation = 'P',
        public readonly string $unit = 'mm',
        public readonly string $pageSize = 'Legal',
        public readonly float $copyOffset = 141.5,
        public readonly string $trackingPrefix = 'Tracking no: ',
        public readonly float $trackingX = 158.0,
        public readonly float $trackingY = 10.5,
        public readonly float $employeeIdGap = 9.0,
        public readonly float $partyGap = 20.0,
        public readonly float $officeX = 12.0,
        public readonly float $lastNameX = 99.0,
        public readonly float $firstNameX = 145.0,
        public readonly float $middleNameX = 187.0,
        public readonly float $filingGap = 10.0,
        public readonly float $filingX = 20.0,
        public readonly float $positionX = 63.0,
        public readonly float $salaryX = 140.0,
        public readonly float $daysGap = 13.0,
        public readonly float $daysX = 30.0,
        public readonly float $ctoDateGap = 13.0,
        public readonly float $ctoDateX = 50.0,
        public readonly float $balanceAsOfX = 30.0,
        public readonly float $balanceAsOfY = 106.0,
        public readonly float $balanceX = 35.0,
        public readonly float $balanceY = 116.0,
        public readonly float $headX = 120.0,
        public readonly float $headY = 122.0,
        public readonly float $certificationX = 22.0,
        public readonly float $certificationY = 132.0,
        public readonly float $signatureWidth = 73.0,
        public readonly float $signatureHeight = 4.0,
        public readonly float $signatureLineGap = 5.0,
        public readonly string $font = 'Arial',
        public readonly float $fontSize = 12.0,
        public readonly float $officeFontSize = 10.0,
        public readonly float $signatureFontSize = 10.0,
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

    public function daysY(): float
    {
        return $this->filingY() + $this->daysGap;
    }

    public function ctoDateY(): float
    {
        return $this->daysY() + $this->ctoDateGap;
    }

    public function headPositionY(): float
    {
        return $this->headY + $this->signatureLineGap;
    }

    public function certificationPositionY(): float
    {
        return $this->certificationY + $this->signatureLineGap;
    }

    public function firstCopy(): float
    {
        return 0.0;
    }

    public function secondCopy(): float
    {
        return $this->copyOffset;
    }
}
