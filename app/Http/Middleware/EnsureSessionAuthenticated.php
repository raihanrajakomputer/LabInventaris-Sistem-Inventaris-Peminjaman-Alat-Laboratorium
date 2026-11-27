<?php

namespace App\Http\Middleware;

use App\Models\Peminjam;
use App\Models\Petugas;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSessionAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $authUser = $request->session()->get('auth_user');

        if (! is_array($authUser) || empty($authUser['id']) || empty($authUser['role'])) {
            return $this->unauthenticatedResponse($request, 'Silakan login terlebih dahulu untuk mengakses aplikasi.');
        }

        $role = $authUser['role'];
        $id = (int) $authUser['id'];

        // Validasi keberadaan akun aktif di DB & sinkronkan data session agar tidak stale
        if ($role === 'mahasiswa') {
            $peminjam = Peminjam::select(['id_peminjam', 'nim_nip', 'nama_peminjam', 'status_peminjam', 'foto_profil', 'email'])
                ->find($id);

            if (! $peminjam || $peminjam->status_peminjam !== 'Mahasiswa') {
                return $this->invalidateAndReject($request, 'Akun mahasiswa Anda sudah tidak aktif atau tidak ditemukan. Silakan login kembali.');
            }

            if (
                ($authUser['identifier'] ?? null) !== $peminjam->nim_nip
                || ($authUser['name'] ?? null) !== $peminjam->nama_peminjam
                || ($authUser['foto_profil'] ?? null) !== $peminjam->foto_profil
                || ($authUser['email'] ?? null) !== $peminjam->email
            ) {
                $authUser['identifier'] = $peminjam->nim_nip;
                $authUser['name'] = $peminjam->nama_peminjam;
                $authUser['foto_profil'] = $peminjam->foto_profil;
                $authUser['email'] = $peminjam->email;
                $request->session()->put('auth_user', $authUser);
            }

            $request->attributes->set('authenticated_actor', $peminjam);
        } elseif ($role === 'petugas') {
            $petugas = Petugas::select(['id_petugas', 'username', 'nama_petugas'])->find($id);

            if (! $petugas) {
                return $this->invalidateAndReject($request, 'Akun petugas Anda sudah tidak ditemukan. Silakan login kembali.');
            }

            if (
                ($authUser['identifier'] ?? null) !== $petugas->username
                || ($authUser['name'] ?? null) !== $petugas->nama_petugas
            ) {
                $authUser['identifier'] = $petugas->username;
                $authUser['name'] = $petugas->nama_petugas;
                $request->session()->put('auth_user', $authUser);
            }

            $request->attributes->set('authenticated_actor', $petugas);
        } else {
            return $this->invalidateAndReject($request, 'Role sesi tidak valid. Silakan login kembali.');
        }

        return $next($request);
    }

    private function invalidateAndReject(Request $request, string $message): Response
    {
        $request->session()->forget(['auth_user', 'id_petugas', 'id_peminjam', 'labbot_history']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->unauthenticatedResponse($request, $message);
    }

    private function unauthenticatedResponse(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 401);
        }

        return redirect()
            ->route('login')
            ->with('info', $message);
    }
}
