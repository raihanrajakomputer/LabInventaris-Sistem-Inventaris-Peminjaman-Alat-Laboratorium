<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $authUser = $request->session()->get('auth_user');

        if (! is_array($authUser) || empty($authUser['role'])) {
            return redirect()->route('login');
        }

        if (! in_array($authUser['role'], $roles, true)) {
            abort(403, 'Akses ditolak. Halaman atau aksi ini hanya dapat diakses oleh Petugas / Asisten Lab.');
        }

        return $next($request);
    }
}
