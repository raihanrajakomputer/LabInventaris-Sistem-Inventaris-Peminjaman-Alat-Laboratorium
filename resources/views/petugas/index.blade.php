@extends('layouts.app')

@section('title', 'Manajemen Petugas')
@section('header', 'Data Petugas Laboratorium')
@section('subheader', 'Kelola daftar petugas yang melayani transaksi peminjaman dan pengembalian')

@section('content')
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
    <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <form method="GET" action="{{ route('petugas.index') }}" class="flex items-center gap-2 flex-1 max-w-md">
            <div class="relative flex-1">
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari username atau nama petugas..."
                       class="w-full rounded-xl border border-slate-200 bg-slate-50/70 pl-9 pr-3 py-2 text-sm focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
            </div>
            <button type="submit" class="rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold px-4 py-2 transition">Cari</button>
            @if ($search !== '')
                <a href="{{ route('petugas.index') }}" class="rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-sm font-medium px-3 py-2">Reset</a>
            @endif
        </form>

        <a href="{{ route('petugas.create') }}"
           class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 shadow-sm shadow-indigo-500/20 transition shrink-0">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Tambah Petugas
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
            <tr class="bg-slate-50/80 border-b border-slate-100 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                <th class="px-6 py-3.5 w-16">ID</th>
                <th class="px-4 py-3.5">Username</th>
                <th class="px-4 py-3.5">Nama Petugas</th>
                <th class="px-4 py-3.5 text-center">Transaksi Dilayani</th>
                <th class="px-6 py-3.5 text-right">Aksi</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
            @forelse ($petugas as $item)
                <tr class="hover:bg-slate-50/70 transition">
                    <td class="px-6 py-4 font-mono text-xs text-slate-400">#{{ $item->id_petugas }}</td>
                    <td class="px-4 py-4 font-mono text-xs font-semibold text-indigo-700">{{ $item->username }}</td>
                    <td class="px-4 py-4 font-semibold text-slate-900">{{ $item->nama_petugas }}</td>
                    <td class="px-4 py-4 text-center font-semibold text-slate-700">{{ $item->peminjaman_count }}</td>
                    <td class="px-6 py-4 text-right whitespace-nowrap space-x-2">
                        <a href="{{ route('petugas.edit', $item) }}"
                           class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                            Edit
                        </a>
                        <form action="{{ route('petugas.destroy', $item) }}" method="POST" class="inline"
                              onsubmit="return confirm('Yakin ingin menghapus petugas {{ addslashes($item->nama_petugas) }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="inline-flex items-center rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100 transition">
                                Hapus
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                        Belum ada data petugas.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if ($petugas->hasPages())
        <div class="px-6 py-4 border-t border-slate-100">
            {{ $petugas->links() }}
        </div>
    @endif
</div>
@endsection
