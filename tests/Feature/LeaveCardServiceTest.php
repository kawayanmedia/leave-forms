<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Tests\Feature;

use InvalidArgumentException;
use KawayanMedia\LeaveForms\Adapter\ArrayLeaveStore;
use KawayanMedia\LeaveForms\Service\LeaveCardService;
use KawayanMedia\LeaveForms\Support\LeaveDataset;
use PHPUnit\Framework\TestCase;

final class LeaveCardServiceTest extends TestCase
{
    private ArrayLeaveStore $store;

    private LeaveCardService $service;

    protected function setUp(): void
    {
        $this->store = new ArrayLeaveStore();
        $this->service = new LeaveCardService($this->store);
    }

    public function testMonthlyEarning(): void
    {
        self::assertTrue($this->service->earnMonth('1234', 2026, 1));

        $row = $this->store->dump(LeaveDataset::CARD)[0];

        self::assertSame('2026-01-31', $row['period']);
        self::assertSame('2026-01-31', $row['date']);
        self::assertSame(1.25, (float) $row['v_earned']);
        self::assertSame(1.25, (float) $row['s_earned']);
        self::assertSame(1, (int) $row['enabled']);
    }

    public function testEarningIsNotDuplicated(): void
    {
        self::assertTrue($this->service->earnMonth('1234', 2026, 2));
        self::assertFalse($this->service->earnMonth('1234', 2026, 2));

        self::assertCount(1, $this->store->dump(LeaveDataset::CARD));
        self::assertSame('2026-02-28', $this->store->dump(LeaveDataset::CARD)[0]['period']);
    }

    public function testSemiMonthlyEarnings(): void
    {
        self::assertTrue($this->service->earnMidMonth('1234', 2026, 1));
        self::assertTrue($this->service->earnMonth('1234', 2026, 1, LeaveCardService::SEMI_MONTHLY_EARNING));

        $rows = $this->store->dump(LeaveDataset::CARD);

        self::assertSame(['2026-01-15', '2026-01-31'], array_column($rows, 'period'));
        self::assertSame([0.625, 0.625], array_map('floatval', array_column($rows, 'v_earned')));
    }

    public function testSpendCreatesThenUpdatesTheRow(): void
    {
        $this->service->spend('1234', 'vacation', 3, 'January 12-14, 2026', '2026-01-12', 1);

        $row = $this->store->dump(LeaveDataset::CARD)[0];

        self::assertSame('3 VL', $row['particulars']);
        self::assertSame(3.0, (float) $row['v_abs']);

        $this->service->spend('1234', 'sick', 2, 'January 12-14, 2026', '2026-01-12', 2);

        $rows = $this->store->dump(LeaveDataset::CARD);

        self::assertCount(1, $rows);
        self::assertSame('2 SL', $rows[0]['particulars']);
        self::assertSame(2.0, (float) $rows[0]['s_abs']);
        self::assertSame(0.0, (float) ($rows[0]['v_abs'] ?? 0), 'The vacation absence is cleared.');
    }

    public function testSpendSupportsWithoutPay(): void
    {
        $this->service->spend('1234', 'wop', 1.5, 'January 12, 2026', '2026-01-12');

        $row = $this->store->dump(LeaveDataset::CARD)[0];

        self::assertSame('1.5 WOP', $row['particulars']);
        self::assertSame(1.5, (float) $row['v_abs_wop']);
    }

    public function testSpendRejectsAnUnknownKind(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->spend('1234', 'holiday', 1, 'January 12, 2026', '2026-01-12');
    }

    public function testForwardingAndItsCleanup(): void
    {
        $this->service->forward('1234', 12.5, 8, '2025-12-31', 'Year end');

        $row = $this->store->dump(LeaveDataset::CARD)[0];

        self::assertSame('Bal. 12.5 VL / 8 SL', $row['particulars']);
        self::assertSame(12.5, (float) $row['v_balance']);
        self::assertSame(8.0, (float) $row['s_balance']);

        self::assertSame(1, $this->service->deleteForwarded('1234'));
        self::assertSame([], $this->store->dump(LeaveDataset::CARD));
    }

    public function testDeleteThroughAndDeleteEarnings(): void
    {
        $this->service->forward('1234', 5, 5, '2025-01-31');
        $this->service->earnMonth('1234', 2025, 2);
        $this->service->earnMonth('1234', 2025, 3);

        self::assertSame(1, $this->service->deleteThrough('1234', '2025-01-31'));

        $rows = $this->store->dump(LeaveDataset::CARD);

        self::assertSame(['2025-02-28', '2025-03-31'], array_column($rows, 'period'));

        self::assertSame(1, $this->service->deleteEarnings('1234', '2025-02-01', '2025-02-28'));
        self::assertSame(['2025-03-31'], array_column($this->store->dump(LeaveDataset::CARD), 'period'));
    }

    public function testUndertimeCanBeDisabled(): void
    {
        $this->store->insert(LeaveDataset::CARD, ['employee_id' => '1234', 'particulars' => 'UT-0-1-0', 'date' => '2026-01-31', 'enabled' => 1]);
        $this->store->insert(LeaveDataset::CARD, ['employee_id' => '1234', 'particulars' => '1 VL', 'date' => '2026-01-31', 'enabled' => 1]);

        self::assertSame(1, $this->service->setUndertimeEnabled('2026-01-31', false));

        $rows = $this->store->dump(LeaveDataset::CARD);

        self::assertSame(0, (int) $rows[0]['enabled']);
        self::assertSame(1, (int) $rows[1]['enabled']);
    }

    public function testEntriesHonourTheEnabledFlagAndDateLimit(): void
    {
        $this->store->insert(LeaveDataset::CARD, ['employee_id' => '1234', 'v_earned' => 1.25, 'date' => '2026-01-31', 'enabled' => 1]);
        $this->store->insert(LeaveDataset::CARD, ['employee_id' => '1234', 'v_earned' => 1.25, 'date' => '2026-02-28', 'enabled' => 1]);
        $this->store->insert(LeaveDataset::CARD, ['employee_id' => '1234', 'v_earned' => 1.25, 'date' => '2026-03-31', 'enabled' => 0]);

        self::assertCount(2, $this->service->entries('1234'));
        self::assertCount(1, $this->service->entries('1234', '2026-01-31'));
    }

    public function testImpossibleMonthIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->earnMonth('1234', 2026, 13);
    }

    public function testZeroEarningIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->earnMonth('1234', 2026, 1, 0.0);
    }
}
