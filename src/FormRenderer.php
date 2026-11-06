<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms;

use KawayanMedia\LeaveForms\Config\FormLayout;
use KawayanMedia\LeaveForms\Config\FormOptions;
use KawayanMedia\LeaveForms\Document\CtoApplication;
use KawayanMedia\LeaveForms\Document\LeaveApplication;
use KawayanMedia\LeaveForms\Document\LeaveCertification;
use KawayanMedia\LeaveForms\Document\MoneyValue;
use KawayanMedia\LeaveForms\Document\PrintableDocument;
use KawayanMedia\LeaveForms\Document\RetirementSchedule;
use KawayanMedia\LeaveForms\Document\ServiceRecord;
use KawayanMedia\LeaveForms\Document\StatementOfLeave;
use KawayanMedia\LeaveForms\Renderer\OutputMode;
use KawayanMedia\LeaveForms\Renderer\Page\CtoApplicationPage;
use KawayanMedia\LeaveForms\Renderer\Page\LeaveApplicationPage;
use KawayanMedia\LeaveForms\Renderer\Page\LeaveCertificationPage;
use KawayanMedia\LeaveForms\Renderer\Page\MoneyValuePage;
use KawayanMedia\LeaveForms\Renderer\Page\RetirementSchedulePage;
use KawayanMedia\LeaveForms\Renderer\Page\ServiceRecordPage;
use KawayanMedia\LeaveForms\Renderer\Page\StatementOfLeavePage;
use KawayanMedia\LeaveForms\Renderer\PageWriter;
use RuntimeException;
use setasign\Fpdi\Fpdi;

/**
 * Draws every supported document onto its template.
 *
 * One document becomes one page (the CTO form carries two copies on that page);
 * renderMany() puts several documents into one PDF, which replaces the legacy
 * "write both files then concatenate" step.
 */
final class FormRenderer
{
    private readonly PageWriter $writer;

    private readonly ServiceRecordPage $serviceRecord;

    private readonly LeaveCertificationPage $leaveCertification;

    private readonly LeaveApplicationPage $leaveApplication;

    private readonly CtoApplicationPage $ctoApplication;

    private readonly MoneyValuePage $moneyValue;

    private readonly RetirementSchedulePage $retirementSchedule;

    private readonly StatementOfLeavePage $statementOfLeave;

    public function __construct(
        private readonly FormLayout $layout,
        private readonly FormOptions $options = new FormOptions(),
    ) {
        $this->writer = new PageWriter();
        $this->serviceRecord = new ServiceRecordPage($layout->serviceRecord, $this->writer);
        $this->leaveCertification = new LeaveCertificationPage($layout->leaveCertification, $this->writer, $options);
        $this->leaveApplication = new LeaveApplicationPage($layout->leaveApplication, $this->writer, $options);
        $this->ctoApplication = new CtoApplicationPage($layout->ctoApplication, $this->writer);
        $this->moneyValue = new MoneyValuePage($layout->moneyValue, $this->writer);
        $this->retirementSchedule = new RetirementSchedulePage($layout->retirementSchedule, $this->writer, $options);
        $this->statementOfLeave = new StatementOfLeavePage($layout->statementOfLeave, $this->writer);
    }

    public function layout(): FormLayout
    {
        return $this->layout;
    }

    public function options(): FormOptions
    {
        return $this->options;
    }

    public function withOptions(FormOptions $options): self
    {
        return new self($this->layout, $options);
    }

    public function render(PrintableDocument $document): string
    {
        return $this->renderMany([$document]);
    }

    /**
     * @param list<PrintableDocument> $documents
     */
    public function renderMany(array $documents): string
    {
        return $this->output($documents, '', OutputMode::String);
    }

    public function save(PrintableDocument|array $documents, string $path): void
    {
        $this->output($this->normalize($documents), $path, OutputMode::File);
    }

    /**
     * @param list<PrintableDocument> $documents
     */
    public function saveMany(array $documents, string $path): void
    {
        $this->output(array_values($documents), $path, OutputMode::File);
    }

    public function stream(PrintableDocument|array $documents, string $filename = 'form.pdf'): void
    {
        $this->output($this->normalize($documents), $filename, OutputMode::Inline);
    }

    public function download(PrintableDocument|array $documents, string $filename = 'form.pdf'): void
    {
        $this->output($this->normalize($documents), $filename, OutputMode::Download);
    }

    /**
     * @param list<PrintableDocument> $documents
     */
    public function output(array $documents, string $filename, OutputMode $mode): string
    {
        if ($documents === []) {
            throw new RuntimeException('No document to render.');
        }

        $this->assertTemplatesExist($documents);

        $first = $this->layout->geometry($documents[0]->layoutKey());
        $pdf = new Fpdi($first['orientation'], $first['unit'], $first['pageSize']);
        $pdf->SetCompression($this->layout->compress);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins($first['margin'], 10, 10);

        foreach ($documents as $document) {
            $this->draw($pdf, $document);
        }

        $result = $pdf->Output($mode->value, $filename);

        return is_string($result) ? $result : '';
    }

    private function draw(Fpdi $pdf, PrintableDocument $document): void
    {
        $geometry = $this->layout->geometry($document->layoutKey());

        $pdf->AddPage($geometry['orientation'], $geometry['pageSize']);
        $pdf->SetMargins($geometry['margin'], 10, 10);

        $this->importTemplate($pdf, $document->layoutKey());

        match (true) {
            $document instanceof ServiceRecord => $this->serviceRecord->draw($pdf, $document),
            $document instanceof LeaveCertification => $this->leaveCertification->draw($pdf, $document),
            $document instanceof LeaveApplication => $this->leaveApplication->draw($pdf, $document),
            $document instanceof CtoApplication => $this->ctoApplication->draw($pdf, $document),
            $document instanceof MoneyValue => $this->moneyValue->draw($pdf, $document),
            $document instanceof RetirementSchedule => $this->retirementSchedule->draw($pdf, $document),
            $document instanceof StatementOfLeave => $this->statementOfLeave->draw($pdf, $document),
            default => throw new RuntimeException('Unsupported document: ' . $document::class),
        };
    }

    private function importTemplate(Fpdi $pdf, string $documentKey): void
    {
        $path = $this->layout->template($documentKey);

        if ($path === '') {
            return;
        }

        $pdf->setSourceFile($path);
        $pageId = $pdf->importPage(1);
        $pdf->useTemplate($pageId);
    }

    /**
     * @param list<PrintableDocument> $documents
     */
    private function assertTemplatesExist(array $documents): void
    {
        foreach ($documents as $document) {
            $key = $document->layoutKey();

            if ($key === FormLayout::SERVICE_RECORD && $this->layout->serviceRecord->template === '') {
                continue;
            }

            if (!$this->layout->hasTemplate($key)) {
                throw new RuntimeException('Template not found for ' . $key . ': ' . $this->layout->template($key));
            }
        }
    }

    /**
     * @return list<PrintableDocument>
     */
    private function normalize(PrintableDocument|array $documents): array
    {
        return $documents instanceof PrintableDocument ? [$documents] : array_values($documents);
    }
}
