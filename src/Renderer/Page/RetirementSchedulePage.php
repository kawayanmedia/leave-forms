<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Renderer\Page;

use KawayanMedia\LeaveForms\Config\FormOptions;
use KawayanMedia\LeaveForms\Config\Layout\RetirementScheduleLayout;
use KawayanMedia\LeaveForms\Document\RetirementSchedule;
use KawayanMedia\LeaveForms\Model\Signatory;
use KawayanMedia\LeaveForms\Renderer\PageWriter;
use KawayanMedia\LeaveForms\Support\Dates;
use KawayanMedia\LeaveForms\Support\Money;
use setasign\Fpdi\Fpdi;

/**
 * Schedule of retirement pay (landscape; legacy schedule_retirement_pay).
 */
final class RetirementSchedulePage
{
    public function __construct(
        private readonly RetirementScheduleLayout $layout,
        private readonly PageWriter $writer,
        private readonly FormOptions $options,
    ) {
    }

    public function draw(Fpdi $pdf, RetirementSchedule $document): void
    {
        $layout = $this->layout;
        $color = $layout->textColor;
        $employee = $document->employee;

        $this->writer->text($pdf, $layout->periodX, $layout->periodY, $document->period, $layout->font, '', $layout->fontSize, $color);
        $this->writer->text($pdf, $layout->partyX, $layout->officeY, $document->office->name, $layout->font, '', $layout->fontSize, $color);
        $this->writer->text($pdf, $layout->partyX, $layout->lguY, $document->lguName !== '' ? $document->lguName : $this->options->lguName, $layout->font, '', $layout->fontSize, $color);

        $this->writer->text($pdf, $layout->nameX, $layout->nameY, $employee->middleInitialName(), $layout->font, 'B', $layout->fontSize, $color);
        $this->writer->text($pdf, $layout->birthDateX, $layout->nameY, Dates::middleMonth($employee->birthDate), $layout->font, '', $layout->fontSize, $color);
        $this->writer->text($pdf, $layout->retirementDateX, $layout->nameY, Dates::middleMonth($document->retirementDate), $layout->font, '', $layout->fontSize, $color);

        $this->drawServiceDuration($pdf, $document, $color);

        $this->writer->cell($pdf, $layout->salaryX, $layout->salaryY, $layout->salaryWidth, $layout->cellHeight, Money::amount($document->salary), $layout->font, '', $layout->fontSize, 'C', '0', $color);

        $this->writer->cell($pdf, $layout->leaveX, $layout->vacationY, $layout->leaveWidth, $layout->cellHeight, Money::credits($document->balance->vacation), $layout->font, '', $layout->fontSize, 'R', '0', $color);
        $this->writer->cell($pdf, $layout->leaveX, $layout->sickY, $layout->leaveWidth, $layout->cellHeight, Money::credits($document->balance->sick), $layout->font, '', $layout->fontSize, 'R', '0', $color);
        $this->writer->cell($pdf, $layout->leaveX, $layout->totalY, $layout->leaveWidth, $layout->cellHeight, Money::credits($document->totalLeave()), $layout->font, '', $layout->fontSize, 'R', '0', $color);
        $this->writer->cell($pdf, $layout->terminalX, $layout->totalY, $layout->leaveWidth, $layout->cellHeight, Money::amount($document->terminalLeave()), $layout->font, '', $layout->fontSize, 'R', '0', $color);

        $this->writer->cell($pdf, $layout->leaveX, $layout->grandTotalY, $layout->leaveWidth, $layout->cellHeight, Money::credits($document->totalLeave()), $layout->font, 'B', $layout->fontSize, 'R', '0', $color);
        $this->writer->cell($pdf, $layout->terminalX, $layout->grandTotalY, $layout->leaveWidth, $layout->cellHeight, Money::amount($document->terminalLeave()), $layout->font, 'B', $layout->fontSize, 'R', '0', $color);

        $this->drawSignatories($pdf, $document, $color);
    }

