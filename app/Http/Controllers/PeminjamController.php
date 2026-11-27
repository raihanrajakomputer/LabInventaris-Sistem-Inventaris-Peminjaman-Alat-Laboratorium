<?php

namespace App\Http\Controllers;

use App\Models\Peminjam;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PeminjamController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $escapedSearch = addcslashes($search, '\\%_');
        $status = (string) $request->input('status', '');

        $peminjam = Peminjam::query()
            ->withCount('peminjaman')
            ->when($search !== '', function ($query) use ($escapedSearch) {
                $query->where(function ($q) use ($escapedSearch) {
                    $q->where('nim_nip', 'like', "%{$escapedSearch}%")
                        ->orWhere('nama_peminjam', 'like', "%{$escapedSearch}%")
                        ->orWhere('no_hp', 'like', "%{$escapedSearch}%");
                });
            })
            ->when($status !== '', fn ($q) => $q->where('status_peminjam', $status))
            ->orderBy('nama_peminjam')
            ->paginate(12)
            ->withQueryString();

        return view('peminjam.index', compact('peminjam', 'search', 'status'));
    }

    public function create(): View
    {
        return view('peminjam.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nim_nip' => ['required', 'string', 'max:30', 'unique:peminjam,nim_nip'],
            'nama_peminjam' => ['required', 'string', 'max:100'],
            'status_peminjam' => ['required', Rule::in(['Mahasiswa', 'Dosen'])],
            'no_hp' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100', 'unique:peminjam,email'],
            'password' => ['required', 'string', 'min:4', 'max:100'],
        ], [
            'nim_nip.required' => 'NIM / NIP wajib diisi.',
            'nim_nip.unique' => 'NIM / NIP sudah terdaftar.',
            'nama_peminjam.required' => 'Nama peminjam wajib diisi.',
            'status_peminjam.required' => 'Status peminjam wajib dipilih.',
            'no_hp.required' => 'Nomor HP wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'email.unique' => 'Alamat email sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 4 karakter.',
        ]);

        $validated['email'] = ! empty($validated['email']) ? strtolower(trim($validated['email'])) : null;
        $validated['password'] = Hash::make($validated['password']);
        Peminjam::create($validated);

        return redirect()
            ->route('peminjam.index')
            ->with('success', 'Data peminjam berhasil ditambahkan.');
    }

    public function edit(Peminjam $peminjam): View
    {
        return view('peminjam.edit', compact('peminjam'));
    }

    public function update(Request $request, Peminjam $peminjam): RedirectResponse
    {
        $validated = $request->validate([
            'nim_nip' => [
                'required',
                'string',
                'max:30',
                Rule::unique('peminjam', 'nim_nip')->ignore($peminjam->id_peminjam, 'id_peminjam'),
            ],
            'nama_peminjam' => ['required', 'string', 'max:100'],
            'status_peminjam' => ['required', Rule::in(['Mahasiswa', 'Dosen'])],
            'no_hp' => ['required', 'string', 'max:20'],
            'email' => [
                'nullable',
                'email',
                'max:100',
                Rule::unique('peminjam', 'email')->ignore($peminjam->id_peminjam, 'id_peminjam'),
            ],
            'password' => ['nullable', 'string', 'min:4', 'max:100'],
        ], [
            'nim_nip.unique' => 'NIM / NIP sudah digunakan oleh peminjam lain.',
            'email.email' => 'Format alamat email tidak valid.',
            'email.unique' => 'Alamat email sudah digunakan oleh peminjam lain.',
            'password.min' => 'Password minimal 4 karakter.',
        ]);

        $data = [
            'nim_nip' => $validated['nim_nip'],
            'nama_peminjam' => $validated['nama_peminjam'],
            'status_peminjam' => $validated['status_peminjam'],
            'no_hp' => $validated['no_hp'],
            'email' => ! empty($validated['email']) ? strtolower(trim($validated['email'])) : null,
        ];

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $peminjam->update($data);

        return redirect()
            ->route('peminjam.index')
            ->with('success', 'Data peminjam berhasil diperbarui.');
    }

    public function destroy(Peminjam $peminjam): RedirectResponse
    {
        if ($peminjam->peminjaman()->exists()) {
            return redirect()
                ->route('peminjam.index')
                ->with('error', 'Peminjam tidak dapat dihapus karena memiliki riwayat transaksi peminjaman.');
        }

        try {
            $fotoPath = $peminjam->foto_profil;
            $peminjam->delete();

            // Bersihkan file foto profil di storage agar tidak tersisa orphan file
            if (! empty($fotoPath) && Storage::disk('public')->exists($fotoPath)) {
                Storage::disk('public')->delete($fotoPath);
            }

            return redirect()
                ->route('peminjam.index')
                ->with('success', 'Data peminjam berhasil dihapus.');
        } catch (QueryException) {
            return redirect()
                ->route('peminjam.index')
                ->with('error', 'Data peminjam tidak dapat dihapus karena masih digunakan di transaksi.');
        }
    }
}
