@extends('layouts.app')

@section('title', 'Edit Petugas')
@section('header', 'Edit Petugas Laboratorium')
@section('subheader', 'Perbarui data petugas {{ $petugas->nama_petugas }}')

@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6">
        <form method="POST" action="{{ route('petugas.update', $petugas) }}" class="space-y-5">
            @csrf
            @method('PUT')
            <div>
                <label for="nama_petugas" class="block text-sm font-semibold text-slate-700 mb-1.5">Nama Petugas <span class="text-rose-500">*</span></label>
                <input type="text" id="nama_petugas" name="nama_petugas" value="{{ old('nama_petugas', $petugas->nama_petugas) }}" required maxlength="100"
                       class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="username" class="block text-sm font-semibold text-slate-700 mb-1.5">Username <span class="text-rose-500">*</span></label>
                    <input type="text" id="username" name="username" value="{{ old('username', $petugas->username) }}" required maxlength="50"
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-mono focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none">
                </div>

                <div>
                    <label for="password" class="block text-sm font-semibold text-slate-700 mb-1.5">Password Baru <span class="text-xs font-normal text-slate-400">(opsional)</span></label>
                    <input type="password" id="password" name="password" minlength="4" maxlength="100"
                           placeholder="Kosongkan jika tidak diubah"
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none">
                </div>
            </div>

            <div class="pt-3 flex items-center justify-end gap-3 border-t border-slate-100">
                <a href="{{ route('petugas.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition">Batal</a>
                <button type="submit" class="rounded-xl bg-indigo-600 hover:bg-indigo-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-indigo-500/20 transition">Perbarui</button>
            </div>
        </form>
    </div>
</div>
@endsection