    private function drawServiceDuration(Fpdi $pdf, RetirementSchedule $document, array $color): void
    {
        $layout = $this->layout;
        $start = Dates::parse($document->employee->firstDayOfService);
        $end = Dates::parse($document->retirementDate);

        if ($start === null || $end === null) {
            return;
        }

        $interval = $start->diff($end);
        $y = $layout->nameY;

        foreach (['y' => 'years', 'm' => 'months', 'd' => 'days'] as $unit => $label) {
            $value = (int) $interval->{$unit};

            if ($value !== 0) {
                $this->writer->text($pdf, $layout->serviceX, $y, $value . ' ' . $label, $layout->font, '', $layout->fontSize, $color);
            }

            $y += $layout->serviceLineHeight;
        }
    }

    private function drawSignatories(Fpdi $pdf, RetirementSchedule $document, array $color): void
    {
        $layout = $this->layout;

        $this->signatory($pdf, $layout->signatoryX, $layout->signatoryY(), $document->prepared, $color);
        $this->writer->cell($pdf, $layout->dateX, $layout->signatoryY(), $layout->signatoryWidth, $layout->cellHeight, Dates::longDate($document->issuedOn ?? $this->options->printedOn()), $layout->font, '', $layout->fontSize, 'C', '0', $color);
        $this->signatory($pdf, $layout->approvedX, $layout->signatoryY(), $document->approved, $color);

        $this->writer->cell($pdf, $layout->signatoryX, $layout->signatoryPositionY(), $layout->signatoryWidth, $layout->cellHeight, $document->prepared?->position ?? '', $layout->font, '', $layout->fontSize, 'C', '0', $color);
        $this->writer->cell($pdf, $layout->approvedPositionX, $layout->signatoryPositionY(), $layout->signatoryWidth, $layout->cellHeight, $document->approved?->position ?? '', $layout->font, '', $layout->fontSize, 'C', '0', $color);

        $this->signatory($pdf, $layout->certifiedX, $layout->lowerSignatoryY(), $document->certified, $color);
        $this->signatory($pdf, $layout->attestedX, $layout->lowerSignatoryY(), $document->attested, $color);
        $this->signatory($pdf, $layout->availabilityX, $layout->lowerSignatoryY(), $document->availability, $color);
        $this->signatory($pdf, $layout->notedX, $layout->lowerSignatoryY(), $document->noted, $color);

        $this->writer->cell($pdf, $layout->certifiedX, $layout->lowerSignatoryPositionY(), $layout->signatoryWidth, $layout->cellHeight, $document->certified?->position ?? '', $layout->font, '', $layout->fontSize, 'C', '0', $color);
        $this->writer->cell($pdf, $layout->attestedX, $layout->lowerSignatoryPositionY(), $layout->signatoryWidth, $layout->cellHeight, $document->attested?->position ?? '', $layout->font, '', $layout->fontSize, 'C', '0', $color);
        $this->writer->cell($pdf, $layout->availabilityX, $layout->lowerSignatoryPositionY(), $layout->signatoryWidth, $layout->cellHeight, $document->availability?->position ?? '', $layout->font, '', $layout->fontSize, 'C', '0', $color);
        $this->writer->cell($pdf, $layout->notedX, $layout->lowerSignatoryPositionY(), $layout->signatoryWidth, $layout->cellHeight, $document->noted?->position ?? '', $layout->font, '', $layout->fontSize, 'C', '0', $color);
    }

    private function signatory(Fpdi $pdf, float $x, float $y, ?Signatory $signatory, array $color): void
    {
        $this->writer->cell(
            $pdf,
            $x,
            $y,
            $this->layout->signatoryWidth,
            $this->layout->cellHeight,
            $signatory?->name ?? '',
            $this->layout->font,
            'B',
            $this->layout->fontSize,
            'C',
            '0',
            $color,
        );
    }
}
