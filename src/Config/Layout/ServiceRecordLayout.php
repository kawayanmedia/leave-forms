<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Config\Layout;

/**
 * Employee's Service Record (legacy Sr_preview::preview).
 *
 * The legacy screen draws on a blank page, so no template is required; pass a
 * path to $template to print on a scanned letterhead instead.
 */
final class ServiceRecordLayout
{
    /**
     * @param list<int>         $tallRows rows that advance one millimetre more
     * @param array<string, float> $columnX  legacy SetX() for each row field
     */
    public function __construct(
        public readonly string $template = '',
        public readonly string $orientation = 'P',
        public readonly string $unit = 'mm',
        public readonly string $pageSize = 'Letter',
        public readonly float $leftMargin = 10.0,
        public readonly float $titleX = 15.0,
        public readonly float $titleY = 60.0,
        public readonly float $titleHeight = 3.0,
        public readonly float $titleGap = 4.0,
        public readonly float $blockHeight = 8.0,
        public readonly float $blockGap = 2.0,
        public readonly float $tableGap = 4.0,
        public readonly float $rowHeight = 7.0,
        public readonly float $rowHeightTall = 8.0,
        public readonly array $tallRows = [6, 10, 13, 16, 19, 22],
        public readonly array $columnX = [
            'date_from' => 10.0,
            'date_to' => 29.0,
            'position' => 50.0,
            'company' => 100.0,
            'movement' => 160.0,
            'status' => 185.0,
        ],
        public readonly int $positionLimit = 35,
        public readonly int $companyLimit = 35,
        public readonly float $closingGap = 15.0,
        public readonly float $signatoryX = 20.0,
        public readonly float $signatoryWidth = 90.0,
        public readonly float $signatoryHeight = 5.0,
        public readonly float $signatoryNameGap = 10.0,
        public readonly string $titleFont = 'Arial',
        public readonly float $titleFontSize = 16.0,
        public readonly string $bodyFont = 'Arial',
        public readonly float $bodyFontSize = 12.0,
        public readonly float $rowFontSize = 8.0,
    ) {
    }

    public static function legacy(): self
    {
        return new self();
    }

    public function withTemplate(string $template): self
    {
        return new self(
            template: $template,
            orientation: $this->orientation,
            unit: $this->unit,
            pageSize: $this->pageSize,
            leftMargin: $this->leftMargin,
            titleX: $this->titleX,
            titleY: $this->titleY,
            titleHeight: $this->titleHeight,
            titleGap: $this->titleGap,
            blockHeight: $this->blockHeight,
            blockGap: $this->blockGap,
            tableGap: $this->tableGap,
            rowHeight: $this->rowHeight,
            rowHeightTall: $this->rowHeightTall,
            tallRows: $this->tallRows,
            columnX: $this->columnX,
            positionLimit: $this->positionLimit,
            companyLimit: $this->companyLimit,
            closingGap: $this->closingGap,
            signatoryX: $this->signatoryX,
            signatoryWidth: $this->signatoryWidth,
            signatoryHeight: $this->signatoryHeight,
            signatoryNameGap: $this->signatoryNameGap,
            titleFont: $this->titleFont,
            titleFontSize: $this->titleFontSize,
            bodyFont: $this->bodyFont,
            bodyFontSize: $this->bodyFontSize,
            rowFontSize: $this->rowFontSize,
        );
    }

    public function employeeNumberY(): float
    {
        return $this->titleY + $this->titleHeight + $this->titleGap;
    }

    public function employeeNameY(): float
    {
        return $this->employeeNumberY() + $this->blockHeight + $this->blockGap;
    }

    public function tableHeaderY(): float
    {
        return $this->employeeNameY() + $this->blockHeight + $this->tableGap;
    }

    public function rowStartY(): float
    {
        return $this->tableHeaderY() + $this->blockHeight + $this->tableGap;
    }

    public function heightFor(int $rowNumber): float
    {
        return in_array($rowNumber, $this->tallRows, true) ? $this->rowHeightTall : $this->rowHeight;
    }
}
