@extends('layouts.app')

@section('title', 'Pengaturan & Pegawai')

@section('content')
<div class="space-y-4">
    <!-- Header -->
    <div>
        <h1 class="font-heading font-black text-xl text-slate-900">Pengaturan Toko</h1>
        <p class="text-xs text-slate-500">Kelola akun pegawai, reset PIN, dan batas peringatan kategori.</p>
    </div>

    <!-- Section 1: Manajemen Akun Pegawai (AUTH-05) -->
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs space-y-3">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
            <div>
                <h2 class="font-heading font-bold text-sm text-slate-900">Akun Pegawai Toko</h2>
                <p class="text-xs text-slate-500">Pegawai masuk dengan memilih nama & PIN 6 digit.</p>
            </div>
            <button type="button" onclick="openStaffModal()" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs">
                + Tambah Pegawai
            </button>
        </div>

        @if($staffUsers->isEmpty())
            <p class="text-xs text-slate-400 py-3 text-center">Belum ada pegawai terdaftar.</p>
        @else
            <div class="divide-y divide-slate-100 text-xs">
                @foreach($staffUsers as $st)
                    <div class="py-3 flex items-center justify-between">
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="font-bold text-slate-900">{{ $st->name }}</span>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded {{ $st->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">
                                    {{ $st->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>
                            <span class="text-[11px] text-slate-400">PIN: ••••••</span>
                        </div>

                        <div class="flex items-center space-x-2">
                            <button type="button" onclick="openResetPinModal('{{ $st->id }}', '{{ addslashes($st->name) }}')"
                                class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg text-xs">
                                Reset PIN
                            </button>
                            <form method="POST" action="{{ route('settings.staff.toggle', $st->id) }}">
                                @csrf
                                <button type="submit" class="px-2.5 py-1.5 {{ $st->is_active ? 'bg-rose-50 text-rose-700 hover:bg-rose-100' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }} font-bold rounded-lg text-xs">
                                    {{ $st->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Section 2: Ambang Batas Kategori (Lead Time & Warning Days) -->
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs space-y-3">
        <div class="pb-2 border-b border-slate-100">
            <h2 class="font-heading font-bold text-sm text-slate-900">Parameter Kategori Barang</h2>
            <p class="text-xs text-slate-500">Lama pengiriman supplier (lead time) dan peringatan kedaluwarsa.</p>
        </div>

        <div class="space-y-3">
            @foreach($categories as $cat)
                <form method="POST" action="{{ route('settings.category.update', $cat->id) }}" class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2.5 text-xs">
                    @csrf
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-slate-900 text-xs">{{ $cat->name }} ({{ $cat->products_count }} Produk)</span>
                        <input type="hidden" name="name" value="{{ $cat->name }}">
                        <button type="submit" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-lg text-[11px]">
                            Simpan Perubahan
                        </button>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <div>
                            <label class="block text-[10px] text-slate-500 font-bold mb-0.5">Lama Kirim (Hari)</label>
                            <input type="number" name="lead_time_days" value="{{ $cat->lead_time_days }}" required min="0"
                                class="w-full text-xs font-bold p-1.5 bg-white border border-slate-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-[10px] text-slate-500 font-bold mb-0.5">Stok Pengaman</label>
                            <input type="number" step="any" name="safety_stock" value="{{ $cat->safety_stock }}" required min="0"
                                class="w-full text-xs font-bold p-1.5 bg-white border border-slate-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-[10px] text-slate-500 font-bold mb-0.5">Peringatan Exp (Hari)</label>
                            <input type="number" name="expiry_warning_days" value="{{ $cat->expiry_warning_days }}" required min="1"
                                class="w-full text-xs font-bold p-1.5 bg-white border border-slate-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-[10px] text-slate-500 font-bold mb-0.5">Ambang Menumpuk (Hari)</label>
                            <input type="number" name="overstock_days" value="{{ $cat->overstock_days }}" required min="1"
                                class="w-full text-xs font-bold p-1.5 bg-white border border-slate-300 rounded-lg">
                        </div>
                    </div>
                </form>
            @endforeach
        </div>
    </div>
</div>

<!-- Modal Tambah Pegawai (AUTH-05) -->
<div id="staff-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-5 w-full max-w-sm shadow-2xl">
        <h3 class="font-heading font-black text-slate-900 text-base mb-1">Tambah Pegawai Baru</h3>
        <p class="text-xs text-slate-500 mb-4">Pegawai dapat mencatat kasir penjualan dan stok opname draft.</p>

        <form method="POST" action="{{ route('settings.staff.store') }}" class="space-y-3">
            @csrf
            <div>
                <label for="staff_name" class="block text-xs font-bold text-slate-700 mb-1">Nama Pegawai</label>
                <input type="text" id="staff_name" name="name" required placeholder="Contoh: Budi, Siti"
                    class="w-full text-xs font-bold p-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500">
            </div>

            <div>
                <label for="staff_pin" class="block text-xs font-bold text-slate-700 mb-1">PIN Masuk (6 Digit Angka)</label>
                <input type="password" id="staff_pin" name="pin" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="••••••"
                    class="w-full text-center text-sm font-bold font-mono tracking-widest p-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500">
            </div>

            <div class="flex space-x-2 pt-2">
                <button type="button" onclick="closeStaffModal()" class="flex-1 py-2.5 rounded-xl border border-slate-300 text-slate-600 font-bold text-xs">Batal</button>
                <button type="submit" class="flex-1 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs">Tambah</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Reset PIN -->
<div id="reset-pin-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-5 w-full max-w-sm shadow-2xl">
        <h3 class="font-heading font-black text-slate-900 text-base mb-1" id="reset-pin-title">Reset PIN Pegawai</h3>
        <p class="text-xs text-slate-500 mb-4">Masukkan 6 digit angka PIN baru untuk pegawai ini.</p>

        <form id="reset-pin-form" method="POST" action="" class="space-y-3">
            @csrf
            <div>
                <label for="new_pin" class="block text-xs font-bold text-slate-700 mb-1">PIN Baru (6 Digit)</label>
                <input type="password" id="new_pin" name="pin" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="••••••"
                    class="w-full text-center text-sm font-bold font-mono tracking-widest p-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500">
            </div>

            <div class="flex space-x-2 pt-2">
                <button type="button" onclick="closeResetPinModal()" class="flex-1 py-2.5 rounded-xl border border-slate-300 text-slate-600 font-bold text-xs">Batal</button>
                <button type="submit" class="flex-1 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-xs">Simpan PIN Baru</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openStaffModal() {
        document.getElementById('staff-modal').classList.remove('hidden');
    }
    function closeStaffModal() {
        document.getElementById('staff-modal').classList.add('hidden');
    }

    function openResetPinModal(staffId, staffName) {
        document.getElementById('reset-pin-form').action = `/pengaturan/pegawai/${staffId}/reset-pin`;
        document.getElementById('reset-pin-title').innerText = `Reset PIN: ${staffName}`;
        document.getElementById('new_pin').value = '';
        document.getElementById('reset-pin-modal').classList.remove('hidden');
    }
    function closeResetPinModal() {
        document.getElementById('reset-pin-modal').classList.add('hidden');
    }
</script>
@endpush
@endsection
