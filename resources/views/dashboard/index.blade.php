@extends('layouts.app')

@section('title', 'Peringatan & Dashboard')

@section('content')
<div class="space-y-4">
    <!-- Header -->
    <div>
        <h1 class="font-heading font-black text-xl text-slate-900">Peringatan Stok</h1>
        <p class="text-xs text-slate-500">Pantau barang yang mendesak untuk dibeli dan mendekati kedaluwarsa.</p>
    </div>

    <!-- 3 Summary Metric Counters (DSH-05) -->
    <div class="grid grid-cols-3 gap-2.5">
        <!-- 1. Perlu Dibeli -->
        <button type="button" onclick="switchDashboardTab('reorder')"
            class="p-3 rounded-2xl border text-left transition-all {{ $summary['reorder_count'] > 0 ? 'bg-rose-50 border-rose-200 text-rose-900' : 'bg-white border-slate-200 text-slate-800' }}">
            <div class="flex items-center justify-between mb-1">
                <span class="text-[10px] font-bold uppercase tracking-wider text-rose-700">Perlu Dibeli</span>
                <span class="text-xs">🛒</span>
            </div>
            <div class="font-heading font-black text-2xl text-rose-700 leading-none">
                {{ $summary['reorder_count'] }}
            </div>
            <span class="text-[10px] text-slate-500 mt-1 block">Habis & menipis</span>
        </button>

        <!-- 2. Mendekati Exp -->
        <button type="button" onclick="switchDashboardTab('expiring')"
            class="p-3 rounded-2xl border text-left transition-all {{ $summary['expiring_count'] > 0 ? 'bg-amber-50 border-amber-200 text-amber-900' : 'bg-white border-slate-200 text-slate-800' }}">
            <div class="flex items-center justify-between mb-1">
                <span class="text-[10px] font-bold uppercase tracking-wider text-amber-700">Dekat Exp</span>
                <span class="text-xs">⏳</span>
            </div>
            <div class="font-heading font-black text-2xl text-amber-700 leading-none">
                {{ $summary['expiring_count'] }}
            </div>
            <span class="text-[10px] text-slate-500 mt-1 block">Batch & lewat exp</span>
        </button>

        <!-- 3. Stok Minus -->
        <div class="p-3 rounded-2xl border text-left {{ $summary['negative_count'] > 0 ? 'bg-orange-50 border-orange-200 text-orange-900' : 'bg-white border-slate-200 text-slate-800' }}">
            <div class="flex items-center justify-between mb-1">
                <span class="text-[10px] font-bold uppercase tracking-wider text-orange-700">Stok Minus</span>
                <span class="text-xs">⚠️</span>
            </div>
            <div class="font-heading font-black text-2xl text-orange-700 leading-none">
                {{ $summary['negative_count'] }}
            </div>
            <span class="text-[10px] text-slate-500 mt-1 block">Perlu opname</span>
        </div>
    </div>

    <!-- Tab Selector -->
    <div class="flex p-1 bg-slate-100 rounded-2xl">
        <button type="button" id="tab-reorder-btn" onclick="switchDashboardTab('reorder')"
            class="flex-1 py-2 text-xs font-bold rounded-xl transition-all bg-white text-slate-900 shadow-xs">
            Perlu Dibeli ({{ count($reorderList) }})
        </button>
        <button type="button" id="tab-expiring-btn" onclick="switchDashboardTab('expiring')"
            class="flex-1 py-2 text-xs font-bold rounded-xl transition-all text-slate-500 hover:text-slate-900">
            Mendekati Exp ({{ count($expiringBatches) }})
        </button>
    </div>

    <!-- Section 1: Daftar Perlu Dibeli (DSH-01, TC-07, TC-08, TC-09) -->
    <div id="section-reorder" class="space-y-2.5">
        @if(empty($reorderList))
            <div class="bg-white border border-slate-200 rounded-2xl p-8 text-center shadow-xs">
                <span class="text-3xl mb-2 block">✨</span>
                <p class="font-bold text-slate-800 text-sm">Stok Aman Terkendali</p>
                <p class="text-xs text-slate-500 mt-1">Tidak ada produk yang habis atau berada di bawah batas minimum.</p>
            </div>
        @else
            @foreach($reorderList as $item)
                <div class="bg-white border {{ $item['is_negative'] ? 'border-orange-300 bg-orange-50/20' : 'border-slate-200' }} rounded-2xl p-3.5 shadow-xs flex items-center justify-between space-x-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center space-x-2">
                            <span class="text-xs font-bold text-slate-900 truncate">{{ $item['name'] }}</span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded {{ $item['status'] === 'Habis' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ $item['status'] }}
                            </span>
                            @if($item['is_negative'])
                                <span class="text-[10px] font-bold bg-orange-100 text-orange-800 px-1.5 py-0.5 rounded">
                                    Stok Minus!
                                </span>
                            @endif
                        </div>

                        <div class="text-[11px] text-slate-500 mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5">
                            <span>Sisa: <strong class="{{ $item['sellable_stock'] <= 0 ? 'text-rose-600' : 'text-slate-800' }} font-mono font-bold">{{ $item['sellable_stock'] }} {{ $item['base_unit'] }}</strong></span>
                            <span>· Batas pesan: <strong class="text-slate-700 font-mono">{{ $item['threshold'] ?? '-' }}</strong></span>
                            @if($item['days_left'] !== null)
                                <span>· Habis dlm: <strong class="text-slate-700">{{ $item['days_left'] }} hari</strong></span>
                            @endif
                        </div>

                        @if($item['is_negative'])
                            <div class="mt-1 text-[11px] text-orange-700">
                                <a href="{{ route('stocktake.index') }}" class="font-bold underline">Mulai Opname Produk Ini &rarr;</a>
                            </div>
                        @endif
                    </div>

                    @if($isOwner)
                        <a href="{{ route('purchases.create') }}" class="shrink-0 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs">
                            + Beli
                        </a>
                    @endif
                </div>
            @endforeach
        @endif
    </div>

    <!-- Section 2: Daftar Mendekati Exp (DSH-02, TC-10) -->
    <div id="section-expiring" class="hidden space-y-2.5">
        @if(empty($expiringBatches))
            <div class="bg-white border border-slate-200 rounded-2xl p-8 text-center shadow-xs">
                <span class="text-3xl mb-2 block">🌿</span>
                <p class="font-bold text-slate-800 text-sm">Tidak Ada Barang Mendekati Exp</p>
                <p class="text-xs text-slate-500 mt-1">Semua batch barang masih memiliki masa simpan yang cukup lama.</p>
            </div>
        @else
            @foreach($expiringBatches as $batch)
                <div class="bg-white border {{ $batch['is_expired'] ? 'border-rose-300 bg-rose-50/30' : 'border-amber-200 bg-amber-50/20' }} rounded-2xl p-3.5 shadow-xs flex items-center justify-between space-x-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center space-x-2">
                            <span class="text-xs font-bold text-slate-900 truncate">{{ $batch['product_name'] }}</span>
                            <span class="text-[10px] font-black px-2 py-0.5 rounded {{ $batch['is_expired'] ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ $batch['status_text'] }}
                            </span>
                        </div>

                        <div class="text-[11px] text-slate-500 mt-1 flex flex-wrap items-center gap-x-2">
                            <span>Sisa batch: <strong class="font-bold text-slate-800">{{ $batch['remaining_qty'] }} {{ $batch['base_unit'] }}</strong></span>
                            <span>· Masuk: {{ $batch['received_on'] }}</span>
                            <span>· Exp: <strong class="{{ $batch['is_expired'] ? 'text-rose-600' : 'text-amber-800' }}">{{ $batch['expiry_date'] }}</strong></span>
                        </div>
                    </div>

                    @if($isOwner)
                        <button type="button" onclick="openWasteModal('{{ $batch['id'] }}', '{{ addslashes($batch['product_name']) }}', {{ $batch['remaining_qty'] }}, '{{ $batch['base_unit'] }}')"
                            class="shrink-0 px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold rounded-xl border border-rose-200">
                            🗑️ Buang
                        </button>
                    @endif
                </div>
            @endforeach
        @endif
    </div>
