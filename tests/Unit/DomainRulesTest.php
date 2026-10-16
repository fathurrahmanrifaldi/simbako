<?php

namespace Tests\Unit;

use App\Domain\CashCalculator;
use App\Domain\FefoAllocator;
use App\Domain\InventoryCalculator;
use PHPUnit\Framework\TestCase;

class DomainRulesTest extends TestCase
{
    /**
     * TC-01: FEFO dasar.
     * Batch A exp 10 Nov 2026 sisa 5; Batch B exp 20 Nov 2026 sisa 10. Jual 7.
     * Hasil: A sisa 0 (terpakai 5), B sisa 8 (terpakai 2).
     */
    public function test_tc01_basic_fefo_allocation(): void
    {
        $batches = [
            [
                'id' => 'batch-B',
                'expiry_date' => '2026-11-20',
                'received_on' => '2026-10-01',
                'remaining_qty' => 10.0,
                'unit_cost' => 2000,
            ],
            [
                'id' => 'batch-A',
                'expiry_date' => '2026-11-10',
                'received_on' => '2026-10-02',
                'remaining_qty' => 5.0,
                'unit_cost' => 1900,
            ],
        ];

        $allocator = new FefoAllocator();
        $result = $allocator->allocateForSale($batches, 7.0, '2026-10-16');

        $this->assertEquals(0.0, $result['unallocated_qty']);
        $this->assertCount(2, $result['allocations']);

        // Batch A must be first because 10 Nov < 20 Nov
        $this->assertEquals('batch-A', $result['allocations'][0]['batch_id']);
        $this->assertEquals(5.0, $result['allocations'][0]['qty']);

        // Batch B is second
        $this->assertEquals('batch-B', $result['allocations'][1]['batch_id']);
        $this->assertEquals(2.0, $result['allocations'][1]['qty']);
    }

    /**
     * TC-02: Batch lewat exp tidak dipakai untuk penjualan.
     * Batch A exp 15 Okt 2026 sisa 3 (sudah expired per 16 Okt);
     * Batch B exp 20 Nov 2026 sisa 2. Jual 4.
     * Hasil: B sisa 0 (terpakai 2); kekurangan 2 dicatat tanpa batch (unallocated_qty = 2). A tetap 3 (tidak terpakai).
     */
    public function test_tc02_expired_batch_excluded_from_sale(): void
    {
        $batches = [
            [
                'id' => 'batch-A',
                'expiry_date' => '2026-10-15', // Expired yesterday
                'received_on' => '2026-09-01',
                'remaining_qty' => 3.0,
                'unit_cost' => 1500,
            ],
            [
                'id' => 'batch-B',
                'expiry_date' => '2026-11-20',
                'received_on' => '2026-10-01',
                'remaining_qty' => 2.0,
                'unit_cost' => 1600,
            ],
        ];

        $allocator = new FefoAllocator();
        $result = $allocator->allocateForSale($batches, 4.0, '2026-10-16');

        $this->assertCount(1, $result['allocations']);
        $this->assertEquals('batch-B', $result['allocations'][0]['batch_id']);
        $this->assertEquals(2.0, $result['allocations'][0]['qty']);
        $this->assertEquals(2.0, $result['unallocated_qty']); // negative unbatched quantity
    }

    /**
     * TC-07: Threshold hasil hitung.
     * D = 28, terjual 56 dalam 28 hari (daily_rate 2), lead_time 2 hari, safety_stock 4 (threshold 8).
     * Stok layak jual 8: tampil Menipis dengan days_left 4. Stok 9: tidak tampil.
     */
    public function test_tc07_inventory_threshold_and_days_left(): void
    {
        $calc = new InventoryCalculator();

        $dailyRate = $calc->calculateDailyRate(56.0, 28);
        $this->assertEquals(2.0, $dailyRate);

        $threshold = $calc->calculateThreshold(null, $dailyRate, 2, 4.0);
        $this->assertEquals(8.0, $threshold);

        $daysLeftAt8 = $calc->calculateDaysLeft(8.0, $dailyRate);
        $this->assertEquals(4.0, $daysLeftAt8);

        // At stock 8 <= threshold (8) => Menipis
        $this->assertTrue(8.0 <= $threshold);

        // At stock 9 > threshold (8) => Normal
        $this->assertFalse(9.0 <= $threshold);
    }

    /**
     * TC-08: min_stock menimpa threshold hitung.
     * Seperti TC-07 tetapi min_stock = 5.
     * Stok 6: tidak tampil. Stok 5: tampil.
     */
    public function test_tc08_min_stock_overrides_calculated_threshold(): void
    {
        $calc = new InventoryCalculator();
        $dailyRate = 2.0;

        $threshold = $calc->calculateThreshold(5.0, $dailyRate, 2, 4.0);
        $this->assertEquals(5.0, $threshold);

        $this->assertFalse(6.0 <= $threshold);
        $this->assertTrue(5.0 <= $threshold);
    }

    /**
     * TC-09: Data belum cukup.
     * Produk dengan riwayat 3 hari (D < 7) dan min_stock kosong.
     * Label: data belum cukup.
     */
    public function test_tc09_insufficient_history_evaluation(): void
    {
        $calc = new InventoryCalculator();
        $status = $calc->evaluateProductDataSufficiency(3, null);

        $this->assertEquals('data_belum_cukup', $status);
    }

    /**
     * TC-14: Kas.
     * opening_cash 100.000; penjualan tunai 450.000; pengeluaran 30.000;
     * pembelian paid_from_cash 120.000; kas fisik 395.000.
     * expected_cash = 100.000 + 450.000 - 30.000 - 120.000 = 400.000.
     * selisih = 395.000 - 400.000 = -5.000 (kurang Rp 5.000).
     */
    public function test_tc14_expected_cash_and_reconciliation(): void
    {
        $calc = new CashCalculator();

        $expected = $calc->calculateExpectedCash(100000, 450000, 30000, 120000);
        $this->assertEquals(400000, $expected);

        $diff = $calc->calculateCashDiff(395000, $expected);
        $this->assertEquals(-5000, $diff['diff']);
        $this->assertEquals('kurang Rp 5.000', $diff['formatted_diff']);
    }
}
