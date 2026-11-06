<?php

declare(strict_types=1);

/**
 * Generates one of every document so the package can be inspected without a
 * database.
 *
 * Usage:
 *   php packages/leave-forms/examples/generate-sample.php [template-directory] [output-directory]
 *
 * By default it uses the six templates bundled with the package
 * (resources/templates) and writes the PDFs to examples/output.
 */

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
use KawayanMedia\LeaveForms\Model\Employee;
use KawayanMedia\LeaveForms\Model\LeaveBalance;
use KawayanMedia\LeaveForms\Model\Office;
use KawayanMedia\LeaveForms\Model\Signatory;
use KawayanMedia\LeaveForms\Model\WorkHistoryRow;

$autoloaders = [
    dirname(__DIR__) . '/vendor/autoload.php',
    dirname(__DIR__, 3) . '/vendor/autoload.php',
    dirname(__DIR__, 3) . '/autoload.php',
    dirname(__DIR__, 4) . '/vendor/autoload.php',
];

foreach ($autoloaders as $autoloader) {
    if (is_file($autoloader)) {
        require $autoloader;
        break;
    }
}

if (!class_exists(FormRenderer::class)) {
    fwrite(STDERR, "Composer autoloader not found. Run composer install first.\n");
    exit(1);
}

$packageRoot = dirname(__DIR__);
$templateDirectory = $argv[1] ?? $packageRoot . '/resources/templates';
$outputDirectory = $argv[2] ?? $packageRoot . '/examples/output';

if (!is_file($templateDirectory . '/certification4.pdf')) {
    fwrite(STDERR, "certification4.pdf not found in: {$templateDirectory}\n");
    exit(1);
}

if (!is_dir($outputDirectory)) {
    mkdir($outputDirectory, 0777, true);
}

$employee = new Employee(
    id: '013001',
    salutation: 'Mr.',
    firstName: 'Juan',
    middleName: 'Santos',
    lastName: 'Dela Cruz',
    position: 'Information Technology Officer I',
    officeId: '013',
    salaryGrade: '19',
    step: '1',
    birthDate: '1985-03-14',
    firstDayOfService: '2005-07-01',
);

$office = new Office(
    name: 'Provincial Human Resource Management Office',
    code: 'PHRMO',
    head: 'Maria L. Santos',
    headPosition: 'Provincial Human Resource Officer',
);

$balance = new LeaveBalance(15.5, 12.25);

$hr = new Signatory('Maria L. Santos', 'Provincial Human Resource Officer');
$administrator = new Signatory('Pedro R. Reyes', 'Provincial Administrator');
$governor = new Signatory('Antonio L. Uy', 'Provincial Governor');

$documents = [
    'service-record' => new ServiceRecord(
        employee: $employee,
        work: [
            new WorkHistoryRow('2010-01-04', 'Present', 'Information Technology Officer I', 'Provincial Government of Marinduque', 'Original', 'Permanent'),
            new WorkHistoryRow('2006-02-01', '2009-12-31', 'Computer Operator', 'Marinduque State College', 'Original', 'Contractual'),
            new WorkHistoryRow('2005-07-01', '2006-01-31', 'Encoder', 'Municipality of Boac', 'Original', 'Casual'),
        ],
        prepared: $hr,
        certified: $administrator,
    ),
    'leave-certification' => new LeaveCertification(
        employee: $employee,
        office: $office,
        balance: $balance,
        noticedBy: $hr,
        notedBy: $governor,
        requestedOn: '2026-11-06',
        issuedOn: '2026-11-06',
    ),
    'leave-application' => new LeaveApplication(
        employee: $employee,
        office: $office,
        balance: $balance,
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
        certifiedBy: $hr,
    ),
    'cto-application' => new CtoApplication(
        employee: $employee,
        office: $office,
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
        officeHead: $hr,
        certifiedBy: $administrator,
    ),
    'money-value' => new MoneyValue(
        employee: $employee,
        salary: 45000.0,
        leaveBalance: 27.75,
        prepared: $hr,
        certified: $administrator,
        period: 'January 1, 2012 May 10, 2012',
        retirementDate: '2030-03-14',
    ),
    'retirement-schedule' => new RetirementSchedule(
        employee: $employee,
        office: $office,
        balance: $balance,
        salary: 45000.0,
        lguName: 'Province of Marinduque',
        period: 'January 1, 2012 May 10, 2012',
        retirementDate: '2030-03-14',
        prepared: $hr,
        approved: $governor,
        certified: $administrator,
        attested: new Signatory('Ana C. Cruz', 'Provincial Treasurer'),
        availability: new Signatory('Jose D. Santos', 'Provincial Accountant'),
        noted: new Signatory('Liza M. Reyes', 'Provincial Budget Officer'),
        issuedOn: '2026-11-06',
    ),
    'statement-of-leave' => new StatementOfLeave(
        employee: $employee,
        office: $office,
        years: [
            2006 => ['vacation_earned' => 15.0, 'sick_earned' => 15.0, 'vacation_spent' => 5.0, 'sick_spent' => 2.5],
            2007 => ['vacation_earned' => 15.0, 'sick_earned' => 15.0, 'vacation_spent' => 3.0, 'sick_spent' => 1.0],
            2026 => ['vacation_earned' => 1.25, 'sick_earned' => 1.25, 'vacation_spent' => 0.0, 'sick_spent' => 0.0],
        ],
        stepSalary: 45000.0,
        prepared: $hr,
        certified: $administrator,
        asOf: '2026-11-06',
    ),
];

$renderer = new FormRenderer(
    FormLayout::fromDirectory($templateDirectory),
    new FormOptions(printedOn: '2026-11-06'),
);

foreach ($documents as $key => $document) {
    $path = $outputDirectory . '/sample-' . $key . '.pdf';

    $renderer->save($document, $path);

    printf("%-22s %s\n", $key, $path);
}

$renderer->saveMany([$documents['money-value'], $documents['retirement-schedule']], $outputDirectory . '/sample-retirement-pack.pdf');

printf("Combined money value + retirement schedule written to %s\n", $outputDirectory . '/sample-retirement-pack.pdf');
