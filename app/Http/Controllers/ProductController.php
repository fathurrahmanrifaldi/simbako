<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index(Request $request)
    {
        $categories = Category::orderBy('name')->get();
        $isOwner = Auth::user()->isOwner();

        $query = Product::with('category')->where('is_active', true)->orderBy('name');
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $products = $query->get()->map(function ($p) use ($isOwner) {
            $p->stock_total = $this->stockService->getProductStockTotal($p->id);
            $p->sellable_stock = $this->stockService->getProductSellableStock($p->id);
            if (!$isOwner) {
                unset($p->last_buy_price);
            }
            return $p;
        });

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'code' => 200,
                'data' => $products,
            ]);
        }

        return view('products.index', compact('products', 'categories', 'isOwner'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('products')->where(function ($query) {
                    return $query->where('is_active', true);
                }),
            ],
            'category_id' => 'required|uuid|exists:categories,id',
            'base_unit' => 'required|string|max:30',
            'sell_price' => 'required|integer|min:0',
            'last_buy_price' => 'nullable|integer|min:0',
            'min_stock' => 'nullable|numeric|min:0',
            'lead_time_days' => 'nullable|integer|min:0',
            'safety_stock' => 'nullable|numeric|min:0',
            'has_expiry' => 'nullable|boolean',
            'is_favorite' => 'nullable|boolean',
        ], [
            'name.unique' => 'Nama produk sudah terdaftar di antara produk aktif.',
            'sell_price.min' => 'Harga jual tidak boleh negatif.',
        ]);

        $product = Product::create([
            'name' => trim($validated['name']),
            'category_id' => $validated['category_id'],
            'base_unit' => trim($validated['base_unit']),
            'sell_price' => $validated['sell_price'],
            'last_buy_price' => $validated['last_buy_price'] ?? 0,
            'min_stock' => $validated['min_stock'] ?? null,
            'lead_time_days' => $validated['lead_time_days'] ?? null,
            'safety_stock' => $validated['safety_stock'] ?? null,
            'has_expiry' => $request->boolean('has_expiry'),
            'is_favorite' => $request->boolean('is_favorite'),
            'is_active' => true,
        ]);

        AuditLog::log('create_product', 'products', $product->id, null, $product->toArray());

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['code' => 201, 'data' => $product], 201);
        }

        return redirect()->route('products.index')->with('success', "Produk '{$product->name}' berhasil ditambahkan.");
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('products')->where(function ($query) use ($id) {
                    return $query->where('is_active', true)->where('id', '!=', $id);
                }),
            ],
            'category_id' => 'required|uuid|exists:categories,id',
            'base_unit' => 'required|string|max:30',
            'sell_price' => 'required|integer|min:0',
            'last_buy_price' => 'nullable|integer|min:0',
            'min_stock' => 'nullable|numeric|min:0',
            'lead_time_days' => 'nullable|integer|min:0',
            'safety_stock' => 'nullable|numeric|min:0',
            'has_expiry' => 'nullable|boolean',
            'is_favorite' => 'nullable|boolean',
        ]);

        $before = $product->toArray();

        $product->update([
            'name' => trim($validated['name']),
            'category_id' => $validated['category_id'],
            'base_unit' => trim($validated['base_unit']),
            'sell_price' => $validated['sell_price'],
            'last_buy_price' => $validated['last_buy_price'] ?? $product->last_buy_price,
            'min_stock' => $validated['min_stock'] ?? null,
            'lead_time_days' => $validated['lead_time_days'] ?? null,
            'safety_stock' => $validated['safety_stock'] ?? null,
            'has_expiry' => $request->boolean('has_expiry'),
            'is_favorite' => $request->boolean('is_favorite'),
        ]);

        AuditLog::log('update_product', 'products', $product->id, $before, $product->toArray());

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['code' => 200, 'data' => $product]);
        }

        return redirect()->route('products.index')->with('success', "Produk '{$product->name}' berhasil diperbarui.");
    }

    public function deactivate(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $stockTotal = $this->stockService->getProductStockTotal($id);

        $product->update(['is_active' => false]);
        AuditLog::log('deactivate_product', 'products', $id, ['is_active' => true], ['is_active' => false]);

        $msg = "Produk '{$product->name}' dinonaktifkan.";
        if ($stockTotal != 0) {
            $msg .= " Catatan: Produk ini masih memiliki stok tersisa {$stockTotal} {$product->base_unit}.";
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['code' => 200, 'message' => $msg]);
        }

        return redirect()->route('products.index')->with('success', $msg);
    }
}
