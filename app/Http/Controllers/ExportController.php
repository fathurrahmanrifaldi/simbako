<?php

namespace App\Http\Controllers;

use App\Models\CashClosing;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Stocktake;
use App\Models\StocktakeItem;
use App\Services\StockService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index()
    {
        return view('export.index');
    }

    public function download(Request $request, $dataset)
    {
        $delimiter = $request->input('delimiter', ';'); // Default semicolon for Indonesian Excel
        $from = $request->input('from');
        $to = $request->input('to');

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"simbako_{$dataset}_" . now()->format('Ymd_His') . ".csv\"",
        ];

        return new StreamedResponse(function () use ($dataset, $delimiter, $from, $to) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM
            fputs($handle, "\xEF\xBB\xBF");

            switch ($dataset) {
                case 'products':
                    fputcsv($handle, ['ID', 'Nama Produk', 'Kategori', 'Satuan', 'Harga Jual', 'Harga Beli Terakhir', 'Stok Total', 'Stok Minimum', 'Punya Exp', 'Favorit', 'Aktif'], $delimiter);
                    Product::with('category')->chunk(100, function ($products) use ($handle, $delimiter) {
                        foreach ($products as $p) {
                            $stock = $this->stockService->getProductStockTotal($p->id);
                            fputcsv($handle, [
                                $p->id,
                                $p->name,
                                $p->category->name ?? '-',
                                $p->base_unit,
                                $p->sell_price,
                                $p->last_buy_price,
                                $stock,
                                $p->min_stock ?? 0,
                                $p->has_expiry ? 'Ya' : 'Tidak',
                                $p->is_favorite ? 'Ya' : 'Tidak',
                                $p->is_active ? 'Aktif' : 'Nonaktif',
                            ], $delimiter);
                        }
                    });
                    break;

                case 'sales':
                    fputcsv($handle, ['ID Penjualan', 'Waktu', 'Status', 'Metode Bayar', 'Produk', 'Jumlah', 'Harga Satuan', 'Total Baris', 'HPP/Unit Cost', 'Kasir', 'Alasan Void'], $delimiter);
                    $query = SaleItem::with(['sale.creator', 'product']);
                    if ($from) $query->whereHas('sale', fn($q) => $q->whereDate('sold_at', '>=', $from));
                    if ($to) $query->whereHas('sale', fn($q) => $q->whereDate('sold_at', '<=', $to));

                    $query->chunk(100, function ($items) use ($handle, $delimiter) {
                        foreach ($items as $it) {
                            fputcsv($handle, [
                                $it->sale_id,
                                $it->sale->sold_at ? $it->sale->sold_at->format('Y-m-d H:i:s') : '',
                                $it->sale->status,
                                $it->sale->payment_method,
                                $it->product->name ?? '-',
                                $it->qty,
                                $it->unit_price,
                                (int)($it->qty * $it->unit_price),
                                $it->unit_cost,
                                $it->sale->creator->name ?? '-',
                                $it->sale->void_reason ?? '',
                            ], $delimiter);
                        }
                    });
                    break;

                case 'purchases':
                    fputcsv($handle, ['ID Pembelian', 'Tanggal Beli', 'Supplier', 'Status', 'Dari Kas', 'Produk', 'Jumlah', 'Harga Beli Satuan', 'Total Baris', 'Tgl Kedaluwarsa', 'Petugas'], $delimiter);
                    $query = PurchaseItem::with(['purchase.creator', 'product']);
                    if ($from) $query->whereHas('purchase', fn($q) => $q->whereDate('purchased_on', '>=', $from));
                    if ($to) $query->whereHas('purchase', fn($q) => $q->whereDate('purchased_on', '<=', $to));

                    $query->chunk(100, function ($items) use ($handle, $delimiter) {
                        foreach ($items as $it) {
                            fputcsv($handle, [
                                $it->purchase_id,
                                $it->purchase->purchased_on ? $it->purchase->purchased_on->format('Y-m-d') : '',
                                $it->purchase->supplier ?? '-',
                                $it->purchase->status,
                                $it->purchase->paid_from_cash ? 'Ya' : 'Tidak',
                                $it->product->name ?? '-',
                                $it->qty,
                                $it->unit_price,
                                (int)($it->qty * $it->unit_price),
                                $it->expiry_date ? $it->expiry_date->format('Y-m-d') : '-',
                                $it->purchase->creator->name ?? '-',
                            ], $delimiter);
                        }
                    });
                    break;

                case 'stock_movements':
                    fputcsv($handle, ['ID', 'Waktu', 'Produk', 'Jenis', 'Jumlah (+/-)', 'Batch ID', 'Tipe Referensi', 'ID Ref', 'Alasan', 'Petugas'], $delimiter);
                    $query = StockMovement::with(['product', 'creator'])->orderBy('created_at', 'desc');
                    if ($from) $query->whereDate('created_at', '>=', $from);
                    if ($to) $query->whereDate('created_at', '<=', $to);

                    $query->chunk(100, function ($movements) use ($handle, $delimiter) {
                        foreach ($movements as $m) {
                            fputcsv($handle, [
                                $m->id,
                                $m->created_at ? $m->created_at->format('Y-m-d H:i:s') : '',
                                $m->product->name ?? '-',
                                $m->type,
                                $m->qty,
                                $m->batch_id ?? 'tanpa_batch',
                                $m->ref_type ?? '-',
                                $m->ref_id ?? '-',
                                $m->reason ?? '-',
                                $m->creator->name ?? '-',
                            ], $delimiter);
                        }
                    });
                    break;

                case 'cash_closings':
                    fputcsv($handle, ['ID', 'Tanggal Toko', 'Kas Awal', 'Kas Seharusnya', 'Kas Fisik', 'Selisih', 'Catatan', 'Ditutup Oleh', 'Waktu Tutup'], $delimiter);
                    CashClosing::with('closer')->orderBy('business_date', 'desc')->chunk(100, function ($closings) use ($handle, $delimiter) {
                        foreach ($closings as $c) {
                            fputcsv($handle, [
                                $c->id,
                                $c->business_date ? $c->business_date->format('Y-m-d') : '',
                                $c->opening_cash,
                                $c->expected_cash,
                                $c->actual_cash,
                                $c->diff,
                                $c->note ?? '',
                                $c->closer->name ?? '-',
                                $c->closed_at ? $c->closed_at->format('Y-m-d H:i:s') : '',
                            ], $delimiter);
                        }
                    });
                    break;

                case 'expenses':
                    fputcsv($handle, ['ID', 'Tanggal', 'Nominal', 'Keterangan', 'Dicatat Oleh'], $delimiter);
                    Expense::with('creator')->orderBy('spent_on', 'desc')->chunk(100, function ($expenses) use ($handle, $delimiter) {
                        foreach ($expenses as $ex) {
                            fputcsv($handle, [
                                $ex->id,
                                $ex->spent_on ? $ex->spent_on->format('Y-m-d') : '',
                                $ex->amount,
                                $ex->description,
                                $ex->creator->name ?? '-',
                            ], $delimiter);
                        }
                    });
                    break;
            }

            fclose($handle);
        }, 200, $headers);
    }
}
