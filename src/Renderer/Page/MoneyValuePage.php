<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Renderer\Page;

use KawayanMedia\LeaveForms\Config\Layout\MoneyValueLayout;
use KawayanMedia\LeaveForms\Document\MoneyValue;
use KawayanMedia\LeaveForms\Renderer\PageWriter;
use KawayanMedia\LeaveForms\Support\Dates;
use KawayanMedia\LeaveForms\Support\Money;
use setasign\Fpdi\Fpdi;

/**
 * Computation of money value (legacy money_value).
 */
final class MoneyValuePage
{
    public function __construct(
        private readonly MoneyValueLayout $layout,
        private readonly PageWriter $writer,
    ) {
    }

    public function draw(Fpdi $pdf, MoneyValue $document): void
    {
        $layout = $this->layout;
        $employee = $document->employee;
        $color = $layout->textColor;

        $this->writer->cell(
            $pdf,
            $layout->nameX,
            $layout->nameY,
            $layout->nameWidth,
            $layout->cellHeight,
            $employee->firstName . ' ' . $employee->middleName . ' ' . $employee->lastName,
            $layout->font,
            'B',
            $layout->fontSize,
            'C',
            '0',
            $color,
        );

        $this->writer->cell(
            $pdf,
            $layout->nameX,
            $layout->periodY(),
            $layout->nameWidth,
            $layout->cellHeight,
            $document->period,
            $layout->font,
            '',
            $layout->fontSize,
            'C',
            '0',
            $color,
        );

        $this->writer->text($pdf, $layout->detailsX, $layout->birthDateY(), Dates::longDate($employee->birthDate), $layout->font, '', $layout->fontSize, $color);
        $this->writer->text($pdf, $layout->detailsX, $layout->serviceDateY(), Dates::longDate($employee->firstDayOfService), $layout->font, '', $layout->fontSize, $color);
        $this->writer->text($pdf, $layout->detailsX, $layout->retirementDateY(), Dates::longDate($document->retirementDate), $layout->font, '', $layout->fontSize, $color);
        $this->writer->text($pdf, $layout->detailsX, $layout->salaryY(), Money::amount($document->salary), $layout->font, '', $layout->fontSize, $color);
        $this->writer->text($pdf, $layout->detailsX, $layout->leaveBalanceY(), Money::credits($document->leaveBalance), $layout->font, '', $layout->fontSize, $color);

        $y = $layout->calculationY();

        $this->writer->cell($pdf, $layout->calculationSalaryX, $y, $layout->calculationWidth, $layout->cellHeight, Money::amount($document->salary), $layout->font, '', $layout->fontSize, 'R', '0', $color);
        $this->writer->cell($pdf, $layout->calculationLeaveX, $y, $layout->calculationWidth, $layout->cellHeight, Money::credits($document->leaveBalance), $layout->font, '', $layout->fontSize, 'R', '0', $color);
        $this->writer->cell($pdf, $layout->totalX, $y, $layout->totalWidth, $layout->cellHeight, Money::amount($document->totalMoney()), $layout->font, '', $layout->fontSize, 'L', '0', $color);

        $signatoryY = $layout->signatoryY();

        $this->writer->cell($pdf, $layout->preparedX, $signatoryY, $layout->signatoryWidth, $layout->cellHeight, $document->prepared?->name ?? '', $layout->font, '', $layout->fontSize, 'C', '0', $color);
        $this->writer->cell($pdf, $layout->certifiedX, $signatoryY, $layout->signatoryWidth, $layout->cellHeight, $document->certified?->name ?? '', $layout->font, '', $layout->fontSize, 'C', '0', $color);
        $this->writer->cell($pdf, $layout->preparedX, $layout->signatoryPositionY(), $layout->signatoryWidth, $layout->cellHeight, $document->prepared?->position ?? '', $layout->font, '', $layout->fontSize, 'C', '0', $color);
        $this->writer->cell($pdf, $layout->certifiedX, $layout->signatoryPositionY(), $layout->signatoryWidth, $layout->cellHeight, $document->certified?->position ?? '', $layout->font, '', $layout->fontSize, 'C', '0', $color);
    }
}
