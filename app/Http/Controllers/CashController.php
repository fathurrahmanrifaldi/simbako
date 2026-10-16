<?php

namespace App\Http\Controllers;

use App\Domain\CashCalculator;
use App\Models\AuditLog;
use App\Models\CashClosing;
use App\Models\Expense;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CashController extends Controller
{
    protected CashCalculator $cashCalculator;

    public function __construct(?CashCalculator $cashCalculator = null)
    {
        $this->cashCalculator = $cashCalculator ?? new CashCalculator();
    }

    public function index(Request $request)
    {
        $selectedDate = $request->input('date', now()->format('Y-m-d'));

        // 1. Check if already closed for this date
        $closing = CashClosing::with(['closer', 'reopener'])
            ->where('business_date', $selectedDate)
            ->first();

        // 2. Default opening cash = actual_cash from previous closing
        $lastClosing = CashClosing::where('business_date', '<', $selectedDate)
            ->orderBy('business_date', 'desc')
            ->first();
        $defaultOpeningCash = $lastClosing ? (int)$lastClosing->actual_cash : 0;

        // 3. Sales for date
        $activeSales = Sale::whereDate('sold_at', $selectedDate)
            ->where('status', 'active')
            ->get();

        $totalSales = $activeSales->sum('total');
        $transactionCount = $activeSales->count();
        $hasManualSummary = $activeSales->where('is_summary', true)->isNotEmpty();

        $voidSales = Sale::whereDate('sold_at', $selectedDate)
            ->where('status', 'void')
            ->get();
        $totalVoidAmount = $voidSales->sum('total');
        $totalVoidCount = $voidSales->count();

        // Top 5 products by quantity
        $topProducts = SaleItem::select('product_id', DB::raw('SUM(qty) as total_qty'), DB::raw('SUM(qty * unit_price) as total_revenue'))
            ->whereHas('sale', function ($q) use ($selectedDate) {
                $q->whereDate('sold_at', $selectedDate)->where('status', 'active');
            })
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->with('product')
            ->get();

        // 4. Expenses for date
        $expenses = Expense::whereDate('spent_on', $selectedDate)->get();
        $totalExpenses = $expenses->sum('amount');

        // 5. Purchases paid from cash for date
        $cashPurchases = Purchase::whereDate('purchased_on', $selectedDate)
            ->where('paid_from_cash', true)
            ->where('status', 'active')
            ->get();
        $totalCashPurchases = $cashPurchases->sum('total');

        // Opening cash to use: if closing exists, use its saved opening_cash; else defaultOpeningCash
        $openingCash = $closing ? (int)$closing->opening_cash : $defaultOpeningCash;

        // Calculate expected cash
        $expectedCash = $this->cashCalculator->calculateExpectedCash(
            $openingCash,
            (int)$totalSales,
            (int)$totalExpenses,
            (int)$totalCashPurchases
        );

        $historyClosings = CashClosing::orderBy('business_date', 'desc')->limit(10)->get();

        return view('cash.index', compact(
            'selectedDate',
            'closing',
            'openingCash',
            'expectedCash',
            'totalSales',
            'transactionCount',
            'hasManualSummary',
            'totalVoidAmount',
            'totalVoidCount',
            'topProducts',
            'expenses',
            'totalExpenses',
            'cashPurchases',
            'totalCashPurchases',
            'historyClosings'
        ));
    }

    public function close(Request $request)
    {
        $validated = $request->validate([
            'business_date' => 'required|date',
            'opening_cash' => 'required|integer|min:0',
            'actual_cash' => 'required|integer|min:0',
            'expected_cash' => 'required|integer',
            'note' => 'nullable|string',
        ], [
            'actual_cash.required' => 'Jumlah kas fisik wajib diisi.',
        ]);

        $diff = (int)$validated['actual_cash'] - (int)$validated['expected_cash'];

        if ($diff !== 0 && empty($validated['note'])) {
            return back()->withErrors(['note' => 'Catatan wajib diisi bila terdapat selisih kas fisik dan kas seharusnya.'])->withInput();
        }

        $existing = CashClosing::where('business_date', $validated['business_date'])->first();
        if ($existing) {
            return back()->withErrors(['close' => 'Kas tanggal ini sudah ditutup. Buka ulang terlebih dahulu jika ingin merevisi.']);
        }

        CashClosing::create([
            'business_date' => $validated['business_date'],
            'opening_cash' => $validated['opening_cash'],
            'expected_cash' => $validated['expected_cash'],
            'actual_cash' => $validated['actual_cash'],
            'diff' => $diff,
            'note' => $validated['note'] ?? null,
            'closed_by' => Auth::id(),
            'closed_at' => now(),
        ]);

        AuditLog::log('close_cash', 'cash_closings', null, null, [
            'business_date' => $validated['business_date'],
            'diff' => $diff,
            'actual_cash' => $validated['actual_cash'],
        ]);

        return redirect()->route('cash.index', ['date' => $validated['business_date']])
            ->with('success', 'Tutup kas harian berhasil disimpan!');
    }

    public function reopen(Request $request, $id)
    {
        $closing = CashClosing::findOrFail($id);

        AuditLog::log('reopen_cash', 'cash_closings', $closing->id, $closing->toArray(), [
            'reopened_by' => Auth::id(),
            'reopened_at' => now(),
        ]);

        $closing->delete(); // Delete snapshot so day can be reconciled and closed again

        return back()->with('success', 'Kas harian dibuka ulang untuk penyesuaian.');
    }
}
