<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#059669">
    <link rel="manifest" href="/manifest.json">
    <title>Masuk - SIMBAKO</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex items-center justify-center p-4 bg-slate-900 font-sans selection:bg-emerald-500 selection:text-white">

    <div class="w-full max-w-sm bg-white rounded-3xl p-6 sm:p-8 shadow-2xl border border-slate-800">
        <!-- Logo -->
        <div class="text-center mb-6">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-emerald-600 text-white font-black text-2xl flex items-center justify-center shadow-lg shadow-emerald-600/30 mb-3">
                S
            </div>
            <h1 class="font-heading font-black text-2xl text-slate-900 tracking-tight">SIMBAKO</h1>
            <p class="text-xs text-slate-500 mt-1">Sistem Stok & Kas Warung Sembako</p>
        </div>

        @if($errors->has('login'))
            <div class="mb-5 bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold p-3.5 rounded-xl flex items-center space-x-2">
                <svg class="w-4 h-4 text-rose-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                <span>{{ $errors->first('login') }}</span>
            </div>
        @endif

        <!-- Role Tabs -->
        <div class="grid grid-cols-2 p-1 bg-slate-100 rounded-xl mb-6">
            <button type="button" id="tab-owner" onclick="switchTab('owner')" class="py-2.5 rounded-lg text-xs font-bold transition-all text-slate-900 bg-white shadow-xs">
                👤 Pemilik
            </button>
            <button type="button" id="tab-staff" onclick="switchTab('staff')" class="py-2.5 rounded-lg text-xs font-bold transition-all text-slate-500 hover:text-slate-900">
                🏷️ Pegawai
            </button>
        </div>

        <!-- Form Pemilik (Email & Password) -->
        <form id="form-owner" action="{{ route('login.submit') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="login_type" value="owner">

            <div>
                <label for="email" class="block text-xs font-bold text-slate-700 mb-1.5">Email Pemilik</label>
                <input type="email" id="email" name="email" value="{{ old('email', 'user@simbako.test') }}" required
                    class="w-full touch-target px-4 text-sm bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 font-medium"
                    placeholder="nama@email.com">
                @error('email') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="block text-xs font-bold text-slate-700 mb-1.5">Kata Sandi</label>
                <input type="password" id="password" name="password" required minlength="8"
                    class="w-full touch-target px-4 text-sm bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 font-medium"
                    placeholder="Minimal 8 karakter">
                @error('password') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="w-full touch-target mt-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-bold rounded-xl text-sm shadow-md transition-all flex items-center justify-center space-x-2">
                <span>Masuk sebagai Pemilik</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </button>
        </form>

        <!-- Form Pegawai (Pilih Nama + PIN 6 Digit) -->
        <form id="form-staff" action="{{ route('login.submit') }}" method="POST" class="space-y-4 hidden">
            @csrf
            <input type="hidden" name="login_type" value="staff">

            <div>
                <label for="staff_id" class="block text-xs font-bold text-slate-700 mb-1.5">Pilih Nama Pegawai</label>
                <select id="staff_id" name="staff_id" required
                    class="w-full touch-target px-4 text-sm bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 font-semibold text-slate-800">
                    <option value="" disabled selected>-- Pilih Nama --</option>
                    @foreach($activeStaff as $staff)
                        <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                    @endforeach
                </select>
                @error('staff_id') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="pin" class="block text-xs font-bold text-slate-700 mb-1.5">PIN (6 Digit Angka)</label>
                <input type="password" id="pin" name="pin" inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                    class="w-full touch-target text-center tracking-widest text-lg font-mono font-bold bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                    placeholder="••••••">
                @error('pin') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="w-full touch-target mt-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-bold rounded-xl text-sm shadow-md transition-all flex items-center justify-center space-x-2">
                <span>Masuk sebagai Pegawai</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </button>
        </form>

        <div class="mt-6 pt-4 border-t border-slate-100 text-center">
            <span class="text-[11px] text-slate-400 font-medium">Demo: user@simbako.test <code>password123</code> | Pegawai PIN <code>123456</code></span>
        </div>
    </div>

    <script>
        function switchTab(type) {
            const formOwner = document.getElementById('form-owner');
            const formStaff = document.getElementById('form-staff');
            const tabOwner = document.getElementById('tab-owner');
            const tabStaff = document.getElementById('tab-staff');

            if (type === 'owner') {
                formOwner.classList.remove('hidden');
                formStaff.classList.add('hidden');
                tabOwner.className = 'py-2.5 rounded-lg text-xs font-bold transition-all text-slate-900 bg-white shadow-xs';
                tabStaff.className = 'py-2.5 rounded-lg text-xs font-bold transition-all text-slate-500 hover:text-slate-900';
            } else {
                formOwner.classList.add('hidden');
                formStaff.classList.remove('hidden');
                tabStaff.className = 'py-2.5 rounded-lg text-xs font-bold transition-all text-slate-900 bg-white shadow-xs';
                tabOwner.className = 'py-2.5 rounded-lg text-xs font-bold transition-all text-slate-500 hover:text-slate-900';
            }
        }
    </script>
</body>
</html>
