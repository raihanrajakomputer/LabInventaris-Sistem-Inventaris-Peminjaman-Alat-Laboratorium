<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\DaftarAlatView;
use App\Models\DetailPeminjaman;
use App\Models\LaporanPeminjamanView;
use App\Models\Peminjam;
use App\Models\Peminjaman;
use App\Models\Petugas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PeminjamanController extends Controller
{
    public function index(Request $request): View
    {
        $authUser = $request->session()->get('auth_user');
        $isMahasiswa = ($authUser['role'] ?? null) === 'mahasiswa';

        $search = trim((string) $request->input('search', ''));
        $status = (string) $request->input('status', '');
        $dari = (string) $request->input('dari', '');
        $sampai = (string) $request->input('sampai', '');

        // Menggunakan View SQL v_laporan_peminjaman untuk menampilkan tabel laporan peminjaman
        $laporan = LaporanPeminjamanView::query()
            ->when($isMahasiswa, fn ($q) => $q->where('nim_nip', $authUser['identifier']))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nim_nip', 'like', "%{$search}%")
                        ->orWhere('nama_peminjam', 'like', "%{$search}%")
                        ->orWhere('nama_petugas', 'like', "%{$search}%")
                        ->orWhere('kode_alat', 'like', "%{$search}%")
                        ->orWhere('nama_alat', 'like', "%{$search}%")
                        ->orWhere('id_peminjaman', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($q) => $q->where('status_peminjaman', $status))
            ->when($dari !== '', fn ($q) => $q->whereDate('tanggal_pinjam', '>=', $dari))
            ->when($sampai !== '', fn ($q) => $q->whereDate('tanggal_pinjam', '<=', $sampai))
            ->orderByDesc('id_peminjaman')
            ->orderBy('nama_alat')
            ->paginate(15)
            ->withQueryString();

        $baseCountQuery = Peminjaman::query()
            ->when($isMahasiswa, fn ($q) => $q->where('id_peminjam', (int) $authUser['id']));

        $ringkasan = [
            'semua' => (clone $baseCountQuery)->count(),
            'Dipinjam' => (clone $baseCountQuery)->where('status_peminjaman', 'Dipinjam')->count(),
            'Terlambat' => (clone $baseCountQuery)->where('status_peminjaman', 'Terlambat')->count(),
            'Dikembalikan' => (clone $baseCountQuery)->where('status_peminjaman', 'Dikembalikan')->count(),
        ];

        return view('peminjaman.index', compact(
            'laporan',
            'ringkasan',
            'search',
            'status',
            'dari',
            'sampai',
            'isMahasiswa'
        ));
    }

    public function create(Request $request): View
    {
        $authUser = $request->session()->get('auth_user');
        $isMahasiswa = ($authUser['role'] ?? null) === 'mahasiswa';

        $currentPeminjam = $isMahasiswa
            ? Peminjam::findOrFail((int) $authUser['id'])
            : null;

        $peminjamList = $isMahasiswa
            ? collect([$currentPeminjam])
            : Peminjam::orderBy('nama_peminjam')->get();

        $petugasList = Petugas::orderBy('nama_petugas')->get();
        $defaultPetugasId = ! $isMahasiswa ? (int) $authUser['id'] : null;

        $alatList = DaftarAlatView::query()
            ->where('stok_tersedia', '>', 0)
            ->orderBy('nama_alat')
            ->get();

        return view('peminjaman.create', compact(
            'peminjamList',
            'petugasList',
            'alatList',
            'isMahasiswa',
            'currentPeminjam',
            'defaultPetugasId'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $authUser = $request->session()->get('auth_user');
        $isMahasiswa = ($authUser['role'] ?? null) === 'mahasiswa';

        // Jika login sebagai Mahasiswa, kunci id_peminjam di sisi server ke akun yang sedang login
        if ($isMahasiswa) {
            $request->merge(['id_peminjam' => (int) $authUser['id']]);
        }

        $validated = $request->validate([
            'id_peminjam' => ['required', 'integer', 'exists:peminjam,id_peminjam'],
            'id_petugas' => ['required', 'integer', 'exists:petugas,id_petugas'],
            'tanggal_pinjam' => ['required', 'date'],
            'jadwal_kembali' => ['required', 'date', 'after_or_equal:tanggal_pinjam'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_alat' => ['required', 'integer', 'distinct', 'exists:alat,id_alat'],
            'items.*.jumlah_pinjam' => ['required', 'integer', 'min:1'],
            'items.*.kondisi_saat_pinjam' => ['required', 'string', 'max:50'],
        ], [
            'id_peminjam.required' => 'Peminjam wajib dipilih.',
            'id_petugas.required' => 'Petugas wajib dipilih.',
            'jadwal_kembali.required' => 'Jadwal kembali wajib diisi.',
            'jadwal_kembali.after_or_equal' => 'Jadwal kembali tidak boleh lebih awal dari tanggal pinjam.',
            'items.required' => 'Minimal pilih satu alat untuk dipinjam.',
            'items.*.id_alat.required' => 'Alat pada setiap baris wajib dipilih.',
            'items.*.id_alat.distinct' => 'Alat yang sama tidak boleh dipilih dua kali dalam satu transaksi. Gabungkan jumlahnya pada satu baris.',
            'items.*.jumlah_pinjam.min' => 'Jumlah pinjam minimal 1 unit.',
            'items.*.kondisi_saat_pinjam.required' => 'Kondisi alat saat pinjam wajib diisi.',
        ]);

        $peminjaman = DB::transaction(function () use ($validated, $isMahasiswa, $authUser) {
            // Cek ketersediaan stok terlebih dahulu dengan row lock agar aman dari race condition.
            // Pengurangan stok tetap diserahkan sepenuhnya kepada Trigger MySQL (trg_kurangi_stok_alat).
            foreach ($validated['items'] as $index => $item) {
                $alat = Alat::where('id_alat', $item['id_alat'])->lockForUpdate()->first();

                if (! $alat || $alat->stok_tersedia < (int) $item['jumlah_pinjam']) {
                    $tersedia = $alat ? $alat->stok_tersedia : 0;
                    throw ValidationException::withMessages([
                        "items.{$index}.jumlah_pinjam" => "Stok alat \"{$alat?->nama_alat}\" tidak mencukupi (tersedia: {$tersedia} unit).",
                    ]);
                }
            }

            $peminjaman = Peminjaman::create([
                'id_peminjam' => $isMahasiswa ? (int) $authUser['id'] : (int) $validated['id_peminjam'],
                'id_petugas' => (int) $validated['id_petugas'],
                'tanggal_pinjam' => $validated['tanggal_pinjam'],
                'jadwal_kembali' => $validated['jadwal_kembali'],
                'tanggal_kembali_aktual' => null,
                'status_peminjaman' => 'Dipinjam',
            ]);

            foreach ($validated['items'] as $item) {
                // INSERT ke detail_peminjaman akan otomatis memicu trigger trg_kurangi_stok_alat di MySQL
                DetailPeminjaman::create([
                    'id_peminjaman' => $peminjaman->id_peminjaman,
                    'id_alat' => $item['id_alat'],
                    'jumlah_pinjam' => (int) $item['jumlah_pinjam'],
                    'kondisi_saat_pinjam' => $item['kondisi_saat_pinjam'],
                    'kondisi_saat_kembali' => null,
                ]);
            }

            return $peminjaman;
        });

        return redirect()
            ->route('peminjaman.show', $peminjaman)
            ->with('success', "Transaksi peminjaman #{$peminjaman->id_peminjaman} berhasil disimpan. Stok alat telah diperbarui otomatis oleh database.");
    }

    public function show(Request $request, Peminjaman $peminjaman): View
    {
        $authUser = $request->session()->get('auth_user');
        $isMahasiswa = ($authUser['role'] ?? null) === 'mahasiswa';

        if ($isMahasiswa && (int) $peminjaman->id_peminjam !== (int) $authUser['id']) {
            abort(403, 'Anda tidak diizinkan mengakses detail peminjaman milik pengguna lain.');
        }

        $peminjaman->load(['peminjam', 'petugas', 'details.alat.laboratorium']);

        return view('peminjaman.show', compact('peminjaman', 'isMahasiswa'));
    }

    public function kembalikanForm(Peminjaman $peminjaman): View|RedirectResponse
    {
        if ($peminjaman->status_peminjaman === 'Dikembalikan') {
            return redirect()
                ->route('peminjaman.show', $peminjaman)
                ->with('info', "Transaksi #{$peminjaman->id_peminjaman} sudah berstatus Dikembalikan.");
        }

        $peminjaman->load(['peminjam', 'petugas', 'details.alat.laboratorium']);

        return view('peminjaman.kembalikan', compact('peminjaman'));
    }

    public function kembalikanProcess(Request $request, Peminjaman $peminjaman): RedirectResponse
    {
        $validated = $request->validate([
            'tanggal_kembali_aktual' => ['required', 'date'],
            'kondisi_kembali' => ['required', 'array', 'min:1'],
            'kondisi_kembali.*' => ['required', 'string', 'max:50'],
        ], [
            'tanggal_kembali_aktual.required' => 'Tanggal kembali aktual wajib diisi.',
            'kondisi_kembali.*.required' => 'Kondisi saat kembali wajib diisi untuk setiap alat.',
        ]);

        DB::transaction(function () use ($peminjaman, $validated) {
            $locked = Peminjaman::where('id_peminjaman', $peminjaman->id_peminjaman)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status_peminjaman === 'Dikembalikan') {
                return;
            }

            $details = DetailPeminjaman::where('id_peminjaman', $locked->id_peminjaman)->get();

            foreach ($details as $detail) {
                $kondisi = $validated['kondisi_kembali'][$detail->id_detail] ?? 'Baik';
                $detail->update([
                    'kondisi_saat_kembali' => $kondisi,
                ]);
            }

            // UPDATE status_peminjaman -> 'Dikembalikan' otomatis memicu trigger trg_kembalikan_stok_alat di MySQL.
            // Jangan menambah stok secara manual di controller agar stok tidak terhitung ganda.
            $locked->update([
                'tanggal_kembali_aktual' => $validated['tanggal_kembali_aktual'],
                'status_peminjaman' => 'Dikembalikan',
            ]);
        });

        return redirect()
            ->route('peminjaman.show', $peminjaman)
            ->with('success', "Pengembalian transaksi #{$peminjaman->id_peminjaman} berhasil dicatat. Stok alat telah dikembalikan otomatis oleh database.");
    }

    public function tandaiTerlambat(Peminjaman $peminjaman): RedirectResponse
    {
        if ($peminjaman->status_peminjaman !== 'Dipinjam') {
            return redirect()
                ->back()
                ->with('error', 'Hanya transaksi berstatus Dipinjam yang dapat ditandai Terlambat.');
        }

        $peminjaman->update([
            'status_peminjaman' => 'Terlambat',
        ]);

        return redirect()
            ->back()
            ->with('success', "Status transaksi #{$peminjaman->id_peminjaman} diperbarui menjadi Terlambat.");
    }
}
