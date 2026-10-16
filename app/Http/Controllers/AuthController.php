<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('sales.index');
        }

        $activeStaff = User::where('role', 'staff')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('auth.login', compact('activeStaff'));
    }

    public function login(Request $request)
    {
        $loginType = $request->input('login_type', 'owner');

        if ($loginType === 'owner') {
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string|min:8',
            ], [
                'email.required' => 'Email wajib diisi.',
                'email.email' => 'Format email tidak valid.',
                'password.required' => 'Kata sandi wajib diisi.',
                'password.min' => 'Kata sandi minimal 8 karakter.',
            ]);

            $user = User::where('email', $validated['email'])->first();

            if (!$user || !$user->isOwner()) {
                return back()->withErrors(['login' => 'Email atau kata sandi tidak cocok.'])->withInput();
            }

            if (!$user->is_active) {
                return back()->withErrors(['login' => 'Akun Anda dinonaktifkan.'])->withInput();
            }

            // AUTH-02 / TC-17: Check lock
            if ($user->isLocked()) {
                $seconds = now()->diffInSeconds($user->locked_until);
                $minutes = ceil($seconds / 60);
                return back()->withErrors([
                    'login' => "Akun Anda terkunci sementara karena 5 kali percobaan gagal. Coba lagi dalam {$minutes} menit.",
                ])->withInput();
            }

            if (!Hash::check($validated['password'], $user->password_hash)) {
                $this->handleFailedAttempt($user);
                return back()->withErrors(['login' => 'Email atau kata sandi tidak cocok.'])->withInput();
            }

            // Success
            $this->resetAttempts($user);
            Auth::login($user, true); // 30-day session
            $request->session()->regenerate();

            return redirect()->intended(route('sales.index'));
        }

        if ($loginType === 'staff') {
            $validated = $request->validate([
                'staff_id' => 'required|uuid',
                'pin' => 'required|digits:6',
            ], [
                'staff_id.required' => 'Pilih nama pegawai.',
                'pin.required' => 'PIN 6 digit wajib diisi.',
                'pin.digits' => 'PIN harus tepat 6 digit angka.',
            ]);

            $user = User::where('id', $validated['staff_id'])
                ->where('role', 'staff')
                ->first();

            if (!$user || !$user->is_active) {
                return back()->withErrors(['login' => 'Pegawai tidak ditemukan atau sudah dinonaktifkan.'])->withInput();
            }

            if ($user->isLocked()) {
                $seconds = now()->diffInSeconds($user->locked_until);
                $minutes = ceil($seconds / 60);
                return back()->withErrors([
                    'login' => "Akun terkunci sementara karena 5 kali percobaan gagal. Coba lagi dalam {$minutes} menit.",
                ])->withInput();
            }

            if (!Hash::check($validated['pin'], $user->pin_hash)) {
                $this->handleFailedAttempt($user);
                return back()->withErrors(['login' => 'PIN tidak cocok.'])->withInput();
            }

            $this->resetAttempts($user);
            Auth::login($user, true);
            $request->session()->regenerate();

            return redirect()->intended(route('sales.index'));
        }

        return back()->withErrors(['login' => 'Tipe login tidak valid.']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    protected function handleFailedAttempt(User $user): void
    {
        $user->increment('failed_attempts');
        if ($user->failed_attempts >= 5) {
            $user->update([
                'locked_until' => now()->addMinutes(5),
            ]);
        }
    }

    protected function resetAttempts(User $user): void
    {
        if ($user->failed_attempts > 0 || $user->locked_until !== null) {
            $user->update([
                'failed_attempts' => 0,
                'locked_until' => null,
            ]);
        }
    }
}
