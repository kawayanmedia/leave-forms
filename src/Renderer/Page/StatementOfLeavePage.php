<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Renderer\Page;

use KawayanMedia\LeaveForms\Config\Layout\StatementOfLeaveLayout;
use KawayanMedia\LeaveForms\Document\StatementOfLeave;
use KawayanMedia\LeaveForms\Model\LeaveYearRow;
use KawayanMedia\LeaveForms\Renderer\PageWriter;
use KawayanMedia\LeaveForms\Support\Dates;
use KawayanMedia\LeaveForms\Support\Money;
use setasign\Fpdi\Fpdi;

/**
 * Statement of leave (legacy statement_leave).
 */
final class StatementOfLeavePage
{
    public function __construct(
        private readonly StatementOfLeaveLayout $layout,
        private readonly PageWriter $writer,
    ) {
    }

    public function draw(Fpdi $pdf, StatementOfLeave $document): void
    {
        $layout = $this->layout;
        $employee = $document->employee;
        $font = $layout->font;
        $size = $layout->fontSize;

        $this->writer->text(
            $pdf,
            $layout->nameX,
            $layout->nameY,
            '  ' . trim($employee->salutation . ' ' . $employee->firstName . ' ' . $employee->middleName . ' ' . $employee->lastName),
            $font,
            'B',
            $size,
        );

        $this->writer->text($pdf, $layout->nameX, $layout->officeY, '  ' . $document->office->name, $font, 'B', $size);
        $this->writer->text($pdf, $layout->serviceDateX, $layout->officeY, Dates::longDate($employee->firstDayOfService), $font, 'B', $size);

        $this->drawHeader($pdf);

        $rows = $document->rows();
        $y = $layout->firstRowY();

        foreach ($rows as $row) {
            $this->drawRow($pdf, $row, $y);
            $y += $layout->rowPitch;
        }

        $this->drawTotals($pdf, $document, $rows);
        $this->drawSignatories($pdf, $document, count($rows));
    }

    private function drawHeader(Fpdi $pdf): void
    {
        $layout = $this->layout;
        $font = $layout->font;
        $size = $layout->fontSize;

        $this->writer->cell($pdf, $layout->headerX, $layout->headerY, $layout->periodWidth, $layout->headerHeight, 'Period Covered', $font, '', $size, 'C', 'RLTB');

        $groups = ['Leave Earned', 'Leave Spent', 'Leave Balance'];
        $x = $layout->headerX + $layout->periodWidth;

        foreach ($groups as $group) {
            $this->writer->cell($pdf, $x, $layout->headerY, $layout->groupWidth, $layout->subHeaderHeight, $group, $font, '', $size, 'C', '1');
            $x += $layout->groupWidth;
        }

        $this->writer->cell($pdf, $x, $layout->headerY, $layout->remarksWidth, $layout->subHeaderHeight, 'Remarks', $font, '', $size, 'C', '1');

        $y = $layout->subHeaderY();
        $this->writer->cell($pdf, $layout->headerX, $y, $layout->periodWidth, $layout->subHeaderHeight, '', $font, '', $size, 'C', 'RLB');

        $labels = ['Vacation', 'Sick', 'Vacation', 'Sick', 'Vacation', 'Sick'];
        $x = $layout->headerX + $layout->periodWidth;

        foreach ($labels as $label) {
            $this->writer->cell($pdf, $x, $y, $layout->columnWidth, $layout->subHeaderHeight, $label, $font, '', $size, 'C', '1');
            $x += $layout->columnWidth;
        }

        $this->writer->cell($pdf, $x, $y, $layout->remarksWidth, $layout->subHeaderHeight, '', $font, '', $size, 'C', '1');
    }

    private function drawRow(Fpdi $pdf, LeaveYearRow $row, float $y): void
    {
        $layout = $this->layout;

        $this->writer->text($pdf, $layout->periodX, $y, $row->period, $layout->font, '', $layout->fontSize);

        $values = [
            'vacation_earned' => $row->vacationEarned,
            'sick_earned' => $row->sickEarned,
            'vacation_spent' => $row->vacationSpent,
            'sick_spent' => $row->sickSpent,
            'vacation_balance' => $row->vacationBalance,
            'sick_balance' => $row->sickBalance,
        ];

        foreach ($values as $key => $value) {
            if (Money::credits($value) === '0.000') {
                continue;
            }

            $this->writer->text($pdf, $layout->valueX[$key], $y, Money::credits($value), $layout->font, '', $layout->fontSize);
        }
    }

    /**
     * @param list<LeaveYearRow> $rows
     */
    private function drawTotals(Fpdi $pdf, StatementOfLeave $document, array $rows): void
    {
        $layout = $this->layout;
        $y = $layout->totalsY(count($rows));
        $font = $layout->font;
        $size = $layout->fontSize;

        $totals = [
            'vacation_earned' => $document->vacationEarnedTotal(),
            'sick_earned' => $document->sickEarnedTotal(),
            'vacation_spent' => $document->vacationSpentTotal(),
            'sick_spent' => $document->sickSpentTotal(),
            'vacation_balance' => $document->vacationBalance(),
            'sick_balance' => $document->sickBalance(),
        ];

        foreach ($totals as $key => $value) {
            $this->writer->text($pdf, $layout->valueX[$key], $y, Money::credits($value), $font, 'B', $size);
        }

        $this->writer->text(
            $pdf,
            $layout->valueX['sick_balance'],
            $layout->combinedY(count($rows)),
            Money::credits($document->sickBalance() + $document->vacationBalance()),
            $font,
            'B',
            $size,
        );
    }

    private function drawSignatories(Fpdi $pdf, StatementOfLeave $document, int $rowCount): void
    {
        $layout = $this->layout;
        $y = $layout->signatoryY($rowCount);

        $this->writer->cell($pdf, $layout->signatoryX, $y, $layout->signatoryWidth, $layout->signatoryHeight, 'PREPARED BY:', $layout->font, '', $layout->fontSize, 'C');
        $this->writer->cell($pdf, $layout->signatoryX + $layout->signatoryWidth, $y, $layout->signatoryWidth, $layout->signatoryHeight, 'CERTIFIED CORRECT:', $layout->font, '', $layout->fontSize, 'C');

        $namesY = $layout->signatoryNameY($rowCount);

        $this->writer->cell($pdf, $layout->signatoryX, $namesY, $layout->signatoryWidth, $layout->signatoryHeight, $document->prepared?->name ?? '', $layout->font, '', $layout->fontSize, 'C');
        $this->writer->cell($pdf, $layout->signatoryX + $layout->signatoryWidth, $namesY, $layout->signatoryWidth, $layout->signatoryHeight, $document->certified?->name ?? '', $layout->font, '', $layout->fontSize, 'C');
        $this->writer->cell($pdf, $layout->signatoryX, $namesY + $layout->signatoryHeight, $layout->signatoryWidth, $layout->signatoryHeight, $document->prepared?->position ?? '', $layout->font, '', $layout->fontSize, 'C');
        $this->writer->cell($pdf, $layout->signatoryX + $layout->signatoryWidth, $namesY + $layout->signatoryHeight, $layout->signatoryWidth, $layout->signatoryHeight, $document->certified?->position ?? '', $layout->font, '', $layout->fontSize, 'C');
    }
}
