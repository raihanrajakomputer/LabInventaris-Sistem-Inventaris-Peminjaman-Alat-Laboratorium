@extends('layouts.app')

@section('title', 'Edit Peminjam')
@section('header', 'Edit Data Peminjam')
@section('subheader', 'Perbarui informasi peminjam {{ $peminjam->nama_peminjam }}')

@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6">
        <form method="POST" action="{{ route('peminjam.update', $peminjam) }}" class="space-y-5">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="nim_nip" class="block text-sm font-semibold text-slate-700 mb-1.5">NIM / NIP <span class="text-rose-500">*</span></label>
                    <input type="text" id="nim_nip" name="nim_nip" value="{{ old('nim_nip', $peminjam->nim_nip) }}" required maxlength="30"
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-mono focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none">
                </div>

                <div>
                    <label for="status_peminjam" class="block text-sm font-semibold text-slate-700 mb-1.5">Status Peminjam <span class="text-rose-500">*</span></label>
                    <select id="status_peminjam" name="status_peminjam" required
                            class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none bg-white">
                        <option value="Mahasiswa" @selected(old('status_peminjam', $peminjam->status_peminjam) === 'Mahasiswa')>Mahasiswa</option>
                        <option value="Dosen" @selected(old('status_peminjam', $peminjam->status_peminjam) === 'Dosen')>Dosen</option>
                    </select>
                </div>
            </div>

            <div>
                <label for="nama_peminjam" class="block text-sm font-semibold text-slate-700 mb-1.5">Nama Lengkap <span class="text-rose-500">*</span></label>
                <input type="text" id="nama_peminjam" name="nama_peminjam" value="{{ old('nama_peminjam', $peminjam->nama_peminjam) }}" required maxlength="100"
                       class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="no_hp" class="block text-sm font-semibold text-slate-700 mb-1.5">Nomor HP / WhatsApp <span class="text-rose-500">*</span></label>
                    <input type="text" id="no_hp" name="no_hp" value="{{ old('no_hp', $peminjam->no_hp) }}" required maxlength="20"
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
                <a href="{{ route('peminjam.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition">Batal</a>
                <button type="submit" class="rounded-xl bg-indigo-600 hover:bg-indigo-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-indigo-500/20 transition">Perbarui</button>
            </div>
        </form>
    </div>
</div>
@endsection
