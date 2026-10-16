<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Services\StockService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SaleController extends Controller
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index(Request $request)
    {
        // PRO-04: Max 24 favorites ordered alphabetically for consistent button positions
        $favorites = Product::where('is_active', true)
            ->where('is_favorite', true)
            ->orderBy('name')
            ->limit(24)
            ->get();

        $allProducts = Product::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'base_unit', 'sell_price', 'has_expiry', 'is_favorite']);

        $categories = Category::orderBy('name')->get();

        return view('sales.index', compact('favorites', 'allProducts', 'categories'));
    }

    public function history(Request $request)
    {
        $user = Auth::user();
        $isOwner = $user->isOwner();

        $query = Sale::with(['items.product', 'creator'])->orderBy('sold_at', 'desc');

        // Staff only sees today's sales (Matriks 2.2 / JUL-06)
        if (!$isOwner) {
            $query->whereDate('sold_at', now()->format('Y-m-d'));
        }

        if ($request->filled('date')) {
            $query->whereDate('sold_at', $request->date);
        }

        $sales = $query->paginate(20)->withQueryString();

        return view('sales.history', compact('sales', 'isOwner'));
    }

    public function summaryForm()
    {
        $products = Product::where('is_active', true)->orderBy('name')->get();
        return view('sales.summary', compact('products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|uuid', // Client UUID for idempotency
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid|exists:products,id',
            'items.*.qty' => 'required|numeric|gt:0',
            'items.*.unit_price' => 'required|integer|min:0',
            'items.*.price_overridden' => 'nullable|boolean',
        ], [
            'items.required' => 'Keranjang penjualan tidak boleh kosong.',
            'items.min' => 'Minimal 1 item untuk transaksi penjualan.',
        ]);

        try {
            $sale = $this->stockService->recordSale(
                $validated['id'],
                $validated['items'],
                Auth::id(),
                'cash'
            );

            // Check if any product has negative stock for UI warning (JUL-04)
            $warnings = [];
            foreach ($validated['items'] as $item) {
                $stock = $this->stockService->getProductSellableStock($item['product_id']);
                if ($stock < 0) {
                    $prod = Product::find($item['product_id']);
                    $warnings[] = "Stok sistem untuk '{$prod->name}' sekarang minus ({$stock} {$prod->base_unit}).";
                }
            }

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'code' => 200,
                    'data' => $sale,
                    'warnings' => $warnings,
                    'message' => 'Transaksi penjualan berhasil disimpan.',
                ]);
            }

            return redirect()->route('sales.index')->with('success', 'Transaksi berhasil disimpan!');
        } catch (Exception $e) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['code' => 400, 'message' => $e->getMessage()], 400);
            }
            return back()->withErrors(['sale' => $e->getMessage()])->withInput();
        }
    }

    public function storeSummary(Request $request)
    {
        $validated = $request->validate([
            'summary_date' => 'required|date|before_or_equal:today',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid|exists:products,id',
            'items.*.qty' => 'required|numeric|gt:0',
            'items.*.unit_price' => 'required|integer|min:0',
        ], [
            'summary_date.before_or_equal' => 'Tanggal rekap tidak boleh di masa depan.',
        ]);

        try {
            $sale = $this->stockService->recordDailySummarySale(
                $validated['summary_date'],
                $validated['items'],
                Auth::id()
            );

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'code' => 200,
                    'data' => $sale,
                    'message' => 'Rekap harian berhasil disimpan.',
                ]);
            }

            return redirect()->route('sales.history')->with('success', 'Rekap akhir hari berhasil disimpan.');
        } catch (Exception $e) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['code' => 400, 'message' => $e->getMessage()], 400);
            }
            return back()->withErrors(['summary' => $e->getMessage()])->withInput();
        }
    }

    public function voidSale(Request $request, $id)
    {
        $validated = $request->validate([
            'void_reason' => 'required|string|min:3|max:255',
        ], [
            'void_reason.required' => 'Alasan pembatalan (void) wajib diisi.',
            'void_reason.min' => 'Alasan pembatalan minimal 3 karakter.',
        ]);

        try {
            $this->stockService->voidSale(
                $id,
                $validated['void_reason'],
                Auth::id(),
                Auth::user()->isOwner()
            );

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'code' => 200,
                    'message' => 'Transaksi penjualan berhasil dibatalkan (void).',
                ]);
            }

            return back()->with('success', 'Penjualan berhasil dibatalkan (void).');
        } catch (Exception $e) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['code' => 400, 'message' => $e->getMessage()], 400);
            }
            return back()->withErrors(['void' => $e->getMessage()]);
        }
    }
}
