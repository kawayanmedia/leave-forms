<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Renderer\Page;

use KawayanMedia\LeaveForms\Config\FormOptions;
use KawayanMedia\LeaveForms\Config\Layout\LeaveApplicationLayout;
use KawayanMedia\LeaveForms\Document\LeaveApplication;
use KawayanMedia\LeaveForms\Renderer\PageWriter;
use KawayanMedia\LeaveForms\Support\Dates;
use KawayanMedia\LeaveForms\Support\Money;
use KawayanMedia\LeaveForms\Support\Text;
use setasign\Fpdi\Fpdi;

/**
 * Application for leave (legacy leave_apps, base version).
 */
final class LeaveApplicationPage
{
    public function __construct(
        private readonly LeaveApplicationLayout $layout,
        private readonly PageWriter $writer,
        private readonly FormOptions $options,
    ) {
    }

    public function draw(Fpdi $pdf, LeaveApplication $document): void
    {
        $layout = $this->layout;
        $employee = $document->employee;

        $this->writer->cell($pdf, $layout->trackingX, $layout->trackingY, 0.0, 5.0, $layout->trackingPrefix . $document->applicationId, $layout->font, 'B', $layout->fontSize);
        $this->writer->cell($pdf, $layout->trackingX, $layout->employeeIdY(), 0.0, 5.0, ' ' . $employee->id, $layout->font, 'B', $layout->fontSize);

        $partyY = $layout->partyY();

        $this->writer->text($pdf, $layout->officeX, $partyY, $document->office->name, $layout->font, 'B', $layout->fontSize);
        $this->writer->text($pdf, $layout->lastNameX, $partyY, $employee->lastName, $layout->font, 'B', $layout->fontSize);
        $this->writer->text($pdf, $layout->firstNameX, $partyY, $employee->firstName, $layout->font, 'B', $layout->fontSize);
        $this->writer->text($pdf, $layout->middleNameX, $partyY, $employee->middleInitial(), $layout->font, 'B', $layout->fontSize);

        $filingY = $layout->filingY();

        $this->writer->text($pdf, $layout->filingX, $filingY, Dates::longDate($document->filedOn), $layout->font, 'B', $layout->fontSize);
        $this->writer->text($pdf, $layout->positionX, $filingY, $employee->position, $layout->font, 'B', $layout->fontSize);
        $this->writer->text($pdf, $layout->salaryX, $filingY, Money::peso($document->salary), $layout->font, 'B', $layout->fontSize);

        $this->drawChoice(
            $pdf,
            $layout->firstRowLeaveTypes,
            $document,
            $layout->firstTickY(),
            $layout->firstLeaveNameY(),
        );

        $this->drawChoice(
            $pdf,
            $layout->secondRowLeaveTypes,
            $document,
            $layout->secondTickY(),
            $layout->secondLeaveNameY(),
        );

        $this->writer->text($pdf, $layout->daysX, $layout->daysY, $document->daysLabel(), $layout->font, 'B', $layout->fontSize);
        $this->writer->text($pdf, $layout->rangeX, $layout->rangeY(), $this->rangeLabel($document), $layout->font, 'B', $layout->fontSize);
        $this->writer->text($pdf, $layout->lastEarnX, $layout->lastEarnY(), Dates::longDate($document->lastEarned ?? $this->options->printedOn()), $layout->font, 'B', $layout->fontSize);

        $balanceY = $layout->balanceY();

        $this->writer->text($pdf, $layout->vacationBalanceX, $balanceY, Text::blank($document->balance->vacation), $layout->font, 'B', $layout->fontSize);
        $this->writer->text($pdf, $layout->sickBalanceX, $balanceY, Text::blank($document->balance->sick), $layout->font, 'B', $layout->fontSize);
        $this->writer->text($pdf, $layout->totalBalanceX, $balanceY, Text::blank($document->totalBalance()), $layout->font, 'B', $layout->fontSize);

        $this->writer->cell(
            $pdf,
            $layout->certificationX,
            $layout->certificationY,
            $layout->signatureWidth,
            $layout->signatureHeight,
            Text::uppercase($document->certifiedBy?->name ?? ''),
            $layout->font,
            'B',
            $layout->fontSize,
            'C',
        );

        $this->writer->cell(
            $pdf,
            $layout->certificationX,
            $layout->certificationPositionY,
            $layout->signatureWidth,
            $layout->signatureHeight,
            $document->certifiedBy?->position ?? '',
            $layout->font,
            'I',
            $layout->certificationPositionFontSize,
            'C',
        );

        $this->writer->cell(
            $pdf,
            $layout->applicantX,
            $layout->applicantY,
            $layout->signatureWidth,
            $layout->signatureHeight,
            $employee->firstName . ' ' . $employee->middleName . ' ' . $employee->lastName,
            $layout->font,
            'B',
            $layout->fontSize,
            'C',
        );

        $this->writer->cell(
            $pdf,
            $layout->applicantX,
            $layout->officeHeadY,
            $layout->signatureWidth,
            $layout->signatureHeight,
            $document->office->head,
            $layout->font,
            'B',
            $layout->fontSize,
            'C',
        );

        $this->writer->cell(
            $pdf,
            $layout->authorityX,
            $layout->authorityY,
            $layout->signatureWidth,
            $layout->signatureHeight,
            $document->authorityOfMayor,
            $layout->font,
            'B',
            $layout->fontSize,
            'C',
        );
    }

    /**
     * @param list<int> $leaveTypeIds
     */
    private function drawChoice(Fpdi $pdf, array $leaveTypeIds, LeaveApplication $document, float $tickY, float $nameY): void
    {
        if (!in_array($document->leaveTypeId, $leaveTypeIds, true)) {
            return;
        }

        $this->writer->check($pdf, $this->layout->tickX, $tickY);

        $name = in_array($document->leaveTypeId, $this->layout->unnamedLeaveTypes, true)
            ? ''
            : $document->leaveTypeName;

        $this->writer->text(
            $pdf,
            $this->layout->leaveNameX,
            $nameY,
            $name,
            $this->layout->font,
            'B',
            $this->layout->leaveNameFontSize,
        );
    }

    private function rangeLabel(LeaveApplication $document): string
    {
        $label = Dates::monthName($document->month) . ' ' . $document->fromDay . ', ' . $document->year;

        if (!$document->hasSecondRange()) {
            return $label;
        }

        return $label . ' - ' . Dates::monthName($document->secondMonth) . ' ' . $document->secondFromDay . ', ' . $document->secondYear;
    }
}
