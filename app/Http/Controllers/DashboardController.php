<?php

namespace App\Http\Controllers;

use App\Domain\InventoryCalculator;
use App\Models\Batch;
use App\Models\Category;
use App\Models\Product;
use App\Models\SaleItem;
use App\Services\StockService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    protected StockService $stockService;
    protected InventoryCalculator $inventoryCalculator;

    public function __construct(StockService $stockService, ?InventoryCalculator $inventoryCalculator = null)
    {
        $this->stockService = $stockService;
        $this->inventoryCalculator = $inventoryCalculator ?? new InventoryCalculator();
    }

    public function index(Request $request)
    {
        $isOwner = Auth::user()->isOwner();
        $categories = Category::all()->keyBy('id');
        $today = now()->startOfDay();

        // 1. Gather all active products and calculate metrics
        $products = Product::with('category')->where('is_active', true)->get();

        $reorderList = [];
        $negativeStockCount = 0;

        foreach ($products as $p) {
            $stockTotal = $this->stockService->getProductStockTotal($p->id);
            $sellableStock = $this->stockService->getProductSellableStock($p->id);

            if ($sellableStock < 0) {
                $negativeStockCount++;
            }

            // Calculation of D (history days)
            $firstMovement = \App\Models\StockMovement::where('product_id', $p->id)->orderBy('created_at', 'asc')->first();
            $daysSinceFirst = $firstMovement ? max(1, $today->diffInDays($firstMovement->created_at) + 1) : 0;
            $D = min(28, max(1, $daysSinceFirst));

            // Daily rate: total qty sold in last D days / D (penjualan aktif)
            $qtySold = SaleItem::where('product_id', $p->id)
                ->whereHas('sale', function ($q) use ($D) {
                    $q->where('status', 'active')
                      ->where('sold_at', '>=', now()->subDays($D)->startOfDay());
                })
                ->sum('qty');

            $dailyRate = $this->inventoryCalculator->calculateDailyRate((float)$qtySold, $D);

            $leadTime = $p->lead_time_days ?? ($p->category->lead_time_days ?? 1);
            $safetyStock = (float) ($p->safety_stock ?? ($p->category->safety_stock ?? 0));

            $threshold = $this->inventoryCalculator->calculateThreshold(
                $p->min_stock !== null ? (float)$p->min_stock : null,
                $dailyRate,
                $leadTime,
                $safetyStock
            );

            $daysLeft = $this->inventoryCalculator->calculateDaysLeft($sellableStock, $dailyRate);

            // Status check
            $isOutOfStock = $sellableStock <= 0;
            $isLowStock = ($threshold !== null) && ($sellableStock <= $threshold);

            if ($isOutOfStock || $isLowStock) {
                $status = $isOutOfStock ? 'Habis' : 'Menipis';
                $reorderList[] = [
                    'product_id' => $p->id,
                    'name' => $p->name,
                    'category_name' => $p->category->name ?? '-',
                    'base_unit' => $p->base_unit,
                    'sellable_stock' => $sellableStock,
                    'stock_total' => $stockTotal,
                    'threshold' => $threshold,
                    'daily_rate' => $dailyRate,
                    'days_left' => $daysLeft,
                    'status' => $status,
                    'is_negative' => $sellableStock < 0,
                    'min_stock_set' => $p->min_stock !== null,
                ];
            }
        }

        // Sort Reorder List: Habis first, then days_left ASC, then without days_left
        usort($reorderList, function ($a, $b) {
            if ($a['status'] !== $b['status']) {
                return $a['status'] === 'Habis' ? -1 : 1;
            }
            if ($a['days_left'] !== null && $b['days_left'] !== null) {
                return $a['days_left'] <=> $b['days_left'];
            }
            if ($a['days_left'] !== null) return -1;
            if ($b['days_left'] !== null) return 1;
            return strcmp($a['name'], $b['name']);
        });

        // 2. Gather Expiring Batches (DSH-02)
        $batches = Batch::with(['product.category'])
            ->whereNotNull('expiry_date')
            ->orderBy('expiry_date', 'asc')
            ->get();

        $expiringBatches = [];
        foreach ($batches as $batch) {
            $remaining = $this->stockService->getBatchRemaining($batch->id);
            if ($remaining <= 0.0001) {
                continue;
            }

            $warningDays = $batch->product->category->expiry_warning_days ?? 14;
            $expiryDate = Carbon::parse($batch->expiry_date)->startOfDay();
            $diffInDays = $today->diffInDays($expiryDate, false); // negative if expired

            if ($diffInDays <= $warningDays) {
                $statusText = $diffInDays < 0
                    ? 'Lewat ' . abs($diffInDays) . ' hari'
                    : ($diffInDays == 0 ? 'Hari ini' : "{$diffInDays} hari lagi");

                $expiringBatches[] = [
                    'id' => $batch->id,
                    'product_id' => $batch->product_id,
                    'product_name' => $batch->product->name,
                    'base_unit' => $batch->product->base_unit,
                    'received_on' => $batch->received_on ? $batch->received_on->format('d/m/Y') : '-',
                    'expiry_date' => $batch->expiry_date ? $batch->expiry_date->format('d/m/Y') : '-',
                    'remaining_qty' => $remaining,
                    'diff_days' => $diffInDays,
                    'status_text' => $statusText,
                    'is_expired' => $diffInDays < 0,
                ];
            }
        }

        // Summary metric counts (DSH-05)
        $summary = [
            'reorder_count' => count($reorderList),
            'expiring_count' => count($expiringBatches),
            'negative_count' => $negativeStockCount,
        ];

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'code' => 200,
                'summary' => $summary,
                'reorder' => $reorderList,
                'expiring' => $expiringBatches,
            ]);
        }

        return view('dashboard.index', compact('summary', 'reorderList', 'expiringBatches', 'isOwner'));
    }

    public function waste(Request $request, $batchId)
    {
        $validated = $request->validate([
            'qty' => 'required|numeric|gt:0',
            'reason' => 'required|string|in:kedaluwarsa,rusak,lainnya',
        ], [
            'qty.required' => 'Jumlah barang yang dibuang wajib diisi.',
            'reason.required' => 'Pilih alasan buang barang.',
        ]);

        try {
            $this->stockService->recordWaste(
                $batchId,
                (float) $validated['qty'],
                $validated['reason'],
                Auth::id()
            );

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['code' => 200, 'message' => 'Barang berhasil dicatat dibuang.']);
            }

            return redirect()->route('dashboard.index')->with('success', 'Barang berhasil dicatat dibuang dan stok diperbarui.');
        } catch (Exception $e) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['code' => 400, 'message' => $e->getMessage()], 400);
            }
            return back()->withErrors(['waste' => $e->getMessage()]);
        }
    }
}
