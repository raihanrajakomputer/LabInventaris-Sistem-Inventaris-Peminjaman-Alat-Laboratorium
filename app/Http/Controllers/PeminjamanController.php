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
use Illuminate\Support\Carbon;
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
        $escapedSearch = $this->escapeLike($search);
        $laporan = LaporanPeminjamanView::query()
            ->when($isMahasiswa, fn ($q) => $q->where('nim_nip', $authUser['identifier']))
            ->when($search !== '', function ($query) use ($escapedSearch) {
                $query->where(function ($q) use ($escapedSearch) {
                    $q->where('nim_nip', 'like', "%{$escapedSearch}%")
                        ->orWhere('nama_peminjam', 'like', "%{$escapedSearch}%")
                        ->orWhere('nama_petugas', 'like', "%{$escapedSearch}%")
                        ->orWhere('kode_alat', 'like', "%{$escapedSearch}%")
                        ->orWhere('nama_alat', 'like', "%{$escapedSearch}%")
                        ->orWhere('id_peminjaman', 'like', "%{$escapedSearch}%");
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
            'Menunggu' => (clone $baseCountQuery)->where('status_peminjaman', 'Menunggu')->count(),
            'Dipinjam' => (clone $baseCountQuery)->where('status_peminjaman', 'Dipinjam')->count(),
            'Terlambat' => (clone $baseCountQuery)->where('status_peminjaman', 'Terlambat')->count(),
            'Dikembalikan' => (clone $baseCountQuery)->where('status_peminjaman', 'Dikembalikan')->count(),
            'Ditolak' => (clone $baseCountQuery)->where('status_peminjaman', 'Ditolak')->count(),
        ];

        $pageIds = $laporan->getCollection()
            ->pluck('id_peminjaman')
            ->unique()
            ->values();

        $peminjamanMeta = $pageIds->isNotEmpty()
            ? Peminjaman::with('peminjam:id_peminjam,nama_peminjam,no_hp')
                ->whereIn('id_peminjaman', $pageIds)
                ->get()
                ->keyBy('id_peminjaman')
            : collect();

        return view('peminjaman.index', compact(
            'laporan',
            'ringkasan',
            'search',
            'status',
            'dari',
            'sampai',
            'isMahasiswa',
            'peminjamanMeta'
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

        $petugasList = $isMahasiswa
            ? collect()
            : Petugas::orderBy('nama_petugas')->get();

        $defaultPetugasId = ! $isMahasiswa
            ? (int) ($request->session()->get('id_petugas') ?? $authUser['id'])
            : null;

        $alatList = DaftarAlatView::query()
            ->where('stok_tersedia', '>', 0)
            ->where('kondisi_umum', '!=', 'Rusak Berat')
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

        // Mahasiswa: kunci id_peminjam ke akun sendiri, id_petugas = null, status = 'Menunggu'
        if ($isMahasiswa) {
            $request->merge([
                'id_peminjam' => (int) $authUser['id'],
                'id_petugas' => null,
            ]);
        }

        $rules = [
            'id_peminjam' => ['required', 'integer', 'exists:peminjam,id_peminjam'],
            'tanggal_pinjam' => ['required', 'date'],
            'jadwal_kembali' => ['required', 'date', 'after_or_equal:tanggal_pinjam'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_alat' => ['required', 'integer', 'distinct', 'exists:alat,id_alat'],
            'items.*.jumlah_pinjam' => ['required', 'integer', 'min:1'],
            'items.*.kondisi_saat_pinjam' => ['required', 'string', 'max:50'],
        ];

        if (! $isMahasiswa) {
            $rules['id_petugas'] = ['required', 'integer', 'exists:petugas,id_petugas'];
        }

        $validated = $request->validate($rules, [
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

        // Validasi rentang tanggal wajar (cegah input tahun ekstrem / penyalahgunaan jadwal)
        $tglPinjam = Carbon::parse($validated['tanggal_pinjam']);
        $tglJadwal = Carbon::parse($validated['jadwal_kembali']);

        if ($isMahasiswa && ($tglPinjam->lt(now()->subDays(7)) || $tglPinjam->gt(now()->addDays(30)))) {
            throw ValidationException::withMessages([
                'tanggal_pinjam' => 'Tanggal pinjam untuk mahasiswa harus berada dalam rentang 7 hari terakhir hingga 30 hari ke depan.',
            ]);
        }

        if ($tglJadwal->gt($tglPinjam->copy()->addDays(90))) {
            throw ValidationException::withMessages([
                'jadwal_kembali' => 'Durasi peminjaman maksimal 90 hari sejak tanggal pinjam.',
            ]);
        }

        $peminjaman = DB::transaction(function () use ($validated, $isMahasiswa, $authUser, $request) {
            // Urutkan berdasarkan id_alat menaik sebelum lockForUpdate() untuk mencegah MySQL deadlock
            $indexedItems = [];
            foreach ($validated['items'] as $index => $item) {
                $indexedItems[] = ['index' => $index, 'item' => $item];
            }
            usort($indexedItems, fn (array $a, array $b): int => (int) $a['item']['id_alat'] <=> (int) $b['item']['id_alat']);

            // Validasi ketersediaan stok & kondisi alat. Pengurangan stok tetap diserahkan kepada Trigger MySQL.
            foreach ($indexedItems as $entry) {
                $index = $entry['index'];
                $item = $entry['item'];
                $alat = Alat::where('id_alat', $item['id_alat'])->lockForUpdate()->first();

                if (! $alat || $alat->kondisi_umum === 'Rusak Berat') {
                    throw ValidationException::withMessages([
                        "items.{$index}.id_alat" => "Alat \"{$alat?->nama_alat}\" berstatus Rusak Berat dan tidak dapat dipinjam.",
                    ]);
                }

                if ($alat->stok_tersedia < (int) $item['jumlah_pinjam']) {
                    $tersedia = $alat->stok_tersedia;
                    throw ValidationException::withMessages([
                        "items.{$index}.jumlah_pinjam" => "Stok alat \"{$alat->nama_alat}\" tidak mencukupi (tersedia: {$tersedia} unit).",
                    ]);
                }
            }

            $petugasId = $isMahasiswa
                ? null
                : (int) ($validated['id_petugas'] ?? $request->session()->get('id_petugas') ?? $authUser['id']);

            $statusAwal = $isMahasiswa ? 'Menunggu' : 'Dipinjam';

            $peminjaman = Peminjaman::create([
                'id_peminjam' => $isMahasiswa ? (int) $authUser['id'] : (int) $validated['id_peminjam'],
                'id_petugas' => $petugasId,
                'tanggal_pinjam' => $validated['tanggal_pinjam'],
                'jadwal_kembali' => $validated['jadwal_kembali'],
                'tanggal_kembali_aktual' => null,
                'status_peminjaman' => $statusAwal,
            ]);

            foreach ($validated['items'] as $item) {
                // Trigger MySQL:
                // - Jika status 'Menunggu' (pengajuan mahasiswa): stok TIDAK berkurang.
                // - Jika status 'Dipinjam' (input langsung petugas): stok berkurang otomatis.
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

        $message = $isMahasiswa
            ? "Pengajuan peminjaman #{$peminjaman->id_peminjaman} berhasil dikirim dan sedang Menunggu verifikasi (ACC) Petugas."
            : "Transaksi peminjaman #{$peminjaman->id_peminjaman} berhasil disimpan dengan status Dipinjam. Stok alat telah berkurang otomatis oleh database.";

        return redirect()
            ->route('peminjaman.show', $peminjaman)
            ->with('success', $message);
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

    public function setujui(Request $request, Peminjaman $peminjaman): RedirectResponse
    {
        $idPetugas = (int) ($request->session()->get('id_petugas') ?? $request->session()->get('auth_user.id'));

        $result = DB::transaction(function () use ($peminjaman, $idPetugas) {
            $locked = Peminjaman::where('id_peminjaman', $peminjaman->id_peminjaman)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status_peminjaman !== 'Menunggu') {
                return [
                    'ok' => false,
                    'message' => "Transaksi #{$locked->id_peminjaman} tidak berstatus Menunggu sehingga tidak dapat disetujui.",
                ];
            }

            $details = DetailPeminjaman::where('id_peminjaman', $locked->id_peminjaman)->get();

            if ($details->isEmpty()) {
                return [
                    'ok' => false,
                    'message' => "Transaksi #{$locked->id_peminjaman} tidak memiliki item alat.",
                ];
            }

            // Akumulasi kebutuhan per id_alat dan urutkan key menaik untuk mencegah deadlock saat lockForUpdate()
            $kebutuhanPerAlat = [];
            foreach ($details as $detail) {
                $kebutuhanPerAlat[(int) $detail->id_alat] = ($kebutuhanPerAlat[(int) $detail->id_alat] ?? 0) + (int) $detail->jumlah_pinjam;
            }
            ksort($kebutuhanPerAlat);

            $kekurangan = [];
            foreach ($kebutuhanPerAlat as $idAlat => $jumlahDiminta) {
                $alat = Alat::where('id_alat', $idAlat)->lockForUpdate()->first();
                if (! $alat || $alat->kondisi_umum === 'Rusak Berat') {
                    $nama = $alat ? "{$alat->nama_alat} ({$alat->kode_alat})" : "Alat #{$idAlat}";
                    $kekurangan[] = "{$nama}: kondisi Rusak Berat";
                    continue;
                }
                if ($alat->stok_tersedia < $jumlahDiminta) {
                    $nama = "{$alat->nama_alat} ({$alat->kode_alat})";
                    $tersedia = $alat->stok_tersedia;
                    $kekurangan[] = "{$nama}: diminta {$jumlahDiminta} unit, tersedia {$tersedia} unit";
                }
            }

            if (! empty($kekurangan)) {
                return [
                    'ok' => false,
                    'message' => 'Persetujuan (ACC) gagal karena stok tidak mencukupi — ' . implode('; ', $kekurangan) . '.',
                ];
            }

            // UPDATE status 'Menunggu' -> 'Dipinjam' otomatis memicu Trigger MySQL untuk mengurangi stok.
            // Jangan mengurangi stok secara manual di Controller!
            $locked->update([
                'status_peminjaman' => 'Dipinjam',
                'id_petugas' => $idPetugas,
            ]);

            return [
                'ok' => true,
                'message' => "Pengajuan peminjaman #{$locked->id_peminjaman} berhasil disetujui (ACC). Stok alat telah berkurang otomatis oleh database.",
            ];
        });

        return redirect()
            ->back()
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function tolak(Request $request, Peminjaman $peminjaman): RedirectResponse
    {
        $idPetugas = (int) ($request->session()->get('id_petugas') ?? $request->session()->get('auth_user.id'));

        $result = DB::transaction(function () use ($peminjaman, $idPetugas) {
            $locked = Peminjaman::where('id_peminjaman', $peminjaman->id_peminjaman)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status_peminjaman !== 'Menunggu') {
                return [
                    'ok' => false,
                    'message' => "Transaksi #{$locked->id_peminjaman} tidak berstatus Menunggu sehingga tidak dapat ditolak.",
                ];
            }

            $locked->update([
                'status_peminjaman' => 'Ditolak',
                'id_petugas' => $idPetugas,
            ]);

            return [
                'ok' => true,
                'message' => "Pengajuan peminjaman #{$locked->id_peminjaman} telah ditolak.",
            ];
        });

        return redirect()
            ->back()
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function kembalikanForm(Peminjaman $peminjaman): View|RedirectResponse
    {
        if (! in_array($peminjaman->status_peminjaman, ['Dipinjam', 'Terlambat'], true)) {
            return redirect()
                ->route('peminjaman.show', $peminjaman)
                ->with('info', "Transaksi #{$peminjaman->id_peminjaman} berstatus {$peminjaman->status_peminjaman} dan tidak dapat diproses pengembaliannya.");
        }

        $peminjaman->load(['peminjam', 'petugas', 'details.alat.laboratorium']);

        return view('peminjaman.kembalikan', compact('peminjaman'));
    }

    public function kembalikanProcess(Request $request, Peminjaman $peminjaman): RedirectResponse
    {
        if (! in_array($peminjaman->status_peminjaman, ['Dipinjam', 'Terlambat'], true)) {
            return redirect()
                ->route('peminjaman.show', $peminjaman)
                ->with('info', "Transaksi #{$peminjaman->id_peminjaman} berstatus {$peminjaman->status_peminjaman} sehingga tidak dapat diproses pengembaliannya.");
        }

        $validated = $request->validate([
            'tanggal_kembali_aktual' => ['required', 'date'],
            'kondisi_kembali' => ['required', 'array', 'min:1'],
            'kondisi_kembali.*' => ['required', 'string', 'max:50'],
        ], [
            'tanggal_kembali_aktual.required' => 'Tanggal kembali aktual wajib diisi.',
            'kondisi_kembali.*.required' => 'Kondisi saat kembali wajib diisi untuk setiap alat.',
        ]);

        $waktuKembali = Carbon::parse($validated['tanggal_kembali_aktual']);

        if ($peminjaman->tanggal_pinjam) {
            $batasMinimalMenit = $peminjaman->tanggal_pinjam->copy()->startOfMinute();

            if ($waktuKembali->lt($batasMinimalMenit)) {
                throw ValidationException::withMessages([
                    'tanggal_kembali_aktual' => 'Tanggal kembali aktual tidak boleh lebih awal dari tanggal pinjam (' . $peminjaman->tanggal_pinjam->format('d M Y, H:i') . ').',
                ]);
            }

            if ($waktuKembali->lt($peminjaman->tanggal_pinjam)) {
                $waktuKembali = $peminjaman->tanggal_pinjam->copy();
            }
        }

        $berhasil = DB::transaction(function () use ($peminjaman, $validated, $waktuKembali): bool {
            $locked = Peminjaman::where('id_peminjaman', $peminjaman->id_peminjaman)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($locked->status_peminjaman, ['Dipinjam', 'Terlambat'], true)) {
                return false;
            }

            $details = DetailPeminjaman::where('id_peminjaman', $locked->id_peminjaman)->get();

            foreach ($details as $detail) {
                $kondisi = trim((string) ($validated['kondisi_kembali'][$detail->id_detail] ?? 'Baik'));
                $detail->update([
                    'kondisi_saat_kembali' => $kondisi !== '' ? $kondisi : 'Baik',
                ]);
            }

            // UPDATE status_peminjaman -> 'Dikembalikan' otomatis memicu Trigger MySQL untuk mengembalikan stok.
            // Jangan menambah stok secara manual di controller agar stok tidak terhitung ganda.
            $locked->update([
                'tanggal_kembali_aktual' => $waktuKembali,
                'status_peminjaman' => 'Dikembalikan',
            ]);

            return true;
        });

        if (! $berhasil) {
            return redirect()
                ->route('peminjaman.show', $peminjaman)
                ->with('info', "Transaksi #{$peminjaman->id_peminjaman} sudah tidak dalam status aktif untuk dikembalikan.");
        }

        return redirect()
            ->route('peminjaman.show', $peminjaman)
            ->with('success', "Pengembalian transaksi #{$peminjaman->id_peminjaman} berhasil dicatat. Stok alat telah dikembalikan otomatis oleh database.");
    }

    public function kembalikanOlehMahasiswa(Request $request, Peminjaman $peminjaman): RedirectResponse
    {
        $authUser = $request->session()->get('auth_user', []);
        $isPetugas = ($authUser['role'] ?? null) === 'petugas';
        $isMahasiswa = ($authUser['role'] ?? null) === 'mahasiswa';
        $sessionPeminjamId = (int) ($request->session()->get('id_peminjam') ?? ($isMahasiswa ? ($authUser['id'] ?? 0) : 0));

        // Pastikan hanya pemilik transaksi atau Petugas yang dapat memproses pengembalian
        if (! $isPetugas && (! $isMahasiswa || (int) $peminjaman->id_peminjam !== $sessionPeminjamId)) {
            abort(403, 'Anda tidak diizinkan memproses pengembalian untuk transaksi milik pengguna lain.');
        }

        if (! in_array($peminjaman->status_peminjaman, ['Dipinjam', 'Terlambat'], true)) {
            return redirect()
                ->route('peminjaman.show', $peminjaman)
                ->with('info', "Transaksi #{$peminjaman->id_peminjaman} berstatus {$peminjaman->status_peminjaman} sehingga tidak dapat diproses pengembaliannya.");
        }

        $validated = $request->validate([
            'tanggal_kembali_aktual' => ['nullable', 'date'],
            'kondisi_kembali' => ['required', 'array', 'min:1'],
            'kondisi_kembali.*' => ['required', 'string', 'max:50'],
        ], [
            'tanggal_kembali_aktual.date' => 'Format waktu pengembalian tidak valid.',
            'kondisi_kembali.required' => 'Kondisi saat kembali wajib diisi untuk setiap alat.',
            'kondisi_kembali.*.required' => 'Kondisi saat kembali wajib dipilih atau diisi untuk setiap alat.',
            'kondisi_kembali.*.max' => 'Keterangan kondisi alat maksimal 50 karakter.',
        ]);

        // Tentukan waktu pengembalian aktual (default now()), pastikan tidak lebih awal dari tanggal_pinjam
        $waktuKembali = ! empty($validated['tanggal_kembali_aktual'])
            ? Carbon::parse($validated['tanggal_kembali_aktual'])
            : now();

        if ($peminjaman->tanggal_pinjam) {
            $batasMinimalMenit = $peminjaman->tanggal_pinjam->copy()->startOfMinute();

            if ($waktuKembali->lt($batasMinimalMenit)) {
                throw ValidationException::withMessages([
                    'tanggal_kembali_aktual' => 'Waktu pengembalian tidak boleh lebih awal dari tanggal pinjam (' . $peminjaman->tanggal_pinjam->format('d M Y, H:i') . ').',
                ]);
            }

            if ($waktuKembali->lt($peminjaman->tanggal_pinjam)) {
                $waktuKembali = $peminjaman->tanggal_pinjam->copy();
            }
        }

        $berhasil = DB::transaction(function () use ($peminjaman, $validated, $waktuKembali): bool {
            $locked = Peminjaman::where('id_peminjaman', $peminjaman->id_peminjaman)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($locked->status_peminjaman, ['Dipinjam', 'Terlambat'], true)) {
                return false;
            }

            $details = DetailPeminjaman::where('id_peminjaman', $locked->id_peminjaman)->get();

            foreach ($details as $detail) {
                $kondisi = trim((string) ($validated['kondisi_kembali'][$detail->id_detail] ?? 'Baik'));
                $detail->update([
                    'kondisi_saat_kembali' => $kondisi !== '' ? $kondisi : 'Baik',
                ]);
            }

            // UPDATE status_peminjaman -> 'Dikembalikan' otomatis memicu Trigger MySQL untuk mengembalikan stok.
            // JANGAN menambah stok_tersedia secara manual di Controller!
            $locked->update([
                'tanggal_kembali_aktual' => $waktuKembali,
                'status_peminjaman' => 'Dikembalikan',
            ]);

            return true;
        });

        if (! $berhasil) {
            return redirect()
                ->route('peminjaman.show', $peminjaman)
                ->with('info', "Transaksi #{$peminjaman->id_peminjaman} sudah tidak dalam status aktif untuk dikembalikan.");
        }

        return redirect()
            ->route('peminjaman.show', $peminjaman)
            ->with('success', 'Barang berhasil dikembalikan. Terima kasih telah mengembalikan alat tepat waktu.');
    }

    public function tandaiTerlambat(Peminjaman $peminjaman): RedirectResponse
    {
        if ($peminjaman->status_peminjaman !== 'Dipinjam') {
            return redirect()
                ->back()
                ->with('error', 'Hanya transaksi berstatus Dipinjam yang dapat ditandai Terlambat.');
        }

        if (! $peminjaman->isOverdue()) {
            return redirect()
                ->back()
                ->with('error', "Transaksi #{$peminjaman->id_peminjaman} belum melewati jadwal kembali (" . $peminjaman->jadwal_kembali?->format('d M Y, H:i') . ').');
        }

        $peminjaman->update([
            'status_peminjaman' => 'Terlambat',
        ]);

        return redirect()
            ->back()
            ->with('success', "Status transaksi #{$peminjaman->id_peminjaman} diperbarui menjadi Terlambat.");
    }

    public function kirimPeringatan(Request $request, Peminjaman $peminjaman): RedirectResponse
    {
        $validated = $request->validate([
            'pesan_peringatan' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'pesan_peringatan.required' => 'Pesan peringatan wajib diisi.',
            'pesan_peringatan.min' => 'Pesan peringatan minimal 5 karakter.',
            'pesan_peringatan.max' => 'Pesan peringatan maksimal 1000 karakter.',
        ]);

        $result = DB::transaction(function () use ($peminjaman, $validated): array {
            $locked = Peminjaman::where('id_peminjaman', $peminjaman->id_peminjaman)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($locked->status_peminjaman, ['Dipinjam', 'Terlambat'], true)) {
                return [
                    'ok' => false,
                    'message' => "Transaksi #{$locked->id_peminjaman} tidak sedang berstatus Dipinjam atau Terlambat sehingga peringatan tidak dapat dikirim.",
                ];
            }

            if (! $locked->isOverdue()) {
                return [
                    'ok' => false,
                    'message' => "Transaksi #{$locked->id_peminjaman} belum melewati jadwal kembali sehingga peringatan keterlambatan belum dapat dikirim.",
                ];
            }

            $locked->update([
                'pesan_peringatan' => trim($validated['pesan_peringatan']),
                'peringatan_dikirim_at' => now(),
                'status_peminjaman' => 'Terlambat',
            ]);

            return [
                'ok' => true,
                'message' => "Peringatan keterlambatan untuk transaksi #{$locked->id_peminjaman} berhasil dikirim dan status otomatis diperbarui menjadi Terlambat.",
            ];
        });

        return redirect()
            ->back()
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
