<?php

namespace App\Http\Controllers;

use App\Models\Laboratorium;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LaboratoriumController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $escapedSearch = addcslashes($search, '\\%_');

        $laboratorium = Laboratorium::query()
            ->withCount('alat')
            ->withSum('alat', 'stok_tersedia')
            ->when($search !== '', function ($query) use ($escapedSearch) {
                $query->where(function ($q) use ($escapedSearch) {
                    $q->where('nama_lab', 'like', "%{$escapedSearch}%")
                        ->orWhere('lokasi', 'like', "%{$escapedSearch}%");
                });
            })
            ->orderBy('nama_lab')
            ->paginate(10)
            ->withQueryString();

        return view('laboratorium.index', compact('laboratorium', 'search'));
    }

    public function create(): View
    {
        return view('laboratorium.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_lab' => ['required', 'string', 'max:100'],
            'lokasi' => ['required', 'string', 'max:100'],
        ], [
            'nama_lab.required' => 'Nama laboratorium wajib diisi.',
            'lokasi.required' => 'Lokasi laboratorium wajib diisi.',
        ]);

        Laboratorium::create($validated);

        return redirect()
            ->route('laboratorium.index')
            ->with('success', 'Data laboratorium berhasil ditambahkan.');
    }

    public function edit(Laboratorium $laboratorium): View
    {
        return view('laboratorium.edit', compact('laboratorium'));
    }

    public function update(Request $request, Laboratorium $laboratorium): RedirectResponse
    {
        $validated = $request->validate([
            'nama_lab' => ['required', 'string', 'max:100'],
            'lokasi' => ['required', 'string', 'max:100'],
        ], [
            'nama_lab.required' => 'Nama laboratorium wajib diisi.',
            'lokasi.required' => 'Lokasi laboratorium wajib diisi.',
        ]);

        $laboratorium->update($validated);

        return redirect()
            ->route('laboratorium.index')
            ->with('success', 'Data laboratorium berhasil diperbarui.');
    }

    public function destroy(Laboratorium $laboratorium): RedirectResponse
    {
        if ($laboratorium->alat()->exists()) {
            return redirect()
                ->route('laboratorium.index')
                ->with('error', 'Laboratorium tidak dapat dihapus karena masih memiliki data alat terdaftar.');
        }

        try {
            $laboratorium->delete();

            return redirect()
                ->route('laboratorium.index')
                ->with('success', 'Data laboratorium berhasil dihapus.');
        } catch (QueryException) {
            return redirect()
                ->route('laboratorium.index')
                ->with('error', 'Data laboratorium tidak dapat dihapus karena masih direferensikan oleh data lain.');
        }
    }
}
