@extends('layouts.app')

@section('title', 'Riwayat Penjualan')

@section('content')
<div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-heading font-black text-xl text-slate-900">Riwayat Penjualan</h1>
            <p class="text-xs text-slate-500">
                {{ $isOwner ? 'Semua transaksi penjualan warung' : 'Transaksi Anda hari ini' }}
            </p>
        </div>
        <a href="{{ route('sales.summary') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl flex items-center space-x-1 border border-slate-200">
            <span>📝 Rekap Akhir Hari</span>
        </a>
    </div>

    <!-- Date Filter -->
    <form method="GET" action="{{ route('sales.history') }}" class="flex items-center space-x-2 bg-white p-2.5 rounded-2xl border border-slate-200 shadow-xs">
        <label for="date-filter" class="text-xs font-bold text-slate-600 shrink-0">Tanggal:</label>
        <input type="date" id="date-filter" name="date" value="{{ request('date', now()->format('Y-m-d')) }}"
            class="flex-1 text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-800">
        <button type="submit" class="px-3 py-2 bg-slate-800 text-white rounded-xl text-xs font-bold shrink-0">Filter</button>
    </form>

    <!-- Sales List -->
    @if($sales->isEmpty())
        <div class="bg-white border border-dashed border-slate-300 rounded-2xl p-8 text-center">
            <span class="text-3xl mb-2 block">🧾</span>
            <p class="font-bold text-slate-800 text-sm">Belum ada transaksi</p>
            <p class="text-xs text-slate-500 mt-1">Tidak ada penjualan yang tercatat pada filter ini.</p>
        </div>
    @else
        <div class="space-y-2.5">
            @foreach($sales as $sale)
                <div class="bg-white border {{ $sale->status === 'void' ? 'border-rose-200 bg-rose-50/20' : 'border-slate-200' }} rounded-2xl p-4 shadow-xs">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="font-mono text-xs font-bold text-slate-500">#{{ substr($sale->id, 0, 8) }}</span>
                                <span class="text-[11px] font-bold px-2 py-0.5 rounded-md {{ $sale->status === 'void' ? 'bg-rose-100 text-rose-800' : 'bg-emerald-100 text-emerald-800' }}">
                                    {{ $sale->status === 'void' ? 'Dibatalkan (Void)' : 'Sukses' }}
                                </span>
                                @if($sale->is_summary)
                                    <span class="text-[10px] font-bold bg-amber-100 text-amber-800 px-1.5 py-0.5 rounded">Rekap Manual</span>
                                @endif
                            </div>
                            <div class="text-xs text-slate-400 mt-1">
                                {{ $sale->sold_at ? $sale->sold_at->format('d/m/Y H:i') : '-' }} · Kasir: <strong class="text-slate-600">{{ $sale->creator->name ?? '-' }}</strong>
                            </div>
                        </div>

                        <div class="text-right">
                            <div class="font-heading font-black text-sm text-slate-900">
                                Rp {{ number_format($sale->total, 0, ',', '.') }}
                            </div>
                            <span class="text-[10px] text-slate-400 font-semibold uppercase">{{ $sale->payment_method }}</span>
                        </div>
                    </div>

                    <!-- Items Preview -->
                    <div class="mt-3 pt-2.5 border-t border-slate-100 text-xs space-y-1">
                        @foreach($sale->items as $item)
                            <div class="flex items-center justify-between text-slate-700">
                                <span>{{ $item->product->name ?? 'Produk dihapus' }} &times; {{ $item->qty }} {{ $item->product->base_unit ?? '' }}</span>
                                <span class="font-mono font-medium text-slate-500">Rp {{ number_format($item->qty * $item->unit_price, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>

                    <!-- Void Reason Info if voided -->
                    @if($sale->status === 'void')
                        <div class="mt-2.5 p-2 bg-rose-50 border border-rose-100 rounded-xl text-xs text-rose-700">
                            <strong>Alasan Batal:</strong> {{ $sale->void_reason }}
                            <div class="text-[10px] text-rose-500 mt-0.5">Dibatalkan oleh {{ $sale->voider->name ?? '-' }} pada {{ $sale->voided_at ? $sale->voided_at->format('d/m/Y H:i') : '-' }}</div>
                        </div>
                    @else
                        <!-- Void Action Button -->
                        <div class="mt-3 pt-2 border-t border-slate-100 flex justify-end">
                            <button type="button" onclick="openVoidModal('{{ $sale->id }}', '{{ number_format($sale->total, 0, ',', '.') }}')"
                                class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold rounded-xl transition-colors">
                                Batalkan (Void)
                            </button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $sales->links() }}
        </div>
    @endif
</div>

<!-- Modal Dialog Void Penjualan (JUL-06) -->
<div id="void-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-5 w-full max-w-sm shadow-2xl">
        <h3 class="font-heading font-black text-slate-900 text-base mb-1 text-rose-600">Batalkan Transaksi (Void)</h3>
        <p class="text-xs text-slate-500 mb-3" id="void-modal-info">Stok akan dikembalikan ke batch awal secara otomatis.</p>

        <form id="void-form" method="POST" action="">
            @csrf
            <div class="space-y-3">
                <div>
                    <label for="void_reason" class="block text-xs font-bold text-slate-700 mb-1">Alasan Pembatalan (Wajib)</label>
                    <textarea id="void_reason" name="void_reason" required minlength="3" rows="3"
                        class="w-full text-xs font-medium p-3 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500"
                        placeholder="Contoh: Pembeli salah beli item, transaksi dibatalkan..."></textarea>
                </div>

                <div class="flex space-x-2 pt-2">
                    <button type="button" onclick="closeVoidModal()" class="flex-1 py-2.5 rounded-xl border border-slate-300 text-slate-600 font-bold text-xs">Batal</button>
                    <button type="submit" class="flex-1 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-xs">Konfirmasi Void</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openVoidModal(saleId, total) {
        document.getElementById('void-form').action = `/jual/${saleId}/void`;
        document.getElementById('void-modal-info').innerText = `Transaksi senilai Rp ${total} akan dibatalkan dan stok dikembalikan.`;
        document.getElementById('void-modal').classList.remove('hidden');
    }
    function closeVoidModal() {
        document.getElementById('void-modal').classList.add('hidden');
    }
</script>
@endpush
@endsection
