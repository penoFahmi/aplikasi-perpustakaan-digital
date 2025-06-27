<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles  Daftar peran yang diizinkan
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user = Auth::user();

        foreach ($roles as $role) {
            // Gunakan fungsi hasRole() yang sudah kita buat di model User
            if ($user->hasRole($role)) {
                // Jika user memiliki salah satu peran yang diizinkan, lanjutkan request
                return $next($request);
            }
        }

        // Jika setelah dicek semua peran tidak ada yang cocok, tolak akses
        return response()->json(['message' => 'Anda tidak memiliki akses.'], 403);
    }
}
