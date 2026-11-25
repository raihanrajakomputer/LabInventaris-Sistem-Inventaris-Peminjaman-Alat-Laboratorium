@extends('layouts.app')

@section('title', 'Dashboard')
@section('header', $isMahasiswa ? 'Dashboard Mahasiswa' : 'Dashboard Inventaris')
@section('subheader', $isMahasiswa ? 'Ringkasan aktivitas peminjaman alat laboratorium milik Anda' : 'Ringkasan ketersediaan alat laboratorium dan aktivitas peminjaman terbaru')

@section('content')
@if ($isMahasiswa)
    <!-- KPI Cards Khusus Mahasiswa -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Peminjaman Saya</p>
                    <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($totalPeminjamanSaya) }}</p>
                    <p class="mt-1.5 text-xs text-slate-500">Seluruh riwayat transaksi Anda</p>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Sedang Dipinjam</p>
                    <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($totalPeminjamanAktif) }}</p>
                    <p class="mt-1.5 text-xs text-slate-500">
                        @if ($totalTerlambat > 0)
                            <span class="font-semibold text-rose-600">{{ $totalTerlambat }} transaksi terlambat</span>
                        @else
                            <span class="text-emerald-600 font-medium">Tidak ada keterlambatan</span>
                        @endif
                    </p>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Telah Dikembalikan</p>
                    <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($totalDikembalikan) }}</p>
                    <p class="mt-1.5 text-xs text-slate-500">Transaksi yang sudah selesai</p>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Alat Siap Dipinjam</p>
                    <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($totalAlatTersedia) }}</p>
                    <p class="mt-1.5 text-xs text-slate-500">Jenis alat dengan stok &gt; 0</p>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <!-- Tabel Riwayat Peminjaman Mahasiswa -->
        <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Riwayat Peminjaman Terbaru Saya</h2>
                    <p class="text-xs text-slate-500">Menampilkan transaksi atas NIM <span class="font-mono font-semibold text-indigo-700">{{ $authUser['identifier'] }}</span></p>
                </div>
                <a href="{{ route('peminjaman.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">Lihat Semua &rarr;</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-100 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                        <th class="px-5 py-3">ID &amp; Alat</th>
                        <th class="px-4 py-3">Petugas</th>
                        <th class="px-4 py-3">Jadwal Kembali</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse ($peminjamanTerbaru as $row)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-mono font-semibold text-slate-400">#{{ $row->id_peminjaman }}</span>
                                    <span class="font-semibold text-slate-900">{{ $row->nama_alat }}</span>
                                </div>
                                <p class="text-xs text-slate-500">{{ $row->kode_alat }} &middot; <span class="font-semibold text-slate-700">{{ $row->jumlah_pinjam }} unit</span></p>
                            </td>
                            <td class="px-4 py-3.5 text-xs font-medium text-slate-700">{{ $row->nama_petugas }}</td>
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <p class="text-xs font-medium text-slate-700">{{ $row->jadwal_kembali?->format('d M Y, H:i') }}</p>
                                @if ($row->status_peminjaman !== 'Dikembalikan' && $row->jadwal_kembali && $row->jadwal_kembali->isPast())
                                    <span class="text-[11px] font-semibold text-rose-600">Melewati jadwal</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                @if ($row->status_peminjaman === 'Dikembalikan')
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Dikembalikan</span>
                                @elseif ($row->status_peminjaman === 'Terlambat')
                                    <span class="inline-flex items-center rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-600/20">Terlambat</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20">Dipinjam</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                <a href="{{ route('peminjaman.show', $row->id_peminjaman) }}"
                                   class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <p class="text-sm font-semibold text-slate-600">Anda belum memiliki riwayat peminjaman</p>
                                <p class="text-xs text-slate-400 mt-1">Ajukan peminjaman alat laboratorium dengan mudah melalui formulir di bawah.</p>
                                <a href="{{ route('peminjaman.create') }}" class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-4 py-2 transition">
                                    + Buat Peminjaman Baru
                                </a>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Side Panel Mahasiswa -->
        <div class="space-y-6">
            <div class="bg-gradient-to-br from-indigo-900 to-slate-900 rounded-2xl p-6 text-white shadow-sm space-y-4">
                <div>
                    <span class="inline-flex items-center rounded-full bg-sky-400/20 px-2.5 py-0.5 text-[11px] font-bold text-sky-200">
                        Akun Mahasiswa
                    </span>
                    <h3 class="mt-2 text-lg font-bold">{{ $authUser['name'] }}</h3>
                    <p class="text-xs font-mono text-slate-300">NIM: {{ $authUser['identifier'] }}</p>
                </div>
                <p class="text-xs text-slate-300 leading-relaxed">
                    Untuk pengembalian alat, serahkan alat ke Petugas / Asisten Lab agar status peminjaman diperbarui menjadi Dikembalikan.
                </p>
                <div class="grid grid-cols-1 gap-2 pt-1">
                    <a href="{{ route('peminjaman.create') }}" class="rounded-xl bg-indigo-500 hover:bg-indigo-400 px-4 py-2.5 text-xs font-bold text-center transition">
                        + Buat Peminjaman Baru
                    </a>
                    <a href="{{ route('alat.index') }}" class="rounded-xl bg-white/10 hover:bg-white/20 px-4 py-2.5 text-xs font-semibold text-center transition">
                        Lihat Daftar Alat Tersedia
                    </a>
                </div>
            </div>
        </div>
    </div>
