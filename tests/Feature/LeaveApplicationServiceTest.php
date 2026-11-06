<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Tests\Feature;

use KawayanMedia\LeaveForms\Adapter\ArrayLeaveStore;
use KawayanMedia\LeaveForms\Exception\ValidationException;
use KawayanMedia\LeaveForms\Model\LeaveApplicationRequest;
use KawayanMedia\LeaveForms\Model\LeaveStatus;
use KawayanMedia\LeaveForms\Service\LeaveApplicationService;
use KawayanMedia\LeaveForms\Support\LeaveDataset;
use PHPUnit\Framework\TestCase;

final class LeaveApplicationServiceTest extends TestCase
{
    private ArrayLeaveStore $store;

    private LeaveApplicationService $service;

    protected function setUp(): void
    {
        $this->store = new ArrayLeaveStore();
        $this->service = new LeaveApplicationService($this->store);
    }

    public function testFileStoresAPendingApplication(): void
    {
        $id = $this->service->file($this->request());

        self::assertSame(1, $id);

        $row = $this->service->find($id);

        self::assertNotNull($row);
        self::assertSame(LeaveStatus::PENDING, (int) $row['approved']);
        self::assertSame('2026', $row['year']);
        self::assertSame('01', $row['month']);
        self::assertSame('12-14', $row['multiple']);
        self::assertSame(3.0, (float) $row['days']);
        self::assertSame(1, (int) $row['leave_type_id']);
        self::assertSame('1234', $row['employee_id']);
        self::assertSame('7', (string) $row['office_id']);
        self::assertSame('', $row['month5']);
    }

    public function testFileRecordsASecondRangeWhenTheMonthsDiffer(): void
    {
        $id = $this->service->file(new LeaveApplicationRequest(
            employeeId: '1234',
            leaveTypeId: 1,
            fromDate: '2026-01-30',
            toDate: '2026-02-03',
        ));

        $row = $this->service->find($id);

        self::assertSame('30', $row['multiple']);
        self::assertSame('01', $row['month']);
        self::assertSame('2026', $row['year']);
        self::assertSame('3', $row['multiple5']);
        self::assertSame('02', $row['month5']);
        self::assertSame('2026', $row['year5']);
        self::assertSame(3.0, (float) $row['days'], 'Jan 30, Feb 2 and Feb 3 are working days.');
    }

    public function testFileUsesAnExplicitDayCount(): void
    {
        $id = $this->service->file(new LeaveApplicationRequest(
            employeeId: '1234',
            leaveTypeId: 2,
            fromDate: '2026-01-12',
            toDate: '2026-01-14',
            days: 1.5,
        ));

        self::assertSame(1.5, (float) $this->service->find($id)['days']);
    }

    public function testFileRejectsBadInput(): void
    {
        try {
            $this->service->file(new LeaveApplicationRequest('', 0, '', ''));
            self::fail('Expected a validation exception.');
        } catch (ValidationException $exception) {
            self::assertSame([
                'employee_id' => 'An employee id is required.',
                'leave_type_id' => 'A leave type is required.',
                'dates' => 'Both the start and the end date are required.',
            ], $exception->errors());
        }
    }

    public function testFileRejectsABackwardsRange(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->file(new LeaveApplicationRequest('1234', 1, '2026-01-14', '2026-01-12'));
    }

    public function testFileRejectsAWeekendOnlyRange(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->file(new LeaveApplicationRequest('1234', 1, '2026-01-10', '2026-01-11'));
    }

    public function testFileRejectsADuplicatePendingApplication(): void
    {
        $this->service->file($this->request());

        $this->expectException(ValidationException::class);

        $this->service->file($this->request());
    }

    public function testApproveWritesTheCardRowAndTheCover(): void
    {
        $id = $this->service->file($this->request());

        $row = $this->service->approve($id, 'approver');

        self::assertSame(LeaveStatus::APPROVED, (int) $row['approved']);
        self::assertSame(LeaveStatus::APPROVED, (int) $this->service->find($id)['approved']);

        $card = $this->store->dump(LeaveDataset::CARD);

        self::assertCount(1, $card);
        self::assertSame('3 VL', $card[0]['particulars']);
        self::assertSame('January 12-14, 2026', $card[0]['action_take']);
        self::assertSame(3.0, (float) $card[0]['v_abs']);
        self::assertSame(1, (int) $card[0]['leave_type_id']);
        self::assertSame('2026-01-12', $card[0]['date']);

        $cover = $this->store->dump(LeaveDataset::MANUAL_LOG);

        self::assertCount(1, $cover);
        self::assertSame('2026-01-12', $cover[0]['cover_if_ob_or_leave']);
        self::assertSame('2026-01-14', $cover[0]['cover_if_ob_or_leave2']);
        self::assertSame('January 12-14, 2026', $cover[0]['notes']);
        self::assertSame('approver', $cover[0]['username']);
    }

