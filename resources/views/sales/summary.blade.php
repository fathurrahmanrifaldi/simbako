@extends('layouts.app')

@section('title', 'Rekap Akhir Hari')

@section('content')
<div class="space-y-4">
    <!-- Header -->
    <div>
        <h1 class="font-heading font-black text-xl text-slate-900">Rekap Akhir Hari</h1>
        <p class="text-xs text-slate-500 mt-0.5">Catat total penjualan per produk untuk hari yang tidak sempat dicatat per transaksi.</p>
    </div>

    <form method="POST" action="{{ route('sales.summary.store') }}" class="space-y-4">
        @csrf

        <!-- Tanggal Rekap -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
            <label for="summary_date" class="block text-xs font-bold text-slate-700 mb-1">Tanggal Toko (WIB)</label>
            <input type="date" id="summary_date" name="summary_date" max="{{ now()->format('Y-m-d') }}" value="{{ old('summary_date', now()->format('Y-m-d')) }}" required
                class="w-full text-sm font-semibold bg-slate-50 border border-slate-300 rounded-xl px-3 py-2.5 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
            <p class="text-[11px] text-slate-400 mt-1">Hanya berlaku untuk produk yang belum memiliki transaksi pada tanggal terpilih.</p>
        </div>

        <!-- Daftar Produk -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-3 bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-600 flex justify-between items-center">
                <span>Produk & Harga Jual</span>
                <span>Jumlah Terjual</span>
            </div>

            <div class="divide-y divide-slate-100 max-h-[50vh] overflow-y-auto p-2">
                @foreach($products as $index => $prod)
                    <div class="py-2.5 px-2 flex items-center justify-between space-x-3 text-xs">
                        <div class="flex-1 min-w-0">
                            <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $prod->id }}">
                            <input type="hidden" name="items[{{ $index }}][unit_price]" value="{{ $prod->sell_price }}">
                            <div class="font-bold text-slate-800 truncate">{{ $prod->name }}</div>
                            <span class="text-slate-400 font-mono">Rp {{ number_format($prod->sell_price, 0, ',', '.') }} / {{ $prod->base_unit }}</span>
                        </div>

                        <div class="w-28 shrink-0">
                            <input type="number" step="any" min="0" name="items[{{ $index }}][qty]" placeholder="0"
                                class="w-full text-center font-bold text-sm bg-slate-50 border border-slate-200 rounded-xl py-1.5 focus:bg-white focus:ring-2 focus:ring-emerald-500">
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <button type="submit" class="w-full touch-target bg-emerald-600 hover:bg-emerald-700 text-white font-heading font-black text-sm py-3.5 rounded-2xl shadow-md transition-all">
            Simpan Rekap Akhir Hari
        </button>
    </form>
</div>
@endsection
