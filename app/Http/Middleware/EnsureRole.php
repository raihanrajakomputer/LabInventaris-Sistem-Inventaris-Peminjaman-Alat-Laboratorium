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
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Silakan login terlebih dahulu.'], 401);
            }

            return redirect()->route('login');
        }

        if (! in_array($authUser['role'], $roles, true)) {
            $roleLabels = array_map(static fn (string $r): string => match ($r) {
                'mahasiswa' => 'Peminjam (Mahasiswa)',
                'petugas' => 'Petugas / Asisten Lab',
                default => ucfirst($r),
            }, $roles);

            $message = 'Akses ditolak. Halaman atau aksi ini hanya dapat diakses oleh ' . implode(' atau ', $roleLabels) . '.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 403);
            }

            abort(403, $message);
        }

        return $next($request);
    }
}
