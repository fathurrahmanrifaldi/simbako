<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with('creator')->orderBy('spent_on', 'desc')->orderBy('created_at', 'desc');

        if ($request->filled('date')) {
            $query->whereDate('spent_on', $request->date);
        }

        $expenses = $query->paginate(20)->withQueryString();
        $totalThisMonth = Expense::whereMonth('spent_on', now()->month)
            ->whereYear('spent_on', now()->year)
            ->sum('amount');

        return view('cash.expenses', compact('expenses', 'totalThisMonth'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'spent_on' => 'required|date',
            'amount' => 'required|integer|gt:0',
            'description' => 'required|string|max:255',
        ], [
            'amount.gt' => 'Nominal pengeluaran harus lebih besar dari 0.',
            'description.required' => 'Keterangan pengeluaran wajib diisi.',
        ]);

        $expense = Expense::create([
            'spent_on' => $validated['spent_on'],
            'amount' => $validated['amount'],
            'description' => trim($validated['description']),
            'created_by' => Auth::id(),
        ]);

        AuditLog::log('create_expense', 'expenses', $expense->id, null, $expense->toArray());

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['code' => 201, 'data' => $expense], 201);
        }

        return redirect()->route('expenses.index')->with('success', 'Pengeluaran kas berhasil dicatat.');
    }
}