</div>

<!-- Modal Aksi Buang Barang (DSH-03) -->
<div id="waste-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-5 w-full max-w-sm shadow-2xl">
        <h3 class="font-heading font-black text-slate-900 text-base mb-1 text-rose-600">Catat Buang Barang</h3>
        <p class="text-xs text-slate-500 mb-3" id="waste-modal-info">Batch yang kedaluwarsa atau rusak dikeluarkan dari stok layak jual.</p>

        <form id="waste-form" method="POST" action="">
            @csrf
            <div class="space-y-3">
                <div>
                    <label for="waste_qty" class="block text-xs font-bold text-slate-700 mb-1">Jumlah yang Dibuang</label>
                    <input type="number" step="any" min="0.001" id="waste_qty" name="qty" required
                        class="w-full text-xs font-bold p-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500 font-mono">
                </div>

                <div>
                    <label for="waste_reason" class="block text-xs font-bold text-slate-700 mb-1">Alasan Pembuangan</label>
                    <select id="waste_reason" name="reason" required
                        class="w-full text-xs font-bold p-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500">
                        <option value="kedaluwarsa">Kedaluwarsa (Lewat Exp)</option>
                        <option value="rusak">Barang Rusak / Pecah</option>
                        <option value="lainnya">Lainnya</option>
                    </select>
                </div>

                <div class="flex space-x-2 pt-2">
                    <button type="button" onclick="closeWasteModal()" class="flex-1 py-2.5 rounded-xl border border-slate-300 text-slate-600 font-bold text-xs">Batal</button>
                    <button type="submit" class="flex-1 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-xs">Buang Barang</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function switchDashboardTab(tab) {
        const secReorder = document.getElementById('section-reorder');
        const secExpiring = document.getElementById('section-expiring');
        const btnReorder = document.getElementById('tab-reorder-btn');
        const btnExpiring = document.getElementById('tab-expiring-btn');

        if (tab === 'reorder') {
            secReorder.classList.remove('hidden');
            secExpiring.classList.add('hidden');
            btnReorder.className = 'flex-1 py-2 text-xs font-bold rounded-xl transition-all bg-white text-slate-900 shadow-xs';
            btnExpiring.className = 'flex-1 py-2 text-xs font-bold rounded-xl transition-all text-slate-500 hover:text-slate-900';
        } else {
            secReorder.classList.add('hidden');
            secExpiring.classList.remove('hidden');
            btnExpiring.className = 'flex-1 py-2 text-xs font-bold rounded-xl transition-all bg-white text-slate-900 shadow-xs';
            btnReorder.className = 'flex-1 py-2 text-xs font-bold rounded-xl transition-all text-slate-500 hover:text-slate-900';
        }
    }

    function openWasteModal(batchId, productName, remainingQty, baseUnit) {
        document.getElementById('waste-form').action = `/peringatan/buang/${batchId}`;
        document.getElementById('waste_qty').value = remainingQty;
        document.getElementById('waste_qty').max = remainingQty;
        document.getElementById('waste-modal-info').innerText = `Produk: ${productName} (Sisa batch: ${remainingQty} ${baseUnit})`;
        document.getElementById('waste-modal').classList.remove('hidden');
    }

    function closeWasteModal() {
        document.getElementById('waste-modal').classList.add('hidden');
    }
</script>
@endpush
@endsection
