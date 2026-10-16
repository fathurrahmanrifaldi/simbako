@extends('layouts.app')

@section('title', 'Kasir Penjualan Cepat')

@section('content')
<div class="space-y-3 pb-40" id="sales-app">

    <!-- Search Input & Quick Actions Bar -->
    <div class="relative">
        <input type="text" id="search-input" placeholder="🔍 Cari nama barang sembako..."
            class="w-full touch-target pl-10 pr-10 bg-white border border-slate-300 rounded-2xl text-sm font-semibold shadow-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 placeholder-slate-400"
            oninput="handleSearch(this.value)">
        <button type="button" id="clear-search-btn" onclick="clearSearch()" class="hidden absolute right-3 top-3 text-slate-400 hover:text-slate-600 text-sm">✕</button>
    </div>

    <!-- Search Results Grid (Visible when searching) -->
    <div id="search-results-container" class="hidden bg-white border border-slate-200 rounded-2xl p-3 shadow-md max-h-72 overflow-y-auto space-y-2">
        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider px-1">Hasil Pencarian</div>
        <div id="search-results-list" class="divide-y divide-slate-100"></div>
    </div>

    <!-- 3-Column Favorites Grid (PRO-04, JUL-01: Alphabetical Order, Consistent Touch Target) -->
    <div id="favorites-section">
        <div class="flex items-center justify-between mb-2 px-1">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center space-x-1">
                <span>⭐ Produk Favorit Cepat (Maks 24)</span>
            </span>
            <span class="text-[11px] text-slate-400">Ketuk untuk +1</span>
        </div>

        @if($favorites->isEmpty())
            <div class="bg-white border border-dashed border-slate-300 rounded-2xl p-8 text-center">
                <span class="text-3xl mb-2 block">🏪</span>
                <p class="font-bold text-slate-800 text-sm">Belum ada produk favorit</p>
                <p class="text-xs text-slate-500 mt-1">Tandai bintang ⭐ pada produk di menu Master Produk agar tampil di sini.</p>
                @if(auth()->user()->isOwner())
                    <a href="{{ route('products.index') }}" class="mt-3 inline-block px-4 py-2 bg-emerald-600 text-white font-bold text-xs rounded-xl shadow-xs">Buka Master Produk</a>
                @endif
            </div>
        @else
            <div class="grid grid-cols-3 gap-2.5">
                @foreach($favorites as $fav)
                    <button type="button"
                        onclick="addToCart('{{ $fav->id }}', '{{ addslashes($fav->name) }}', {{ $fav->sell_price }}, '{{ $fav->base_unit }}')"
                        class="touch-target bg-white hover:bg-emerald-50 active:scale-95 border border-slate-200 hover:border-emerald-500 p-2.5 rounded-2xl text-left flex flex-col justify-between shadow-xs transition-all cursor-pointer relative overflow-hidden group">
                        <div>
                            <div class="text-xs font-bold text-slate-800 line-clamp-2 leading-tight group-hover:text-emerald-900">
                                {{ $fav->name }}
                            </div>
                            <span class="text-[10px] text-slate-400 font-semibold uppercase">{{ $fav->base_unit }}</span>
                        </div>
                        <div class="mt-2 flex items-baseline justify-between">
                            <span class="font-mono text-xs font-black text-emerald-700">Rp {{ number_format($fav->sell_price, 0, ',', '.') }}</span>
                            <span id="fav-badge-{{ $fav->id }}" class="hidden text-[10px] font-black bg-emerald-600 text-white rounded-full w-5 h-5 flex items-center justify-center shrink-0 shadow-xs">0</span>
                        </div>
                    </button>
                @endforeach
            </div>
        @endif
    </div>
    <div class="h-16"></div>

    <!-- Floating / Sticky Cart Bottom Drawer & Checkout Bar -->
    <div id="cart-drawer-backdrop" onclick="toggleCartDrawer(false)" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-40 transition-opacity"></div>

    <!-- Cart Panel -->
    <div id="cart-drawer" class="fixed bottom-16 left-0 right-0 max-w-2xl mx-auto bg-white border-t border-slate-200 z-40 shadow-2xl transition-transform duration-200 translate-y-full rounded-t-3xl">
        <div class="p-4 max-h-[60vh] flex flex-col">
            <!-- Drawer Header -->
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center space-x-2">
                    <span class="font-heading font-bold text-slate-900 text-base">Keranjang Belanja</span>
                    <span id="cart-item-count" class="bg-emerald-100 text-emerald-800 text-xs font-black px-2 py-0.5 rounded-full">0 item</span>
                </div>
                <div class="flex items-center space-x-2">
                    <button type="button" onclick="clearCart()" class="text-xs font-bold text-rose-600 hover:text-rose-800 px-2 py-1 rounded-lg">Kosongkan</button>
                    <button type="button" onclick="toggleCartDrawer(false)" class="p-1 text-slate-400 hover:text-slate-600 text-lg">✕</button>
                </div>
            </div>

            <!-- Cart Items List -->
            <div id="cart-items-list" class="flex-1 overflow-y-auto divide-y divide-slate-100 my-2 pr-1 space-y-1">
                <!-- Injected via JS -->
            </div>
        </div>
    </div>

    <!-- Persistent Bottom Bar: Total & Quick Pay Button (JUL-01: 2 Taps to Finish) -->
    <div id="checkout-bar" class="fixed bottom-16 left-0 right-0 max-w-2xl mx-auto p-3 bg-white/95 backdrop-blur-md border-t border-slate-200 z-30 shadow-lg">
        <div class="flex items-center justify-between space-x-3">
            <button type="button" onclick="toggleCartDrawer()" class="flex flex-col text-left cursor-pointer group">
                <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400 group-hover:text-emerald-600 flex items-center space-x-1">
                    <span>Lihat Rincian</span>
                    <span id="cart-badge-count" class="bg-slate-200 text-slate-800 px-1.5 py-0.2 rounded-full font-bold">0</span>
                    <span>▲</span>
                </span>
                <span id="cart-total-display" class="font-heading text-lg font-black text-slate-900 tracking-tight">Rp 0</span>
            </button>

            <button type="button" id="pay-button" onclick="processCheckout()" disabled
                class="touch-target flex-1 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 disabled:bg-slate-300 disabled:cursor-not-allowed text-white font-heading font-black text-base px-6 rounded-2xl shadow-md transition-all flex items-center justify-center space-x-2">
                <span>BAYAR TUNAI</span>
                <span id="btn-total-preview" class="text-emerald-200 font-mono text-sm font-normal"></span>
            </button>
        </div>
    </div>

