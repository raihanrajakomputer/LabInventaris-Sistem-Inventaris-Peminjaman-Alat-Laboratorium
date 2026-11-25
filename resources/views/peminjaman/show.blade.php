@extends('layouts.app')

@section('title', 'Detail Peminjaman #' . $peminjaman->id_peminjaman)
@section('header', 'Detail Transaksi #' . $peminjaman->id_peminjaman)
@section('subheader', 'Rincian peminjam, petugas, jadwal, dan daftar alat yang dipinjam')

@section('content')
<div class="space-y-6">
    <!-- Action Header -->
    <div class="no-print flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('peminjaman.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-600 hover:text-slate-900">
            &larr; Kembali ke {{ $isMahasiswa ? 'Riwayat Peminjaman Saya' : 'Laporan Peminjaman' }}
        </a>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button" onclick="window.print()"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 px-3.5 py-2 text-xs sm:text-sm font-semibold text-slate-700 transition">
                Cetak Bukti
            </button>

            @if (! $isMahasiswa && $peminjaman->status_peminjaman === 'Dipinjam')
                <form action="{{ route('peminjaman.terlambat', $peminjaman) }}" method="POST"
                      onsubmit="return confirm('Tandai transaksi #{{ $peminjaman->id_peminjaman }} sebagai Terlambat?')">
                    @csrf
                    @method('PATCH')
                    <button type="submit"
                            class="inline-flex items-center rounded-xl border border-rose-200 bg-rose-50 hover:bg-rose-100 px-3.5 py-2 text-xs sm:text-sm font-semibold text-rose-700 transition">
                        Tandai Terlambat
                    </button>
                </form>
            @endif

            @if (! $isMahasiswa && in_array($peminjaman->status_peminjaman, ['Dipinjam', 'Terlambat']))
                <a href="{{ route('peminjaman.kembalikan.form', $peminjaman) }}"
                   class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 px-4 py-2 text-xs sm:text-sm font-semibold text-white shadow-sm shadow-emerald-600/20 transition">
                    Kembalikan Alat
                </a>
            @endif
        </div>
    </div>

    <!-- Summary Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6">
        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 pb-5">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Nomor Transaksi</p>
                <h2 class="text-2xl font-extrabold text-slate-900 font-mono mt-0.5">#{{ $peminjaman->id_peminjaman }}</h2>
            </div>
            <div>
                @if ($peminjaman->status_peminjaman === 'Dikembalikan')
                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-3.5 py-1.5 text-sm font-bold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Dikembalikan</span>
                @elseif ($peminjaman->status_peminjaman === 'Terlambat')
                    <span class="inline-flex items-center rounded-full bg-rose-50 px-3.5 py-1.5 text-sm font-bold text-rose-700 ring-1 ring-inset ring-rose-600/20">Terlambat</span>
                @else
                    <span class="inline-flex items-center rounded-full bg-amber-50 px-3.5 py-1.5 text-sm font-bold text-amber-700 ring-1 ring-inset ring-amber-600/20">Dipinjam</span>
                @endif
            </div>
        </div>

        <div class="mt-5 grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Peminjam</p>
                <p class="font-bold text-slate-900">{{ $peminjaman->peminjam?->nama_peminjam }}</p>
                <p class="text-xs text-slate-600 font-mono mt-0.5">{{ $peminjaman->peminjam?->nim_nip }}</p>
                <p class="text-xs text-slate-500 mt-0.5">{{ $peminjaman->peminjam?->status_peminjam }} &middot; {{ $peminjaman->peminjam?->no_hp }}</p>
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Petugas Melayani</p>
                <p class="font-bold text-slate-900">{{ $peminjaman->petugas?->nama_petugas }}</p>
                <p class="text-xs text-slate-500 font-mono mt-0.5">{{ $peminjaman->petugas?->username }}</p>
            </div>

            <div class="space-y-1.5 text-xs">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Timeline</p>
                <p class="text-slate-600"><span class="text-slate-400">Tanggal Pinjam:</span> <span class="font-semibold text-slate-800">{{ $peminjaman->tanggal_pinjam?->format('d M Y, H:i') }}</span></p>
                <p class="text-slate-600"><span class="text-slate-400">Jadwal Kembali:</span> <span class="font-semibold text-slate-800">{{ $peminjaman->jadwal_kembali?->format('d M Y, H:i') }}</span></p>
                <p class="text-slate-600">
                    <span class="text-slate-400">Kembali Aktual:</span>
                    @if ($peminjaman->tanggal_kembali_aktual)
                        <span class="font-bold text-emerald-700">{{ $peminjaman->tanggal_kembali_aktual->format('d M Y, H:i') }}</span>
                    @else
                        <span class="italic text-slate-400">Belum dikembalikan</span>
                    @endif
                </p>
            </div>
        </div>
    </div>

    <!-- Detail Alat Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">Daftar Alat Dipinjam ({{ $peminjaman->details->count() }} item)</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                <tr class="bg-slate-50/80 border-b border-slate-100 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                    <th class="px-6 py-3.5">Kode Alat</th>
                    <th class="px-4 py-3.5">Nama Alat &amp; Lab</th>
                    <th class="px-4 py-3.5 text-center">Jumlah</th>
                    <th class="px-4 py-3.5">Kondisi Saat Pinjam</th>
                    <th class="px-6 py-3.5">Kondisi Saat Kembali</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                @foreach ($peminjaman->details as $detail)
                    <tr class="hover:bg-slate-50/60">
                        <td class="px-6 py-4 font-mono text-xs font-semibold text-slate-600">{{ $detail->alat?->kode_alat }}</td>
                        <td class="px-4 py-4">
                            <p class="font-semibold text-slate-900">{{ $detail->alat?->nama_alat }}</p>
                            <p class="text-xs text-slate-500">{{ $detail->alat?->laboratorium?->nama_lab }} &middot; {{ $detail->alat?->laboratorium?->lokasi }}</p>
                        </td>
                        <td class="px-4 py-4 text-center font-bold text-indigo-700">{{ $detail->jumlah_pinjam }} unit</td>
                        <td class="px-4 py-4 text-slate-700">{{ $detail->kondisi_saat_pinjam }}</td>
                        <td class="px-6 py-4">
                            @if ($detail->kondisi_saat_kembali)
                                <span class="font-medium text-slate-800">{{ $detail->kondisi_saat_kembali }}</span>
                            @else
                                <span class="text-xs italic text-slate-400">Menunggu pengembalian</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
