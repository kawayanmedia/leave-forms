<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Tests\Feature;

use KawayanMedia\LeaveForms\Adapter\PdoLeaveStore;
use KawayanMedia\LeaveForms\Config\LeaveTables;
use KawayanMedia\LeaveForms\Model\CtoRequest;
use KawayanMedia\LeaveForms\Model\LeaveApplicationRequest;
use KawayanMedia\LeaveForms\Service\CtoService;
use KawayanMedia\LeaveForms\Service\LeaveApplicationService;
use KawayanMedia\LeaveForms\Service\LeaveCardService;
use KawayanMedia\LeaveForms\Support\LeaveDataset;
use KawayanMedia\Persistence\Adapter\PdoDatabase;
use PDO;
use PHPUnit\Framework\TestCase;

final class PdoLeaveStoreTest extends TestCase
{
    private PdoDatabase $database;

    private PdoLeaveStore $store;

    protected function setUp(): void
    {
        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            self::markTestSkipped('The pdo_sqlite extension is not installed.');
        }

        $this->database = PdoDatabase::fromDsn('sqlite::memory:');
        $this->store = new PdoLeaveStore($this->database);

        $this->database->execute('create table leave_apps (id integer primary key autoincrement, employee_id text, office_id text, multiple text, month text, year text, month5 text, year5 text, multiple5 text, leave_type_id integer, special_priv_id text, days real, mone text, date_encode text, allow_sat_sun integer, username text, approved integer default 0, approved_by text)');
        $this->database->execute("create table leave_card (id integer primary key autoincrement, employee_id text, particulars text, action_take text, date text, period text default '', v_earned real, s_earned real, v_abs real, s_abs real, v_abs_wop real, v_balance real, s_balance real, enabled integer default 1, leave_type_id integer default 0, special_priv_id text, max_leave_date text, manual_log_id integer, pass_slip_date text)");
        $this->database->execute('create table compensatory_timeoffs (id integer primary key autoincrement, employee_id text, office_id text, month text, year text, days real, dates text, date_file text, type text, status text, approved_by text)');
        $this->database->execute('create table manual_log (id integer primary key autoincrement, employee_id text, office_id text, cover_if_ob_or_leave text, cover_if_ob_or_leave2 text, multiple integer, log_type integer, notes text, username text)');
        $this->database->execute('create table dtr (id integer primary key autoincrement, employee_id text, office_id text, log_date text, am_login text, am_logout text, pm_login text, pm_logout text)');
        $this->database->execute('create table logs (id integer primary key autoincrement, module text, username text, office_id text, command text, details text, employee_id_affected text)');
    }

    public function testFilingAndApprovingALeaveApplication(): void
    {
        $service = new LeaveApplicationService($this->store);

        $id = $service->file(new LeaveApplicationRequest(
            employeeId: '1234',
            leaveTypeId: 1,
            fromDate: '2026-01-12',
            toDate: '2026-01-14',
            officeId: '7',
        ));

        self::assertSame(1, $id);
        self::assertSame(0, (int) $this->database->select('leave_apps', ['approved'])[0]['approved']);

        $service->approve($id, 'approver');

        $application = $this->database->select('leave_apps', ['*'], ['id' => $id])[0];

        self::assertSame(1, (int) $application['approved']);
        self::assertSame('approver', $application['approved_by']);

        $card = $this->database->select('leave_card', ['*'])[0];

        self::assertSame('3 VL', $card['particulars']);
        self::assertSame('January 12-14, 2026', $card['action_take']);
        self::assertSame(3.0, (float) $card['v_abs']);

        $cover = $this->database->select('manual_log', ['*'])[0];

        self::assertSame('2026-01-12', $cover['cover_if_ob_or_leave']);
        self::assertSame('2026-01-14', $cover['cover_if_ob_or_leave2']);
    }

    public function testCancellingAnApprovedApplicationCleansUp(): void
    {
        $service = new LeaveApplicationService($this->store);

        $id = $service->file(new LeaveApplicationRequest('1234', 1, '2026-01-12', '2026-01-13'));
        $service->approve($id);
        $service->cancel($id);

        self::assertSame(0, $this->database->count('leave_apps'));
        self::assertSame(0, $this->database->count('leave_card'));
        self::assertSame(0, $this->database->count('manual_log'));
    }

    public function testLeaveCardEarningsAndSpending(): void
    {
        $card = new LeaveCardService($this->store);

        self::assertTrue($card->earnMonth('1234', 2026, 1));
        self::assertFalse($card->earnMonth('1234', 2026, 1));

        $card->spend('1234', 'vacation', 1.25, 'January 12, 2026', '2026-01-12', 1);

        $service = new LeaveApplicationService($this->store);

        self::assertSame(0.0, $service->balances('1234')['vacation']);
        self::assertSame(1.25, $service->balances('1234')['sick']);
    }

    public function testCtoFilingAndApproval(): void
    {
        $service = new CtoService($this->store);

        $id = $service->file(new CtoRequest('1234', ['2026-01-12', '2026-01-13'], '7'));

        self::assertSame('inactive', $this->database->select('compensatory_timeoffs', ['status'])[0]['status']);

        $service->approve($id);

        self::assertSame('active', $this->database->select('compensatory_timeoffs', ['status'])[0]['status']);
        self::assertSame(2, $this->database->count('dtr'));
        self::assertSame('offset', $this->database->select('dtr', ['am_login'], ['log_date' => '2026-01-12'])[0]['am_login']);
    }

    public function testCustomTableNames(): void
    {
        $this->database->execute('create table hr_leave_apps (id integer primary key autoincrement, employee_id text, office_id text, multiple text, month text, year text, month5 text, year5 text, multiple5 text, leave_type_id integer, special_priv_id text, days real, mone text, date_encode text, allow_sat_sun integer, username text, approved integer)');

        $store = new PdoLeaveStore($this->database, LeaveTables::fromArray([
            LeaveDataset::APPLICATIONS => 'hr_leave_apps',
        ]));

        $service = new LeaveApplicationService($store);
        $id = $service->file(new LeaveApplicationRequest('1234', 1, '2026-01-12', '2026-01-13'));

        self::assertSame(1, $id);
        self::assertSame('1234', $this->database->select('hr_leave_apps', ['employee_id'])[0]['employee_id']);
    }

    public function testTransactionRollsBack(): void
    {
        try {
            $this->store->transaction(function (PdoLeaveStore $store): void {
                (new LeaveCardService($store))->earnMonth('1234', 2026, 1);

                throw new \RuntimeException('boom');
            });

            self::fail('The exception should have been rethrown.');
        } catch (\RuntimeException) {
            self::assertSame(0, $this->database->count('leave_card'));
        }
    }
}
