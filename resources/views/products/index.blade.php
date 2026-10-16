@extends('layouts.app')

@section('title', 'Master Produk & Kategori')

@section('content')
<div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-heading font-black text-xl text-slate-900">Master Produk</h1>
            <p class="text-xs text-slate-500">Katalog barang, harga jual, dan stok minimum.</p>
        </div>
        @if($isOwner)
            <button type="button" onclick="openProductModal()" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs">
                + Tambah Produk
            </button>
        @endif
    </div>

    <!-- Search & Filter Bar -->
    <form method="GET" action="{{ route('products.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-2 bg-white p-3 rounded-2xl border border-slate-200 shadow-xs">
        <div class="sm:col-span-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="🔍 Cari nama barang..."
                class="w-full text-xs font-medium bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-800 focus:bg-white focus:ring-2 focus:ring-emerald-500">
        </div>
        <div class="flex space-x-1.5">
            <select name="category_id" onchange="this.form.submit()" class="flex-1 text-xs font-bold bg-slate-50 border border-slate-200 rounded-xl px-2 py-2 text-slate-700">
                <option value="">Semua Kategori</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category_id') === $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
            @if(request('search') || request('category_id'))
                <a href="{{ route('products.index') }}" class="px-2 py-2 text-slate-400 hover:text-slate-600 text-xs flex items-center">Reset</a>
            @endif
        </div>
    </form>

    <!-- Products List -->
    @if($products->isEmpty())
        <div class="bg-white border border-dashed border-slate-300 rounded-2xl p-8 text-center">
            <span class="text-3xl mb-2 block">🔍</span>
            <p class="font-bold text-slate-800 text-sm">Tidak ada produk ditemukan</p>
            <p class="text-xs text-slate-500 mt-1">Coba ubah kata kunci pencarian atau kategori filter.</p>
        </div>
    @else
        <div class="space-y-2.5">
            @foreach($products as $prod)
                <div class="bg-white border border-slate-200 rounded-2xl p-3.5 shadow-xs flex items-start justify-between space-x-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center space-x-2">
                            <span class="text-xs font-bold text-slate-900 truncate">{{ $prod->name }}</span>
                            @if($prod->is_favorite)
                                <span class="text-xs" title="Produk Favorit Layar Jual">⭐</span>
                            @endif
                            @if($prod->has_expiry)
                                <span class="text-[9px] font-bold bg-amber-100 text-amber-800 px-1 py-0.5 rounded">Exp</span>
                            @endif
                        </div>

                        <div class="text-[11px] text-slate-400 mt-0.5">
                            {{ $prod->category->name ?? '-' }} · Satuan: <strong class="text-slate-600 uppercase">{{ $prod->base_unit }}</strong>
                        </div>

                        <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                            <span>Jual: <strong class="font-mono text-emerald-700 font-bold">Rp {{ number_format($prod->sell_price, 0, ',', '.') }}</strong></span>
                            @if($isOwner)
                                <span>Beli akhir: <strong class="font-mono text-slate-500 font-bold">Rp {{ number_format($prod->last_buy_price, 0, ',', '.') }}</strong></span>
                            @endif
                            <span>Stok: <strong class="{{ $prod->stock_total <= 0 ? 'text-rose-600' : 'text-slate-800' }} font-bold font-mono">{{ $prod->stock_total }}</strong></span>
                            @if($prod->min_stock !== null)
                                <span class="text-slate-400">Min: {{ $prod->min_stock }}</span>
                            @endif
                        </div>
                    </div>

                    @if($isOwner)
                        <div class="flex items-center space-x-1 shrink-0 pt-0.5">
                            <button type="button" onclick='editProduct(@json($prod))' class="p-2 text-slate-400 hover:text-emerald-700 rounded-lg hover:bg-slate-50" title="Edit">
                                ✏️
                            </button>
                            <form method="POST" action="{{ route('products.deactivate', $prod->id) }}" onsubmit="return confirmDeactivate('{{ addslashes($prod->name) }}', {{ $prod->stock_total }})">
                                @csrf
                                <button type="submit" class="p-2 text-slate-400 hover:text-rose-700 rounded-lg hover:bg-slate-50" title="Nonaktifkan">
                                    🗑️
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>

