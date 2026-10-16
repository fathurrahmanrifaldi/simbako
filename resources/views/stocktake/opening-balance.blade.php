@extends('layouts.app')

@section('title', 'Input Saldo Awal')

@section('content')
<div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-heading font-black text-xl text-slate-900">Input Saldo Awal (Titik Nol)</h1>
            <p class="text-xs text-slate-500">Khusus untuk produk baru yang belum memiliki riwayat transaksi apa pun.</p>
        </div>
        <a href="{{ route('stocktake.index') }}" class="text-xs font-bold text-slate-500 hover:text-slate-800">
            &larr; Kembali
        </a>
    </div>

    <form method="POST" action="{{ route('stocktake.opening.store') }}" class="space-y-4">
        @csrf

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs space-y-3">
            <div>
                <label for="product_id" class="block text-xs font-bold text-slate-700 mb-1">Pilih Produk</label>
                <select id="product_id" name="product_id" required onchange="onProductSelect(this)"
                    class="w-full text-xs font-bold p-3 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500 text-slate-800">
                    <option value="" disabled selected>-- Pilih Produk Baru --</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" data-has-exp="{{ $p->has_expiry ? '1' : '0' }}" data-last-buy="{{ $p->last_buy_price }}">
                            {{ $p->name }} ({{ $p->base_unit }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="qty" class="block text-xs font-bold text-slate-700 mb-1">Jumlah Awal (Qty)</label>
                    <input type="number" step="any" min="0.001" id="qty" name="qty" required placeholder="0"
                        class="w-full text-xs font-bold p-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500">
                </div>

                <div>
                    <label for="unit_cost" class="block text-xs font-bold text-slate-700 mb-1">Harga Beli Satuan (Rp)</label>
                    <input type="number" min="0" id="unit_cost" name="unit_cost" required placeholder="0"
                        class="w-full text-xs font-bold p-2.5 bg-slate-50 border border-slate-300 rounded-xl font-mono focus:bg-white focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <div id="exp-container" class="hidden">
                <label for="expiry_date" class="block text-xs font-bold text-amber-700 mb-1">Tanggal Kedaluwarsa (Wajib)</label>
                <input type="date" id="expiry_date" name="expiry_date" min="{{ now()->format('Y-m-d') }}"
                    class="w-full text-xs font-semibold p-2.5 bg-amber-50/50 border border-amber-300 rounded-xl focus:ring-2 focus:ring-amber-500">
            </div>
        </div>

        <button type="submit" class="w-full touch-target bg-emerald-600 hover:bg-emerald-700 text-white font-heading font-black text-sm py-3.5 rounded-2xl shadow-md transition-all">
            Simpan Saldo Awal
        </button>
    </form>
</div>

@push('scripts')
<script>
    function onProductSelect(select) {
        const option = select.options[select.selectedIndex];
        const hasExp = option.getAttribute('data-has-exp') === '1';
        const lastBuy = option.getAttribute('data-last-buy') || 0;

        const expContainer = document.getElementById('exp-container');
        const expInput = document.getElementById('expiry_date');
        const unitCostInput = document.getElementById('unit_cost');

        if (hasExp) {
            expContainer.classList.remove('hidden');
            expInput.required = true;
        } else {
            expContainer.classList.add('hidden');
            expInput.required = false;
        }

        if (lastBuy > 0) {
            unitCostInput.value = lastBuy;
        }
    }
</script>
@endpush
@endsection
