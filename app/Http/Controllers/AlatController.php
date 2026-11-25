<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\DaftarAlatView;
use App\Models\Laboratorium;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AlatController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $kondisi = (string) $request->input('kondisi', '');
        $namaLab = (string) $request->input('lab', '');

        // Menggunakan View SQL v_daftar_alat untuk menampilkan tabel daftar alat
        $alat = DaftarAlatView::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('kode_alat', 'like', "%{$search}%")
                        ->orWhere('nama_alat', 'like', "%{$search}%")
                        ->orWhere('nama_lab', 'like', "%{$search}%")
                        ->orWhere('lokasi', 'like', "%{$search}%");
                });
            })
            ->when($kondisi !== '', fn ($q) => $q->where('kondisi_umum', $kondisi))
            ->when($namaLab !== '', fn ($q) => $q->where('nama_lab', $namaLab))
            ->orderBy('kode_alat')
            ->paginate(12)
            ->withQueryString();

        $daftarLab = Laboratorium::orderBy('nama_lab')->get();

        return view('alat.index', compact('alat', 'daftarLab', 'search', 'kondisi', 'namaLab'));
    }

    public function create(): View
    {
        $daftarLab = Laboratorium::orderBy('nama_lab')->get();

        return view('alat.create', compact('daftarLab'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_lab' => ['required', 'integer', 'exists:laboratorium,id_lab'],
            'kode_alat' => ['required', 'string', 'max:50', 'unique:alat,kode_alat'],
            'nama_alat' => ['required', 'string', 'max:100'],
            'stok_tersedia' => ['required', 'integer', 'min:0'],
            'kondisi_umum' => ['required', Rule::in(['Baik', 'Rusak Ringan', 'Rusak Berat'])],
        ], [
            'id_lab.required' => 'Laboratorium wajib dipilih.',
            'kode_alat.required' => 'Kode alat wajib diisi.',
            'kode_alat.unique' => 'Kode alat sudah digunakan.',
            'nama_alat.required' => 'Nama alat wajib diisi.',
            'stok_tersedia.required' => 'Stok tersedia wajib diisi.',
            'stok_tersedia.min' => 'Stok tidak boleh kurang dari 0.',
        ]);

        Alat::create($validated);

        return redirect()
            ->route('alat.index')
            ->with('success', 'Data alat berhasil ditambahkan.');
    }

    public function edit(Alat $alat): View
    {
        $daftarLab = Laboratorium::orderBy('nama_lab')->get();

        return view('alat.edit', compact('alat', 'daftarLab'));
    }

    public function update(Request $request, Alat $alat): RedirectResponse
    {
        $validated = $request->validate([
            'id_lab' => ['required', 'integer', 'exists:laboratorium,id_lab'],
            'kode_alat' => [
                'required',
                'string',
                'max:50',
                Rule::unique('alat', 'kode_alat')->ignore($alat->id_alat, 'id_alat'),
            ],
            'nama_alat' => ['required', 'string', 'max:100'],
            'stok_tersedia' => ['required', 'integer', 'min:0'],
            'kondisi_umum' => ['required', Rule::in(['Baik', 'Rusak Ringan', 'Rusak Berat'])],
        ], [
            'id_lab.required' => 'Laboratorium wajib dipilih.',
            'kode_alat.unique' => 'Kode alat sudah digunakan oleh alat lain.',
            'stok_tersedia.min' => 'Stok tidak boleh kurang dari 0.',
        ]);

        $alat->update($validated);

        return redirect()
            ->route('alat.index')
            ->with('success', 'Data alat berhasil diperbarui.');
    }

    public function destroy(Alat $alat): RedirectResponse
    {
        if ($alat->detailPeminjaman()->exists()) {
            return redirect()
                ->route('alat.index')
                ->with('error', 'Alat tidak dapat dihapus karena sudah tercatat dalam riwayat peminjaman.');
        }

        try {
            $alat->delete();

            return redirect()
                ->route('alat.index')
                ->with('success', 'Data alat berhasil dihapus.');
        } catch (QueryException) {
            return redirect()
                ->route('alat.index')
                ->with('error', 'Alat tidak dapat dihapus karena masih direferensikan oleh data transaksi.');
        }
    }
}
