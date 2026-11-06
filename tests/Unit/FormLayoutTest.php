<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Tests\Unit;

use KawayanMedia\LeaveForms\Config\FormLayout;
use PHPUnit\Framework\TestCase;

final class FormLayoutTest extends TestCase
{
    public function test_the_bundled_templates_ship_with_the_package(): void
    {
        $layout = FormLayout::legacyIhrmis();

        foreach ([
            FormLayout::LEAVE_CERTIFICATION,
            FormLayout::LEAVE_APPLICATION,
            FormLayout::CTO_APPLICATION,
            FormLayout::MONEY_VALUE,
            FormLayout::RETIREMENT_SCHEDULE,
            FormLayout::STATEMENT_OF_LEAVE,
        ] as $key) {
            self::assertTrue($layout->hasTemplate($key), $key . ' template is missing');
        }
    }

    public function test_the_service_record_needs_no_template(): void
    {
        $layout = FormLayout::legacyIhrmis();

        self::assertSame('', $layout->template(FormLayout::SERVICE_RECORD));
        self::assertFalse($layout->hasTemplate(FormLayout::SERVICE_RECORD));
    }

    public function test_page_geometry_per_document(): void
    {
        $layout = FormLayout::legacyIhrmis();

        self::assertSame(['P', 'Letter'], [$layout->geometry(FormLayout::LEAVE_CERTIFICATION)['orientation'], $layout->geometry(FormLayout::LEAVE_CERTIFICATION)['pageSize']]);
        self::assertSame(['P', 'A4'], [$layout->geometry(FormLayout::LEAVE_APPLICATION)['orientation'], $layout->geometry(FormLayout::LEAVE_APPLICATION)['pageSize']]);
        self::assertSame(['L', 'Legal'], [$layout->geometry(FormLayout::RETIREMENT_SCHEDULE)['orientation'], $layout->geometry(FormLayout::RETIREMENT_SCHEDULE)['pageSize']]);
        self::assertSame(['P', 'Legal'], [$layout->geometry(FormLayout::STATEMENT_OF_LEAVE)['orientation'], $layout->geometry(FormLayout::STATEMENT_OF_LEAVE)['pageSize']]);
    }

    public function test_templates_can_be_pointed_at_a_directory(): void
    {
        $layout = FormLayout::fromDirectory('/tmp/forms');

        self::assertSame('/tmp/forms/certification4.pdf', $layout->template(FormLayout::LEAVE_CERTIFICATION));
        self::assertSame('/tmp/forms/statement_leave.pdf', $layout->template(FormLayout::STATEMENT_OF_LEAVE));
    }
}