    public function testApproveChargesSickLeaveToSickCredits(): void
    {
        $id = $this->service->file(new LeaveApplicationRequest(
            employeeId: '1234',
            leaveTypeId: 2,
            fromDate: '2026-01-12',
            toDate: '2026-01-13',
        ));

        $this->service->approve($id);

        $card = $this->store->dump(LeaveDataset::CARD)[0];

        self::assertSame('2 SL', $card['particulars']);
        self::assertSame(2.0, (float) $card['s_abs']);
        self::assertArrayNotHasKey('v_abs', $card);
    }

    public function testApproveSkipsTheCardForNonDeductibleLeave(): void
    {
        $id = $this->service->file(new LeaveApplicationRequest(
            employeeId: '1234',
            leaveTypeId: 5,
            fromDate: '2026-01-12',
            toDate: '2026-01-16',
        ));

        $this->service->approve($id);

        self::assertSame([], $this->store->dump(LeaveDataset::CARD));
    }

    public function testApproveIsNotRepeatable(): void
    {
        $id = $this->service->file($this->request());
        $this->service->approve($id);

        $this->expectException(ValidationException::class);

        $this->service->approve($id);
    }

    public function testApproveComputesTheDaysWhenTheyAreMissing(): void
    {
        $id = $this->service->file($this->request());
        $this->store->update(LeaveDataset::APPLICATIONS, ['days' => 0], ['id' => $id]);

        $this->service->approve($id);

        self::assertSame(3.0, (float) $this->service->find($id)['days']);
    }

    public function testApproveEnforcesTheBalanceWhenAsked(): void
    {
        $service = new LeaveApplicationService($this->store, enforceBalance: true);

        $this->store->insert(LeaveDataset::CARD, ['employee_id' => '1234', 'v_earned' => 1.0, 'enabled' => 1]);

        $id = $service->file($this->request());

        $this->expectException(ValidationException::class);

        $service->approve($id);
    }

    public function testDisapprove(): void
    {
        $id = $this->service->file($this->request());

        $row = $this->service->disapprove($id, 'approver');

        self::assertSame(LeaveStatus::DISAPPROVED, (int) $row['approved']);
        self::assertSame(LeaveStatus::DISAPPROVED, (int) $this->service->find($id)['approved']);
    }

    public function testAnApprovedApplicationCannotBeDisapproved(): void
    {
        $id = $this->service->file($this->request());
        $this->service->approve($id);

        $this->expectException(ValidationException::class);

        $this->service->disapprove($id);
    }

    public function testCancelDeletesAPendingApplication(): void
    {
        $id = $this->service->file($this->request());

        self::assertTrue($this->service->cancel($id));
        self::assertNull($this->service->find($id));
        self::assertSame([], $this->store->dump(LeaveDataset::CARD));
    }

    public function testCancelReversesAnApprovedApplication(): void
    {
        $id = $this->service->file($this->request());
        $this->service->approve($id);

        $this->service->cancel($id);

        self::assertNull($this->service->find($id));
        self::assertSame([], $this->store->dump(LeaveDataset::CARD));
        self::assertSame([], $this->store->dump(LeaveDataset::MANUAL_LOG));
    }

    public function testUnknownApplicationIsRejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->approve(999);
    }

    public function testPendingCanBeFilteredByOffice(): void
    {
        $this->service->file($this->request());
        $this->service->file(new LeaveApplicationRequest('5678', 1, '2026-01-12', '2026-01-13', '9'));

        self::assertCount(2, $this->service->pending());
        self::assertCount(1, $this->service->pending('9'));
        self::assertSame('5678', $this->service->pending('9')[0]['employee_id']);
    }

    public function testBalancesFoldTheEnabledRows(): void
    {
        $this->store->insert(LeaveDataset::CARD, ['employee_id' => '1234', 'v_earned' => 1.25, 's_earned' => 1.25, 'enabled' => 1]);
        $this->store->insert(LeaveDataset::CARD, ['employee_id' => '1234', 'v_abs' => 1.25, 'enabled' => 1]);
        $this->store->insert(LeaveDataset::CARD, ['employee_id' => '1234', 'v_earned' => 99, 's_earned' => 99, 'enabled' => 0]);

        $balances = $this->service->balances('1234');

        self::assertSame(0.0, $balances['vacation'], '1.25 earned minus 1.25 spent.');
        self::assertSame(1.25, $balances['sick']);
    }

    public function testManualLogCanBeSwitchedOff(): void
    {
        $service = new LeaveApplicationService($this->store, writeManualLog: false);

        $id = $service->file($this->request());
        $service->approve($id);

        self::assertSame([], $this->store->dump(LeaveDataset::MANUAL_LOG));
        self::assertCount(1, $this->store->dump(LeaveDataset::CARD));
    }

    private function request(): LeaveApplicationRequest
    {
        return new LeaveApplicationRequest(
            employeeId: '1234',
            leaveTypeId: 1,
            fromDate: '2026-01-12',
            toDate: '2026-01-14',
            officeId: '7',
            username: 'filer',
        );
    }
}
