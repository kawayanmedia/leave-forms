# Using `kawayanmedia/leave-forms` in CodeIgniter

The package has no framework dependency. CodeIgniter only supplies the database
connection, and that stays in your repository implementation.

## 1. Install

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

- **CodeIgniter 4**: `app/Config/Paths.php` already loads `vendor/autoload.php`.
- **CodeIgniter 3**: set `$config['composer_autoload'] = TRUE;`.
- **CodeIgniter 2**: add `require_once dirname(__DIR__) . '/vendor/autoload.php';`
  to `index.php`.

## 2. Repository

```php
<?php

namespace App\Libraries;

use KawayanMedia\LeaveForms\Contract\ServiceRecordRepositoryInterface;
use KawayanMedia\LeaveForms\Document\ServiceRecord;
use KawayanMedia\LeaveForms\Model\Employee;
use KawayanMedia\LeaveForms\Model\Signatory;
use KawayanMedia\LeaveForms\Model\WorkHistoryRow;

final class ServiceRecordRepository implements ServiceRecordRepositoryInterface
{
    public function __construct(private readonly \CodeIgniter\Database\BaseConnection $db)
    {
    }

    public function find(string $employeeId): ?ServiceRecord
    {
        $employee = $this->db->table('employees')->where('id', $employeeId)->get()->getRowArray();

        if ($employee === null) {
            return null;
        }

        $work = array_map(
            static fn (array $row): WorkHistoryRow => WorkHistoryRow::fromArray($row),
            $this->db->table('pds_work_experience')
                ->where('employee_id', $employeeId)
                ->where('govt_service', 1)
                ->orderBy('inclusive_date_from', 'DESC')
                ->get()
                ->getResultArray(),
        );

        return new ServiceRecord(
            employee: Employee::fromArray($employee),
            work: $work,
            prepared: new Signatory($this->setting('sr_prepared'), $this->setting('sr_prepared_position')),
            certified: new Signatory($this->setting('sr_certified'), $this->setting('sr_certified_position')),
        );
    }
}
```

CodeIgniter 2/3 with the legacy models:

```php
class Service_record_repository implements \KawayanMedia\LeaveForms\Contract\ServiceRecordRepositoryInterface
{
    public function find(string $employeeId): ?\KawayanMedia\LeaveForms\Document\ServiceRecord
    {
        $employee = new Employee_m();
        $employee->get_by_id($employeeId);

        if ( ! $employee->exists())
        {
            return NULL;
        }

        $work = new Work();
        $work->where('govt_service', '1');
        $work->order_by('inclusive_date_from', 'DESC');
        $rows = $work->get_by_employee_id($employeeId);

        return \KawayanMedia\LeaveForms\Document\ServiceRecord::fromArray([
            'employee' => $employee->to_array(),
            'work' => $rows->all_to_array(),
            'prepared' => ['name' => Setting::getField('sr_prepared'), 'position' => Setting::getField('sr_prepared_position')],
            'certified' => ['name' => Setting::getField('sr_certified'), 'position' => Setting::getField('sr_certified_position')],
        ]);
    }
}
```

## 3. Controller

### CodeIgniter 4

```php
<?php

namespace Modules\LeaveManage\Controllers;

use App\Controllers\BaseController;
use App\Libraries\LeaveApplicationRepository;
use KawayanMedia\LeaveForms\Config\FormLayout;
use KawayanMedia\LeaveForms\Config\FormOptions;
use KawayanMedia\LeaveForms\FormRenderer;

final class LeavePdf extends BaseController
{
    public function application(string $applicationId)
    {
        $renderer = new FormRenderer(
            FormLayout::fromDirectory(ROOTPATH . 'dtr/template'),
            new FormOptions(printedOn: $this->request->getGet('as_of')),
        );

        $document = (new LeaveApplicationRepository(db_connect()))->find($applicationId);

        if ($document === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setBody($renderer->render($document));
    }
}
```

### CodeIgniter 2/3

`stream()` and `download()` send the FPDF headers through `header()`, which is
what the legacy controller did with `Output(..., 'I')`. Call `exit` afterwards so
the framework does not append its own output:

```php
public function leave_apps($application_id)
{
    $renderer = new \KawayanMedia\LeaveForms\FormRenderer(
        \KawayanMedia\LeaveForms\Config\FormLayout::fromDirectory(FCPATH . 'dtr/template')
    );

    $document = (new Leave_application_repository())->find($application_id);

    $renderer->stream($document, 'leave-' . $application_id . '.pdf');

    exit;
}
```

## 4. Replacing the legacy screens

| Legacy call | Package call |
|---|---|
| `$this->reports->leave_certification($vl, $sl, $id)` | `$renderer->render($certification)` |
| `$this->reports->leave_apps($id)` | `$renderer->render($application)` |
| `$this->reports->cto_apps($id)` | `$renderer->render($cto)` |
| `$this->reports->statement_leave($no, $id)` | `$renderer->render($statement)` |
| `$this->reports->money_value($date, $balance, $id)` | `$renderer->renderMany([$moneyValue, $retirementSchedule])` |
| `$this->pds->sr_print_preview($id)` | `$renderer->render($serviceRecord)` |

Nothing is written to disk unless you call `save()`, so the legacy
`dtr/reports/*.pdf` scratch files and the `concat_pdf` step disappear.

## 5. Templates

```php
// the iHRMIS files, unchanged
FormLayout::fromDirectory(FCPATH . 'dtr/template');

// or the copies bundled with the package
FormLayout::legacyIhrmis();
```

`dtr/template` needs `certification4.pdf`, `APPLICATION_FOR_LEAVE.pdf`,
`app_for_offset.pdf`, `leave/computation_money_value.pdf`,
`leave/schedule_retirement_pay.pdf` and `statement_leave.pdf` — the same files
the legacy controllers loaded.
