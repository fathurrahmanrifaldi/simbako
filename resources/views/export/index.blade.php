@extends('layouts.app')

@section('title', 'Ekspor Data CSV')

@section('content')
<div class="space-y-4">
    <!-- Header -->
    <div>
        <h1 class="font-heading font-black text-xl text-slate-900">Ekspor Data (CSV / Excel)</h1>
        <p class="text-xs text-slate-500">Unduh data toko untuk cadangan lokal atau dibuka di Microsoft Excel.</p>
    </div>

    <!-- Pilihan Pemisah / Delimiter (EXP-01) -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs space-y-3">
        <h2 class="font-heading font-bold text-xs uppercase tracking-wider text-slate-600">Pengaturan Format CSV</h2>

        <div class="flex items-center space-x-4 text-xs font-bold text-slate-700">
            <label class="flex items-center space-x-1.5 cursor-pointer">
                <input type="radio" name="global_delimiter" value=";" checked onchange="updateDelimiter(this.value)" class="text-emerald-600">
                <span>Titik Koma (;) <span class="text-slate-400 font-normal">- Rekomendasi Excel Indonesia</span></span>
            </label>
            <label class="flex items-center space-x-1.5 cursor-pointer">
                <input type="radio" name="global_delimiter" value="," onchange="updateDelimiter(this.value)" class="text-emerald-600">
                <span>Koma (,) <span class="text-slate-400 font-normal">- Standar Internasional</span></span>
            </label>
        </div>
    </div>

    <!-- Dataset Cards -->
    <div class="space-y-2.5">
        <!-- 1. Master Produk -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <h3 class="font-bold text-slate-800 text-sm">Data Master Produk</h3>
                <p class="text-xs text-slate-500">Seluruh produk, kategori, harga jual, harga beli, dan stok total terkini.</p>
            </div>
            <button type="button" onclick="downloadDataset('products')" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs shrink-0">
                Unduh CSV
            </button>
        </div>

        <!-- 2. Riwayat Penjualan -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-slate-800 text-sm">Data Penjualan (Per Baris Item)</h3>
                    <p class="text-xs text-slate-500">Seluruh transaksi penjualan, kasir, unit cost HPP, dan status void.</p>
                </div>
                <button type="button" onclick="downloadDataset('sales')" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs shrink-0">
                    Unduh CSV
                </button>
            </div>
            <div class="grid grid-cols-2 gap-2 text-xs pt-1 border-t border-slate-100">
                <div>
                    <label class="block text-[10px] text-slate-500 mb-0.5 font-bold">Dari Tanggal</label>
                    <input type="date" id="sales_from" class="w-full text-xs p-2 bg-slate-50 border border-slate-200 rounded-lg">
                </div>
                <div>
                    <label class="block text-[10px] text-slate-500 mb-0.5 font-bold">Sampai Tanggal</label>
                    <input type="date" id="sales_to" class="w-full text-xs p-2 bg-slate-50 border border-slate-200 rounded-lg">
                </div>
            </div>
        </div>

        <!-- 3. Riwayat Pembelian -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-slate-800 text-sm">Data Pembelian (Barang Masuk)</h3>
                    <p class="text-xs text-slate-500">Seluruh pembelian, supplier, harga beli, dan tanggal kedaluwarsa batch.</p>
                </div>
                <button type="button" onclick="downloadDataset('purchases')" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs shrink-0">
                    Unduh CSV
                </button>
            </div>
            <div class="grid grid-cols-2 gap-2 text-xs pt-1 border-t border-slate-100">
                <div>
                    <label class="block text-[10px] text-slate-500 mb-0.5 font-bold">Dari Tanggal</label>
                    <input type="date" id="purchases_from" class="w-full text-xs p-2 bg-slate-50 border border-slate-200 rounded-lg">
                </div>
                <div>
                    <label class="block text-[10px] text-slate-500 mb-0.5 font-bold">Sampai Tanggal</label>
                    <input type="date" id="purchases_to" class="w-full text-xs p-2 bg-slate-50 border border-slate-200 rounded-lg">
                </div>
            </div>
        </div>

        <!-- 4. Pergerakan Stok (Ledger) -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-slate-800 text-sm">Buku Besar Stok (Stock Movements)</h3>
                    <p class="text-xs text-slate-500">Catatan ledger seluruh pergerakan stok: masuk, jual, buang, dan opname.</p>
                </div>
                <button type="button" onclick="downloadDataset('stock_movements')" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs shrink-0">
                    Unduh CSV
                </button>
            </div>
        </div>

        <!-- 5. Kas Harian -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <h3 class="font-bold text-slate-800 text-sm">Riwayat Tutup Kas Harian</h3>
                <p class="text-xs text-slate-500">Rekap kas awal, kas seharusnya, kas fisik, dan catatan selisih.</p>
            </div>
            <button type="button" onclick="downloadDataset('cash_closings')" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs shrink-0">
                Unduh CSV
            </button>
        </div>

        <!-- 6. Pengeluaran -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <h3 class="font-bold text-slate-800 text-sm">Daftar Pengeluaran Toko</h3>
                <p class="text-xs text-slate-500">Catatan belanja pasar, operasional, dan keterangan.</p>
            </div>
            <button type="button" onclick="downloadDataset('expenses')" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs shrink-0">
                Unduh CSV
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let activeDelimiter = ';';

    function updateDelimiter(val) {
        activeDelimiter = val;
    }

    function downloadDataset(dataset) {
        let url = `/ekspor/unduh/${dataset}?delimiter=${encodeURIComponent(activeDelimiter)}`;

        if (dataset === 'sales') {
            const from = document.getElementById('sales_from').value;
            const to = document.getElementById('sales_to').value;
            if (from) url += `&from=${from}`;
            if (to) url += `&to=${to}`;
        } else if (dataset === 'purchases') {
            const from = document.getElementById('purchases_from').value;
            const to = document.getElementById('purchases_to').value;
            if (from) url += `&from=${from}`;
            if (to) url += `&to=${to}`;
        }

        window.location.href = url;
    }
</script>
@endpush
@endsection
