<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#059669">
    <link rel="manifest" href="/manifest.json">
    <title>@yield('title', 'SIMBAKO') - Sistem Manajemen Stok Warung</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
        // Register Service Worker for PWA
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(() => {});
            });
        }
    </script>
</head>
<body class="h-full flex flex-col font-sans text-slate-800 antialiased selection:bg-emerald-500 selection:text-white">

    <!-- Offline Alert Banner (SRS 6.2) -->
    <div id="offline-banner" class="hidden bg-amber-600 text-white text-xs font-semibold px-4 py-2 text-center sticky top-0 z-50 shadow-md">
        ⚠️ Tidak ada koneksi internet. Transaksi belum dapat disimpan ke server.
    </div>

    <!-- Top Navigation Header -->
    <header class="bg-slate-900 text-white border-b border-slate-800 sticky top-0 z-40 shadow-sm">
        <div class="max-w-2xl mx-auto px-4 h-14 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <a href="{{ route('sales.index') }}" class="flex items-center space-x-2">
                    <span class="w-8 h-8 rounded-lg bg-emerald-600 text-white font-black text-lg flex items-center justify-center shadow-inner">S</span>
                    <span class="font-heading font-bold text-lg tracking-tight text-white">SIMBAKO</span>
                </a>
                <span class="text-[10px] tracking-wider uppercase px-2 py-0.5 rounded font-bold {{ auth()->user()->isOwner() ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 'bg-blue-950 text-blue-400 border border-blue-800' }}">
                    {{ auth()->user()->isOwner() ? 'Pemilik' : 'Pegawai' }}
                </span>
            </div>

            <div class="flex items-center space-x-3 text-xs">
                <span class="text-slate-300 font-medium hidden sm:inline">{{ auth()->user()->name }}</span>
                <button type="button" onclick="openMenuModal()" class="touch-target p-2 text-slate-300 hover:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500" title="Menu Lengkap" aria-label="Menu Lengkap">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
            </div>
        </div>
    </header>

    <!-- Global Toast Alert Container -->
    <div id="toast-container" class="fixed top-16 right-4 left-4 sm:left-auto sm:w-96 z-50 pointer-events-none flex flex-col space-y-2"></div>

    <!-- Flash Messages -->
    <div class="max-w-2xl mx-auto px-4 w-full mt-3">
        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl flex items-center justify-between shadow-sm animate-fade-in" role="alert">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    <span class="text-sm font-medium">{{ session('success') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 text-sm">✕</button>
            </div>
        @endif

        @if($errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl shadow-sm" role="alert">
                <div class="flex items-start space-x-2">
                    <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                    <div class="text-sm">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Main Content Area -->
    <main class="flex-1 max-w-2xl mx-auto w-full px-4 pt-2 pb-24">
        @yield('content')
    </main>

    <!-- Bottom Navigation Bar (Thumb Friendly 48px+ touch targets) -->
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-slate-200 z-40 shadow-lg">
        <div class="max-w-2xl mx-auto grid {{ auth()->user()->isOwner() ? 'grid-cols-5' : 'grid-cols-4' }} h-16">
            <!-- 1. Jual -->
            <a href="{{ route('sales.index') }}" class="touch-target flex flex-col items-center justify-center text-xs font-semibold transition-colors {{ request()->routeIs('sales.*') ? 'text-emerald-700 bg-emerald-50/50' : 'text-slate-500 hover:text-slate-900' }}">
                <svg class="w-6 h-6 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                <span>Jual</span>
            </a>

            <!-- 2. Barang Masuk (Owner Only) -->
            @if(auth()->user()->isOwner())
            <a href="{{ route('purchases.create') }}" class="touch-target flex flex-col items-center justify-center text-xs font-semibold transition-colors {{ request()->routeIs('purchases.*') ? 'text-emerald-700 bg-emerald-50/50' : 'text-slate-500 hover:text-slate-900' }}">
                <svg class="w-6 h-6 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                <span>Masuk</span>
            </a>
            @endif

            <!-- 3. Peringatan -->
            <a href="{{ route('dashboard.index') }}" class="touch-target flex flex-col items-center justify-center text-xs font-semibold relative transition-colors {{ request()->routeIs('dashboard.*') ? 'text-emerald-700 bg-emerald-50/50' : 'text-slate-500 hover:text-slate-900' }}">
                <svg class="w-6 h-6 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <span>Peringatan</span>
            </a>

            <!-- 4. Opname -->
            <a href="{{ route('stocktake.index') }}" class="touch-target flex flex-col items-center justify-center text-xs font-semibold transition-colors {{ request()->routeIs('stocktake.*') ? 'text-emerald-700 bg-emerald-50/50' : 'text-slate-500 hover:text-slate-900' }}">
                <svg class="w-6 h-6 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                <span>Opname</span>
            </a>

            <!-- 5. Menu Lainnya -->
            <button type="button" onclick="openMenuModal()" class="touch-target flex flex-col items-center justify-center text-xs font-semibold text-slate-500 hover:text-slate-900 transition-colors">
                <svg class="w-6 h-6 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path></svg>
                <span>Lainnya</span>
            </button>
        </div>
    </nav>

    <!-- Modal / Bottom Sheet: Menu Lainnya -->
    <div id="menu-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 hidden transition-opacity flex items-end sm:items-center justify-center">
        <div class="bg-white w-full max-w-md rounded-t-2xl sm:rounded-2xl p-5 shadow-2xl max-h-[85vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center space-x-2">
                    <span class="w-7 h-7 rounded-lg bg-slate-900 text-white font-bold text-sm flex items-center justify-center">S</span>
                    <span class="font-heading font-bold text-slate-900 text-base">Menu SIMBAKO</span>
                </div>
                <button type="button" onclick="closeMenuModal()" class="touch-target p-2 text-slate-400 hover:text-slate-600 rounded-lg text-lg">✕</button>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                <!-- Master Produk -->
                <a href="{{ route('products.index') }}" class="p-3.5 bg-slate-50 hover:bg-emerald-50 hover:border-emerald-200 border border-slate-200 rounded-xl flex flex-col items-start transition-all">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center mb-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </div>
                    <span class="font-bold text-slate-800">Master Produk</span>
                    <span class="text-xs text-slate-500">Katalog & harga jual</span>
                </a>

                <!-- Riwayat Penjualan -->
                <a href="{{ route('sales.history') }}" class="p-3.5 bg-slate-50 hover:bg-emerald-50 hover:border-emerald-200 border border-slate-200 rounded-xl flex flex-col items-start transition-all">
                    <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center mb-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <span class="font-bold text-slate-800">Riwayat Jual</span>
                    <span class="text-xs text-slate-500">Cek nota & void</span>
                </a>

                @if(auth()->user()->isOwner())
                <!-- Kas & Tutup Toko -->
                <a href="{{ route('cash.index') }}" class="p-3.5 bg-slate-50 hover:bg-emerald-50 hover:border-emerald-200 border border-slate-200 rounded-xl flex flex-col items-start transition-all">
                    <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center mb-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                    <span class="font-bold text-slate-800">Kas & Tutup Toko</span>
                    <span class="text-xs text-slate-500">Rekonsiliasi harian</span>
                </a>

                <!-- Catat Pengeluaran -->
                <a href="{{ route('expenses.index') }}" class="p-3.5 bg-slate-50 hover:bg-emerald-50 hover:border-emerald-200 border border-slate-200 rounded-xl flex flex-col items-start transition-all">
                    <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center mb-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <span class="font-bold text-slate-800">Pengeluaran Kas</span>
                    <span class="text-xs text-slate-500">Belanja pasar / operasional</span>
                </a>

                <!-- Riwayat Pembelian -->
                <a href="{{ route('purchases.history') }}" class="p-3.5 bg-slate-50 hover:bg-emerald-50 hover:border-emerald-200 border border-slate-200 rounded-xl flex flex-col items-start transition-all">
                    <div class="w-8 h-8 rounded-lg bg-teal-100 text-teal-700 flex items-center justify-center mb-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <span class="font-bold text-slate-800">Riwayat Pembelian</span>
                    <span class="text-xs text-slate-500">Faktur masuk & supplier</span>
                </a>

                <!-- Saldo Awal (Titik Nol) -->
                <a href="{{ route('stocktake.opening') }}" class="p-3.5 bg-slate-50 hover:bg-emerald-50 hover:border-emerald-200 border border-slate-200 rounded-xl flex flex-col items-start transition-all">
                    <div class="w-8 h-8 rounded-lg bg-cyan-100 text-cyan-700 flex items-center justify-center mb-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                    <span class="font-bold text-slate-800">Saldo Awal</span>
                    <span class="text-xs text-slate-500">Titik nol produk baru</span>
                </a>

                <!-- Ekspor Data CSV -->
                <a href="{{ route('export.index') }}" class="p-3.5 bg-slate-50 hover:bg-emerald-50 hover:border-emerald-200 border border-slate-200 rounded-xl flex flex-col items-start transition-all">
                    <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center mb-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    </div>
                    <span class="font-bold text-slate-800">Ekspor Data</span>
                    <span class="text-xs text-slate-500">Download Excel / CSV</span>
                </a>

                <!-- Pengaturan -->
                <a href="{{ route('settings.index') }}" class="p-3.5 bg-slate-50 hover:bg-emerald-50 hover:border-emerald-200 border border-slate-200 rounded-xl flex flex-col items-start transition-all">
                    <div class="w-8 h-8 rounded-lg bg-slate-200 text-slate-700 flex items-center justify-center mb-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </div>
                    <span class="font-bold text-slate-800">Pengaturan</span>
                    <span class="text-xs text-slate-500">Pegawai & ambang batas</span>
                </a>
                @endif
            </div>

            <!-- Logout -->
            <form action="{{ route('logout') }}" method="POST" class="mt-4 pt-3 border-t border-slate-100">
                @csrf
                <button type="submit" class="w-full touch-target py-3 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-sm flex items-center justify-center space-x-2 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    <span>Keluar dari Aplikasi</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Scripts for UI & PWA -->
    <script>
        function openMenuModal() {
            document.getElementById('menu-modal').classList.remove('hidden');
        }
        function closeMenuModal() {
            document.getElementById('menu-modal').classList.add('hidden');
        }

        // Global Toast Notification
        window.showToast = function(msg, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `p-3.5 rounded-xl shadow-lg text-sm font-semibold flex items-center justify-between text-white pointer-events-auto transition-all transform duration-200 ${
                type === 'success' ? 'bg-emerald-600' : (type === 'warning' ? 'bg-amber-600' : 'bg-rose-600')
            }`;
            toast.innerHTML = `
                <span>${msg}</span>
                <button type="button" class="ml-3 text-white/80 hover:text-white" onclick="this.parentElement.remove()">✕</button>
            `;
            container.appendChild(toast);
            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-y-2');
                setTimeout(() => toast.remove(), 250);
            }, 3500);
        };

        // Offline / Online detection (SRS 6.2)
        window.addEventListener('online', () => {
            document.getElementById('offline-banner').classList.add('hidden');
            window.showToast('Koneksi internet kembali online!', 'success');
        });
        window.addEventListener('offline', () => {
            document.getElementById('offline-banner').classList.remove('hidden');
            window.showToast('Koneksi terputus! Mode offline aktif.', 'warning');
        });
        if (!navigator.onLine) {
            document.getElementById('offline-banner').classList.remove('hidden');
        }
    </script>

    @stack('scripts')
</body>
</html>
