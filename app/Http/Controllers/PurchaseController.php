<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Purchase;
use App\Services\StockService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PurchaseController extends Controller
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function create()
    {
        $products = Product::where('is_active', true)->orderBy('name')->get();
        return view('purchases.create', compact('products'));
    }

    public function history(Request $request)
    {
        $query = Purchase::with(['items.product', 'creator'])->orderBy('purchased_on', 'desc');

        if ($request->filled('date')) {
            $query->whereDate('purchased_on', $request->date);
        }

        $purchases = $query->paginate(20)->withQueryString();
        return view('purchases.history', compact('purchases'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|uuid',
            'purchased_on' => 'required|date|before_or_equal:today',
            'supplier' => 'nullable|string|max:150',
            'paid_from_cash' => 'nullable|boolean',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid|exists:products,id',
            'items.*.qty' => 'required|numeric|gt:0',
            'items.*.unit_price' => 'required|integer|min:0',
            'items.*.expiry_date' => 'nullable|date',
        ], [
            'purchased_on.before_or_equal' => 'Tanggal pembelian tidak boleh di masa depan.',
            'items.required' => 'Minimal 1 item barang yang dibeli.',
        ]);

        try {
            $purchase = $this->stockService->recordPurchase(
                [
                    'id' => $validated['id'] ?? null,
                    'purchased_on' => $validated['purchased_on'],
                    'supplier' => $validated['supplier'] ?? null,
                    'paid_from_cash' => $request->boolean('paid_from_cash'),
                ],
                $validated['items'],
                Auth::id()
            );

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'code' => 201,
                    'data' => $purchase,
                    'message' => 'Pembelian berhasil dicatat.',
                ], 201);
            }

            return redirect()->route('purchases.history')->with('success', 'Pembelian berhasil dicatat dan stok telah diperbarui.');
        } catch (Exception $e) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['code' => 400, 'message' => $e->getMessage()], 400);
            }
            return back()->withErrors(['purchase' => $e->getMessage()])->withInput();
        }
    }

    public function voidPurchase(Request $request, $id)
    {
        $validated = $request->validate([
            'void_reason' => 'required|string|min:3|max:255',
        ], [
            'void_reason.required' => 'Alasan pembatalan pembelian wajib diisi.',
        ]);

        try {
            $this->stockService->voidPurchase($id, $validated['void_reason'], Auth::id());

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'code' => 200,
                    'message' => 'Pembelian berhasil dibatalkan (void).',
                ]);
            }

            return back()->with('success', 'Pembelian berhasil dibatalkan (void).');
        } catch (Exception $e) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['code' => 400, 'message' => $e->getMessage()], 400);
            }
            return back()->withErrors(['void' => $e->getMessage()]);
        }
    }
}
