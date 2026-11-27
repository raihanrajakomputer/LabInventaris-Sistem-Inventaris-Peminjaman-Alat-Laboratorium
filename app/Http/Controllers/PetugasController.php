<?php

namespace App\Http\Controllers;

use App\Models\Petugas;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PetugasController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $escapedSearch = addcslashes($search, '\\%_');

        $petugas = Petugas::query()
            ->withCount('peminjaman')
            ->when($search !== '', function ($query) use ($escapedSearch) {
                $query->where(function ($q) use ($escapedSearch) {
                    $q->where('username', 'like', "%{$escapedSearch}%")
                        ->orWhere('nama_petugas', 'like', "%{$escapedSearch}%");
                });
            })
            ->orderBy('nama_petugas')
            ->paginate(10)
            ->withQueryString();

        return view('petugas.index', compact('petugas', 'search'));
    }

    public function create(): View
    {
        return view('petugas.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:50', 'unique:petugas,username'],
            'nama_petugas' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'min:4', 'max:100'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username sudah terdaftar.',
            'nama_petugas.required' => 'Nama petugas wajib diisi.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 4 karakter.',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        Petugas::create($validated);

        return redirect()
            ->route('petugas.index')
            ->with('success', 'Data petugas berhasil ditambahkan.');
    }

    public function edit(Petugas $petugas): View
    {
        return view('petugas.edit', compact('petugas'));
    }

    public function update(Request $request, Petugas $petugas): RedirectResponse
    {
        $validated = $request->validate([
            'username' => [
                'required',
                'string',
                'max:50',
                Rule::unique('petugas', 'username')->ignore($petugas->id_petugas, 'id_petugas'),
            ],
            'nama_petugas' => ['required', 'string', 'max:100'],
            'password' => ['nullable', 'string', 'min:4', 'max:100'],
        ], [
            'username.unique' => 'Username sudah digunakan oleh petugas lain.',
            'password.min' => 'Password minimal 4 karakter.',
        ]);

        $data = [
            'username' => $validated['username'],
            'nama_petugas' => $validated['nama_petugas'],
        ];

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $petugas->update($data);

        // Sinkronkan session jika petugas mengubah akunnya sendiri
        $authUser = $request->session()->get('auth_user', []);
        if (($authUser['role'] ?? null) === 'petugas' && (int) ($authUser['id'] ?? 0) === (int) $petugas->id_petugas) {
            $authUser['identifier'] = $petugas->username;
            $authUser['name'] = $petugas->nama_petugas;
            $request->session()->put('auth_user', $authUser);
        }

        return redirect()
            ->route('petugas.index')
            ->with('success', 'Data petugas berhasil diperbarui.');
    }

    public function destroy(Request $request, Petugas $petugas): RedirectResponse
    {
        if ((int) $petugas->id_petugas === (int) $request->session()->get('auth_user.id')) {
            return redirect()
                ->route('petugas.index')
                ->with('error', 'Anda tidak dapat menghapus akun petugas yang sedang digunakan untuk login.');
        }

        if ($petugas->peminjaman()->exists()) {
            return redirect()
                ->route('petugas.index')
                ->with('error', 'Petugas tidak dapat dihapus karena masih tercatat dalam riwayat peminjaman.');
        }

        try {
            $petugas->delete();

            return redirect()
                ->route('petugas.index')
                ->with('success', 'Data petugas berhasil dihapus.');
        } catch (QueryException) {
            return redirect()
                ->route('petugas.index')
                ->with('error', 'Data petugas tidak dapat dihapus karena masih direferensikan.');
        }
    }
}
