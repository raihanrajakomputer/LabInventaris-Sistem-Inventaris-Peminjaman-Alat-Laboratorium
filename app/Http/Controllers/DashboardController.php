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

            $totalPeminjamanSaya = Peminjaman::where('id_peminjam', $idPeminjam)->count();
            $totalPeminjamanAktif = Peminjaman::where('id_peminjam', $idPeminjam)
                ->whereIn('status_peminjaman', ['Dipinjam', 'Terlambat'])
                ->count();
            $totalTerlambat = Peminjaman::where('id_peminjam', $idPeminjam)
                ->where('status_peminjaman', 'Terlambat')
                ->count();
            $totalDikembalikan = Peminjaman::where('id_peminjam', $idPeminjam)
                ->where('status_peminjaman', 'Dikembalikan')
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
                'totalPeminjamanSaya',
                'totalPeminjamanAktif',
                'totalTerlambat',
                'totalDikembalikan',
                'totalAlatTersedia',
                'peminjamanTerbaru'
            ));
        }

        // Dashboard keseluruhan untuk Role Petugas / Asisten Lab
        $totalJenisAlat = DaftarAlatView::count();
        $totalStokTersedia = (int) DaftarAlatView::sum('stok_tersedia');
        $totalPeminjamanAktif = Peminjaman::whereIn('status_peminjaman', ['Dipinjam', 'Terlambat'])->count();
        $totalTerlambat = Peminjaman::where('status_peminjaman', 'Terlambat')->count();
        $totalDikembalikan = Peminjaman::where('status_peminjaman', 'Dikembalikan')->count();
        $totalLab = Laboratorium::count();
        $totalPeminjam = Peminjam::count();

        $peminjamanTerbaru = LaporanPeminjamanView::query()
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
