<?php

namespace App\Http\Controllers;

use App\Models\Peminjam;
use App\Models\Petugas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in(['mahasiswa', 'petugas'])],
            'identifier' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'max:100'],
        ], [
            'role.required' => 'Pilih jenis login terlebih dahulu.',
            'identifier.required' => 'NIM atau Username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $role = $validated['role'];
        $identifier = trim($validated['identifier']);
        $password = $validated['password'];

        if ($role === 'mahasiswa') {
            $peminjam = Peminjam::where('nim_nip', $identifier)->first();

            if (! $peminjam) {
                return back()
                    ->withInput($request->only('role', 'identifier'))
                    ->withErrors(['identifier' => 'NIM tidak ditemukan atau password tidak sesuai.']);
            }

            // Validasi khusus: hanya status_peminjam = 'Mahasiswa' yang boleh login
            if ($peminjam->status_peminjam !== 'Mahasiswa') {
                return back()
                    ->withInput($request->only('role', 'identifier'))
                    ->withErrors([
                        'identifier' => "Login ditolak: Portal peminjam khusus untuk Mahasiswa. Akun dengan NIM/NIP tersebut berstatus {$peminjam->status_peminjam}.",
                    ]);
            }

            if (! Hash::check($password, (string) $peminjam->password)) {
                return back()
                    ->withInput($request->only('role', 'identifier'))
                    ->withErrors(['password' => 'Password yang Anda masukkan tidak sesuai.']);
            }

            $request->session()->regenerate();
            $request->session()->forget('id_petugas');
            $request->session()->put('id_peminjam', (int) $peminjam->id_peminjam);
            $request->session()->put('auth_user', [
                'role' => 'mahasiswa',
                'role_label' => 'Peminjam (Mahasiswa)',
                'id' => (int) $peminjam->id_peminjam,
                'identifier' => $peminjam->nim_nip,
                'name' => $peminjam->nama_peminjam,
                'foto_profil' => $peminjam->foto_profil,
                'email' => $peminjam->email,
            ]);

            return redirect()
                ->route('dashboard')
                ->with('success', "Selamat datang, {$peminjam->nama_peminjam}!");
        }

        // Role: Petugas / Asisten Lab
        $petugas = Petugas::where('username', $identifier)->first();

        if (! $petugas || ! Hash::check($password, (string) $petugas->password)) {
            return back()
                ->withInput($request->only('role', 'identifier'))
                ->withErrors(['identifier' => 'Username atau password petugas tidak sesuai.']);
        }

        $request->session()->regenerate();
        $request->session()->forget('id_peminjam');
        $request->session()->put('id_petugas', (int) $petugas->id_petugas);
        $request->session()->put('auth_user', [
            'role' => 'petugas',
            'role_label' => 'Petugas / Asisten Lab',
            'id' => (int) $petugas->id_petugas,
            'identifier' => $petugas->username,
            'name' => $petugas->nama_petugas,
        ]);

        return redirect()
            ->route('dashboard')
            ->with('success', "Selamat datang, {$petugas->nama_petugas}!");
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(['auth_user', 'id_petugas', 'id_peminjam']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('success', 'Anda telah keluar dari sistem.');
    }
}
