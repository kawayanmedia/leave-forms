# kawayanmedia/leave-forms

Framework-agnostic PDF generator for the HR forms that sit beside the DTR and the
Personal Data Sheet:

| Document | Legacy source | Template | Page |
|---|---|---|---|
| Employee's Service Record | `Sr_preview::preview()` | none (blank) | Letter P |
| Certificate of leave balances | `Reports::leave_certification()` | `certification4.pdf` | Letter P |
| Application for leave | `Reports::leave_apps()` | `APPLICATION_FOR_LEAVE.pdf` | A4 P |
| Application for compensatory time off | `Reports::cto_apps()` | `app_for_offset.pdf` | Legal P (two copies) |
| Computation of money value | `Reports::money_value()` | `computation_money_value.pdf` | Letter P |
| Schedule of retirement pay | `Reports::schedule_retirement_pay()` | `schedule_retirement_pay.pdf` | Legal L |
| Statement of leave | `Reports::statement_leave()` | `statement_leave.pdf` | Legal P |

Database access, HTTP handling and filesystem writes are injected, so the package
works in CodeIgniter, Laravel, Symfony or plain PHP. One document becomes one
page (the CTO form carries its two copies on that page) and `renderMany()` puts
several documents into a single PDF — the legacy "write both files then run
`concat_pdf`" step is gone.

## Requirements

- PHP 8.2+
- `setasign/fpdi` ^2.6 and `setasign/fpdf` ^1.9 (installed automatically)
- The six form templates. Copies of the iHRMIS ones ship in
  `resources/templates/`; you can point the layout at your own files.

## Installation

```bash
composer require kawayanmedia/leave-forms
```

While the package lives inside the iHRMIS repository:

```json
{
    "repositories": [
        { "type": "path", "url": "packages/leave-forms", "options": { "symlink": true } }
    ],
    "require": {
        "kawayanmedia/leave-forms": "^0.1"
    }
}
```

`kawayanmedia/persistence` comes with it as a dependency; it is only used by the
write side, so printing needs no database at all.

## See it running

```bash
php packages/leave-forms/examples/generate-sample.php
open packages/leave-forms/examples/output/sample-leave-application.pdf
```

The script prints one of every document from the bundled templates and also a
combined money-value + retirement-schedule PDF.

The write side has its own runnable example, which files, approves and cancels a
leave application, earns leave credits and files a CTO:

```bash
php packages/leave-forms/examples/write-sample.php
```

## Quick start

```php
use KawayanMedia\LeaveForms\Config\FormLayout;
use KawayanMedia\LeaveForms\Config\FormOptions;
use KawayanMedia\LeaveForms\Document\LeaveApplication;
use KawayanMedia\LeaveForms\FormRenderer;
use KawayanMedia\LeaveForms\Model\Employee;
use KawayanMedia\LeaveForms\Model\LeaveBalance;
use KawayanMedia\LeaveForms\Model\Office;
use KawayanMedia\LeaveForms\Model\Signatory;

$renderer = new FormRenderer(
    FormLayout::legacyIhrmis(),                  // bundled templates
    new FormOptions(printedOn: '2026-11-06'),
);

$application = new LeaveApplication(
    employee: Employee::fromArray($employeeRow),
    office: Office::fromArray($officeRow),
    balance: new LeaveBalance(15.5, 12.25),
    applicationId: '2026-0451',
    leaveTypeId: 1,
    leaveTypeName: 'Vacation Leave',
    days: 3,
    month: '11',
    fromDay: '10',
    year: '2026',
    salary: 45000.0,
    filedOn: '2026-11-06',
    certifiedBy: new Signatory('Maria L. Santos', 'Provincial Human Resource Officer'),
);

$bytes = $renderer->render($application);
$renderer->save($application, '/tmp/leave.pdf');
$renderer->stream($application, 'leave.pdf');

// Several documents in one PDF (replaces the legacy concatenation step)
$renderer->saveMany([$moneyValue, $retirementSchedule], '/tmp/retirement-pack.pdf');
```

Constructing documents directly is the shortest path; `fromArray()` accepts the
legacy table rows when your repository returns arrays.

## Architecture

```
rows from your database ──► RepositoryInterface ──► Document (typed model)
                                                          │
                                                     FormRenderer
                                                          │
                          ┌───────────────┬───────────────┼───────────────┬──────────────┐
                          ▼               ▼               ▼               ▼              ▼
                   ServiceRecordPage  LeaveCertification  LeaveApplication  CtoApplication…
                          │               │               │               │
                          └───────────────┴───── PageWriter + FormLayout ──┴──────────────┘
                                                          │
                                              FPDI + your templates ──► %PDF
```

- `FormLayout` holds the templates and the page geometry of every document.
- `Config\Layout\*Layout` holds every legacy coordinate, font size and gap.
- `Document\*` holds the data **and** the computations (balances, monetisation).
- `Renderer\Page\*Page` draws one document.

| Legacy | Package |
|---|---|
| `SetXY()` / `Write()` / `Cell()` chains | `Config\Layout\*Layout` + `Renderer\PageWriter` |
| `convert_long_date()` | `Support\Dates::longDate()` / `middleMonth()` |
| `Helps::get_month_name()` | `Support\Dates::monthName()` |
| `character_limiter()` | `Support\Text::limitForPdf()` |
| `number_format()` rules | `Support\Money::amount()` / `credits()` / `peso()` |
| `Salary_grade::monetary_equivalent()` | `Support\Money::monetize()` |

## Contracts

One repository per document, so a host only implements what it prints:

```php
interface ServiceRecordRepositoryInterface      { public function find(string $employeeId): ?ServiceRecord; }
interface LeaveCertificationRepositoryInterface { public function find(string $employeeId): ?LeaveCertification; }
interface LeaveApplicationRepositoryInterface   { public function find(string $applicationId): ?LeaveApplication; }
interface CtoApplicationRepositoryInterface     { public function find(string $applicationId): ?CtoApplication; }
interface MoneyValueRepositoryInterface         { public function find(string $employeeId): ?MoneyValue; }
interface RetirementScheduleRepositoryInterface { public function find(string $employeeId): ?RetirementSchedule; }
interface StatementOfLeaveRepositoryInterface   { public function find(string $employeeId): ?StatementOfLeave; }
```

`Adapter\Array*Repository` implementations cover tests, examples and one-off
scripts.

## Computations preserved from the legacy code

- `total money = salary × leave balance × 0.0478087` (money value, retirement
  schedule and the statement of leave's grand total).
- `total leave = vacation + sick`.
- Service duration from `DateTime::diff()` printed as years / months / days,
  only the non-zero parts.
- CTO balance `= (balance + earned) − spent`; one day prints "1 day".
- Statement of leave: one line per year of service, running balances, a
  forwarded-balance row followed by that year's earn/spend row, totals, and the
  combined balance under the sick column.
- `number_format` precision per field: salary and money 2, leave credits 3.

## Deviations from the legacy controllers

| Legacy behaviour | Package behaviour |
|---|---|
| `'City of Puerto Pricesa'` hard-coded (and misspelt) in the leave certification | `FormOptions::$lguCity`, defaulting to `City of Puerto Princesa` |
| Leave application printed "Tracking no: …" over the pre-printed label | only the number is printed (the template carries the label) |
| `Write()` wrapped long right-aligned values onto the next line | those values use `Cell()` and stay on their line |
| Statement of leave period printed at x=30 mm, under the earned columns | printed inside the period column (x=12 mm) |
| `explode('-', 'Present')` / empty dates | `Support\Dates` returns `''` or `Present` |
| Money value returned a form when `op` was missing | pure renderer; dates come from the document |
| Money value + retirement schedule written to disk, then concatenated | `renderMany()` / `saveMany()` in memory |
| Office-head overrides for one employee id and one office row | `CtoApplication::$officeHead` (the host decides) |
| Every method sent `Cache-Control` / `Pragma` headers | the renderer returns bytes; the host sends headers |
| `Office->fields` and `Salary_grade->salary_grade_type` mutated | no state; values arrive on the models |
| A forwarded-leave note without a trailing year looped from year 0 | falls back to the current year |

## Writing data

Three services cover leave management, so the same package files, approves and
prints:

```php
use KawayanMedia\LeaveForms\Adapter\PdoLeaveStore;
use KawayanMedia\LeaveForms\Model\CtoRequest;
use KawayanMedia\LeaveForms\Model\LeaveApplicationRequest;
use KawayanMedia\LeaveForms\Service\CtoService;
use KawayanMedia\LeaveForms\Service\LeaveApplicationService;
use KawayanMedia\LeaveForms\Service\LeaveCardService;

$store = new PdoLeaveStore($db);
$applications = new LeaveApplicationService($store);
$card = new LeaveCardService($store);
$cto = new CtoService($store);

$id = $applications->file(new LeaveApplicationRequest('013001', 1, '2026-01-12', '2026-01-14', '7'));
$applications->approve($id, 'approver');   // leave_card row + DTR cover, one transaction
$applications->balances('013001');         // ['vacation' => float, 'sick' => float]

$card->earnMonth('013001', 2026, 1);       // 1.25 VL + 1.25 SL on the last day
$cto->file(new CtoRequest('013001', ['2026-01-21', '2026-01-22 am'], '7'));
```

The legacy `approved` values `0/1/2` are preserved, credits are checked only when
the service is constructed with `enforceBalance: true`, and every table name can
be overridden. See **[docs/writing-data.md](docs/writing-data.md)** for the
columns, the day-counting rules and the behaviour differences.

## Using it in another project

| Stack | Guide |
|---|---|
| Plain PHP (PDO, no framework) | [docs/plain-php.md](docs/plain-php.md) |
| Laravel (service provider, controller, job) | [docs/laravel.md](docs/laravel.md) |
| CodeIgniter 2 / 3 / 4 | [docs/codeigniter.md](docs/codeigniter.md) |

## Testing

```bash
vendor/bin/phpunit -c packages/leave-forms/phpunit.xml.dist
```

The feature tests build blank templates with FPDF, so they never depend on the
scanned forms. Unit tests cover the date and money helpers, the name shapes, the
document computations and the statement-of-leave row builder.

## Licence

MIT.
