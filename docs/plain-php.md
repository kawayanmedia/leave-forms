# Using `kawayanmedia/leave-forms` in plain PHP

A complete, framework-free integration. The package needs Composer, PDO and the
form templates.

## 1. Install

```bash
composer require kawayanmedia/leave-forms
```

While the package lives inside the iHRMIS repository, point Composer at the
folder:

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

```php
require __DIR__ . '/vendor/autoload.php';
```

## 2. Implement the repositories you print

Each document has its own contract, so implement only what you need. The shape is
always the same: load the rows and return the typed document.

```php
use KawayanMedia\LeaveForms\Contract\LeaveApplicationRepositoryInterface;
use KawayanMedia\LeaveForms\Document\LeaveApplication;

final class PdoLeaveApplications implements LeaveApplicationRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function find(string $applicationId): ?LeaveApplication
    {
        $statement = $this->pdo->prepare('SELECT * FROM leave_apps WHERE id = ? LIMIT 1');
        $statement->execute([$applicationId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return LeaveApplication::fromArray([
            'application_id' => $row['id'],
            'employee' => $this->employee($row['employee_id']),
            'office' => $this->office($row['office_id'] ?? null),
            'balance' => $this->balance($row['employee_id']),
            'leave_type_id' => (int) $row['leave_type_id'],
            'leave_type' => $this->leaveTypeName((int) $row['leave_type_id']),
            'days' => (float) $row['days'],
            'month' => $row['month'],
            'from_day' => $row['multiple'],
            'year' => $row['year'],
            'second_month' => $row['month5'],
            'second_from_day' => $row['multiple5'],
            'second_year' => $row['year5'],
            'salary' => $this->monthlySalary($row['employee_id']),
            'filed_on' => $row['date_encode'],
            'last_earned' => $this->lastEarn($row['employee_id']),
            'certified_by' => ['name' => $this->setting('statement_certified'), 'position' => $this->setting('statement_certified_position')],
        ]);
    }
}
```

The `employee`, `office`, `balance`, `leaveTypeName()`, `monthlySalary()`,
`lastEarn()` and `setting()` helpers are your own queries — they are the same
lookups the legacy controller performed through `Employee`, `Office`,
`Leave_card`, `Leave_type`, `Salary_grade` and `Setting`.

## 3. Render

```php
use KawayanMedia\LeaveForms\Config\FormLayout;
use KawayanMedia\LeaveForms\Config\FormOptions;
use KawayanMedia\LeaveForms\FormRenderer;

$renderer = new FormRenderer(
    FormLayout::fromDirectory(__DIR__ . '/forms/leave'),
    new FormOptions(lguCity: 'City of Boaz', printedOn: date('Y-m-d')),
);

$application = (new PdoLeaveApplications($pdo))->find('2026-0451');

$renderer->save($application, __DIR__ . '/storage/leave-2026-0451.pdf');
$bytes = $renderer->render($application);
```

`stream()` and `download()` send the headers for you:

```php
$renderer->download($application, 'leave-2026-0451.pdf');
```

## 4. One PDF with several documents

```php
$moneyValue = (new PdoMoneyValues($pdo))->find('013001');
$schedule = (new PdoRetirementSchedules($pdo))->find('013001');

$renderer->saveMany([$moneyValue, $schedule], __DIR__ . '/storage/retirement-013001.pdf');
```

This replaces the legacy flow that wrote `money_value{id}.pdf` and
`schedule_retirement_pay{id}.pdf` and then concatenated them.

## 5. Templates

The bundled templates are the iHRMIS copies of each form. Point the layout at
your own scan when your agency prints a different version:

```php
$layout = FormLayout::legacyIhrmis([
    FormLayout::LEAVE_CERTIFICATION => __DIR__ . '/forms/certification.pdf',
    FormLayout::LEAVE_APPLICATION => __DIR__ . '/forms/application.pdf',
    FormLayout::CTO_APPLICATION => __DIR__ . '/forms/cto.pdf',
    FormLayout::MONEY_VALUE => __DIR__ . '/forms/money-value.pdf',
    FormLayout::RETIREMENT_SCHEDULE => __DIR__ . '/forms/retirement.pdf',
    FormLayout::STATEMENT_OF_LEAVE => __DIR__ . '/forms/statement.pdf',
]);
```

The service record is drawn on a blank page. If your agency pre-prints a
letterhead, pass it in the service-record layout:

```php
$layout = new FormLayout(
    templates: FormLayout::bundledTemplates(),
    serviceRecord: \KawayanMedia\LeaveForms\Config\Layout\ServiceRecordLayout::legacy()
        ->withTemplate(__DIR__ . '/forms/letterhead.pdf'),
);
```

## 6. Where the numbers come from

| Value | Source in iHRMIS |
|---|---|
| Leave balances | `Leave_card::get_total_leave_credits()` → `vacation`, `sick` |
| Yearly statement lines | `Leave_card::get_total_leave_earned()` / `get_total_leave_spent()` |
| Forwarded balances | `Leave_forwarded::get_forwarded_leave()` → `forwarded_vacation`, `forwarded_sick`, `forwarded_note` |
| Monthly salary | `Salary_grade::get_monthly_salary($grade, $step)` |
| Step amount for the monetary equivalent | `salary_grade.step{step}` of the employee's grade |
| Signatories | `Setting::getField('statement_prepared')`, `retirement_signatory_*`, `cto_certification*`, `sr_prepared*`, `money_value_signatory_*`, `notice_leave_balance*` |

The monetary equivalent can be supplied either way:

```php
// the legacy formula: days x 0.0478087 x step amount
new StatementOfLeave(..., stepSalary: 45000.0);

// or the amount you already computed
new StatementOfLeave(..., monetaryEquivalent: 59701.11);
```
