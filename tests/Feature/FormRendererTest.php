<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Tests\Feature;

use KawayanMedia\LeaveForms\Config\FormLayout;
use KawayanMedia\LeaveForms\Config\FormOptions;
use KawayanMedia\LeaveForms\Document\CtoApplication;
use KawayanMedia\LeaveForms\Document\LeaveApplication;
use KawayanMedia\LeaveForms\Document\LeaveCertification;
use KawayanMedia\LeaveForms\Document\MoneyValue;
use KawayanMedia\LeaveForms\Document\RetirementSchedule;
use KawayanMedia\LeaveForms\Document\ServiceRecord;
use KawayanMedia\LeaveForms\Document\StatementOfLeave;
use KawayanMedia\LeaveForms\FormRenderer;
use KawayanMedia\LeaveForms\Tests\Support\FormFixture;
use KawayanMedia\LeaveForms\Tests\Support\TemplateFactory;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class FormRendererTest extends TestCase
{
    private string $directory;

    /** @var array<string, string> */
    private array $templates;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/leave-forms-' . bin2hex(random_bytes(6));
        $this->templates = TemplateFactory::create($this->directory);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/*.pdf') ?: [] as $file) {
            unlink($file);
        }

        if (is_dir($this->directory)) {
            rmdir($this->directory);
        }
    }

    public function test_every_document_renders_one_page(): void
    {
        $renderer = $this->renderer();

        foreach (FormFixture::all() as $key => $document) {
            $pdf = $renderer->render($document);

            self::assertStringStartsWith('%PDF', $pdf, $key);
            self::assertSame(1, TemplateFactory::pageCount($pdf), $key);
        }
    }

    public function test_the_service_record_prints_every_government_row(): void
    {
        $pdf = $this->renderer()->render(FormFixture::serviceRecord());

        self::assertStringContainsString('Employee No.:013001', $pdf);
        self::assertStringContainsString('Employee Name:Dela Cruz, Juan Santos', $pdf);
        self::assertStringContainsString('Information Technology Officer I', $pdf);
        self::assertStringContainsString('Nothing Follows', $pdf);
        self::assertStringContainsString('PREPARED BY:', $pdf);
        self::assertStringContainsString('CERTIFIED CORRECT:', $pdf);
    }

    public function test_the_leave_certification_prints_the_credits_and_the_city(): void
    {
        $pdf = $this->renderer()->render(FormFixture::leaveCertification());

        self::assertStringContainsString('Mr. Juan Santos Dela Cruz', $pdf);
        self::assertStringContainsString('Provincial Human Resource Management Office', $pdf);
        self::assertStringContainsString('Sir/Madam:', $pdf);
        self::assertStringContainsString('15.500', $pdf);
        self::assertStringContainsString('12.250', $pdf);
        self::assertStringContainsString('27.750', $pdf);
        self::assertStringContainsString('City of Boac', $pdf);
    }

    public function test_the_leave_application_ticks_the_matching_leave_type(): void
    {
        $pdf = $this->renderer()->render(FormFixture::leaveApplication());

        self::assertStringContainsString('2026-0451', $pdf);
        self::assertStringContainsString('November 10, 2026', $pdf);
        self::assertStringContainsString('3 days', $pdf);
        self::assertStringContainsString('October 31, 2026', $pdf);

        $sick = new LeaveApplication(
            employee: FormFixture::employee(),
            office: FormFixture::office(),
            applicationId: '2026-0452',
            leaveTypeId: 2,
            leaveTypeName: 'Sick Leave',
            days: 1,
            month: '11',
            fromDay: '12',
            year: '2026',
            salary: 45000.0,
            filedOn: '2026-11-06',
        );

        $pdf = $this->renderer()->render($sick);

        self::assertStringContainsString('1 day', $pdf);
        self::assertStringNotContainsString('Sick Leave', $pdf, 'The pre-printed Sick label is left alone.');
    }

    public function test_the_leave_application_prints_a_second_date_range(): void
    {
        $document = new LeaveApplication(
            employee: FormFixture::employee(),
            office: FormFixture::office(),
            applicationId: '2026-0453',
            leaveTypeId: 3,
            leaveTypeName: 'Special Privilege Leave',
            days: 2,
            month: '11',
            fromDay: '10',
            year: '2026',
            secondMonth: '12',
            secondFromDay: '01',
            secondYear: '2026',
            salary: 45000.0,
            filedOn: '2026-11-06',
        );

        $pdf = $this->renderer()->render($document);

        self::assertStringContainsString('November 10, 2026 - December 01, 2026', $pdf);
    }

    public function test_the_cto_form_is_printed_twice_on_one_page(): void
    {
        $pdf = $this->renderer()->render(FormFixture::ctoApplication());

        self::assertSame(1, TemplateFactory::pageCount($pdf));
        // The office name and the office head are printed once per copy.
        self::assertSame(2, substr_count($pdf, '(Provincial Human Resource Management Office) Tj'));
        self::assertSame(2, substr_count($pdf, '(MARIA L. SANTOS) Tj'));
        // The balance is only printed on the first copy.
        self::assertSame(1, substr_count($pdf, '(8.75) Tj'));
    }

    public function test_the_money_value_prints_the_computation(): void
    {
        $pdf = $this->renderer()->render(FormFixture::moneyValue());

        self::assertStringContainsString('45,000.00', $pdf);
        self::assertStringContainsString('27.750', $pdf);
        self::assertStringContainsString('59,701.11', $pdf);
        self::assertStringContainsString('March 14, 1985', $pdf);
    }

    public function test_the_retirement_schedule_prints_the_service_duration(): void
    {
        $pdf = $this->renderer()->render(FormFixture::retirementSchedule());

        self::assertStringContainsString('24 years', $pdf);
        self::assertStringContainsString('8 months', $pdf);
        self::assertStringContainsString('13 days', $pdf);
        self::assertStringContainsString('14-Mar-85', $pdf);
        self::assertStringContainsString('Province of Marinduque', $pdf);
        self::assertStringContainsString('59,701.11', $pdf);
    }

    public function test_the_statement_of_leave_prints_every_period_and_the_totals(): void
    {
        $pdf = $this->renderer()->render(FormFixture::statementOfLeave());

        self::assertStringContainsString('07/01/05', $pdf);
        self::assertStringContainsString('Period Covered', $pdf);
        self::assertStringContainsString('Leave Earned', $pdf);
        self::assertStringContainsString('PREPARED BY:', $pdf);
        self::assertStringContainsString('31.250', $pdf);
        self::assertStringContainsString('51.000', $pdf);
    }

    public function test_several_documents_become_one_pdf(): void
    {
        $pdf = $this->renderer()->renderMany([
            FormFixture::moneyValue(),
            FormFixture::retirementSchedule(),
        ]);

        self::assertSame(2, TemplateFactory::pageCount($pdf));
    }

    public function test_the_report_can_be_saved_to_a_file(): void
    {
        $path = $this->directory . '/out.pdf';

        $this->renderer()->save(FormFixture::leaveCertification(), $path);

        self::assertFileExists($path);
        self::assertStringStartsWith('%PDF', (string) file_get_contents($path));
    }

    public function test_a_missing_template_is_reported(): void
    {
        $layout = FormLayout::legacyIhrmis([
            FormLayout::LEAVE_CERTIFICATION => $this->directory . '/missing.pdf',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Template not found');

        (new FormRenderer($layout))->render(FormFixture::leaveCertification());
    }

    public function test_the_service_record_renders_without_any_template(): void
    {
        $layout = new FormLayout(templates: [], compress: false);

        $pdf = (new FormRenderer($layout))->render(FormFixture::serviceRecord());

        self::assertSame(1, TemplateFactory::pageCount($pdf));
        self::assertStringContainsString('Employee\'s Service Record', $pdf);
    }

    public function test_nothing_to_render_is_reported(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No document to render');

        $this->renderer()->renderMany([]);
    }

    private function renderer(): FormRenderer
    {
        return new FormRenderer(
            FormLayout::legacyIhrmis($this->templates, compress: false),
            new FormOptions(lguCity: 'City of Boac', printedOn: '2026-11-06'),
        );
    }
}