@else
    <!-- KPI Cards Petugas / Asisten Lab (Keseluruhan) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs hover:shadow-md transition">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Jenis Alat</p>
                    <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($totalJenisAlat) }}</p>
                    <p class="mt-1.5 text-xs text-slate-500">
                        <span class="font-semibold text-indigo-600">{{ number_format($totalStokTersedia) }} unit</span> stok tersedia
                    </p>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs hover:shadow-md transition">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Peminjaman Aktif</p>
                    <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($totalPeminjamanAktif) }}</p>
                    <p class="mt-1.5 text-xs text-slate-500">
                        @if ($totalTerlambat > 0)
                            <span class="font-semibold text-rose-600">{{ $totalTerlambat }} transaksi terlambat</span>
                        @else
                            <span class="text-emerald-600 font-medium">Tidak ada yang terlambat</span>
                        @endif
                    </p>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs hover:shadow-md transition">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Telah Dikembalikan</p>
                    <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($totalDikembalikan) }}</p>
                    <p class="mt-1.5 text-xs text-slate-500">Transaksi selesai &amp; stok pulih</p>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs hover:shadow-md transition">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Laboratorium</p>
                    <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($totalLab) }}</p>
                    <p class="mt-1.5 text-xs text-slate-500">
                        <span class="font-semibold text-slate-700">{{ number_format($totalPeminjam) }}</span> peminjam terdaftar
                    </p>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Grid Petugas -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Peminjaman Terbaru</h2>
                    <p class="text-xs text-slate-500">Bersumber dari SQL View <code class="px-1.5 py-0.5 rounded bg-slate-100 text-indigo-700 font-mono text-[11px]">v_laporan_peminjaman</code></p>
                </div>
                <a href="{{ route('peminjaman.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 inline-flex items-center gap-1">
                    Lihat Semua &rarr;
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-100 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                        <th class="px-5 py-3">ID &amp; Peminjam</th>
                        <th class="px-4 py-3">Alat</th>
                        <th class="px-4 py-3">Jadwal Kembali</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse ($peminjamanTerbaru as $row)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-mono font-semibold text-slate-400">#{{ $row->id_peminjaman }}</span>
                                    <span class="font-semibold text-slate-900">{{ $row->nama_peminjam }}</span>
                                </div>
                                <p class="text-xs text-slate-500">{{ $row->nim_nip }} &middot; {{ $row->status_peminjam }}</p>
                            </td>
                            <td class="px-4 py-3.5">
                                <p class="font-medium text-slate-800">{{ $row->nama_alat }}</p>
                                <p class="text-xs text-slate-500">{{ $row->kode_alat }} &middot; <span class="font-semibold text-slate-700">{{ $row->jumlah_pinjam }} unit</span></p>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <p class="text-xs font-medium text-slate-700">{{ $row->jadwal_kembali?->format('d M Y, H:i') }}</p>
                                @if ($row->status_peminjaman !== 'Dikembalikan' && $row->jadwal_kembali && $row->jadwal_kembali->isPast())
                                    <span class="text-[11px] font-semibold text-rose-600">Melewati jadwal</span>
                                @else
                                    <span class="text-[11px] text-slate-400">Pinjam: {{ $row->tanggal_pinjam?->format('d M Y') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                @if ($row->status_peminjaman === 'Dikembalikan')
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Dikembalikan</span>
                                @elseif ($row->status_peminjaman === 'Terlambat')
                                    <span class="inline-flex items-center rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-600/20">Terlambat</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20">Dipinjam</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right whitespace-nowrap space-x-1.5">
                                <a href="{{ route('peminjaman.show', $row->id_peminjaman) }}"
                                   class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                                    Detail
                                </a>
                                @if (in_array($row->status_peminjaman, ['Dipinjam', 'Terlambat']))
                                    <a href="{{ route('peminjaman.kembalikan.form', $row->id_peminjaman) }}"
                                       class="inline-flex items-center rounded-lg bg-emerald-600 hover:bg-emerald-700 px-2.5 py-1.5 text-xs font-semibold text-white transition">
                                        Kembalikan
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <p class="text-sm font-semibold text-slate-600">Belum ada data transaksi peminjaman</p>
                                <a href="{{ route('peminjaman.create') }}" class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-4 py-2 transition">
                                    + Buat Peminjaman Pertama
                                </a>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Perhatian Inventaris</h2>
                        <p class="text-xs text-slate-500">Stok menipis (&le; 2) atau kondisi rusak</p>
                    </div>
                    <a href="{{ route('alat.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">Semua</a>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($alatPerhatian as $item)
                        <div class="px-5 py-3 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-800 truncate">{{ $item->nama_alat }}</p>
                                <p class="text-xs text-slate-500 truncate">{{ $item->kode_alat }} &middot; {{ $item->nama_lab }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-bold {{ $item->stok_tersedia == 0 ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700' }}">
                                    Stok: {{ $item->stok_tersedia }}
                                </span>
                                @if ($item->kondisi_umum !== 'Baik')
                                    <p class="text-[11px] font-medium text-rose-600 mt-0.5">{{ $item->kondisi_umum }}</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-8 text-center">
                            <p class="text-xs font-medium text-emerald-700 bg-emerald-50 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full">
                                Semua stok &amp; kondisi alat dalam keadaan baik
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="bg-gradient-to-br from-slate-900 to-indigo-950 rounded-2xl p-5 text-white shadow-sm">
                <h3 class="text-sm font-bold">Akses Cepat Data Master</h3>
                <p class="text-xs text-slate-300 mt-1">Kelola ruangan lab, inventaris alat, dan data peminjam.</p>
                <div class="mt-4 grid grid-cols-2 gap-2.5">
                    <a href="{{ route('alat.create') }}" class="rounded-xl bg-white/10 hover:bg-white/20 px-3 py-2.5 text-xs font-semibold transition text-center">+ Tambah Alat</a>
                    <a href="{{ route('laboratorium.create') }}" class="rounded-xl bg-white/10 hover:bg-white/20 px-3 py-2.5 text-xs font-semibold transition text-center">+ Tambah Lab</a>
                    <a href="{{ route('peminjam.create') }}" class="rounded-xl bg-white/10 hover:bg-white/20 px-3 py-2.5 text-xs font-semibold transition text-center">+ Peminjam</a>
                    <a href="{{ route('petugas.create') }}" class="rounded-xl bg-white/10 hover:bg-white/20 px-3 py-2.5 text-xs font-semibold transition text-center">+ Petugas</a>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection
