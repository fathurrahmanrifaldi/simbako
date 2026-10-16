@extends('layouts.app')

@section('title', 'Sesi Opname: ' . $stocktake->scope)

@section('content')
<div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center space-x-2">
                <h1 class="font-heading font-black text-xl text-slate-900">Hitung Fisik Opname</h1>
                <span class="text-xs font-bold px-2 py-0.5 rounded-md {{ $stocktake->status === 'confirmed' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                    {{ $stocktake->status === 'confirmed' ? 'Terkonfirmasi' : 'Draft' }}
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">Cakupan: {{ $stocktake->scope }}</p>
        </div>
        <a href="{{ route('stocktake.index') }}" class="text-xs font-bold text-slate-500 hover:text-slate-800">
            &larr; Kembali
        </a>
    </div>

    <!-- Blind Count Toggle (OPN-02) -->
    @if($stocktake->status === 'draft')
        <div class="bg-white p-3 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between text-xs">
            <div class="flex items-center space-x-2">
                <span>🙈</span>
                <span class="font-bold text-slate-700">Mode Hitung Buta</span>
                <span class="text-slate-400 text-[11px]">(Sembunyikan angka sistem untuk objektivitas)</span>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" id="blind-toggle" checked onchange="toggleBlindMode(this.checked)" class="sr-only peer">
                <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
            </label>
        </div>
    @endif

    <!-- Items Counting Table -->
    <form id="stocktake-form" method="POST" action="{{ route('stocktake.draft', $stocktake->id) }}" class="space-y-4">
        @csrf

        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-3 bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-600 flex justify-between items-center">
                <span>Nama Produk</span>
                <div class="flex items-center space-x-4">
                    <span class="system-col w-20 text-center">Sistem</span>
                    <span class="w-24 text-center">Fisik</span>
                    <span class="w-20 text-right">Selisih</span>
                </div>
            </div>

            <div class="divide-y divide-slate-100 p-2 space-y-2">
                @foreach($stocktake->items as $index => $item)
                    @php
                        $sys = (float)$item->system_qty;
                        $phys = (float)$item->physical_qty;
                        $diff = $phys - $sys;
                    @endphp
                    <div class="p-2 bg-slate-50/50 rounded-xl space-y-2" id="item-card-{{ $item->product_id }}">
                        <div class="flex items-center justify-between space-x-2 text-xs">
                            <div class="flex-1 min-w-0">
                                <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item->product_id }}">
                                <div class="font-bold text-slate-900 truncate">{{ $item->product->name }}</div>
                                <span class="text-[10px] text-slate-400 font-semibold uppercase">{{ $item->product->base_unit }}</span>
                            </div>

                            <div class="flex items-center space-x-4 shrink-0">
                                <!-- System Qty -->
                                <span class="system-col w-20 text-center font-mono font-bold text-slate-500 text-xs {{ $stocktake->status === 'draft' ? 'blur-xs select-none' : '' }}"
                                    data-sys="{{ $sys }}" id="sys-val-{{ $item->product_id }}">
                                    {{ $sys }}
                                </span>

                                <!-- Physical Qty Input -->
                                <div class="w-24">
                                    <input type="number" step="any" min="0" name="items[{{ $index }}][physical_qty]"
                                        value="{{ $phys }}" {{ $stocktake->status === 'confirmed' ? 'disabled' : '' }}
                                        oninput="calculateDiff('{{ $item->product_id }}', this.value, {{ $sys }})"
                                        class="w-full text-center font-bold text-xs bg-white border border-slate-300 rounded-lg py-1.5 focus:ring-2 focus:ring-emerald-500">
                                </div>

                                <!-- Difference Indicator -->
                                <span class="w-20 text-right font-mono font-bold text-xs" id="diff-val-{{ $item->product_id }}">
                                    @if($diff == 0)
                                        <span class="text-emerald-600">Cocok</span>
                                    @elseif($diff < 0)
                                        <span class="text-rose-600">{{ $diff }}</span>
                                    @else
                                        <span class="text-blue-600">+{{ $diff }}</span>
                                    @endif
                                </span>
                            </div>
                        </div>

                        <!-- Reason selector (Required if diff != 0) -->
                        <div id="reason-box-{{ $item->product_id }}" class="{{ abs($diff) > 0.0001 ? '' : 'hidden' }} pt-2 border-t border-slate-200 grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Alasan Selisih (Wajib)</label>
                                <select name="items[{{ $index }}][reason]" {{ $stocktake->status === 'confirmed' ? 'disabled' : '' }}
                                    class="w-full text-xs font-semibold bg-white border border-slate-300 rounded-lg p-1.5">
                                    <option value="" disabled {{ empty($item->reason) ? 'selected' : '' }}>-- Pilih Alasan --</option>
                                    <option value="lost" {{ $item->reason === 'lost' ? 'selected' : '' }}>Hilang</option>
                                    <option value="damaged" {{ $item->reason === 'damaged' ? 'selected' : '' }}>Rusak</option>
                                    <option value="input_error" {{ $item->reason === 'input_error' ? 'selected' : '' }}>Salah Catat</option>
                                    <option value="expired" {{ $item->reason === 'expired' ? 'selected' : '' }}>Kedaluwarsa</option>
                                    <option value="other" {{ $item->reason === 'other' ? 'selected' : '' }}>Lainnya</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Catatan Tambahan</label>
                                <input type="text" name="items[{{ $index }}][note]" value="{{ $item->note }}" {{ $stocktake->status === 'confirmed' ? 'disabled' : '' }}
                                    placeholder="Opsional..." class="w-full text-xs bg-white border border-slate-300 rounded-lg p-1.5">
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @if($stocktake->status === 'draft')
            <div class="flex space-x-2">
                <button type="submit" class="flex-1 py-3 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs rounded-xl border border-slate-200 transition-colors">
                    Simpan Draft Sementara
                </button>

                @if($isOwner)
                    <button type="button" onclick="confirmOpname()" class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-heading font-black text-xs rounded-xl shadow-md transition-colors">
                        KONFIRMASI OPNAME
                    </button>
                @endif
            </div>
        @endif
    </form>
</div>

<!-- Hidden Form for Owner Confirmation (OPN-03) -->
@if($isOwner && $stocktake->status === 'draft')
    <form id="confirm-opname-form" method="POST" action="{{ route('stocktake.confirm', $stocktake->id) }}" class="hidden">
        @csrf
        @foreach($stocktake->items as $item)
            <input type="hidden" name="expected_system_qty[{{ $item->product_id }}]" value="{{ $item->current_live_stock }}">
        @endforeach
    </form>
@endif

@push('scripts')
<script>
    function toggleBlindMode(isBlind) {
        document.querySelectorAll('.system-col').forEach(el => {
            el.classList.toggle('blur-xs', isBlind);
        });
    }

    function calculateDiff(productId, physicalVal, systemVal) {
        const phys = parseFloat(physicalVal) || 0;
        const diff = Math.round((phys - systemVal) * 1000) / 1000;

        const diffEl = document.getElementById(`diff-val-${productId}`);
        const reasonBox = document.getElementById(`reason-box-${productId}`);

        if (Math.abs(diff) <= 0.0001) {
            diffEl.innerHTML = '<span class="text-emerald-600 font-bold">Cocok</span>';
            reasonBox.classList.add('hidden');
        } else if (diff < 0) {
            diffEl.innerHTML = `<span class="text-rose-600 font-bold">${diff}</span>`;
            reasonBox.classList.remove('hidden');
        } else {
            diffEl.innerHTML = `<span class="text-blue-600 font-bold">+${diff}</span>`;
            reasonBox.classList.remove('hidden');
        }
    }

    function confirmOpname() {
        if (confirm('Konfirmasi stok opname sekarang? Stok sistem akan diperbarui menyesuaikan jumlah fisik yang dihitung.')) {
            // Submit the draft form first then confirmation or direct confirm
            document.getElementById('confirm-opname-form').submit();
        }
    }
</script>
@endpush
@endsection
