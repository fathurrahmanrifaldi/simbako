@extends('layouts.app')

@section('title', 'Catat Pengeluaran Kas')

@section('content')
<div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-heading font-black text-xl text-slate-900">Pengeluaran Kas</h1>
            <p class="text-xs text-slate-500">Catat belanja harian pasar, ongkos, atau keperluan toko.</p>
        </div>
        <a href="{{ route('cash.index') }}" class="text-xs font-bold text-slate-500 hover:text-slate-800">
            &larr; Rekap Kas
        </a>
    </div>

    <!-- Form Pengeluaran Baru -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs space-y-3">
        <h2 class="font-heading font-bold text-xs uppercase tracking-wider text-slate-600">Catat Pengeluaran Baru</h2>

        <form method="POST" action="{{ route('expenses.store') }}" class="space-y-3">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="spent_on" class="block text-xs font-bold text-slate-700 mb-1">Tanggal</label>
                    <input type="date" id="spent_on" name="spent_on" value="{{ old('spent_on', now()->format('Y-m-d')) }}" required
                        class="w-full text-xs font-semibold p-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500">
                </div>

                <div>
                    <label for="amount" class="block text-xs font-bold text-slate-700 mb-1">Nominal (Rp)</label>
                    <input type="number" min="1" id="amount" name="amount" required placeholder="Contoh: 25000"
                        class="w-full text-xs font-bold p-2.5 bg-slate-50 border border-slate-300 rounded-xl font-mono focus:bg-white focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <div>
                <label for="description" class="block text-xs font-bold text-slate-700 mb-1">Keterangan / Keperluan</label>
                <input type="text" id="description" name="description" required placeholder="Contoh: Beli kantong plastik, beli bensin kulakan"
                    class="w-full text-xs font-medium p-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500">
            </div>

            <button type="submit" class="w-full touch-target bg-purple-600 hover:bg-purple-700 text-white font-heading font-bold text-xs py-3 rounded-xl shadow-xs transition-colors">
                Simpan Pengeluaran
            </button>
        </form>
    </div>

    <!-- Daftar Pengeluaran -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-3.5 bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-700 flex justify-between items-center">
            <span>Riwayat Pengeluaran</span>
            <span class="text-xs text-purple-700">Bulan Ini: Rp {{ number_format($totalThisMonth, 0, ',', '.') }}</span>
        </div>

        @if($expenses->isEmpty())
            <div class="p-8 text-center text-xs text-slate-400">
                Belum ada pengeluaran kas yang dicatat.
            </div>
        @else
            <div class="divide-y divide-slate-100 text-xs">
                @foreach($expenses as $ex)
                    <div class="p-3.5 flex items-center justify-between">
                        <div>
                            <div class="font-bold text-slate-800">{{ $ex->description }}</div>
                            <div class="text-[11px] text-slate-400 mt-0.5">
                                {{ $ex->spent_on ? $ex->spent_on->format('d/m/Y') : '-' }} · Oleh: {{ $ex->creator->name ?? '-' }}
                            </div>
                        </div>

                        <div class="text-right font-mono font-bold text-rose-600 text-sm">
                            - Rp {{ number_format($ex->amount, 0, ',', '.') }}
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="mt-4">
        {{ $expenses->links() }}
    </div>
</div>
@endsection
