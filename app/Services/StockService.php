<?php

namespace App\Services;

use App\Domain\FefoAllocator;
use App\Models\AuditLog;
use App\Models\Batch;
use App\Models\CashClosing;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Stocktake;
use App\Models\StocktakeItem;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockService
{
    protected FefoAllocator $fefoAllocator;

    public function __construct(?FefoAllocator $fefoAllocator = null)
    {
        $this->fefoAllocator = $fefoAllocator ?? new FefoAllocator();
    }

    /**
     * Get product's total physical stock across all movements.
     */
    public function getProductStockTotal(string $productId): float
    {
        $sum = StockMovement::where('product_id', $productId)->sum('qty');
        return round((float) $sum, 3);
    }

    /**
     * Get product's sellable stock = remaining unexpired batches + unbatched movements (which are negative).
     */
    public function getProductSellableStock(string $productId, ?string $referenceDate = null): float
    {
        $refDate = $referenceDate ?? now()->format('Y-m-d');

        // Sum remaining of unexpired batches
        $batches = $this->getProductBatchesWithRemaining($productId);
        $batchesSum = 0.0;
        foreach ($batches as $b) {
            if ($b['expiry_date'] !== null && $b['expiry_date'] < $refDate) {
                // Expired batch, excluded from sellable stock (TC-02)
                continue;
            }
            $batchesSum += (float) $b['remaining_qty'];
        }

        // Sum unbatched movements (batch_id is null)
        $unbatchedSum = StockMovement::where('product_id', $productId)
            ->whereNull('batch_id')
            ->sum('qty');

        return round($batchesSum + (float) $unbatchedSum, 3);
    }

    /**
     * Get remaining quantity of a single batch.
     */
    public function getBatchRemaining(string $batchId): float
    {
        $batch = Batch::findOrFail($batchId);
        $movementsSum = StockMovement::where('batch_id', $batchId)->sum('qty');
        return round((float) $movementsSum, 3);
    }

    /**
     * Get all batches for a product with their current remaining qty.
     */
    public function getProductBatchesWithRemaining(string $productId): array
    {
        $batches = Batch::where('product_id', $productId)
            ->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END, expiry_date ASC')
            ->orderBy('received_on', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $result = [];
        foreach ($batches as $batch) {
            $sum = StockMovement::where('batch_id', $batch->id)->sum('qty');
            $remaining = round((float) $sum, 3);

            $result[] = [
                'id' => $batch->id,
                'product_id' => $batch->product_id,
                'received_on' => $batch->received_on ? $batch->received_on->format('Y-m-d') : null,
                'expiry_date' => $batch->expiry_date ? $batch->expiry_date->format('Y-m-d') : null,
                'initial_qty' => (float) $batch->initial_qty,
                'remaining_qty' => max(0.0, $remaining),
                'unit_cost' => (int) $batch->unit_cost,
                'source' => $batch->source,
            ];
        }

        return $result;
    }

    /**
     * Record opening balance (OPN-06). Only allowed if product has 0 movements.
     */
    public function recordOpeningBalance(string $productId, float $qty, ?string $expiryDate, int $unitCost, string $userId): Batch
    {
        return DB::transaction(function () use ($productId, $qty, $expiryDate, $unitCost, $userId) {
            $product = Product::lockForUpdate()->findOrFail($productId);

            $hasMovements = StockMovement::where('product_id', $productId)->exists();
            if ($hasMovements) {
                throw new Exception('Produk sudah memiliki riwayat pergerakan stok. Gunakan stok opname untuk penyesuaian.');
            }

            if ($product->has_expiry && empty($expiryDate)) {
                throw new Exception('Tanggal kedaluwarsa wajib diisi untuk produk ini.');
            }

            $batch = Batch::create([
                'product_id' => $productId,
                'received_on' => now()->format('Y-m-d'),
                'expiry_date' => $expiryDate,
                'initial_qty' => $qty,
                'unit_cost' => $unitCost,
                'source' => 'opening',
            ]);

            StockMovement::create([
                'product_id' => $productId,
                'batch_id' => $batch->id,
                'type' => 'opening',
                'qty' => $qty,
                'ref_type' => 'batch',
                'ref_id' => $batch->id,
                'reason' => 'Saldo awal sistem',
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            if ($unitCost > 0) {
                $product->update(['last_buy_price' => $unitCost]);
            }

            AuditLog::log('opening_balance', 'products', $productId, null, [
                'qty' => $qty,
                'batch_id' => $batch->id,
                'unit_cost' => $unitCost,
            ], $userId);

            return $batch;
        });
    }

    /**
     * Record purchase (BEL-01, BEL-02).
     */
    public function recordPurchase(array $purchaseData, array $itemsData, string $userId): Purchase
    {
        return DB::transaction(function () use ($purchaseData, $itemsData, $userId) {
            $purchaseId = $purchaseData['id'] ?? (string) Str::uuid();

            // Check idempotency (TC-05 style)
            $existing = Purchase::with('items')->find($purchaseId);
            if ($existing) {
                return $existing;
            }

            if (empty($itemsData)) {
                throw new Exception('Pembelian harus memiliki minimal 1 item.');
            }

            $purchaseDate = $purchaseData['purchased_on'] ?? now()->format('Y-m-d');
            if ($purchaseDate > now()->format('Y-m-d')) {
                throw new Exception('Tanggal pembelian tidak boleh di masa depan.');
            }

            $total = 0;
            foreach ($itemsData as $it) {
                $total += ((int) $it['unit_price'] * (float) $it['qty']);
            }

            $purchase = Purchase::create([
                'id' => $purchaseId,
                'purchased_on' => $purchaseDate,
                'supplier' => $purchaseData['supplier'] ?? null,
                'total' => $total,
                'paid_from_cash' => $purchaseData['paid_from_cash'] ?? false,
                'status' => 'active',
                'created_by' => $userId,
            ]);

            foreach ($itemsData as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                if ($product->has_expiry) {
                    if (empty($item['expiry_date'])) {
                        throw new Exception("Tanggal kedaluwarsa wajib diisi untuk produk '{$product->name}'.");
                    }
                    if ($item['expiry_date'] < $purchaseDate) {
                        throw new Exception("Tanggal kedaluwarsa produk '{$product->name}' tidak boleh di masa lalu.");
                    }
                }

                $qty = round((float) $item['qty'], 3);
                $unitPrice = (int) $item['unit_price'];

                $batch = Batch::create([
                    'product_id' => $product->id,
                    'received_on' => $purchaseDate,
                    'expiry_date' => $item['expiry_date'] ?? null,
                    'initial_qty' => $qty,
                    'unit_cost' => $unitPrice,
                    'source' => 'purchase',
                ]);

                $purchaseItem = PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $product->id,
                    'batch_id' => $batch->id,
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'expiry_date' => $item['expiry_date'] ?? null,
                ]);

                $batch->update(['purchase_item_id' => $purchaseItem->id]);

                // Append-only stock movement
                StockMovement::create([
                    'product_id' => $product->id,
                    'batch_id' => $batch->id,
                    'type' => 'purchase',
                    'qty' => $qty,
                    'ref_type' => 'purchase',
                    'ref_id' => $purchase->id,
                    'reason' => 'Pembelian dari supplier: ' . ($purchase->supplier ?? '-'),
                    'created_by' => $userId,
                    'created_at' => now(),
                ]);

                // F-12: Update last_buy_price
                $product->update(['last_buy_price' => $unitPrice]);
            }

            return $purchase;
        });
    }

    /**
     * Void purchase (BEL-04, TC-04).
     */
    public function voidPurchase(string $purchaseId, string $voidReason, string $userId): void
    {
        DB::transaction(function () use ($purchaseId, $voidReason, $userId) {
            $purchase = Purchase::with('items')->lockForUpdate()->findOrFail($purchaseId);

            if ($purchase->status === 'void') {
                throw new Exception('Pembelian ini sudah dibatalkan sebelumnya.');
            }

            // Check if any batch from this purchase has other movements besides initial purchase
            foreach ($purchase->items as $item) {
                if ($item->batch_id) {
                    $otherMovements = StockMovement::where('batch_id', $item->batch_id)
                        ->where('type', '!=', 'purchase')
                        ->exists();

                    if ($otherMovements) {
                        throw new Exception("Sebagian barang '{$item->product->name}' dari pembelian ini sudah terjual atau bergerak. Koreksi lewat stok opname.");
                    }
                }
            }

            // Void each batch
            foreach ($purchase->items as $item) {
                if ($item->batch_id) {
                    $batch = Batch::lockForUpdate()->find($item->batch_id);
                    StockMovement::create([
                        'product_id' => $item->product_id,
                        'batch_id' => $batch->id,
                        'type' => 'void_purchase',
                        'qty' => -((float) $batch->initial_qty),
                        'ref_type' => 'purchase',
                        'ref_id' => $purchase->id,
                        'reason' => $voidReason,
                        'created_by' => $userId,
                        'created_at' => now(),
                    ]);

                    // Recalculate last_buy_price from most recent active purchase
                    $lastActivePurchaseItem = PurchaseItem::where('product_id', $item->product_id)
                        ->whereHas('purchase', function ($q) use ($purchaseId) {
                            $q->where('status', 'active')->where('id', '!=', $purchaseId);
                        })
                        ->orderBy('created_at', 'desc')
                        ->first();

                    $newLastBuy = $lastActivePurchaseItem ? $lastActivePurchaseItem->unit_price : 0;
                    Product::where('id', $item->product_id)->update(['last_buy_price' => $newLastBuy]);
                }
            }

            $purchase->update([
                'status' => 'void',
                'void_reason' => $voidReason,
                'voided_by' => $userId,
                'voided_at' => now(),
            ]);

            AuditLog::log('void_purchase', 'purchases', $purchaseId, null, [
                'reason' => $voidReason,
            ], $userId);
        });
    }

    /**
     * Record sale with FEFO allocation (JUL-01, JUL-02, JUL-03, JUL-04, TC-01, TC-02, TC-05).
     */
    public function recordSale(string $saleId, array $itemsData, string $userId, ?string $paymentMethod = 'cash'): Sale
    {
        return DB::transaction(function () use ($saleId, $itemsData, $userId, $paymentMethod) {
            // Idempotency check (TC-05)
            $existing = Sale::with('items')->find($saleId);
            if ($existing) {
                return $existing;
            }

            if (empty($itemsData)) {
                throw new Exception('Penjualan harus memiliki minimal 1 item.');
            }

            $total = 0;
            foreach ($itemsData as $it) {
                $unitPrice = (int) $it['unit_price'];
                $qty = (float) $it['qty'];
                $total += (int) round($unitPrice * $qty);
            }

            $sale = Sale::create([
                'id' => $saleId,
                'sold_at' => now(),
                'payment_method' => $paymentMethod ?? 'cash',
                'total' => $total,
                'status' => 'active',
                'created_by' => $userId,
            ]);

            $referenceDate = now()->format('Y-m-d');

            foreach ($itemsData as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                $qtyToSell = round((float) $item['qty'], 3);
                $unitPrice = (int) $item['unit_price'];
                $isPriceOverridden = (bool) ($item['price_overridden'] ?? false);

                // Fetch candidate batches with remaining qty
                $batches = $this->getProductBatchesWithRemaining($product->id);

                // FEFO allocation (excluding expired batches as per JUL-03, TC-02)
                $allocationResult = $this->fefoAllocator->allocateForSale($batches, $qtyToSell, $referenceDate);

                // 1. Record movements for allocated batches
                foreach ($allocationResult['allocations'] as $alloc) {
                    StockMovement::create([
                        'product_id' => $product->id,
                        'batch_id' => $alloc['batch_id'],
                        'type' => 'sale',
                        'qty' => -$alloc['qty'],
                        'ref_type' => 'sale',
                        'ref_id' => $sale->id,
                        'reason' => 'Penjualan FEFO',
                        'created_by' => $userId,
                        'created_at' => now(),
                    ]);
                }

                // 2. JUL-04: If remaining batches insufficient, record unallocated as unbatched negative movement
                if ($allocationResult['unallocated_qty'] > 0.0001) {
                    StockMovement::create([
                        'product_id' => $product->id,
                        'batch_id' => null, // stok minus
                        'type' => 'sale',
                        'qty' => -$allocationResult['unallocated_qty'],
                        'ref_type' => 'sale',
                        'ref_id' => $sale->id,
                        'reason' => 'Stok minus (kekurangan batch layak jual)',
                        'created_by' => $userId,
                        'created_at' => now(),
                    ]);
                }

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'qty' => $qtyToSell,
                    'unit_price' => $unitPrice,
                    'unit_cost' => $allocationResult['weighted_unit_cost'],
                    'price_overridden' => $isPriceOverridden,
                ]);
            }

            return $sale;
        });
    }

    /**
     * Void sale (JUL-06, TC-06).
     */
    public function voidSale(string $saleId, string $voidReason, string $userId, bool $isOwner): void
    {
        DB::transaction(function () use ($saleId, $voidReason, $userId, $isOwner) {
            $sale = Sale::lockForUpdate()->findOrFail($saleId);

            if ($sale->status === 'void') {
                throw new Exception('Penjualan ini sudah dibatalkan sebelumnya.');
            }

            if (!$isOwner) {
                // Pegawai restrictions: must be creator, same store day, cash not closed
                if ($sale->created_by !== $userId) {
                    throw new Exception('Pegawai hanya dapat membatalkan penjualan buatannya sendiri.');
                }

                $saleDate = $sale->sold_at->format('Y-m-d');
                $today = now()->format('Y-m-d');
                if ($saleDate !== $today) {
                    throw new Exception('Pegawai hanya dapat membatalkan penjualan pada hari yang sama.');
                }

                $isCashClosed = CashClosing::where('business_date', $today)->exists();
                if ($isCashClosed) {
                    throw new Exception('Kas hari ini sudah ditutup. Penjualan tidak dapat dibatalkan oleh pegawai.');
                }
            }

            // Find all sale movements for this sale
            $movements = StockMovement::where('ref_type', 'sale')
                ->where('ref_id', $saleId)
                ->where('type', 'sale')
                ->get();

            foreach ($movements as $m) {
                // Restore exactly to the same batch (or null for unbatched)
                StockMovement::create([
                    'product_id' => $m->product_id,
                    'batch_id' => $m->batch_id,
                    'type' => 'void_sale',
                    'qty' => abs((float) $m->qty),
                    'ref_type' => 'sale',
                    'ref_id' => $saleId,
                    'reason' => $voidReason,
                    'created_by' => $userId,
                    'created_at' => now(),
                ]);
            }

            $sale->update([
                'status' => 'void',
                'void_reason' => $voidReason,
                'voided_by' => $userId,
                'voided_at' => now(),
            ]);

            AuditLog::log('void_sale', 'sales', $saleId, null, [
                'reason' => $voidReason,
            ], $userId);
        });
    }

    /**
     * Record daily summary sale (JUL-07, TC-15).
     */
    public function recordDailySummarySale(string $summaryDate, array $itemsData, string $userId): Sale
    {
        return DB::transaction(function () use ($summaryDate, $itemsData, $userId) {
            $today = now()->format('Y-m-d');
            if ($summaryDate > $today) {
                throw new Exception('Tanggal rekap akhir hari tidak boleh di masa depan.');
            }

            // Verify no transaction-level sale exists for these products on summaryDate
            foreach ($itemsData as $item) {
                $hasTransactionSales = Sale::whereDate('sold_at', $summaryDate)
                    ->where('is_summary', false)
                    ->where('status', 'active')
                    ->whereHas('items', function ($q) use ($item) {
                        $q->where('product_id', $item['product_id']);
                    })
                    ->exists();

                if ($hasTransactionSales) {
                    $p = Product::find($item['product_id']);
                    throw new Exception("Produk '{$p->name}' sudah memiliki transaksi penjualan pada tanggal {$summaryDate}. Baris produk ditolak.");
                }
            }

            $saleId = (string) Str::uuid();
            $total = 0;
            foreach ($itemsData as $it) {
                $total += (int) round((int)$it['unit_price'] * (float)$it['qty']);
            }

            $sale = Sale::create([
                'id' => $saleId,
                'sold_at' => Carbon::parse($summaryDate . ' 23:59:59'),
                'payment_method' => 'cash',
                'total' => $total,
                'status' => 'active',
                'is_summary' => true,
                'summary_date' => $summaryDate,
                'created_by' => $userId,
            ]);

            foreach ($itemsData as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                $qty = round((float) $item['qty'], 3);
                $unitPrice = (int) $item['unit_price'];

                // Batches must have received_on <= summaryDate and not expired before summaryDate
                $candidateBatches = Batch::where('product_id', $product->id)
                    ->where('received_on', '<=', $summaryDate)
                    ->get();

                $batchList = [];
                foreach ($candidateBatches as $b) {
                    $sum = StockMovement::where('batch_id', $b->id)->sum('qty');
                    $batchList[] = [
                        'id' => $b->id,
                        'expiry_date' => $b->expiry_date ? $b->expiry_date->format('Y-m-d') : null,
                        'received_on' => $b->received_on ? $b->received_on->format('Y-m-d') : null,
                        'remaining_qty' => max(0.0, (float) $sum),
                        'unit_cost' => (int) $b->unit_cost,
                    ];
                }

                $allocation = $this->fefoAllocator->allocateForSale($batchList, $qty, $summaryDate);

                foreach ($allocation['allocations'] as $alloc) {
                    StockMovement::create([
                        'product_id' => $product->id,
                        'batch_id' => $alloc['batch_id'],
                        'type' => 'sale',
                        'qty' => -$alloc['qty'],
                        'ref_type' => 'sale',
                        'ref_id' => $sale->id,
                        'reason' => 'Rekap akhir hari',
                        'created_by' => $userId,
                        'created_at' => now(),
                    ]);
                }

                if ($allocation['unallocated_qty'] > 0.0001) {
                    StockMovement::create([
                        'product_id' => $product->id,
                        'batch_id' => null,
                        'type' => 'sale',
                        'qty' => -$allocation['unallocated_qty'],
                        'ref_type' => 'sale',
                        'ref_id' => $sale->id,
                        'reason' => 'Stok minus rekap akhir hari',
                        'created_by' => $userId,
                        'created_at' => now(),
                    ]);
                }

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'unit_cost' => $allocation['weighted_unit_cost'],
                    'price_overridden' => false,
                ]);
            }

            return $sale;
        });
    }

    /**
     * Record waste action (DSH-03: Buang barang expired / rusak).
     */
    public function recordWaste(string $batchId, float $qty, string $reason, string $userId): void
    {
        DB::transaction(function () use ($batchId, $qty, $reason, $userId) {
            $batch = Batch::lockForUpdate()->findOrFail($batchId);
            $remaining = $this->getBatchRemaining($batchId);

            if ($qty > $remaining + 0.0001) {
                throw new Exception("Jumlah buang ({$qty}) melebihi sisa batch ({$remaining}).");
            }

            StockMovement::create([
                'product_id' => $batch->product_id,
                'batch_id' => $batchId,
                'type' => 'waste',
                'qty' => -$qty,
                'ref_type' => 'batch',
                'ref_id' => $batchId,
                'reason' => $reason,
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            AuditLog::log('waste', 'batches', $batchId, null, [
                'qty' => $qty,
                'reason' => $reason,
            ], $userId);
        });
    }

    /**
     * Confirm stocktake adjustment (OPN-03, OPN-04, TC-11, TC-12, TC-13).
     */
    public function confirmStocktake(string $stocktakeId, array $itemsExpectedSystemQty, string $userId): Stocktake
    {
        return DB::transaction(function () use ($stocktakeId, $itemsExpectedSystemQty, $userId) {
            $stocktake = Stocktake::with('items.product')->lockForUpdate()->findOrFail($stocktakeId);

            if ($stocktake->status === 'confirmed') {
                throw new Exception('Sesi stok opname ini sudah dikonfirmasi.');
            }

            // OPN-03 / TC-13: Optimistic concurrency check against current system stock
            foreach ($stocktake->items as $item) {
                $currentSystemStock = $this->getProductStockTotal($item->product_id);
                $expected = (float) ($itemsExpectedSystemQty[$item->product_id] ?? $item->system_qty);

                if (abs($currentSystemStock - $expected) > 0.0001) {
                    throw new Exception("Konflik opname: Stok produk '{$item->product->name}' telah berubah selama sesi opname. Silakan muat ulang data.", 409);
                }

                // Update snapshot of system_qty
                $item->system_qty = $currentSystemStock;
                $item->save();
            }

            foreach ($stocktake->items as $item) {
                $diff = round((float) $item->physical_qty - (float) $item->system_qty, 3);
                if (abs($diff) <= 0.0001) {
                    continue; // No difference, no movement needed
                }

                if (empty($item->reason)) {
                    throw new Exception("Alasan selisih wajib dipilih untuk produk '{$item->product->name}'.");
                }

                if ($diff < 0) {
                    // OPN-04 / TC-11: Negative difference. FEFO earliest expiry first, INCLUDING expired batches.
                    $batches = $this->getProductBatchesWithRemaining($item->product_id);
                    $allocation = $this->fefoAllocator->allocateForAdjustment($batches, abs($diff));

                    foreach ($allocation['allocations'] as $alloc) {
                        StockMovement::create([
                            'product_id' => $item->product_id,
                            'batch_id' => $alloc['batch_id'],
                            'type' => 'adjustment',
                            'qty' => -$alloc['qty'],
                            'ref_type' => 'stocktake',
                            'ref_id' => $stocktake->id,
                            'reason' => $item->reason . ($item->note ? ": {$item->note}" : ''),
                            'created_by' => $userId,
                            'created_at' => now(),
                        ]);
                    }

                    if ($allocation['unallocated_qty'] > 0.0001) {
                        StockMovement::create([
                            'product_id' => $item->product_id,
                            'batch_id' => null,
                            'type' => 'adjustment',
                            'qty' => -$allocation['unallocated_qty'],
                            'ref_type' => 'stocktake',
                            'ref_id' => $stocktake->id,
                            'reason' => $item->reason . ($item->note ? ": {$item->note}" : ''),
                            'created_by' => $userId,
                            'created_at' => now(),
                        ]);
                    }
                } else {
                    // OPN-04 / TC-12: Positive difference. Create new batch source=adjustment with last_buy_price.
                    $product = Product::lockForUpdate()->findOrFail($item->product_id);

                    $expiryDate = null;
                    if ($product->has_expiry) {
                        // Default to future date if not given, or today + 30 days
                        $expiryDate = now()->addMonths(6)->format('Y-m-d');
                    }

                    $newBatch = Batch::create([
                        'product_id' => $product->id,
                        'received_on' => now()->format('Y-m-d'),
                        'expiry_date' => $expiryDate,
                        'initial_qty' => $diff,
                        'unit_cost' => $product->last_buy_price,
                        'source' => 'adjustment',
                    ]);

                    StockMovement::create([
                        'product_id' => $product->id,
                        'batch_id' => $newBatch->id,
                        'type' => 'adjustment',
                        'qty' => $diff,
                        'ref_type' => 'stocktake',
                        'ref_id' => $stocktake->id,
                        'reason' => $item->reason . ($item->note ? ": {$item->note}" : ''),
                        'created_by' => $userId,
                        'created_at' => now(),
                    ]);
                }
            }

            $stocktake->update([
                'status' => 'confirmed',
                'confirmed_by' => $userId,
                'confirmed_at' => now(),
            ]);

            AuditLog::log('confirm_stocktake', 'stocktakes', $stocktakeId, null, [
                'confirmed_by' => $userId,
            ], $userId);

            return $stocktake;
        });
    }
}
