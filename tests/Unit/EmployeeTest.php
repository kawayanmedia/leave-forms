<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Tests\Unit;

use KawayanMedia\LeaveForms\Model\Employee;
use KawayanMedia\LeaveForms\Tests\Support\FormFixture;
use PHPUnit\Framework\TestCase;

final class EmployeeTest extends TestCase
{
    public function test_the_name_shapes_each_document_needs(): void
    {
        $employee = FormFixture::employee();

        self::assertSame('Dela Cruz, Juan Santos', $employee->lastFirst());
        self::assertSame('Mr. Juan Santos Dela Cruz', $employee->salutationName());
        self::assertSame('Juan S. Dela Cruz', $employee->middleInitialName());
        self::assertSame('Juan Santos Dela Cruz', $employee->firstMiddleLast());
        self::assertSame('S.', $employee->middleInitial());
    }

    public function test_the_legacy_columns_are_mapped(): void
    {
        $employee = Employee::fromArray([
            'employee_id' => '1',
            'salut' => 'Ms.',
            'salary_grade' => 19,
            'step' => 2,
            'first_day_of_service' => '2005-07-01',
        ]);

        self::assertSame('1', $employee->id);
        self::assertSame('Ms.', $employee->salutation);
        self::assertSame('19', $employee->salaryGrade);
        self::assertSame('2', $employee->step);
        self::assertSame('2005-07-01', $employee->firstDayOfService);
    }

    public function test_an_empty_middle_name_does_not_leave_a_gap(): void
    {
        $employee = Employee::fromArray(['fname' => 'Juan', 'lname' => 'Cruz']);

        self::assertSame('Juan Cruz', $employee->middleInitialName());
    }
}
