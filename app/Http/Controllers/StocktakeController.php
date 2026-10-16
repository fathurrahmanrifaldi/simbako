<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Stocktake;
use App\Models\StocktakeItem;
use App\Services\StockService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class StocktakeController extends Controller
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index()
    {
        $categories = Category::orderBy('name')->get();
        $stocktakes = Stocktake::with(['counter', 'confirmer', 'items'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $activeDraft = Stocktake::where('status', 'draft')->latest()->first();

        return view('stocktake.index', compact('categories', 'stocktakes', 'activeDraft'));
    }

    public function start(Request $request)
    {
        $scope = $request->input('scope', 'all');
        $categoryId = $request->input('category_id');

        $query = Product::where('is_active', true)->orderBy('name');
        if ($scope === 'category' && $categoryId) {
            $query->where('category_id', $categoryId);
            $cat = Category::find($categoryId);
            $scopeText = 'Kategori: ' . ($cat->name ?? 'Terpilih');
        } else {
            $scopeText = 'Semua Produk Aktif';
        }

        $products = $query->get();

        $stocktake = Stocktake::create([
            'scope' => $scopeText,
            'status' => 'draft',
            'counted_by' => Auth::id(),
        ]);

        foreach ($products as $p) {
            $currentStock = $this->stockService->getProductStockTotal($p->id);
            StocktakeItem::create([
                'stocktake_id' => $stocktake->id,
                'product_id' => $p->id,
                'system_qty' => $currentStock,
                'physical_qty' => 0,
            ]);
        }

        return redirect()->route('stocktake.show', $stocktake->id);
    }

    public function show($id)
    {
        $stocktake = Stocktake::with(['items.product.category', 'counter', 'confirmer'])->findOrFail($id);
        $isOwner = Auth::user()->isOwner();

        // Refresh system_qty snapshot for accurate comparisons
        foreach ($stocktake->items as $item) {
            $item->current_live_stock = $this->stockService->getProductStockTotal($item->product_id);
        }

        return view('stocktake.show', compact('stocktake', 'isOwner'));
    }

    public function updateDraft(Request $request, $id)
    {
        $stocktake = Stocktake::findOrFail($id);
        if ($stocktake->status === 'confirmed') {
            return response()->json(['code' => 400, 'message' => 'Opname sudah dikonfirmasi.'], 400);
        }

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|uuid',
            'items.*.physical_qty' => 'required|numeric|min:0',
            'items.*.reason' => 'nullable|string',
            'items.*.note' => 'nullable|string',
        ]);

        foreach ($validated['items'] as $it) {
            StocktakeItem::where('stocktake_id', $id)
                ->where('product_id', $it['product_id'])
                ->update([
                    'physical_qty' => round((float) $it['physical_qty'], 3),
                    'reason' => $it['reason'] ?? null,
                    'note' => $it['note'] ?? null,
                ]);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['code' => 200, 'message' => 'Hitungan draft disimpan.']);
        }

        return back()->with('success', 'Hitungan draft berhasil disimpan.');
    }

    public function confirm(Request $request, $id)
    {
        if (!Auth::user()->isOwner()) {
            abort(403, 'Hanya pemilik yang dapat mengonfirmasi stok opname.');
        }

        $expectedSystemQty = $request->input('expected_system_qty', []);

        try {
            $this->stockService->confirmStocktake($id, $expectedSystemQty, Auth::id());

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['code' => 200, 'message' => 'Stok opname berhasil dikonfirmasi.']);
            }

            return redirect()->route('stocktake.index')->with('success', 'Stok opname berhasil dikonfirmasi dan penyesuaian stok telah dicatat.');
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 409 ? 409 : 400;
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['code' => $statusCode, 'message' => $e->getMessage()], $statusCode);
            }
            return back()->withErrors(['stocktake' => $e->getMessage()]);
        }
    }

    public function openingBalanceForm()
    {
        $products = Product::where('is_active', true)->orderBy('name')->get();
        return view('stocktake.opening-balance', compact('products'));
    }

    public function storeOpeningBalance(Request $request)
    {
        if (!Auth::user()->isOwner()) {
            abort(403);
        }

        $validated = $request->validate([
            'product_id' => 'required|uuid|exists:products,id',
            'qty' => 'required|numeric|gt:0',
            'unit_cost' => 'required|integer|min:0',
            'expiry_date' => 'nullable|date',
        ]);

        try {
            $this->stockService->recordOpeningBalance(
                $validated['product_id'],
                (float) $validated['qty'],
                $validated['expiry_date'] ?? null,
                (int) $validated['unit_cost'],
                Auth::id()
            );

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['code' => 201, 'message' => 'Saldo awal berhasil dicatat.'], 201);
            }

            return redirect()->route('products.index')->with('success', 'Saldo awal berhasil dicatat.');
        } catch (Exception $e) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['code' => 400, 'message' => $e->getMessage()], 400);
            }
            return back()->withErrors(['opening' => $e->getMessage()])->withInput();
        }
    }
}
