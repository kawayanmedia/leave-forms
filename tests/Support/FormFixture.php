<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Tests\Support;

use KawayanMedia\LeaveForms\Document\CtoApplication;
use KawayanMedia\LeaveForms\Document\LeaveApplication;
use KawayanMedia\LeaveForms\Document\LeaveCertification;
use KawayanMedia\LeaveForms\Document\MoneyValue;
use KawayanMedia\LeaveForms\Document\PrintableDocument;
use KawayanMedia\LeaveForms\Document\RetirementSchedule;
use KawayanMedia\LeaveForms\Document\ServiceRecord;
use KawayanMedia\LeaveForms\Document\StatementOfLeave;
use KawayanMedia\LeaveForms\Model\Employee;
use KawayanMedia\LeaveForms\Model\LeaveBalance;
use KawayanMedia\LeaveForms\Model\Office;
use KawayanMedia\LeaveForms\Model\Signatory;
use KawayanMedia\LeaveForms\Model\WorkHistoryRow;

/**
 * One ready-to-print set of every document.
 */
final class FormFixture
{
    /**
     * @return array<string, mixed>
     */
    public static function employeeRow(): array
    {
        return [
            'id' => '013001',
            'salut' => 'Mr.',
            'fname' => 'Juan',
            'mname' => 'Santos',
            'lname' => 'Dela Cruz',
            'position' => 'Information Technology Officer I',
            'office_id' => '013',
            'salary_grade' => '19',
            'step' => '1',
            'birth_date' => '1985-03-14',
            'first_day_of_service' => '2005-07-01',
        ];
    }

    public static function employee(): Employee
    {
        return Employee::fromArray(self::employeeRow());
    }

    /**
     * @return array<string, mixed>
     */
    public static function officeRow(): array
    {
        return [
            'office_name' => 'Provincial Human Resource Management Office',
            'office_code' => 'PHRMO',
            'office_head' => 'Maria L. Santos',
            'position' => 'Provincial Human Resource Officer',
            'salary_grade_type' => '',
        ];
    }

    public static function office(): Office
    {
        return Office::fromArray(self::officeRow());
    }

    public static function balance(): LeaveBalance
    {
        return new LeaveBalance(15.5, 12.25);
    }

    public static function serviceRecord(): ServiceRecord
    {
        return new ServiceRecord(
            employee: self::employee(),
            work: [
                new WorkHistoryRow('2010-01-04', 'Present', 'Information Technology Officer I', 'Provincial Government of Marinduque', 'Original', 'Permanent'),
                new WorkHistoryRow('2006-02-01', '2009-12-31', 'Computer Operator', 'Marinduque State College', 'Original', 'Contractual'),
                new WorkHistoryRow('2005-07-01', '2006-01-31', 'Encoder', 'Municipality of Boac', 'Original', 'Casual'),
            ],
            prepared: new Signatory('Maria L. Santos', 'Provincial Human Resource Officer'),
            certified: new Signatory('Pedro R. Reyes', 'Provincial Administrator'),
        );
    }

    public static function leaveCertification(): LeaveCertification
    {
        return new LeaveCertification(
            employee: self::employee(),
            office: self::office(),
            balance: self::balance(),
            noticedBy: new Signatory('Maria L. Santos', 'Provincial Human Resource Officer'),
            notedBy: new Signatory('Antonio L. Uy', 'Provincial Governor'),
            requestedOn: '2026-11-06',
            issuedOn: '2026-11-06',
        );
    }

    public static function leaveApplication(): LeaveApplication
    {
        return new LeaveApplication(
            employee: self::employee(),
            office: self::office(),
            balance: self::balance(),
            applicationId: '2026-0451',
            leaveTypeId: 1,
            leaveTypeName: 'Vacation Leave',
            days: 3,
            month: '11',
            fromDay: '10',
            year: '2026',
            salary: 45000.0,
            filedOn: '2026-11-06',
            lastEarned: '2026-10-31',
            certifiedBy: new Signatory('Maria L. Santos', 'Provincial Human Resource Officer'),
        );
    }

    public static function ctoApplication(): CtoApplication
    {
        return new CtoApplication(
            employee: self::employee(),
            office: self::office(),
            applicationId: '2026-0088',
            days: 2,
            month: '11',
            dates: '10-11',
            year: '2026',
            salary: 45000.0,
            balance: 7.25,
            earned: 3.0,
            spent: 1.5,
            filedOn: '2026-11-06',
            officeHead: new Signatory('Maria L. Santos', 'Provincial Human Resource Officer'),
            certifiedBy: new Signatory('Pedro R. Reyes', 'Provincial Administrator'),
        );
    }

    public static function moneyValue(): MoneyValue
    {
        return new MoneyValue(
            employee: self::employee(),
            salary: 45000.0,
            leaveBalance: 27.75,
            prepared: new Signatory('Maria L. Santos', 'Provincial Human Resource Officer'),
            certified: new Signatory('Pedro R. Reyes', 'Provincial Administrator'),
            period: 'January 1, 2012 May 10, 2012',
            retirementDate: '2030-03-14',
        );
    }

    public static function retirementSchedule(): RetirementSchedule
    {
        return new RetirementSchedule(
            employee: self::employee(),
            office: self::office(),
            balance: self::balance(),
            salary: 45000.0,
            lguName: 'Province of Marinduque',
            period: 'January 1, 2012 May 10, 2012',
            retirementDate: '2030-03-14',
            prepared: new Signatory('Maria L. Santos', 'Provincial Human Resource Officer'),
            approved: new Signatory('Antonio L. Uy', 'Provincial Governor'),
            certified: new Signatory('Pedro R. Reyes', 'Provincial Administrator'),
            attested: new Signatory('Ana C. Cruz', 'Provincial Treasurer'),
            availability: new Signatory('Jose D. Santos', 'Provincial Accountant'),
            noted: new Signatory('Liza M. Reyes', 'Provincial Budget Officer'),
            issuedOn: '2026-11-06',
        );
    }

    public static function statementOfLeave(): StatementOfLeave
    {
        return new StatementOfLeave(
            employee: self::employee(),
            office: self::office(),
            years: [
                2006 => ['vacation_earned' => 15.0, 'sick_earned' => 15.0, 'vacation_spent' => 5.0, 'sick_spent' => 2.5],
                2007 => ['vacation_earned' => 15.0, 'sick_earned' => 15.0, 'vacation_spent' => 3.0, 'sick_spent' => 1.0],
                2026 => ['vacation_earned' => 1.25, 'sick_earned' => 1.25, 'vacation_spent' => 0.0, 'sick_spent' => 0.0],
            ],
            stepSalary: 45000.0,
            prepared: new Signatory('Maria L. Santos', 'Provincial Human Resource Officer'),
            certified: new Signatory('Pedro R. Reyes', 'Provincial Administrator'),
            asOf: '2026-11-06',
        );
    }

    /**
     * @return array<string, PrintableDocument>
     */
    public static function all(): array
    {
        return [
            'service-record' => self::serviceRecord(),
            'leave-certification' => self::leaveCertification(),
            'leave-application' => self::leaveApplication(),
            'cto-application' => self::ctoApplication(),
            'money-value' => self::moneyValue(),
            'retirement-schedule' => self::retirementSchedule(),
            'statement-of-leave' => self::statementOfLeave(),
        ];
    }
}
