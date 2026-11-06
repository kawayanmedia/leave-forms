<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Renderer\Page;

use KawayanMedia\LeaveForms\Config\Layout\ServiceRecordLayout;
use KawayanMedia\LeaveForms\Document\ServiceRecord;
use KawayanMedia\LeaveForms\Model\WorkHistoryRow;
use KawayanMedia\LeaveForms\Renderer\PageWriter;
use KawayanMedia\LeaveForms\Support\Text;
use setasign\Fpdi\Fpdi;

/**
 * Employee's Service Record.
 */
final class ServiceRecordPage
{
    public const TABLE_HEADER = 'Date From Date To   Position'
        . '                               Department'
        . '                        Movement Status   Status';

    public const NOTHING_FOLLOWS = '--------------------------------------------------------- Nothing Follows ---------------------------------------------------------';

    public function __construct(
        private readonly ServiceRecordLayout $layout,
        private readonly PageWriter $writer,
    ) {
    }

    public function draw(Fpdi $pdf, ServiceRecord $document): void
    {
        $layout = $this->layout;
        $body = [$layout->bodyFont, '', $layout->bodyFontSize];

        $this->writer->cell(
            $pdf,
            $layout->titleX,
            $layout->titleY,
            0.0,
            $layout->titleHeight,
            "Employee's Service Record",
            $layout->titleFont,
            '',
            $layout->titleFontSize,
            'C',
        );

        $this->writer->cell(
            $pdf,
            $layout->leftMargin,
            $layout->employeeNumberY(),
            0.0,
            $layout->blockHeight,
            'Employee No.:' . $document->employee->id,
            ...$body,
        );

        $this->writer->cell(
            $pdf,
            $layout->leftMargin,
            $layout->employeeNameY(),
            0.0,
            $layout->blockHeight,
            'Employee Name:' . $document->employee->lastFirst(),
            ...$body,
        );

        $this->writer->cell(
            $pdf,
            $layout->leftMargin,
            $layout->tableHeaderY(),
            0.0,
            $layout->blockHeight,
            self::TABLE_HEADER,
            ...$body,
            border: '1',
        );

        $y = $layout->rowStartY();

        foreach ($document->work as $index => $row) {
            $this->drawRow($pdf, $row, $y);
            $y += $layout->heightFor($index + 1);
        }

        $this->writer->cell(
            $pdf,
            $layout->leftMargin,
            $y,
            0.0,
            $layout->blockHeight,
            self::NOTHING_FOLLOWS,
            ...$body,
            align: 'C',
        );

        $this->drawSignatories($pdf, $document, $y + $layout->blockHeight + $layout->closingGap);
    }

    private function drawRow(Fpdi $pdf, WorkHistoryRow $row, float $y): void
    {
        $layout = $this->layout;
        $font = [$layout->bodyFont, '', $layout->rowFontSize];

        $values = [
            'date_from' => $row->dateFromLabel(),
            'date_to' => $row->dateToLabel(),
            'position' => Text::limitForPdf($row->position, $layout->positionLimit),
            'company' => Text::limitForPdf($row->company, $layout->companyLimit),
            'movement' => $row->movement,
            'status' => $row->status,
        ];

        foreach ($values as $key => $value) {
            $this->writer->text($pdf, $layout->columnX[$key], $y, $value, ...$font);
        }
    }

    private function drawSignatories(Fpdi $pdf, ServiceRecord $document, float $y): void
    {
        $layout = $this->layout;

        $this->pair($pdf, $y, 'PREPARED BY:', 'CERTIFIED CORRECT:');

        $namesY = $y + $layout->signatoryHeight + $layout->signatoryNameGap;

        $this->pair($pdf, $namesY, $document->prepared?->name ?? '', $document->certified?->name ?? '');
        $this->pair(
            $pdf,
            $namesY + $layout->signatoryHeight,
            $document->prepared?->position ?? '',
            $document->certified?->position ?? '',
        );
    }

    private function pair(Fpdi $pdf, float $y, string $left, string $right): void
    {
        $layout = $this->layout;

        foreach ([$left, $right] as $column => $value) {
            $this->writer->cell(
                $pdf,
                $layout->signatoryX + ($column * $layout->signatoryWidth),
                $y,
                $layout->signatoryWidth,
                $layout->signatoryHeight,
                $value,
                $layout->bodyFont,
                '',
                $layout->bodyFontSize,
                'C',
            );
        }
    }
}
