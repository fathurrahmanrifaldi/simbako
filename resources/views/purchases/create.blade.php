@extends('layouts.app')

@section('title', 'Catat Barang Masuk (Pembelian)')

@section('content')
<div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-heading font-black text-xl text-slate-900">Catat Barang Masuk</h1>
            <p class="text-xs text-slate-500">Input faktur pembelian dari supplier / pasar.</p>
        </div>
        <a href="{{ route('purchases.history') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl border border-slate-200">
            Riwayat Masuk
        </a>
    </div>

    <form id="purchase-form" method="POST" action="{{ route('purchases.store') }}" onsubmit="disableSubmitBtn()" class="space-y-4">
        @csrf
        <input type="hidden" name="id" id="client-purchase-id">

        <!-- Info Faktur -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs space-y-3">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="purchased_on" class="block text-xs font-bold text-slate-700 mb-1">Tanggal Beli</label>
                    <input type="date" id="purchased_on" name="purchased_on" max="{{ now()->format('Y-m-d') }}" value="{{ old('purchased_on', now()->format('Y-m-d')) }}" required
                        class="w-full text-xs font-semibold bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-800 focus:bg-white focus:ring-2 focus:ring-emerald-500">
                </div>

                <div>
                    <label for="supplier" class="block text-xs font-bold text-slate-700 mb-1">Nama Supplier (Opsional)</label>
                    <input type="text" id="supplier" name="supplier" value="{{ old('supplier') }}" placeholder="Contoh: Toko Barokah, Pasar Induk"
                        class="w-full text-xs font-semibold bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-800 focus:bg-white focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <!-- Opsi Bayar dari Uang Kas Toko -->
            <div class="pt-2 border-t border-slate-100 flex items-center space-x-2">
                <input type="checkbox" id="paid_from_cash" name="paid_from_cash" value="1" class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-slate-300">
                <label for="paid_from_cash" class="text-xs font-bold text-slate-700 cursor-pointer">
                    Dibayar dari uang kas/laci toko (mengurangi kas fisik hari ini)
                </label>
            </div>
        </div>

        <!-- Daftar Barang Masuk -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <span class="font-heading font-bold text-xs uppercase tracking-wider text-slate-600">Daftar Item Barang</span>
                <button type="button" onclick="addItemRow()" class="px-3 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-bold rounded-lg border border-emerald-200">
                    + Tambah Baris
                </button>
            </div>

            <div id="items-container" class="space-y-3">
                <!-- Row items injected via JS -->
            </div>

            <!-- Total Preview -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-sm">
                <span class="font-bold text-slate-600">Total Pembelian:</span>
                <span id="grand-total-display" class="font-heading font-black text-emerald-700 text-base">Rp 0</span>
            </div>
        </div>

        <button type="submit" id="submit-btn" class="w-full touch-target bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-heading font-black text-sm py-3.5 rounded-2xl shadow-md transition-all flex items-center justify-center space-x-2">
            <span>SIMPAN BARANG MASUK</span>
        </button>
    </form>
</div>

@push('scripts')
<script>
    const products = @json($products);
    let rowCount = 0;

    // Set idempotency UUID on load
    document.getElementById('client-purchase-id').value = crypto.randomUUID();

    function addItemRow() {
        const container = document.getElementById('items-container');
        const id = rowCount++;

        const row = document.createElement('div');
        row.id = `row-${id}`;
        row.className = 'p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2 relative';

        let options = '<option value="" disabled selected>-- Pilih Produk --</option>';
        products.forEach(p => {
            options += `<option value="${p.id}" data-has-exp="${p.has_expiry ? '1' : '0'}" data-unit="${p.base_unit}" data-last-buy="${p.last_buy_price || 0}">${p.name} (${p.base_unit})</option>`;
        });

        row.innerHTML = `
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-400">Item #${id + 1}</span>
                <button type="button" onclick="removeRow(${id})" class="text-rose-500 hover:text-rose-700 text-xs font-bold">Hapus</button>
            </div>

            <div>
                <select name="items[${id}][product_id]" required onchange="onProductSelect(${id}, this)"
                    class="w-full text-xs font-bold bg-white border border-slate-300 rounded-xl p-2.5 text-slate-800 focus:ring-2 focus:ring-emerald-500">
                    ${options}
                </select>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Jumlah (Qty)</label>
                    <input type="number" step="any" min="0.001" name="items[${id}][qty]" required placeholder="0" oninput="calculateTotal()"
                        class="w-full text-xs font-bold bg-white border border-slate-300 rounded-xl p-2 text-slate-800 focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Harga Beli Satuan (Rp)</label>
                    <input type="number" id="price-input-${id}" min="0" name="items[${id}][unit_price]" required placeholder="0" oninput="calculateTotal()"
                        class="w-full text-xs font-bold bg-white border border-slate-300 rounded-xl p-2 text-slate-800 font-mono focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <!-- Expiry date if product has_expiry -->
            <div id="exp-container-${id}" class="hidden pt-1 border-t border-slate-200">
                <label class="block text-[10px] font-bold text-amber-700 mb-0.5">Tanggal Kedaluwarsa (Wajib)</label>
                <input type="date" id="exp-input-${id}" name="items[${id}][expiry_date]" min="{{ now()->format('Y-m-d') }}"
                    class="w-full text-xs font-semibold bg-white border border-amber-300 rounded-xl p-2 text-slate-800 focus:ring-2 focus:ring-amber-500">
            </div>
        `;

        container.appendChild(row);
    }

    function removeRow(id) {
        const el = document.getElementById(`row-${id}`);
        if (el) {
            el.remove();
            calculateTotal();
        }
    }

    function onProductSelect(id, select) {
        const option = select.options[select.selectedIndex];
        const hasExp = option.getAttribute('data-has-exp') === '1';
        const lastBuy = option.getAttribute('data-last-buy') || 0;

        const expContainer = document.getElementById(`exp-container-${id}`);
        const expInput = document.getElementById(`exp-input-${id}`);
        const priceInput = document.getElementById(`price-input-${id}`);

        if (hasExp) {
            expContainer.classList.remove('hidden');
            expInput.required = true;
        } else {
            expContainer.classList.add('hidden');
            expInput.required = false;
            expInput.value = '';
        }

        if (lastBuy > 0 && (!priceInput.value || priceInput.value == 0)) {
            priceInput.value = lastBuy;
        }
        calculateTotal();
    }

    function calculateTotal() {
        let total = 0;
        const rows = document.querySelectorAll('[id^="row-"]');
        rows.forEach(r => {
            const qtyInput = r.querySelector('input[name*="[qty]"]');
            const priceInput = r.querySelector('input[name*="[unit_price]"]');
            if (qtyInput && priceInput) {
                const q = parseFloat(qtyInput.value) || 0;
                const p = parseInt(priceInput.value, 10) || 0;
                total += Math.round(q * p);
            }
        });
        document.getElementById('grand-total-display').innerText = `Rp ${total.toLocaleString('id-ID')}`;
    }

    function disableSubmitBtn() {
        const btn = document.getElementById('submit-btn');
        btn.disabled = true;
        btn.innerHTML = '<span>Menyimpan Pembelian...</span>';
    }

    // Add initial item row
    addItemRow();
</script>
@endpush
@endsection