<!-- Modal Tambah / Edit Produk (PRO-01) -->
@if($isOwner)
<div id="product-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-5 w-full max-w-md shadow-2xl max-h-[90vh] overflow-y-auto">
        <h3 class="font-heading font-black text-slate-900 text-base mb-1" id="modal-title">Tambah Produk Baru</h3>
        <p class="text-xs text-slate-500 mb-4">Isi rincian produk, harga jual, dan ketentuan stok.</p>

        <form id="product-form" method="POST" action="{{ route('products.store') }}" class="space-y-3">
            @csrf
            <div>
                <label for="prod_name" class="block text-xs font-bold text-slate-700 mb-1">Nama Produk (Wajib, 1-100 Karakter)</label>
                <input type="text" id="prod_name" name="name" required maxlength="100" placeholder="Contoh: Beras Ramos 5kg, Indomie Goreng"
                    class="w-full text-xs font-semibold p-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500">
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label for="prod_cat" class="block text-xs font-bold text-slate-700 mb-1">Kategori</label>
                    <select id="prod_cat" name="category_id" required class="w-full text-xs font-bold p-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="prod_unit" class="block text-xs font-bold text-slate-700 mb-1">Satuan Dasar</label>
                    <input type="text" id="prod_unit" name="base_unit" required list="unit-list" placeholder="kg, bungkus, pcs"
                        class="w-full text-xs font-bold p-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                    <datalist id="unit-list">
                        <option value="kg">
                        <option value="liter">
                        <option value="bungkus">
                        <option value="pcs">
                        <option value="botol">
                        <option value="sachet">
                        <option value="karung">
                        <option value="kaleng">
                        <option value="renteng">
                        <option value="pouch">
                    </datalist>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label for="prod_sell" class="block text-xs font-bold text-slate-700 mb-1">Harga Jual (Rp)</label>
                    <input type="number" min="0" id="prod_sell" name="sell_price" required placeholder="0"
                        class="w-full text-xs font-bold p-2.5 bg-slate-50 border border-slate-300 rounded-xl font-mono focus:bg-white focus:ring-2 focus:ring-emerald-500">
                </div>

                <div>
                    <label for="prod_min" class="block text-xs font-bold text-slate-700 mb-1">Stok Minimum (Opsional)</label>
                    <input type="number" step="any" min="0" id="prod_min" name="min_stock" placeholder="Default kategori"
                        class="w-full text-xs font-bold p-2.5 bg-slate-50 border border-slate-300 rounded-xl font-mono">
                </div>
            </div>

            <!-- Checkboxes Flags -->
            <div class="pt-2 border-t border-slate-100 space-y-2 text-xs">
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" id="prod_fav" name="is_favorite" value="1" class="w-4 h-4 rounded text-emerald-600 border-slate-300">
                    <span class="font-bold text-slate-800">⭐ Tandai sebagai Produk Favorit (Layar Jual Cepat)</span>
                </label>

                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" id="prod_exp" name="has_expiry" value="1" class="w-4 h-4 rounded text-emerald-600 border-slate-300">
                    <span class="font-bold text-slate-800">📅 Produk Memiliki Tanggal Kedaluwarsa (Wajib Exp saat beli)</span>
                </label>
            </div>

            <div class="flex space-x-2 pt-3">
                <button type="button" onclick="closeProductModal()" class="flex-1 py-2.5 rounded-xl border border-slate-300 text-slate-600 font-bold text-xs">Batal</button>
                <button type="submit" class="flex-1 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs">Simpan Produk</button>
            </div>
        </form>
    </div>
</div>
@endif

@push('scripts')
<script>
    function openProductModal() {
        document.getElementById('modal-title').innerText = 'Tambah Produk Baru';
        document.getElementById('product-form').action = "{{ route('products.store') }}";
        document.getElementById('prod_name').value = '';
        document.getElementById('prod_unit').value = 'bungkus';
        document.getElementById('prod_sell').value = '';
        document.getElementById('prod_min').value = '';
        document.getElementById('prod_fav').checked = false;
        document.getElementById('prod_exp').checked = false;
        document.getElementById('product-modal').classList.remove('hidden');
    }

    function editProduct(product) {
        document.getElementById('modal-title').innerText = `Edit: ${product.name}`;
        document.getElementById('product-form').action = `/produk/${product.id}`;
        document.getElementById('prod_name').value = product.name;
        document.getElementById('prod_cat').value = product.category_id;
        document.getElementById('prod_unit').value = product.base_unit;
        document.getElementById('prod_sell').value = product.sell_price;
        document.getElementById('prod_min').value = product.min_stock || '';
        document.getElementById('prod_fav').checked = !!product.is_favorite;
        document.getElementById('prod_exp').checked = !!product.has_expiry;
        document.getElementById('product-modal').classList.remove('hidden');
    }

    function closeProductModal() {
        document.getElementById('product-modal').classList.add('hidden');
    }

    function confirmDeactivate(name, stockTotal) {
        let msg = `Nonaktifkan produk '${name}'? Produk ini tidak akan muncul lagi di layar kasir.`;
        if (stockTotal != 0) {
            msg += `\n\nPERINGATAN: Produk ini masih memiliki stok tercatat sebanyak ${stockTotal}!`;
        }
        return confirm(msg);
    }
</script>
@endpush
@endsection
