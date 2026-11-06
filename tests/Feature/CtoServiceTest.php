<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Tests\Feature;

use KawayanMedia\LeaveForms\Adapter\ArrayLeaveStore;
use KawayanMedia\LeaveForms\Exception\ValidationException;
use KawayanMedia\LeaveForms\Model\CtoRequest;
use KawayanMedia\LeaveForms\Service\CtoService;
use KawayanMedia\LeaveForms\Support\LeaveDataset;
use PHPUnit\Framework\TestCase;

final class CtoServiceTest extends TestCase
{
    private ArrayLeaveStore $store;

    private CtoService $service;

    protected function setUp(): void
    {
        $this->store = new ArrayLeaveStore();
        $this->service = new CtoService($this->store);
    }

    public function testFileStoresAPendingApplication(): void
    {
        $id = $this->service->file(new CtoRequest('1234', ['2026-01-12', '2026-01-13'], '7'));

        $row = $this->service->find($id);

        self::assertSame(CtoService::TYPE_SPENT, $row['type']);
        self::assertSame(CtoService::STATUS_PENDING, $row['status']);
        self::assertSame(2.0, (float) $row['days']);
        self::assertSame('12-13', $row['dates']);
        self::assertSame('01', $row['month']);
        self::assertSame('2026', $row['year']);
        self::assertSame([], $this->store->dump(LeaveDataset::DTR), 'Filing alone does not touch the DTR.');
    }

    public function testHalfDaysCountAsHalf(): void
    {
        $id = $this->service->file(new CtoRequest('1234', ['2026-01-12 am', '2026-01-13']));

        self::assertSame(1.5, (float) $this->service->find($id)['days']);
        self::assertSame('12am-13', $this->service->find($id)['dates']);
    }

    public function testAutoApproveMarksTheDtr(): void
    {
        $this->service->file(new CtoRequest('1234', ['2026-01-12'], '7'), autoApprove: true);

        $row = $this->store->dump(LeaveDataset::DTR)[0];

        self::assertSame('2026-01-12', $row['log_date']);
        self::assertSame('offset', $row['am_login']);
        self::assertSame('offset', $row['pm_logout']);
        self::assertSame('7', (string) $row['office_id']);
    }

    public function testApproveMarksTheDtrAndActivatesTheRow(): void
    {
        $id = $this->service->file(new CtoRequest('1234', ['2026-01-12', '2026-01-13'], '7'));

        $row = $this->service->approve($id, 'approver');

        self::assertSame(CtoService::STATUS_ACTIVE, $row['status']);
        self::assertSame(CtoService::STATUS_ACTIVE, $this->service->find($id)['status']);
        self::assertCount(2, $this->store->dump(LeaveDataset::DTR));
    }

    public function testApprovalReplacesAnExistingDtrRow(): void
    {
        $this->store->insert(LeaveDataset::DTR, ['employee_id' => '1234', 'log_date' => '2026-01-12', 'am_login' => '08:00']);

        $id = $this->service->file(new CtoRequest('1234', ['2026-01-12']));
        $this->service->approve($id);

        $rows = $this->store->dump(LeaveDataset::DTR);

        self::assertCount(1, $rows);
        self::assertSame('offset', $rows[0]['am_login']);
    }

    public function testHalfDayApprovalOnlyMarksOnePair(): void
    {
        $this->store->insert(LeaveDataset::DTR, ['employee_id' => '1234', 'log_date' => '2026-01-12', 'am_login' => '08:00', 'pm_login' => '13:00']);

        $id = $this->service->file(new CtoRequest('1234', ['2026-01-12 am']));
        $this->service->approve($id);

        $row = $this->store->dump(LeaveDataset::DTR)[0];

        self::assertSame('offset', $row['am_login']);
        self::assertSame('offset', $row['am_logout']);
        self::assertSame('13:00', $row['pm_login']);
    }

    public function testApprovalIsNotRepeatable(): void
    {
        $id = $this->service->file(new CtoRequest('1234', ['2026-01-12']));
        $this->service->approve($id);

        $this->expectException(ValidationException::class);

        $this->service->approve($id);
    }

    public function testCancelDeletesAPendingApplication(): void
    {
        $id = $this->service->file(new CtoRequest('1234', ['2026-01-12']));

        self::assertTrue($this->service->cancel($id));
        self::assertNull($this->service->find($id));
    }

    public function testAnApprovedApplicationCannotBeCancelled(): void
    {
        $id = $this->service->file(new CtoRequest('1234', ['2026-01-12']));
        $this->service->approve($id);

        $this->expectException(ValidationException::class);

        $this->service->cancel($id);
    }

    public function testPendingCanBeFilteredByOffice(): void
    {
        $this->service->file(new CtoRequest('1234', ['2026-01-12'], '7'));
        $this->service->file(new CtoRequest('5678', ['2026-01-12'], '9'));

        self::assertCount(2, $this->service->pending());
        self::assertCount(1, $this->service->pending('9'));
    }

    public function testBalance(): void
    {
        $this->store->insert(LeaveDataset::CTO, ['employee_id' => '1234', 'type' => CtoService::TYPE_EARNED, 'status' => CtoService::STATUS_ACTIVE, 'days' => 8]);
        $this->store->insert(LeaveDataset::CTO, ['employee_id' => '1234', 'type' => CtoService::TYPE_SPENT, 'status' => CtoService::STATUS_ACTIVE, 'days' => 2]);
        $this->store->insert(LeaveDataset::CTO, ['employee_id' => '1234', 'type' => CtoService::TYPE_SPENT, 'status' => CtoService::STATUS_PENDING, 'days' => 3]);

        self::assertSame(6.0, $this->service->balance('1234'));
    }

    public function testDatesValidation(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->file(new CtoRequest('1234', []));
    }

    public function testWeekendDatesAreRejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->file(new CtoRequest('1234', ['2026-01-10']));
    }

    public function testUnknownIdIsRejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->approve(999);
    }
}
