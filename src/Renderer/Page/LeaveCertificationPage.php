<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Renderer\Page;

use KawayanMedia\LeaveForms\Config\FormOptions;
use KawayanMedia\LeaveForms\Config\Layout\LeaveCertificationLayout;
use KawayanMedia\LeaveForms\Document\LeaveCertification;
use KawayanMedia\LeaveForms\Renderer\PageWriter;
use KawayanMedia\LeaveForms\Support\Dates;
use KawayanMedia\LeaveForms\Support\Money;
use setasign\Fpdi\Fpdi;

/**
 * Certificate of leave credits (legacy leave_certification).
 */
final class LeaveCertificationPage
{
    public function __construct(
        private readonly LeaveCertificationLayout $layout,
        private readonly PageWriter $writer,
        private readonly FormOptions $options,
    ) {
    }

    public function draw(Fpdi $pdf, LeaveCertification $document): void
    {
        $layout = $this->layout;
        $employee = $document->employee;

        $this->writer->text(
            $pdf,
            $layout->dateX,
            $layout->dateY,
            Dates::longDate($document->issuedOn ?? $this->options->printedOn()),
            $layout->font,
            $layout->boldStyle,
            $layout->fontSize,
        );

        $this->writer->text(
            $pdf,
            $layout->bodyX,
            $layout->nameY(),
            $employee->salutationName(),
            $layout->font,
            $layout->boldStyle,
            $layout->fontSize,
        );

        $this->writer->text($pdf, $layout->bodyX, $layout->positionY(), $employee->position, $layout->font, '', $layout->fontSize);
        $this->writer->text($pdf, $layout->bodyX, $layout->officeY(), $document->office->name, $layout->font, '', $layout->fontSize);
        $this->writer->text($pdf, $layout->bodyX, $layout->cityY(), $this->options->lguCity, $layout->font, '', $layout->fontSize);
        $this->writer->text($pdf, $layout->bodyX, $layout->greetingY(), $this->options->salutation, $layout->font, $layout->boldStyle, $layout->fontSize);

        $this->writer->text(
            $pdf,
            $layout->requestDateX,
            $layout->requestDateY(),
            Dates::longDate($document->requestedOn ?? $this->options->printedOn()) . ':',
            $layout->font,
            $layout->boldStyle,
            $layout->fontSize,
        );

        $this->writer->text($pdf, $layout->creditsX, $layout->creditsY(), Money::credits($document->balance->vacation), $layout->font, '', $layout->fontSize);
        $this->writer->text($pdf, $layout->sickX, $layout->creditsY(), Money::credits($document->balance->sick), $layout->font, '', $layout->fontSize);
        $this->writer->text($pdf, $layout->totalX, $layout->creditsY(), Money::credits($document->balance->total()), $layout->font, '', $layout->fontSize);

        $this->pair($pdf, $layout->noticeY(), $layout->noticeWidth, $document->noticedBy?->name ?? '', $document->noticedBy?->position ?? '');
        $this->pair($pdf, $layout->notedY(), $layout->notedWidth, $document->notedBy?->name ?? '', $document->notedBy?->position ?? '');
    }

    private function pair(Fpdi $pdf, float $y, float $width, string $name, string $position): void
    {
        $layout = $this->layout;

        $this->writer->cell($pdf, $layout->noticeX, $y, $width, $layout->signatoryHeight, $name, $layout->font, '', $layout->fontSize, 'C');
        $this->writer->cell($pdf, $layout->noticeX, $y + $layout->signatoryHeight, $width, $layout->signatoryHeight, $position, $layout->font, '', $layout->fontSize, 'C');
    }
}
