# Writing leave data

Three services cover the write side of leave management:

| Service | Responsibility |
|---|---|
| `Service\LeaveApplicationService` | file, approve, disapprove, cancel, pending list and balances |
| `Service\LeaveCardService` | earnings, spending, forwardages, deletions |
| `Service\CtoService` | compensatory time off filing and approval |

```php
use KawayanMedia\LeaveForms\Adapter\PdoLeaveStore;
use KawayanMedia\LeaveForms\Model\LeaveApplicationRequest;
use KawayanMedia\LeaveForms\Service\LeaveApplicationService;
use KawayanMedia\Persistence\Adapter\PdoDatabase;

$store = new PdoLeaveStore(PdoDatabase::fromDsn('mysql:host=localhost;dbname=ihrmis', $user, $pass));
$service = new LeaveApplicationService($store);

$id = $service->file(new LeaveApplicationRequest(
    employeeId: '013001',
    leaveTypeId: 1,
    fromDate: '2026-01-12',
    toDate: '2026-01-14',
    officeId: '7',
));

$service->approve($id, 'approver');
$service->disapprove($otherId, 'approver');
$service->cancel($id);
```

## The approval flow

The legacy `leave_apps.approved` values are preserved: `0` pending, `1` approved,
`2` disapproved (`Model\LeaveStatus`).

`approve()` runs in one transaction and:

1. works out the day count when the row does not carry one,
2. writes the leave-card spending row (`v_abs` for vacation, `s_abs` for sick
   leave, nothing for maternity, paternity, solo parent and terminal leave),
3. writes the `manual_log` row that covers the dates in the DTR unless the
   service was constructed with `writeManualLog: false`,
4. sets `approved = 1` (and `approved_by` when a username is given).

`cancel()` deletes a pending application; for an approved one it first removes
the leave-card row and the DTR cover, so cancelling does not leave credits spent.

## Day counting

`Support\LeaveDays` reproduces the legacy counting rules:

- weekends and holidays are skipped,
- leave types `5` (maternity) and `20` count weekends and holidays anyway,
- `allowWeekends` counts every day in the range,
- a half day on the first or last day counts 0.5,
- the legacy token format is available through `LeaveDays::fromTokens()`
  (`['5', '6-7', '8am']`).

Holidays are passed to the constructor as `Y-m-d` strings:

```php
$days = new LeaveDays(['2026-01-01', '2026-04-09']);
$service = new LeaveApplicationService($store, $days);
```

## Leave card

```php
$card = new LeaveCardService($store);

$card->earnMonth('013001', 2026, 1);        // 1.25 VL + 1.25 SL on January 31
$card->earnMidMonth('013001', 2026, 1);     // 0.625 + 0.625 on January 15
$card->earn('013001', '2026-01-31', 1.25);  // one explicit earning row
$card->spend('013001', 'vacation', 3, 'January 12-14, 2026', '2026-01-12', 1);
$card->forward('013001', 12.5, 8, '2025-12-31', 'Year end');
$card->deleteForwarded('013001');
$card->deleteThrough('013001', '2025-01-31');
$card->deleteEarnings('013001', '2025-01-01', '2025-12-31');
```

Earnings are never duplicated: a row is only written when no row with the same
`period`, `v_earned`, `s_earned` and `date` exists, exactly like
`Leave_card::is_leave_earn_exists()`.

`LeaveApplicationService::balances()` folds the enabled card rows the way
`Leave::process_leave_card()` did, including the sick-leave-to-vacation
conversion and the terminal-leave reset. Pass `enforceBalance: true` to reject
applications that exceed the available credits; it is off by default because the
legacy checks were commented out.

## Compensatory time off

```php
$cto = new CtoService($store);

$id = $cto->file(new CtoRequest('013001', ['2026-01-21', '2026-01-22 am'], '7'));
$cto->approve($id, 'approver');
```

`file()` writes a `spent` row with `status = inactive`; `approve()` sets
`status = active` and marks the dates `offset` in the DTR (a half day only marks
its AM or PM pair). Pass `autoApprove: true` to reproduce the legacy case where
a non-manager filing produced an already active row.

## Tables and columns

| Dataset | Default table | Columns written |
|---|---|---|
| `applications` | `leave_apps` | `employee_id`, `office_id`, `multiple`, `month`, `year`, `month5`, `year5`, `multiple5`, `leave_type_id`, `special_priv_id`, `days`, `mone`, `date_encode`, `allow_sat_sun`, `username`, `approved`, `approved_by` |
| `card` | `leave_card` | `employee_id`, `particulars`, `action_take`, `date`, `period`, `v_earned`, `s_earned`, `v_abs`, `s_abs`, `v_abs_wop`, `v_balance`, `s_balance`, `enabled`, `leave_type_id`, `special_priv_id`, `max_leave_date`, `manual_log_id`, `pass_slip_date` |
| `cto` | `compensatory_timeoffs` | `employee_id`, `office_id`, `month`, `year`, `days`, `dates`, `date_file`, `type`, `status`, `approved_by` |
| `manual_log` | `manual_log` | `employee_id`, `office_id`, `cover_if_ob_or_leave`, `cover_if_ob_or_leave2`, `multiple`, `log_type`, `notes`, `username` |
| `dtr` | `dtr` | `employee_id`, `office_id`, `log_date`, `am_login`, `am_logout`, `pm_login`, `pm_logout` |

`Config\LeaveTables` maps datasets to tables and can be overridden. The package
ships its migrations in `database/migrations`; Laravel runs them through the
registered service provider, so run `php artisan migrate` after installing.
Hosts that already own these tables can map `LeaveTables` at their own names
and skip the package migrations.

## Custom persistence

Implement `Contract\LeaveStoreInterface` (dataset based CRUD plus a transaction)
to write through your own ORM. `insert()` returns the new id when the adapter can
report one, which is how `file()` hands back the application id.
`Adapter\ArrayLeaveStore` is the in-memory implementation used by the tests and
`examples/write-sample.php`.

## Differences from the legacy code

- Dates are real dates. The legacy screens stored a month, a year and a day
  token (`25-26`, `1am`); the package derives those columns from `fromDate` and
  `toDate` so the printed forms keep working.
- Duplicate pending applications are rejected instead of silently filed twice.
- A leave whose dates fall entirely on weekends or holidays is rejected.
- The sick-leave-to-vacation rewriting of `particulars` (VSL/WOP splitting) is
  applied to balances only; the stored particulars stay `3 SL` or `3 VL`.
- Multi-employee batch filing, the SPL balance warning and the hospital day
  override screens remain host responsibilities; pass an explicit `days` value to
  reproduce the override.
