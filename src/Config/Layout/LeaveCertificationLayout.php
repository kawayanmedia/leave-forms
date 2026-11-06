<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Config\Layout;

/**
 * Certificate of leave credits (legacy Reports::leave_certification).
 */
final class LeaveCertificationLayout
{
    public function __construct(
        public readonly string $orientation = 'P',
        public readonly string $unit = 'mm',
        public readonly string $pageSize = 'Letter',
        public readonly float $dateX = 136.0,
        public readonly float $dateY = 50.5,
        public readonly float $bodyX = 35.0,
        public readonly float $nameGap = 37.0,
        public readonly float $lineHeight = 5.5,
        public readonly float $greetingGap = 10.0,
        public readonly float $requestDateGap = 9.0,
        public readonly float $creditsGap = 23.0,
        public readonly float $creditsX = 52.0,
        public readonly float $sickX = 108.0,
        public readonly float $totalX = 160.0,
        public readonly float $requestDateX = 136.0,
        public readonly float $noticeGap = 46.0,
        public readonly float $noticeX = 10.0,
        public readonly float $noticeWidth = 290.0,
        public readonly float $notedWidth = 120.0,
        public readonly float $signatoryHeight = 5.0,
        public readonly float $notedBlockGap = 20.0,
        public readonly string $font = 'Arial',
        public readonly float $fontSize = 12.0,
        public readonly string $boldStyle = 'B',
    ) {
    }

    public static function legacy(): self
    {
        return new self();
    }

    public function nameY(): float
    {
        return $this->dateY + $this->nameGap;
    }

    public function positionY(): float
    {
        return $this->nameY() + $this->lineHeight;
    }

    public function officeY(): float
    {
        return $this->positionY() + $this->lineHeight;
    }

    public function cityY(): float
    {
        return $this->officeY() + $this->lineHeight;
    }

    public function greetingY(): float
    {
        return $this->cityY() + $this->greetingGap;
    }

    public function requestDateY(): float
    {
        return $this->greetingY() + $this->requestDateGap;
    }

    public function creditsY(): float
    {
        return $this->requestDateY() + $this->creditsGap;
    }

    public function noticeY(): float
    {
        return $this->creditsY() + $this->noticeGap;
    }

    public function notedY(): float
    {
        return $this->noticeY() + ($this->signatoryHeight * 2) + $this->notedBlockGap;
    }
}
