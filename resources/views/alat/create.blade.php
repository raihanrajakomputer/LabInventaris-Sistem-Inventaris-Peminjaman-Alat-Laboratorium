@extends('layouts.app')

@section('title', 'Tambah Alat')
@section('header', 'Tambah Alat Laboratorium')
@section('subheader', 'Catat peralatan baru ke dalam inventaris laboratorium')

@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6">
        @if ($daftarLab->isEmpty())
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 mb-5">
                Belum ada data laboratorium. Silakan <a href="{{ route('laboratorium.create') }}" class="font-bold underline">tambahkan laboratorium terlebih dahulu</a> sebelum menambah alat.
            </div>
        @endif

        <form method="POST" action="{{ route('alat.store') }}" class="space-y-5">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="kode_alat" class="block text-sm font-semibold text-slate-700 mb-1.5">Kode Alat <span class="text-rose-500">*</span></label>
                    <input type="text" id="kode_alat" name="kode_alat" value="{{ old('kode_alat') }}" required maxlength="50"
                           placeholder="Contoh: ALT-JARKOM-001"
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-mono focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none">
                </div>

                <div>
                    <label for="id_lab" class="block text-sm font-semibold text-slate-700 mb-1.5">Laboratorium <span class="text-rose-500">*</span></label>
                    <select id="id_lab" name="id_lab" required
                            class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none bg-white">
                        <option value="">-- Pilih Laboratorium --</option>
                        @foreach ($daftarLab as $lab)
                            <option value="{{ $lab->id_lab }}" @selected(old('id_lab') == $lab->id_lab)>
                                {{ $lab->nama_lab }} ({{ $lab->lokasi }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label for="nama_alat" class="block text-sm font-semibold text-slate-700 mb-1.5">Nama Alat <span class="text-rose-500">*</span></label>
                <input type="text" id="nama_alat" name="nama_alat" value="{{ old('nama_alat') }}" required maxlength="100"
                       placeholder="Contoh: Mikroskop Binokuler Olympus CX23"
                       class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="stok_tersedia" class="block text-sm font-semibold text-slate-700 mb-1.5">Stok Tersedia <span class="text-rose-500">*</span></label>
                    <input type="number" id="stok_tersedia" name="stok_tersedia" value="{{ old('stok_tersedia', 1) }}" required min="0"
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none">
                </div>

                <div>
                    <label for="kondisi_umum" class="block text-sm font-semibold text-slate-700 mb-1.5">Kondisi Umum <span class="text-rose-500">*</span></label>
                    <select id="kondisi_umum" name="kondisi_umum" required
                            class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none bg-white">
                        @foreach (['Baik', 'Rusak Ringan', 'Rusak Berat'] as $opt)
                            <option value="{{ $opt }}" @selected(old('kondisi_umum', 'Baik') === $opt)>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="pt-3 flex items-center justify-end gap-3 border-t border-slate-100">
                <a href="{{ route('alat.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition">Batal</a>
                <button type="submit" class="rounded-xl bg-indigo-600 hover:bg-indigo-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-indigo-500/20 transition">Simpan Alat</button>
            </div>
        </form>
    </div>
</div>
@endsection
