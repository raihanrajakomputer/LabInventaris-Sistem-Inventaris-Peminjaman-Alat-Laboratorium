@extends('layouts.app')

@section('title', $isMahasiswa ? 'Riwayat Peminjaman Saya' : 'Peminjaman & Laporan')
@section('header', $isMahasiswa ? 'Riwayat Peminjaman Saya' : 'Transaksi & Laporan Peminjaman')
@section('subheader', $isMahasiswa ? 'Daftar seluruh transaksi peminjaman alat laboratorium atas akun Anda' : 'Data laporan diambil dari SQL View v_laporan_peminjaman dengan pembaruan stok otomatis via Trigger')

@section('content')
<!-- Status Filter Tabs -->
<div class="no-print flex flex-wrap items-center justify-between gap-3">
    <div class="flex flex-wrap items-center gap-2">
        @php
            $tabs = [
                '' => ['label' => 'Semua Transaksi', 'count' => $ringkasan['semua'], 'active' => 'bg-slate-900 text-white'],
                'Dipinjam' => ['label' => 'Dipinjam', 'count' => $ringkasan['Dipinjam'], 'active' => 'bg-amber-600 text-white'],
                'Terlambat' => ['label' => 'Terlambat', 'count' => $ringkasan['Terlambat'], 'active' => 'bg-rose-600 text-white'],
                'Dikembalikan' => ['label' => 'Dikembalikan', 'count' => $ringkasan['Dikembalikan'], 'active' => 'bg-emerald-600 text-white'],
            ];
        @endphp
        @foreach ($tabs as $key => $tab)
            <a href="{{ route('peminjaman.index', array_filter(['status' => $key, 'search' => $search, 'dari' => $dari, 'sampai' => $sampai])) }}"
               class="inline-flex items-center gap-2 rounded-xl px-3.5 py-2 text-xs font-semibold transition {{ $status === $key ? $tab['active'] . ' shadow-xs' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                <span>{{ $tab['label'] }}</span>
                <span class="rounded-full px-2 py-0.5 text-[11px] {{ $status === $key ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700' }}">{{ $tab['count'] }}</span>
            </a>
        @endforeach
    </div>

    <div class="flex items-center gap-2">
        <button type="button" onclick="window.print()"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs sm:text-sm font-semibold px-3.5 py-2 transition">
            <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
            </svg>
            Cetak
        </button>
        <a href="{{ route('peminjaman.create') }}"
           class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs sm:text-sm font-semibold px-4 py-2 shadow-sm shadow-indigo-500/20 transition">
            + Peminjaman Baru
        </a>
    </div>
</div>

<!-- Print Header -->
<div class="hidden print:block mb-4 border-b border-slate-300 pb-3">
    <h1 class="text-xl font-bold text-slate-900">{{ $isMahasiswa ? 'Riwayat Peminjaman Alat Laboratorium' : 'Laporan Peminjaman Alat Laboratorium' }}</h1>
    <p class="text-xs text-slate-600">Dicetak pada: {{ now()->format('d M Y H:i') }} | Sumber: View v_laporan_peminjaman</p>
</div>

