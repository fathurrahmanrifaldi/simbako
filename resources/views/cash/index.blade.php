@extends('layouts.app')

@section('title', 'Rekap Kas & Tutup Toko')

@section('content')
<div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-heading font-black text-xl text-slate-900">Kas & Tutup Hari</h1>
            <p class="text-xs text-slate-500">Pencocokan uang kas harian dan penutupan toko.</p>
        </div>
        <a href="{{ route('expenses.index') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl border border-slate-200">
            - Catat Pengeluaran
        </a>
    </div>

    <!-- Date Selector -->
    <form method="GET" action="{{ route('cash.index') }}" class="flex items-center space-x-2 bg-white p-2.5 rounded-2xl border border-slate-200 shadow-xs">
        <label for="date-select" class="text-xs font-bold text-slate-600 shrink-0">Tanggal Toko:</label>
        <input type="date" id="date-select" name="date" value="{{ $selectedDate }}" onchange="this.form.submit()"
            class="flex-1 text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-800">
        <button type="submit" class="px-3 py-2 bg-slate-800 text-white rounded-xl text-xs font-bold shrink-0">Pilih</button>
    </form>

    <!-- Closing Status Banner -->
    @if($closing)
        <div class="bg-emerald-50 border border-emerald-300 rounded-2xl p-4 shadow-xs">
            <div class="flex items-start justify-between">
                <div>
                    <div class="flex items-center space-x-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        <span class="text-xs font-black text-emerald-950 uppercase tracking-wider">Kas Tanggal Ini Telah Ditutup</span>
                    </div>
                    <div class="text-xs text-emerald-800 mt-1">
                        Ditutup oleh: <strong>{{ $closing->closer->name ?? '-' }}</strong> pada {{ $closing->closed_at->format('d/m/Y H:i') }}
                    </div>
                </div>

                <form method="POST" action="{{ route('cash.reopen', $closing->id) }}" onsubmit="return confirm('Buka ulang kas tanggal ini untuk penyesuaian?')">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 bg-white border border-emerald-300 text-emerald-800 hover:bg-emerald-100 text-xs font-bold rounded-xl shadow-xs">
                        Buka Uang Kas
                    </button>
                </form>
            </div>

            <div class="mt-3 pt-2.5 border-t border-emerald-200 grid grid-cols-3 gap-2 text-xs">
                <div>
                    <span class="text-slate-500 text-[10px] block">Kas Seharusnya</span>
                    <strong class="font-mono text-slate-900">Rp {{ number_format($closing->expected_cash, 0, ',', '.') }}</strong>
                </div>
                <div>
                    <span class="text-slate-500 text-[10px] block">Kas Fisik</span>
                    <strong class="font-mono text-slate-900">Rp {{ number_format($closing->actual_cash, 0, ',', '.') }}</strong>
                </div>
                <div>
                    <span class="text-slate-500 text-[10px] block">Selisih</span>
                    <strong class="font-mono {{ $closing->diff == 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                        {{ $closing->diff == 0 ? 'Cocok' : ($closing->diff < 0 ? 'Kurang Rp ' . number_format(abs($closing->diff), 0, ',', '.') : 'Lebih Rp ' . number_format($closing->diff, 0, ',', '.')) }}
                    </strong>
                </div>
            </div>

            @if($closing->note)
                <div class="mt-2 text-xs text-slate-600 bg-white/60 p-2 rounded-lg">
                    <strong>Catatan:</strong> {{ $closing->note }}
                </div>
            @endif
        </div>
    @endif

    <!-- Expected Cash Formula Breakdown (KAS-02, TC-14) -->
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs space-y-3">
        <h2 class="font-heading font-bold text-xs uppercase tracking-wider text-slate-600">Perhitungan Kas Seharusnya</h2>

        <div class="divide-y divide-slate-100 text-xs space-y-2">
            <div class="pt-2 flex justify-between items-center text-slate-700">
                <span>1. Kas Awal Toko (Opening Cash)</span>
                <span class="font-mono font-bold">Rp {{ number_format($openingCash, 0, ',', '.') }}</span>
            </div>

            <div class="pt-2 flex justify-between items-center text-emerald-700 font-semibold">
                <span>2. (+) Total Penjualan Tunai ({{ $transactionCount }} Transaksi)</span>
                <span class="font-mono font-bold">+ Rp {{ number_format($totalSales, 0, ',', '.') }}</span>
            </div>

            <div class="pt-2 flex justify-between items-center text-rose-700 font-semibold">
                <span>3. (-) Total Pengeluaran Kas Dicatat</span>
                <span class="font-mono font-bold">- Rp {{ number_format($totalExpenses, 0, ',', '.') }}</span>
            </div>

            <div class="pt-2 flex justify-between items-center text-rose-700 font-semibold">
                <span>4. (-) Pembelian Barang Dibayar dari Kas</span>
                <span class="font-mono font-bold">- Rp {{ number_format($totalCashPurchases, 0, ',', '.') }}</span>
            </div>

            <div class="pt-3 flex justify-between items-center text-sm font-black text-slate-900 border-t-2 border-slate-200">
                <span>Kas Seharusnya di Laci (Expected Cash)</span>
                <span class="font-mono text-base text-emerald-700">Rp {{ number_format($expectedCash, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <!-- Tutup Toko Form (KAS-03) -->
    @if(!$closing)
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs space-y-3">
            <h2 class="font-heading font-black text-slate-900 text-sm">Input Hitungan Kas Fisik & Tutup Hari</h2>
            <p class="text-xs text-slate-500">Hitung total uang fisik di laci kasir dan simpan penutupan hari ini.</p>

            <form method="POST" action="{{ route('cash.close') }}" class="space-y-3">
                @csrf
                <input type="hidden" name="business_date" value="{{ $selectedDate }}">
                <input type="hidden" name="opening_cash" value="{{ $openingCash }}">
                <input type="hidden" name="expected_cash" value="{{ $expectedCash }}" id="expected-cash-hidden">

                <div>
                    <label for="actual_cash" class="block text-xs font-bold text-slate-700 mb-1">Kas Fisik Nyata di Laci (Rp)</label>
                    <input type="number" min="0" id="actual_cash" name="actual_cash" required placeholder="0"
                        oninput="onActualCashInput(this.value)"
                        class="w-full text-base font-bold font-mono p-3 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500">
                </div>

                <!-- Difference Realtime Display (KAS-03) -->
                <div id="diff-box" class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between text-xs">
                    <span class="font-bold text-slate-600">Selisih Kas:</span>
                    <span id="diff-text" class="font-mono font-black text-slate-800">Rp 0 (Belum dihitung)</span>
                </div>

                <!-- Note Required if diff != 0 -->
                <div id="note-wrapper" class="hidden">
                    <label for="close_note" class="block text-xs font-bold text-rose-700 mb-1">Catatan Penjelasan Selisih (Wajib jika ada selisih)</label>
                    <textarea id="close_note" name="note" rows="2" placeholder="Jelaskan perkiraan penyebab selisih..."
                        class="w-full text-xs p-2.5 bg-rose-50/50 border border-rose-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-rose-500"></textarea>
                </div>

                <button type="submit" class="w-full touch-target bg-slate-900 hover:bg-slate-800 text-white font-heading font-black text-sm py-3.5 rounded-2xl shadow-md transition-all">
                    SIMPAN TUTUP KAS HARI INI
                </button>
            </form>
        </div>
    @endif

    <!-- Top 5 Products Sold Today (KAS-01) -->
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs space-y-2">
        <h2 class="font-heading font-bold text-xs uppercase tracking-wider text-slate-600">5 Produk Terlaris Hari Ini</h2>
        @if($topProducts->isEmpty())
            <p class="text-xs text-slate-400">Belum ada penjualan tercatat untuk tanggal ini.</p>
        @else
            <div class="divide-y divide-slate-100 text-xs">
                @foreach($topProducts as $idx => $tp)
                    <div class="py-2 flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <span class="w-5 h-5 rounded-full bg-slate-100 text-slate-700 font-bold flex items-center justify-center text-[10px]">{{ $idx + 1 }}</span>
                            <span class="font-bold text-slate-800">{{ $tp->product->name ?? '-' }}</span>
                        </div>
                        <div class="text-right">
                            <span class="font-mono font-bold text-emerald-700">{{ $tp->total_qty }} {{ $tp->product->base_unit ?? '' }}</span>
                            <span class="text-slate-400 block text-[10px]">Rp {{ number_format($tp->total_revenue, 0, ',', '.') }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    const expectedCash = {{ $expectedCash }};

    function onActualCashInput(val) {
        const actual = parseInt(val, 10);
        const diffBox = document.getElementById('diff-box');
        const diffText = document.getElementById('diff-text');
        const noteWrapper = document.getElementById('note-wrapper');
        const noteInput = document.getElementById('close_note');

        if (isNaN(actual)) {
            diffText.innerText = 'Rp 0 (Belum dihitung)';
            diffBox.className = 'p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between text-xs';
            noteWrapper.classList.add('hidden');
            noteInput.required = false;
            return;
        }

        const diff = actual - expectedCash;

        if (diff === 0) {
            diffText.innerText = 'Cocok (Rp 0)';
            diffBox.className = 'p-3 rounded-xl bg-emerald-50 border border-emerald-300 text-emerald-900 flex items-center justify-between text-xs';
            noteWrapper.classList.add('hidden');
            noteInput.required = false;
        } else if (diff < 0) {
            diffText.innerText = `Kurang Rp ${Math.abs(diff).toLocaleString('id-ID')}`;
            diffBox.className = 'p-3 rounded-xl bg-rose-50 border border-rose-300 text-rose-900 flex items-center justify-between text-xs';
            noteWrapper.classList.remove('hidden');
            noteInput.required = true;
        } else {
            diffText.innerText = `Lebih Rp ${diff.toLocaleString('id-ID')}`;
            diffBox.className = 'p-3 rounded-xl bg-blue-50 border border-blue-300 text-blue-900 flex items-center justify-between text-xs';
            noteWrapper.classList.remove('hidden');
            noteInput.required = true;
        }
    }
</script>
@endpush
@endsection
