@extends('layouts.app')

@section('title', 'Edit Laboratorium')
@section('header', 'Edit Laboratorium')
@section('subheader', 'Perbarui informasi laboratorium #{{ $laboratorium->id_lab }}')

@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6">
        <form method="POST" action="{{ route('laboratorium.update', $laboratorium) }}" class="space-y-5">
            @csrf
            @method('PUT')
            <div>
                <label for="nama_lab" class="block text-sm font-semibold text-slate-700 mb-1.5">Nama Laboratorium <span class="text-rose-500">*</span></label>
                <input type="text" id="nama_lab" name="nama_lab" value="{{ old('nama_lab', $laboratorium->nama_lab) }}" required maxlength="100"
                       class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none">
            </div>

            <div>
                <label for="lokasi" class="block text-sm font-semibold text-slate-700 mb-1.5">Lokasi <span class="text-rose-500">*</span></label>
                <input type="text" id="lokasi" name="lokasi" value="{{ old('lokasi', $laboratorium->lokasi) }}" required maxlength="100"
                       class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none">
            </div>

            <div class="pt-3 flex items-center justify-end gap-3 border-t border-slate-100">
                <a href="{{ route('laboratorium.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition">Batal</a>
                <button type="submit" class="rounded-xl bg-indigo-600 hover:bg-indigo-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-indigo-500/20 transition">Perbarui</button>
            </div>
        </form>
    </div>
</div>
@endsection