<!-- Main Table Card -->
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
    <!-- Filter Bar -->
    <div class="no-print p-5 border-b border-slate-100">
        <form method="GET" action="{{ route('peminjaman.index') }}" class="flex flex-wrap items-center gap-2.5">
            @if ($status !== '')
                <input type="hidden" name="status" value="{{ $status }}">
            @endif
            <div class="relative flex-1 min-w-[220px] max-w-sm">
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="{{ $isMahasiswa ? 'Cari ID, nama/kode alat, petugas...' : 'Cari ID, NIM/NIP, peminjam, alat, petugas...' }}"
                       class="w-full rounded-xl border border-slate-200 bg-slate-50/70 pl-9 pr-3 py-2 text-sm focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
            </div>
            <div class="flex items-center gap-1.5 text-xs text-slate-500">
                <span>Dari:</span>
                <input type="date" name="dari" value="{{ $dari }}" class="rounded-xl border border-slate-200 bg-slate-50/70 px-2.5 py-2 text-xs text-slate-700 focus:bg-white focus:border-indigo-500 outline-none">
            </div>
            <div class="flex items-center gap-1.5 text-xs text-slate-500">
                <span>Sampai:</span>
                <input type="date" name="sampai" value="{{ $sampai }}" class="rounded-xl border border-slate-200 bg-slate-50/70 px-2.5 py-2 text-xs text-slate-700 focus:bg-white focus:border-indigo-500 outline-none">
            </div>
            <button type="submit" class="rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold px-4 py-2 transition">Filter</button>
            @if ($search !== '' || $status !== '' || $dari !== '' || $sampai !== '')
                <a href="{{ route('peminjaman.index') }}" class="rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-sm font-medium px-3 py-2">Reset</a>
            @endif
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
            <tr class="bg-slate-50/80 border-b border-slate-100 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                <th class="px-5 py-3.5">ID</th>
                @if (! $isMahasiswa)
                    <th class="px-4 py-3.5">Peminjam</th>
                @endif
                <th class="px-4 py-3.5">Alat &amp; Jumlah</th>
                <th class="px-4 py-3.5">Kondisi (Pinjam / Kembali)</th>
                <th class="px-4 py-3.5">Waktu Pinjam &amp; Kembali</th>
                <th class="px-4 py-3.5">Petugas</th>
                <th class="px-4 py-3.5">Status</th>
                <th class="px-5 py-3.5 text-right no-print">Aksi</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
            @forelse ($laporan as $row)
                <tr class="hover:bg-slate-50/70 transition align-top">
                    <td class="px-5 py-4 whitespace-nowrap font-mono text-xs font-bold text-slate-500">
                        #{{ $row->id_peminjaman }}
                    </td>
                    @if (! $isMahasiswa)
                        <td class="px-4 py-4">
                            <p class="font-semibold text-slate-900">{{ $row->nama_peminjam }}</p>
                            <p class="text-xs text-slate-500 font-mono">{{ $row->nim_nip }}</p>
                            <span class="mt-1 inline-block text-[11px] font-medium px-2 py-0.5 rounded-md {{ $row->status_peminjam === 'Dosen' ? 'bg-violet-50 text-violet-700' : 'bg-sky-50 text-sky-700' }}">
                                {{ $row->status_peminjam }}
                            </span>
                        </td>
                    @endif
                    <td class="px-4 py-4">
                        <p class="font-semibold text-slate-800">{{ $row->nama_alat }}</p>
                        <p class="text-xs text-slate-500">
                            <span class="font-mono">{{ $row->kode_alat }}</span> &middot;
                            <span class="font-bold text-indigo-700">{{ $row->jumlah_pinjam }} unit</span>
                        </p>
                    </td>
                    <td class="px-4 py-4 text-xs">
                        <p class="text-slate-600"><span class="text-slate-400">Pinjam:</span> <span class="font-medium text-slate-800">{{ $row->kondisi_saat_pinjam }}</span></p>
                        <p class="mt-1 text-slate-600">
                            <span class="text-slate-400">Kembali:</span>
                            @if ($row->kondisi_saat_kembali)
                                <span class="font-medium text-slate-800">{{ $row->kondisi_saat_kembali }}</span>
                            @else
                                <span class="italic text-slate-400">Belum dikembalikan</span>
                            @endif
                        </p>
                    </td>
                    <td class="px-4 py-4 text-xs whitespace-nowrap space-y-0.5">
                        <p class="text-slate-600"><span class="text-slate-400">Pinjam:</span> {{ $row->tanggal_pinjam?->format('d M Y H:i') }}</p>
                        <p class="text-slate-700 font-medium"><span class="text-slate-400 font-normal">Jadwal:</span> {{ $row->jadwal_kembali?->format('d M Y H:i') }}</p>
                        @if ($row->tanggal_kembali_aktual)
                            <p class="text-emerald-700 font-medium"><span class="text-emerald-500 font-normal">Aktual:</span> {{ $row->tanggal_kembali_aktual->format('d M Y H:i') }}</p>
                        @elseif ($row->jadwal_kembali && $row->jadwal_kembali->isPast())
                            <p class="text-rose-600 font-semibold">Lewat batas waktu!</p>
                        @endif
                    </td>
                    <td class="px-4 py-4 text-xs text-slate-700 font-medium">
                        {{ $row->nama_petugas }}
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap">
                        @if ($row->status_peminjaman === 'Dikembalikan')
                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Dikembalikan</span>
                        @elseif ($row->status_peminjaman === 'Terlambat')
                            <span class="inline-flex items-center rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-600/20">Terlambat</span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20">Dipinjam</span>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-right whitespace-nowrap no-print">
                        <div class="inline-flex flex-col sm:flex-row items-end sm:items-center gap-1.5">
                            <a href="{{ route('peminjaman.show', $row->id_peminjaman) }}"
                               class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                                Detail
                            </a>
                            @if (! $isMahasiswa && in_array($row->status_peminjaman, ['Dipinjam', 'Terlambat']))
                                <a href="{{ route('peminjaman.kembalikan.form', $row->id_peminjaman) }}"
                                   class="inline-flex items-center rounded-lg bg-emerald-600 hover:bg-emerald-700 px-2.5 py-1.5 text-xs font-semibold text-white shadow-xs transition">
                                    Kembalikan
                                </a>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $isMahasiswa ? 7 : 8 }}" class="px-6 py-12 text-center text-slate-500">
                        Belum ada data peminjaman yang sesuai dengan filter.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="no-print px-6 py-3.5 bg-slate-50/70 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
        <span>Sumber data: SQL View <code class="font-mono text-indigo-700">v_laporan_peminjaman</code></span>
        @if ($laporan->hasPages())
            <div>{{ $laporan->links() }}</div>
        @endif
    </div>
</div>
@endsection
