<?php

declare(strict_types=1);

/**
 * Files, approves and cancels leave applications, runs the leave card and files
 * a CTO application, using SQLite so it can be run without a database server.
 *
 * Usage:
 *   php packages/leave-forms/examples/write-sample.php
 */

use KawayanMedia\LeaveForms\Adapter\PdoLeaveStore;
use KawayanMedia\LeaveForms\Model\CtoRequest;
use KawayanMedia\LeaveForms\Model\LeaveApplicationRequest;
use KawayanMedia\LeaveForms\Service\CtoService;
use KawayanMedia\LeaveForms\Service\LeaveApplicationService;
use KawayanMedia\LeaveForms\Service\LeaveCardService;
use KawayanMedia\LeaveForms\Support\LeaveDataset;
use KawayanMedia\Persistence\Adapter\PdoDatabase;

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

if (!class_exists(LeaveApplicationService::class)) {
    fwrite(STDERR, "Composer autoloader not found. Run composer install first.\n");
    exit(1);
}

$file = tempnam(sys_get_temp_dir(), 'leave-write-') . '.sqlite';
$database = PdoDatabase::fromDsn('sqlite:' . $file);

createSchema($database);

$store = new PdoLeaveStore($database);
$applications = new LeaveApplicationService($store);
$card = new LeaveCardService($store);
$cto = new CtoService($store);

$employeeId = '013001';

// 1. Earn January 2026 credits, semi-monthly.
$card->earnMidMonth($employeeId, 2026, 1);
$card->earnMonth($employeeId, 2026, 1, LeaveCardService::SEMI_MONTHLY_EARNING);

$balances = $applications->balances($employeeId);

printf("after earning: VL %s, SL %s\n", $balances['vacation'], $balances['sick']);

// 2. File a vacation leave for 12-14 January 2026 and approve it.
$id = $applications->file(new LeaveApplicationRequest(
    employeeId: $employeeId,
    leaveTypeId: 1,
    fromDate: '2026-01-12',
    toDate: '2026-01-14',
    officeId: '7',
    username: 'filer',
));

printf("filed application %s (days %s)\n", (string) $id, (string) $applications->find($id)['days']);

$applications->approve($id, 'approver');

$balances = $applications->balances($employeeId);

printf("after approval: VL %s, SL %s\n", $balances['vacation'], $balances['sick']);

foreach ($store->rows(LeaveDataset::CARD, ['employee_id' => $employeeId], ['orderBy' => ['id' => 'ASC']]) as $row) {
    printf(
        "card: %-28s %-8s %-8s earned %s/%s abs %s/%s\n",
        (string) $row['action_take'],
        (string) $row['date'],
        (string) $row['period'],
        (string) ($row['v_earned'] ?? '-'),
        (string) ($row['s_earned'] ?? '-'),
        (string) ($row['v_abs'] ?? '-'),
        (string) ($row['s_abs'] ?? '-'),
    );
}

foreach ($store->rows(LeaveDataset::MANUAL_LOG) as $cover) {
    printf("DTR cover: %s to %s (%s)\n", $cover['cover_if_ob_or_leave'], $cover['cover_if_ob_or_leave2'], $cover['notes']);
}

// 3. A sick leave left pending, then cancelled.
$pending = $applications->file(new LeaveApplicationRequest($employeeId, 2, '2026-01-19', '2026-01-20', '7'));

printf("pending applications: %d\n", count($applications->pending('7')));

$applications->cancel($pending);

printf("after cancel: %d pending\n", count($applications->pending('7')));

// 4. Compensatory time off, filed then approved.
$ctoId = $cto->file(new CtoRequest($employeeId, ['2026-01-21', '2026-01-22 am'], '7'));

printf("CTO %s filed, %s day(s)\n", (string) $ctoId, (string) $cto->find($ctoId)['days']);

$cto->approve($ctoId, 'approver');

foreach ($store->rows(LeaveDataset::DTR, ['employee_id' => $employeeId], ['orderBy' => ['log_date' => 'ASC']]) as $day) {
    printf(
        "dtr: %s am %s-%s pm %s-%s\n",
        $day['log_date'],
        (string) $day['am_login'],
        (string) $day['am_logout'],
        (string) $day['pm_login'],
        (string) $day['pm_logout'],
    );
}

unlink($file);

function createSchema(PdoDatabase $database): void
{
    $database->execute('create table leave_apps (id integer primary key autoincrement, employee_id text, office_id text, multiple text, month text, year text, month5 text, year5 text, multiple5 text, leave_type_id integer, special_priv_id text, days real, mone text, date_encode text, allow_sat_sun integer, username text, approved integer default 0, approved_by text)');
    $database->execute("create table leave_card (id integer primary key autoincrement, employee_id text, particulars text, action_take text, date text, period text default '', v_earned real, s_earned real, v_abs real, s_abs real, v_abs_wop real, v_balance real, s_balance real, enabled integer default 1, leave_type_id integer default 0, special_priv_id text, max_leave_date text, manual_log_id integer, pass_slip_date text)");
    $database->execute('create table compensatory_timeoffs (id integer primary key autoincrement, employee_id text, office_id text, month text, year text, days real, dates text, date_file text, type text, status text, approved_by text)');
    $database->execute('create table manual_log (id integer primary key autoincrement, employee_id text, office_id text, cover_if_ob_or_leave text, cover_if_ob_or_leave2 text, multiple integer, log_type integer, notes text, username text)');
    $database->execute('create table dtr (id integer primary key autoincrement, employee_id text, office_id text, log_date text, am_login text, am_logout text, pm_login text, pm_logout text)');
}
