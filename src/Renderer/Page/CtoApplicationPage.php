<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Renderer\Page;

use KawayanMedia\LeaveForms\Config\Layout\CtoApplicationLayout;
use KawayanMedia\LeaveForms\Document\CtoApplication;
use KawayanMedia\LeaveForms\Renderer\PageWriter;
use KawayanMedia\LeaveForms\Support\Dates;
use KawayanMedia\LeaveForms\Support\Money;
use KawayanMedia\LeaveForms\Support\Text;
use setasign\Fpdi\Fpdi;

/**
 * Application for compensatory time off. The paper form carries two copies, so
 * the same block is printed twice on one page.
 */
final class CtoApplicationPage
{
    public function __construct(
        private readonly CtoApplicationLayout $layout,
        private readonly PageWriter $writer,
    ) {
    }

    public function draw(Fpdi $pdf, CtoApplication $document): void
    {
        $this->drawCopy($pdf, $document, $this->layout->firstCopy(), true);
        $this->drawCopy($pdf, $document, $this->layout->secondCopy(), false);
    }

    private function drawCopy(Fpdi $pdf, CtoApplication $document, float $offset, bool $withBalance): void
    {
        $layout = $this->layout;
        $employee = $document->employee;

        $this->writer->cell($pdf, $layout->trackingX, $layout->trackingY, 0.0, 5.0, $layout->trackingPrefix . $document->applicationId, $layout->font, 'B', $layout->fontSize);

        $partyY = $layout->partyY() + $offset;

        $this->writer->text($pdf, $layout->officeX, $partyY, $document->office->name, $layout->font, 'B', $layout->officeFontSize);
        $this->writer->text($pdf, $layout->lastNameX, $partyY, $employee->lastName, $layout->font, 'B', $layout->fontSize);
        $this->writer->text($pdf, $layout->firstNameX, $partyY, $employee->firstName, $layout->font, 'B', $layout->fontSize);
        $this->writer->text($pdf, $layout->middleNameX, $partyY, $employee->middleInitial(), $layout->font, 'B', $layout->fontSize);

        $filingY = $layout->filingY() + $offset;

        $this->writer->text($pdf, $layout->filingX, $filingY, Dates::longDate($document->filedOn), $layout->font, 'B', $layout->fontSize);
        $this->writer->text($pdf, $layout->positionX, $filingY, $employee->position, $layout->font, 'B', $layout->officeFontSize);
        $this->writer->text($pdf, $layout->salaryX, $filingY, Money::peso($document->salary), $layout->font, 'B', $layout->fontSize);

        $this->writer->text($pdf, $layout->daysX, $layout->daysY() + $offset, $document->daysLabel(), $layout->font, 'B', $layout->fontSize);
        $this->writer->text(
            $pdf,
            $layout->ctoDateX,
            $layout->ctoDateY() + $offset,
            Dates::monthName($document->month) . ' ' . $document->dates . ', ' . $document->year,
            $layout->font,
            'B',
            $layout->fontSize,
        );

        if ($withBalance) {
            $this->writer->text(
                $pdf,
                $layout->balanceAsOfX,
                $layout->balanceAsOfY,
                Dates::longDate($this->dayBefore($document->filedOn)),
                $layout->font,
                'B',
                $layout->fontSize,
            );

            $this->writer->text($pdf, $layout->balanceX, $layout->balanceY, Text::blank($document->totalBalance()), $layout->font, 'B', $layout->fontSize);
        }

        $headY = $layout->headY + $offset;

        $this->writer->cell($pdf, $layout->headX, $headY, $layout->signatureWidth, $layout->signatureHeight, Text::uppercase($document->officeHead?->name ?? ''), $layout->font, 'B', $layout->fontSize, 'C');
        $this->writer->cell($pdf, $layout->headX, $headY + $layout->signatureLineGap, $layout->signatureWidth, $layout->signatureHeight, $document->officeHead?->position ?? '', $layout->font, '', $layout->signatureFontSize, 'C');

        $certificationY = $layout->certificationY + $offset;

        $this->writer->cell($pdf, $layout->certificationX, $certificationY, $layout->signatureWidth, $layout->signatureHeight, Text::uppercase($document->certifiedBy?->name ?? ''), $layout->font, 'B', $layout->fontSize, 'C');
        $this->writer->cell($pdf, $layout->certificationX, $certificationY + $layout->signatureLineGap, $layout->signatureWidth, $layout->signatureHeight, $document->certifiedBy?->position ?? '', $layout->font, '', $layout->signatureFontSize, 'C');
    }

    /**
     * The form prints the balance as of the day before the application.
     */
    private function dayBefore(mixed $date): mixed
    {
        return Dates::parse($date)?->modify('-1 day');
    }
}
