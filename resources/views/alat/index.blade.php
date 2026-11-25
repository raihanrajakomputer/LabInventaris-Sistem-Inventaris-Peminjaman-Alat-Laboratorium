@extends('layouts.app')

@php
    $isPetugas = session('auth_user.role') === 'petugas';
@endphp

@section('title', 'Daftar Alat Laboratorium')
@section('header', 'Daftar Inventaris Alat')
@section('subheader', 'Menampilkan daftar alat dari SQL View v_daftar_alat beserta lokasi laboratorium dan stok')

@section('content')
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
    <div class="p-5 border-b border-slate-100 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <form method="GET" action="{{ route('alat.index') }}" class="flex flex-wrap items-center gap-2.5 flex-1">
            <div class="relative flex-1 min-w-[200px] max-w-xs">
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari kode, nama alat, lab..."
                       class="w-full rounded-xl border border-slate-200 bg-slate-50/70 pl-9 pr-3 py-2 text-sm focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
            </div>

            <select name="lab" class="rounded-xl border border-slate-200 bg-slate-50/70 px-3 py-2 text-sm focus:bg-white focus:border-indigo-500 outline-none">
                <option value="">Semua Laboratorium</option>
                @foreach ($daftarLab as $lab)
                    <option value="{{ $lab->nama_lab }}" @selected($namaLab === $lab->nama_lab)>{{ $lab->nama_lab }}</option>
                @endforeach
            </select>

            <select name="kondisi" class="rounded-xl border border-slate-200 bg-slate-50/70 px-3 py-2 text-sm focus:bg-white focus:border-indigo-500 outline-none">
                <option value="">Semua Kondisi</option>
                @foreach (['Baik', 'Rusak Ringan', 'Rusak Berat'] as $opt)
                    <option value="{{ $opt }}" @selected($kondisi === $opt)>{{ $opt }}</option>
                @endforeach
            </select>

            <button type="submit" class="rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold px-4 py-2 transition">Filter</button>
            @if ($search !== '' || $kondisi !== '' || $namaLab !== '')
                <a href="{{ route('alat.index') }}" class="rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-sm font-medium px-3 py-2">Reset</a>
            @endif
        </form>

        @if ($isPetugas)
            <a href="{{ route('alat.create') }}"
               class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 shadow-sm shadow-indigo-500/20 transition shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Alat
            </a>
        @else
            <span class="inline-flex items-center gap-1.5 rounded-full bg-sky-50 border border-sky-200 px-3 py-1.5 text-xs font-semibold text-sky-700 shrink-0">
                Katalog Alat (Read-Only)
            </span>
        @endif
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
            <tr class="bg-slate-50/80 border-b border-slate-100 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                <th class="px-6 py-3.5">Kode Alat</th>
                <th class="px-4 py-3.5">Nama Alat</th>
                <th class="px-4 py-3.5">Laboratorium &amp; Lokasi</th>
                <th class="px-4 py-3.5 text-center">Stok Tersedia</th>
                <th class="px-4 py-3.5">Kondisi Umum</th>
                @if ($isPetugas)
                    <th class="px-6 py-3.5 text-right">Aksi</th>
                @endif
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
            @forelse ($alat as $item)
                <tr class="hover:bg-slate-50/70 transition">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="font-mono text-xs font-semibold px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700">{{ $item->kode_alat }}</span>
                    </td>
                    <td class="px-4 py-4 font-semibold text-slate-900">{{ $item->nama_alat }}</td>
                    <td class="px-4 py-4">
                        <p class="font-medium text-slate-800">{{ $item->nama_lab }}</p>
                        <p class="text-xs text-slate-500">{{ $item->lokasi }}</p>
                    </td>
                    <td class="px-4 py-4 text-center whitespace-nowrap">
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold
                            {{ $item->stok_tersedia == 0 ? 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20' : ($item->stok_tersedia <= 2 ? 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20' : 'bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-600/20') }}">
                            {{ $item->stok_tersedia }} unit
                        </span>
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap">
                        @if ($item->kondisi_umum === 'Baik')
                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Baik</span>
                        @elseif ($item->kondisi_umum === 'Rusak Ringan')
                            <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20">Rusak Ringan</span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-600/20">Rusak Berat</span>
                        @endif
                    </td>
                    @if ($isPetugas)
                        <td class="px-6 py-4 text-right whitespace-nowrap space-x-2">
                            <a href="{{ route('alat.edit', $item->id_alat) }}"
                               class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                                Edit
                            </a>
                            <form action="{{ route('alat.destroy', $item->id_alat) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Yakin ingin menghapus alat {{ addslashes($item->nama_alat) }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="inline-flex items-center rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100 transition">
                                    Hapus
                                </button>
                            </form>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $isPetugas ? 6 : 5 }}" class="px-6 py-12 text-center text-slate-500">
                        Tidak ada data alat ditemukan.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="px-6 py-3.5 bg-slate-50/70 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
        <span>Sumber data: SQL View <code class="font-mono text-indigo-700">v_daftar_alat</code></span>
        @if ($alat->hasPages())
            <div>{{ $alat->links() }}</div>
        @endif
    </div>
</div>
@endsection
