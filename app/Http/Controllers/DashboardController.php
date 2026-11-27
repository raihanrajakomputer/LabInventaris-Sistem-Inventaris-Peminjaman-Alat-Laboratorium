<?php

namespace App\Http\Controllers;

use App\Models\DaftarAlatView;
use App\Models\Laboratorium;
use App\Models\LaporanPeminjamanView;
use App\Models\Peminjam;
use App\Models\Peminjaman;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $authUser = $request->session()->get('auth_user');
        $isMahasiswa = ($authUser['role'] ?? null) === 'mahasiswa';

        if ($isMahasiswa) {
            $idPeminjam = (int) $authUser['id'];
            $nim = (string) $authUser['identifier'];

            $totalMenunggu = Peminjaman::where('id_peminjam', $idPeminjam)
                ->where('status_peminjaman', 'Menunggu')
                ->count();
            $totalPeminjamanAktif = Peminjaman::where('id_peminjam', $idPeminjam)
                ->whereIn('status_peminjaman', ['Dipinjam', 'Terlambat'])
                ->count();
            $totalTerlambat = Peminjaman::where('id_peminjam', $idPeminjam)
                ->where(function ($q) {
                    $q->where('status_peminjaman', 'Terlambat')
                        ->orWhere(function ($sub) {
                            $sub->where('status_peminjaman', 'Dipinjam')
                                ->where('jadwal_kembali', '<', now());
                        });
                })
                ->count();
            $totalDikembalikan = Peminjaman::where('id_peminjam', $idPeminjam)
                ->where('status_peminjaman', 'Dikembalikan')
                ->count();
            $totalDitolak = Peminjaman::where('id_peminjam', $idPeminjam)
                ->where('status_peminjaman', 'Ditolak')
                ->count();
            $totalAlatTersedia = DaftarAlatView::where('stok_tersedia', '>', 0)->count();

            // Menggunakan View SQL v_laporan_peminjaman khusus milik mahasiswa yang login
            $peminjamanTerbaru = LaporanPeminjamanView::query()
                ->where('nim_nip', $nim)
                ->orderByDesc('tanggal_pinjam')
                ->orderByDesc('id_peminjaman')
                ->limit(10)
                ->get();

            return view('dashboard', compact(
                'isMahasiswa',
                'authUser',
                'totalMenunggu',
                'totalPeminjamanAktif',
                'totalTerlambat',
                'totalDikembalikan',
                'totalDitolak',
                'totalAlatTersedia',
                'peminjamanTerbaru'
            ));
        }

        // Dashboard keseluruhan untuk Role Petugas / Asisten Lab
        $totalJenisAlat = DaftarAlatView::count();
        $totalStokTersedia = (int) DaftarAlatView::sum('stok_tersedia');
        $totalMenunggu = Peminjaman::where('status_peminjaman', 'Menunggu')->count();
        $totalPeminjamanAktif = Peminjaman::whereIn('status_peminjaman', ['Dipinjam', 'Terlambat'])->count();
        $totalTerlambat = Peminjaman::where(function ($q) {
            $q->where('status_peminjaman', 'Terlambat')
                ->orWhere(function ($sub) {
                    $sub->where('status_peminjaman', 'Dipinjam')
                        ->where('jadwal_kembali', '<', now());
                });
        })->count();
        $totalDikembalikan = Peminjaman::where('status_peminjaman', 'Dikembalikan')->count();
        $totalLab = Laboratorium::count();
        $totalPeminjam = Peminjam::count();

        $peminjamanTerbaru = LaporanPeminjamanView::query()
            ->orderByRaw("CASE WHEN status_peminjaman = 'Menunggu' THEN 0 ELSE 1 END")
            ->orderByDesc('tanggal_pinjam')
            ->orderByDesc('id_peminjaman')
            ->limit(10)
            ->get();

        $alatPerhatian = DaftarAlatView::query()
            ->where(function ($q) {
                $q->where('stok_tersedia', '<=', 2)
                    ->orWhere('kondisi_umum', '!=', 'Baik');
            })
            ->orderBy('stok_tersedia')
            ->orderBy('nama_alat')
            ->limit(6)
            ->get();

        return view('dashboard', compact(
            'isMahasiswa',
            'authUser',
            'totalJenisAlat',
            'totalStokTersedia',
            'totalMenunggu',
            'totalPeminjamanAktif',
            'totalTerlambat',
            'totalDikembalikan',
            'totalLab',
            'totalPeminjam',
            'peminjamanTerbaru',
            'alatPerhatian'
        ));
    }
}
