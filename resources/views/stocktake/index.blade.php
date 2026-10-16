@extends('layouts.app')

@section('title', 'Stok Opname')

@section('content')
<div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-heading font-black text-xl text-slate-900">Stok Opname</h1>
            <p class="text-xs text-slate-500">Rekonsiliasi fisik barang dengan catatan sistem.</p>
        </div>
        <button type="button" onclick="openStartModal()" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs">
            + Mulai Opname
        </button>
    </div>

    <!-- Active Draft Banner (OPN-01) -->
    @if($activeDraft)
        <div class="bg-amber-50 border border-amber-300 rounded-2xl p-4 shadow-xs flex items-center justify-between space-x-3">
            <div>
                <div class="flex items-center space-x-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                    <span class="text-xs font-black text-amber-900 uppercase tracking-wider">Sesi Draft Berjalan</span>
                </div>
                <p class="text-xs font-semibold text-amber-800 mt-0.5">
                    Cakupan: {{ $activeDraft->scope }} · Dimulai oleh {{ $activeDraft->counter->name ?? '-' }}
                </p>
            </div>
            <a href="{{ route('stocktake.show', $activeDraft->id) }}" class="px-3.5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs rounded-xl shadow-xs shrink-0">
                Lanjutkan &rarr;
            </a>
        </div>
    @endif

    <!-- History Opname -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-3.5 bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-700 flex justify-between items-center">
            <span>Riwayat Sesi Opname</span>
            <span class="text-[11px] text-slate-400 font-normal">Tersimpan permanen</span>
        </div>

        @if($stocktakes->isEmpty())
            <div class="p-8 text-center text-xs text-slate-400">
                Belum ada sesi stok opname yang tercatat.
            </div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach($stocktakes as $st)
                    <a href="{{ route('stocktake.show', $st->id) }}" class="p-3.5 flex items-center justify-between hover:bg-slate-50 transition-colors block">
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="text-xs font-bold text-slate-900">{{ $st->scope }}</span>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded {{ $st->status === 'confirmed' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $st->status === 'confirmed' ? 'Terkonfirmasi' : 'Draft' }}
                                </span>
                            </div>
                            <div class="text-[11px] text-slate-400 mt-1">
                                Dibuat: {{ $st->created_at->format('d/m/Y H:i') }} · Oleh: {{ $st->counter->name ?? '-' }}
                                @if($st->confirmed_at)
                                    · Dikonfirmasi: {{ $st->confirmer->name ?? '-' }}
                                @endif
                            </div>
                        </div>

                        <div class="text-right">
                            <span class="text-xs text-emerald-700 font-bold">Lihat Detail &rarr;</span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    @if(auth()->user()->isOwner())
        <div class="p-3 bg-slate-100 rounded-2xl text-xs text-slate-600 flex items-center justify-between">
            <span>Punya produk baru tanpa riwayat pergerakan?</span>
            <a href="{{ route('stocktake.opening') }}" class="font-bold text-emerald-700 underline">Input Saldo Awal &rarr;</a>
        </div>
    @endif
</div>

<!-- Modal Mulai Opname (OPN-01) -->
<div id="start-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-5 w-full max-w-sm shadow-2xl">
        <h3 class="font-heading font-black text-slate-900 text-base mb-1">Mulai Sesi Stok Opname</h3>
        <p class="text-xs text-slate-500 mb-4">Pilih cakupan produk yang akan dihitung fisiknya.</p>

        <form method="POST" action="{{ route('stocktake.start') }}" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Cakupan Opname</label>
                <div class="space-y-2 text-xs">
                    <label class="flex items-center space-x-2 p-2.5 bg-slate-50 rounded-xl border border-slate-200 cursor-pointer">
                        <input type="radio" name="scope" value="all" checked onchange="toggleCategorySelect(false)" class="text-emerald-600">
                        <span class="font-bold text-slate-800">Semua Produk Aktif</span>
                    </label>
                    <label class="flex items-center space-x-2 p-2.5 bg-slate-50 rounded-xl border border-slate-200 cursor-pointer">
                        <input type="radio" name="scope" value="category" onchange="toggleCategorySelect(true)" class="text-emerald-600">
                        <span class="font-bold text-slate-800">Satu Kategori Tertentu</span>
                    </label>
                </div>
            </div>

            <div id="category-select-wrapper" class="hidden">
                <label for="category_id" class="block text-xs font-bold text-slate-700 mb-1">Pilih Kategori</label>
                <select id="category_id" name="category_id" class="w-full text-xs font-bold p-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex space-x-2 pt-2">
                <button type="button" onclick="closeStartModal()" class="flex-1 py-2.5 rounded-xl border border-slate-300 text-slate-600 font-bold text-xs">Batal</button>
                <button type="submit" class="flex-1 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs">Mulai Hitung</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openStartModal() {
        document.getElementById('start-modal').classList.remove('hidden');
    }
    function closeStartModal() {
        document.getElementById('start-modal').classList.add('hidden');
    }
    function toggleCategorySelect(show) {
        document.getElementById('category-select-wrapper').classList.toggle('hidden', !show);
    }
</script>
@endpush
@endsection
