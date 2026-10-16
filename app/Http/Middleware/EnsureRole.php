<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = Auth::user();

        if (!$user) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['code' => 401, 'message' => 'Silakan masuk terlebih dahulu.'], 401);
            }
            return redirect()->route('login');
        }

        // AUTH-03: Deactivated accounts immediately forbidden
        if (!$user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['code' => 403, 'message' => 'Akun Anda telah dinonaktifkan oleh pemilik.'], 403);
            }
            return redirect()->route('login')->withErrors(['login' => 'Akun Anda telah dinonaktifkan.']);
        }

        // AUTH-04: Server-side role check
        if (!empty($roles) && !in_array($user->role, $roles)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['code' => 403, 'message' => 'Anda tidak memiliki hak akses untuk tindakan ini.'], 403);
            }
            abort(403, 'Anda tidak memiliki hak akses untuk halaman ini.');
        }

        return $next($request);
    }
}
