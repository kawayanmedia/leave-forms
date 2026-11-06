# Using `kawayanmedia/leave-forms` in Laravel

## 1. Install

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

## 2. Repositories

```php
<?php

namespace App\Leave;

use Illuminate\Support\Facades\DB;
use KawayanMedia\LeaveForms\Contract\ServiceRecordRepositoryInterface;
use KawayanMedia\LeaveForms\Document\ServiceRecord;
use KawayanMedia\LeaveForms\Model\Employee;
use KawayanMedia\LeaveForms\Model\Signatory;
use KawayanMedia\LeaveForms\Model\WorkHistoryRow;

final class DatabaseServiceRecords implements ServiceRecordRepositoryInterface
{
    public function find(string $employeeId): ?ServiceRecord
    {
        $employee = DB::table('employees')->where('id', $employeeId)->first();

        if ($employee === null) {
            return null;
        }

        $work = DB::table('pds_work_experience')
            ->where('employee_id', $employeeId)
            ->where('govt_service', 1)
            ->orderByDesc('inclusive_date_from')
            ->get()
            ->map(static fn (object $row): WorkHistoryRow => WorkHistoryRow::fromArray((array) $row))
            ->all();

        return new ServiceRecord(
            employee: Employee::fromArray((array) $employee),
            work: $work,
            prepared: new Signatory($this->setting('sr_prepared'), $this->setting('sr_prepared_position')),
            certified: new Signatory($this->setting('sr_certified'), $this->setting('sr_certified_position')),
        );
    }

    private function setting(string $name): string
    {
        return (string) DB::table('settings')->where('name', $name)->value('value');
    }
}
```

The other six documents follow the same pattern — one class per contract.

## 3. Service provider

```php
<?php

namespace App\Providers;

use App\Leave\DatabaseLeaveApplications;
use App\Leave\DatabaseServiceRecords;
use Illuminate\Support\ServiceProvider;
use KawayanMedia\LeaveForms\Config\FormLayout;
use KawayanMedia\LeaveForms\Config\FormOptions;
use KawayanMedia\LeaveForms\FormRenderer;

final class LeaveFormsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FormLayout::class, static fn (): FormLayout => FormLayout::fromDirectory(config('leave-forms.templates')));

        $this->app->singleton(FormOptions::class, static fn (): FormOptions => new FormOptions(
            lguCity: config('leave-forms.city'),
            lguName: config('leave-forms.lgu'),
        ));

        $this->app->singleton(FormRenderer::class, static fn ($app): FormRenderer => new FormRenderer(
            $app->make(FormLayout::class),
            $app->make(FormOptions::class),
        ));
    }
}
```

```php
// config/leave-forms.php
return [
    'templates' => resource_path('forms/leave'),
    'city' => 'City of Puerto Princesa',
    'lgu' => 'City Government of Puerto Princesa',
];
```

## 4. Controller

```php
<?php

namespace App\Http\Controllers;

use App\Leave\DatabaseLeaveApplications;
use Illuminate\Http\Response;
use KawayanMedia\LeaveForms\FormRenderer;

final class LeaveFormsController extends Controller
{
    public function __construct(private readonly FormRenderer $renderer)
    {
    }

    public function leaveApplication(string $application, DatabaseLeaveApplications $applications): Response
    {
        $document = $applications->find($application);

        abort_if($document === null, 404);

        return response($this->renderer->render($document), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="leave-' . $application . '.pdf"',
        ]);
    }

    public function retirementPack(string $employee, DatabaseMoneyValues $values, DatabaseRetirementSchedules $schedules)
    {
        $documents = array_filter([
            $values->find($employee),
            $schedules->find($employee),
        ]);

        return response($this->renderer->renderMany(array_values($documents)), 200, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
```

## 5. Queued generation

```php
final class GenerateLeavePdf implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly string $applicationId, public readonly string $path)
    {
    }

    public function handle(FormRenderer $renderer, DatabaseLeaveApplications $applications): void
    {
        $document = $applications->find($this->applicationId);

        if ($document !== null) {
            $renderer->save($document, $this->path);
        }
    }
}
```

## 6. Options

| Need | Option |
|---|---|
| Print another agency's city line | `new FormOptions(lguCity: '…')` |
| Back-date the printed date | `new FormOptions(printedOn: '2026-11-06')` |
| Change the salutation | `new FormOptions(salutation: 'Dear Sir/Madam:')` |
