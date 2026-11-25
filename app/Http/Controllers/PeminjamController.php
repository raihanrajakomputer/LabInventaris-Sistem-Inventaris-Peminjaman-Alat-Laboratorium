<?php

namespace App\Http\Controllers;

use App\Models\Peminjam;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PeminjamController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $status = (string) $request->input('status', '');

        $peminjam = Peminjam::query()
            ->withCount('peminjaman')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nim_nip', 'like', "%{$search}%")
                        ->orWhere('nama_peminjam', 'like', "%{$search}%")
                        ->orWhere('no_hp', 'like', "%{$search}%");
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
            'password' => ['required', 'string', 'min:4', 'max:100'],
        ], [
            'nim_nip.required' => 'NIM / NIP wajib diisi.',
            'nim_nip.unique' => 'NIM / NIP sudah terdaftar.',
            'nama_peminjam.required' => 'Nama peminjam wajib diisi.',
            'status_peminjam.required' => 'Status peminjam wajib dipilih.',
            'no_hp.required' => 'Nomor HP wajib diisi.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 4 karakter.',
        ]);

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
            'password' => ['nullable', 'string', 'min:4', 'max:100'],
        ], [
            'nim_nip.unique' => 'NIM / NIP sudah digunakan oleh peminjam lain.',
            'password.min' => 'Password minimal 4 karakter.',
        ]);

        $data = [
            'nim_nip' => $validated['nim_nip'],
            'nama_peminjam' => $validated['nama_peminjam'],
            'status_peminjam' => $validated['status_peminjam'],
            'no_hp' => $validated['no_hp'],
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
            $peminjam->delete();

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
