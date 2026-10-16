@extends('layouts.app')

@section('title', 'Riwayat Barang Masuk')

@section('content')
<div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-heading font-black text-xl text-slate-900">Riwayat Pembelian</h1>
            <p class="text-xs text-slate-500">Daftar stok masuk dari supplier.</p>
        </div>
        <a href="{{ route('purchases.create') }}" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs flex items-center space-x-1">
            <span>+ Catat Masuk</span>
        </a>
    </div>

    <!-- Date Filter -->
    <form method="GET" action="{{ route('purchases.history') }}" class="flex items-center space-x-2 bg-white p-2.5 rounded-2xl border border-slate-200 shadow-xs">
        <label for="date-filter" class="text-xs font-bold text-slate-600 shrink-0">Tanggal:</label>
        <input type="date" id="date-filter" name="date" value="{{ request('date') }}"
            class="flex-1 text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-800">
        <button type="submit" class="px-3 py-2 bg-slate-800 text-white rounded-xl text-xs font-bold shrink-0">Filter</button>
        @if(request('date'))
            <a href="{{ route('purchases.history') }}" class="px-2 py-2 text-slate-400 hover:text-slate-600 text-xs">Reset</a>
        @endif
    </form>

    <!-- Purchases List -->
    @if($purchases->isEmpty())
        <div class="bg-white border border-dashed border-slate-300 rounded-2xl p-8 text-center">
            <span class="text-3xl mb-2 block">📦</span>
            <p class="font-bold text-slate-800 text-sm">Belum ada riwayat pembelian</p>
            <p class="text-xs text-slate-500 mt-1">Gunakan tombol "+ Catat Masuk" untuk menambah stok barang masuk.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($purchases as $p)
                <div class="bg-white border {{ $p->status === 'void' ? 'border-rose-200 bg-rose-50/20' : 'border-slate-200' }} rounded-2xl p-4 shadow-xs">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="font-heading font-bold text-slate-900 text-sm">
                                    {{ $p->supplier ? $p->supplier : 'Tanpa Supplier' }}
                                </span>
                                <span class="text-[11px] font-bold px-2 py-0.5 rounded-md {{ $p->status === 'void' ? 'bg-rose-100 text-rose-800' : 'bg-emerald-100 text-emerald-800' }}">
                                    {{ $p->status === 'void' ? 'Dibatalkan (Void)' : 'Aktif' }}
                                </span>
                                @if($p->paid_from_cash)
                                    <span class="text-[10px] font-bold bg-amber-100 text-amber-800 px-1.5 py-0.5 rounded">Dari Uang Kas</span>
                                @endif
                            </div>
                            <div class="text-xs text-slate-400 mt-1">
                                Tgl: {{ $p->purchased_on ? $p->purchased_on->format('d/m/Y') : '-' }} · Dicatat oleh: {{ $p->creator->name ?? '-' }}
                            </div>
                        </div>

                        <div class="text-right">
                            <div class="font-heading font-black text-sm text-slate-900">
                                Rp {{ number_format($p->total, 0, ',', '.') }}
                            </div>
                        </div>
                    </div>

                    <!-- Items Detail -->
                    <div class="mt-3 pt-2.5 border-t border-slate-100 text-xs space-y-1.5">
                        @foreach($p->items as $item)
                            <div class="flex items-center justify-between text-slate-700">
                                <div>
                                    <span class="font-bold">{{ $item->product->name ?? 'Produk' }}</span> &times; {{ $item->qty }} {{ $item->product->base_unit ?? '' }}
                                    @if($item->expiry_date)
                                        <span class="text-[10px] text-amber-700 bg-amber-50 px-1 py-0.5 rounded border border-amber-200 ml-1">Exp: {{ $item->expiry_date->format('d/m/Y') }}</span>
                                    @endif
                                </div>
                                <span class="font-mono font-medium text-slate-500">Rp {{ number_format($item->qty * $item->unit_price, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>

                    <!-- Void Reason Info if voided -->
                    @if($p->status === 'void')
                        <div class="mt-2.5 p-2 bg-rose-50 border border-rose-100 rounded-xl text-xs text-rose-700">
                            <strong>Alasan Void:</strong> {{ $p->void_reason }}
                            <div class="text-[10px] text-rose-500 mt-0.5">Dibatalkan oleh {{ $p->voider->name ?? '-' }} pada {{ $p->voided_at ? $p->voided_at->format('d/m/Y H:i') : '-' }}</div>
                        </div>
                    @else
                        <!-- Void Action Button (Owner Only) -->
                        <div class="mt-3 pt-2 border-t border-slate-100 flex justify-end">
                            <button type="button" onclick="openVoidPurchaseModal('{{ $p->id }}', '{{ number_format($p->total, 0, ',', '.') }}')"
                                class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold rounded-xl transition-colors">
                                Batalkan (Void)
                            </button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $purchases->links() }}
        </div>
    @endif
</div>

<!-- Modal Dialog Void Pembelian (BEL-04) -->
<div id="void-purchase-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-5 w-full max-w-sm shadow-2xl">
        <h3 class="font-heading font-black text-slate-900 text-base mb-1 text-rose-600">Batalkan Pembelian (Void)</h3>
        <p class="text-xs text-slate-500 mb-3" id="void-purchase-modal-info">Stok akan dikurangkan kembali. Pembatalan hanya diizinkan jika barang belum terjual.</p>

        <form id="void-purchase-form" method="POST" action="">
            @csrf
            <div class="space-y-3">
                <div>
                    <label for="purchase_void_reason" class="block text-xs font-bold text-slate-700 mb-1">Alasan Pembatalan (Wajib)</label>
                    <textarea id="purchase_void_reason" name="void_reason" required minlength="3" rows="3"
                        class="w-full text-xs font-medium p-3 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500"
                        placeholder="Contoh: Salah input faktur, barang retur supplier..."></textarea>
                </div>

                <div class="flex space-x-2 pt-2">
                    <button type="button" onclick="closeVoidPurchaseModal()" class="flex-1 py-2.5 rounded-xl border border-slate-300 text-slate-600 font-bold text-xs">Batal</button>
                    <button type="submit" class="flex-1 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-xs">Konfirmasi Void</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openVoidPurchaseModal(purchaseId, total) {
        document.getElementById('void-purchase-form').action = `/beli/${purchaseId}/void`;
        document.getElementById('void-purchase-modal-info').innerText = `Pembelian senilai Rp ${total} akan dibatalkan jika belum ada barang yang terjual.`;
        document.getElementById('void-purchase-modal').classList.remove('hidden');
    }
    function closeVoidPurchaseModal() {
        document.getElementById('void-purchase-modal').classList.add('hidden');
    }
</script>
@endpush
@endsection