</div>

<!-- Price Override Modal (JUL-05: Tawar-menawar) -->
<div id="override-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-5 w-full max-w-xs shadow-2xl">
        <h3 class="font-heading font-bold text-slate-900 text-sm mb-1" id="override-item-name">Ubah Harga Jual</h3>
        <p class="text-xs text-slate-500 mb-3">Tawar-menawar per transaksi (tidak mengubah harga master).</p>

        <div class="space-y-3">
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Harga Kesepakatan (Rp)</label>
                <input type="number" id="override-price-input" inputmode="numeric" class="w-full touch-target px-3 bg-slate-50 border border-slate-300 rounded-xl font-mono text-base font-bold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
            <div class="flex space-x-2 pt-2">
                <button type="button" onclick="closeOverrideModal()" class="flex-1 py-2.5 rounded-xl border border-slate-300 text-slate-600 font-bold text-xs">Batal</button>
                <button type="button" onclick="savePriceOverride()" class="flex-1 py-2.5 rounded-xl bg-emerald-600 text-white font-bold text-xs">Terapkan</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // All products cache for instant offline-capable search
    const allProducts = @json($allProducts);

    // Cart State (JUL-08: Stored in localStorage)
    let cart = [];
    let overrideTargetIndex = null;
    let isDrawerOpen = false;

    // Load Cart from localStorage on boot
    try {
        const saved = localStorage.getItem('simbako_cart');
        if (saved) {
            cart = JSON.parse(saved);
        }
    } catch(e) {}

    renderCart();

    function saveCart() {
        try {
            localStorage.setItem('simbako_cart', JSON.stringify(cart));
        } catch(e) {}
        renderCart();
    }

    function addToCart(productId, name, sellPrice, baseUnit) {
        const existing = cart.find(i => i.product_id === productId);
        if (existing) {
            existing.qty = Math.round((existing.qty + 1) * 1000) / 1000;
        } else {
            cart.push({
                product_id: productId,
                name: name,
                unit_price: sellPrice,
                original_price: sellPrice,
                qty: 1,
                base_unit: baseUnit,
                price_overridden: false,
            });
        }
        playBeep();
        saveCart();
    }

    function updateQty(index, newQty) {
        newQty = parseFloat(newQty);
        if (isNaN(newQty) || newQty <= 0) {
            cart.splice(index, 1);
        } else {
            cart[index].qty = Math.round(newQty * 1000) / 1000;
        }
        saveCart();
    }

    function openOverrideModal(index) {
        overrideTargetIndex = index;
        const item = cart[index];
        document.getElementById('override-item-name').innerText = `Ubah Harga: ${item.name}`;
        document.getElementById('override-price-input').value = item.unit_price;
        document.getElementById('override-modal').classList.remove('hidden');
    }

    function closeOverrideModal() {
        document.getElementById('override-modal').classList.add('hidden');
        overrideTargetIndex = null;
    }

    function savePriceOverride() {
        if (overrideTargetIndex !== null) {
            const val = parseInt(document.getElementById('override-price-input').value, 10);
            if (!isNaN(val) && val >= 0) {
                cart[overrideTargetIndex].unit_price = val;
                cart[overrideTargetIndex].price_overridden = (val !== cart[overrideTargetIndex].original_price);
                saveCart();
            }
        }
        closeOverrideModal();
    }

    function clearCart() {
        cart = [];
        saveCart();
        toggleCartDrawer(false);
    }

    function renderCart() {
        const list = document.getElementById('cart-items-list');
        const countSpan = document.getElementById('cart-item-count');
        const badgeCount = document.getElementById('cart-badge-count');
        const totalDisplay = document.getElementById('cart-total-display');
        const payBtn = document.getElementById('pay-button');
        const btnTotalPreview = document.getElementById('btn-total-preview');

        // Reset badge numbers on favorite buttons
        document.querySelectorAll('[id^="fav-badge-"]').forEach(b => {
            b.classList.add('hidden');
            b.innerText = '0';
        });

        let grandTotal = 0;
        let totalItems = 0;

        list.innerHTML = '';

        if (cart.length === 0) {
            list.innerHTML = '<div class="py-6 text-center text-xs text-slate-400 font-medium">Keranjang masih kosong.<br>Ketuk produk di atas untuk menambahkan.</div>';
            payBtn.disabled = true;
            btnTotalPreview.innerText = '';
        } else {
            payBtn.disabled = false;

            cart.forEach((item, index) => {
                const lineTotal = Math.round(item.qty * item.unit_price);
                grandTotal += lineTotal;
                totalItems += 1;

                // Update badge on favorites grid
                const favBadge = document.getElementById(`fav-badge-${item.product_id}`);
                if (favBadge) {
                    favBadge.classList.remove('hidden');
                    favBadge.innerText = item.qty;
                }

                const row = document.createElement('div');
                row.className = 'py-2 flex items-center justify-between space-x-2 text-sm';
                row.innerHTML = `
                    <div class="flex-1 min-w-0">
                        <div class="font-bold text-slate-900 truncate">${item.name}</div>
                        <div class="flex items-center space-x-2 mt-0.5">
                            <button type="button" onclick="openOverrideModal(${index})" class="text-xs font-mono font-bold ${item.price_overridden ? 'text-amber-600 bg-amber-50 px-1 rounded border border-amber-200' : 'text-slate-500 hover:text-emerald-700'}">
                                Rp ${item.unit_price.toLocaleString('id-ID')} / ${item.base_unit} ${item.price_overridden ? '✏️ (Tawar)' : ''}
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center space-x-1.5 shrink-0">
                        <button type="button" onclick="updateQty(${index}, ${item.qty - 1})" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-black text-sm flex items-center justify-center">-</button>
                        <input type="number" step="any" inputmode="decimal" value="${item.qty}" onchange="updateQty(${index}, this.value)"
                            class="w-14 h-8 text-center font-bold text-xs bg-slate-50 border border-slate-200 rounded-lg">
                        <button type="button" onclick="updateQty(${index}, ${item.qty + 1})" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-black text-sm flex items-center justify-center">+</button>
                    </div>
                    <div class="text-right shrink-0 w-20">
                        <div class="font-mono font-bold text-slate-900 text-xs">Rp ${lineTotal.toLocaleString('id-ID')}</div>
                    </div>
                `;
                list.appendChild(row);
            });

            btnTotalPreview.innerText = `(Rp ${grandTotal.toLocaleString('id-ID')})`;
        }

        countSpan.innerText = `${totalItems} item`;
        badgeCount.innerText = totalItems;
        totalDisplay.innerText = `Rp ${grandTotal.toLocaleString('id-ID')}`;
    }

    function toggleCartDrawer(forceState) {
        isDrawerOpen = typeof forceState === 'boolean' ? forceState : !isDrawerOpen;
        const drawer = document.getElementById('cart-drawer');
        const backdrop = document.getElementById('cart-drawer-backdrop');

        if (isDrawerOpen) {
            drawer.classList.remove('translate-y-full');
            backdrop.classList.remove('hidden');
        } else {
            drawer.classList.add('translate-y-full');
            backdrop.classList.add('hidden');
        }
    }

    // Instant Client-side Search
    function handleSearch(query) {
        const container = document.getElementById('search-results-container');
        const list = document.getElementById('search-results-list');
        const clearBtn = document.getElementById('clear-search-btn');

        if (!query || query.trim().length === 0) {
            container.classList.add('hidden');
            clearBtn.classList.add('hidden');
            return;
        }

        clearBtn.classList.remove('hidden');
        const q = query.toLowerCase();
        const matches = allProducts.filter(p => p.name.toLowerCase().includes(q));

        list.innerHTML = '';
        if (matches.length === 0) {
            list.innerHTML = '<div class="py-4 text-center text-xs text-slate-400">Tidak ada produk yang cocok.</div>';
        } else {
            matches.slice(0, 10).forEach(p => {
                const item = document.createElement('div');
                item.className = 'py-2 flex items-center justify-between hover:bg-slate-50 cursor-pointer px-1 rounded-lg';
                item.onclick = () => {
                    addToCart(p.id, p.name, p.sell_price, p.base_unit);
                    clearSearch();
                };
                item.innerHTML = `
                    <div>
                        <div class="text-xs font-bold text-slate-900">${p.name}</div>
                        <span class="text-[10px] text-slate-400">${p.base_unit}</span>
                    </div>
                    <div class="text-right">
                        <span class="font-mono text-xs font-bold text-emerald-700">Rp ${p.sell_price.toLocaleString('id-ID')}</span>
                        <span class="ml-2 text-xs text-emerald-600 font-bold">+ Tambah</span>
                    </div>
                `;
                list.appendChild(item);
            });
        }
        container.classList.remove('hidden');
    }

    function clearSearch() {
        document.getElementById('search-input').value = '';
        document.getElementById('search-results-container').classList.add('hidden');
        document.getElementById('clear-search-btn').classList.add('hidden');
    }

    // Checkout execution (JUL-02, JUL-03, JUL-04)
    async function processCheckout() {
        if (cart.length === 0) return;

        const payBtn = document.getElementById('pay-button');
        payBtn.disabled = true;
        payBtn.innerHTML = '<span>Memproses...</span>';

        // Safe Client UUID for Idempotency
        const saleId = crypto.randomUUID();

        const payload = {
            id: saleId,
            items: cart.map(i => ({
                product_id: i.product_id,
                qty: i.qty,
                unit_price: i.unit_price,
                price_overridden: i.price_overridden,
            })),
        };

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const res = await fetch('/api/sales', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            });

            const data = await res.json();

            if (res.ok) {
                // Success! (SRS 6.2: Toast singkat, keranjang kosong, layar siap untuk berikutnya)
                window.showToast('✅ Transaksi Sukses! ' + (data.message || ''), 'success');

                // Warning alert if stock went negative (JUL-04)
                if (data.warnings && data.warnings.length > 0) {
                    data.warnings.forEach(w => window.showToast('⚠️ ' + w, 'warning'));
                }

                clearCart();
            } else {
                window.showToast('Gagal: ' + (data.message || 'Terjadi kesalahan.'), 'error');
                payBtn.disabled = false;
                renderCart();
            }
        } catch(err) {
            window.showToast('Gagal mengirim transaksi: ' + err.message, 'error');
            payBtn.disabled = false;
            renderCart();
        }
    }

    function playBeep() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.value = 880; // A5 tone
            gain.gain.setValueAtTime(0.08, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.08);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.08);
        } catch(e) {}
    }
</script>
@endpush
