@extends('layouts.app')

@section('title', 'Pengembalian Alat #' . $peminjaman->id_peminjaman)
@section('header', 'Proses Pengembalian Alat')
@section('subheader', 'Catat tanggal kembali aktual dan kondisi setiap alat untuk transaksi #' . $peminjaman->id_peminjaman)

@section('content')
<div class="max-w-4xl space-y-6">
    <!-- Ringkasan Transaksi -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
            <div>
                <p class="text-xs text-slate-400 font-semibold uppercase">Peminjam</p>
                <p class="font-bold text-slate-900 mt-0.5">{{ $peminjaman->peminjam?->nama_peminjam }}</p>
                <p class="text-xs text-slate-500 font-mono">{{ $peminjaman->peminjam?->nim_nip }} ({{ $peminjaman->peminjam?->status_peminjam }})</p>
            </div>
            <div>
                <p class="text-xs text-slate-400 font-semibold uppercase">Petugas</p>
                <p class="font-bold text-slate-900 mt-0.5">{{ $peminjaman->petugas?->nama_petugas }}</p>
                <p class="text-xs text-slate-500">Status saat ini: <span class="font-semibold text-amber-700">{{ $peminjaman->status_peminjaman }}</span></p>
            </div>
            <div>
                <p class="text-xs text-slate-400 font-semibold uppercase">Jadwal</p>
                <p class="text-xs text-slate-600 mt-0.5">Pinjam: <span class="font-semibold text-slate-800">{{ $peminjaman->tanggal_pinjam?->format('d M Y, H:i') }}</span></p>
                <p class="text-xs text-slate-600">Jadwal Kembali: <span class="font-semibold text-slate-800">{{ $peminjaman->jadwal_kembali?->format('d M Y, H:i') }}</span></p>
            </div>
        </div>
    </div>

    <!-- Form Pengembalian -->
    <form method="POST" action="{{ route('peminjaman.kembalikan.process', $peminjaman) }}" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-6">
        @csrf
        @method('PUT')

        <div class="rounded-xl border border-emerald-200 bg-emerald-50/70 px-4 py-3 text-xs text-emerald-800">
            <strong>Info Trigger Database:</strong> Saat tombol konfirmasi ditekan, status transaksi akan berubah menjadi <code>Dikembalikan</code> dan stok setiap alat di bawah ini akan <strong>otomatis bertambah kembali</strong> oleh Trigger MySQL (<code>trg_kembalikan_stok_alat</code>).
        </div>

        @php
            $defaultReturnTime = now();
            if ($peminjaman->tanggal_pinjam && $defaultReturnTime->lt($peminjaman->tanggal_pinjam)) {
                $defaultReturnTime = $peminjaman->tanggal_pinjam->copy();
            }
        @endphp

        <div class="max-w-sm">
            <label for="tanggal_kembali_aktual" class="block text-sm font-semibold text-slate-700 mb-1.5">Tanggal &amp; Jam Kembali Aktual <span class="text-rose-500">*</span></label>
            <input type="datetime-local" id="tanggal_kembali_aktual" name="tanggal_kembali_aktual"
                   value="{{ old('tanggal_kembali_aktual', $defaultReturnTime->format('Y-m-d\TH:i')) }}"
                   min="{{ $peminjaman->tanggal_pinjam?->format('Y-m-d\TH:i') }}" required
                   class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none">
            <p class="mt-1 text-[11px] text-slate-400">Tidak boleh lebih awal dari tanggal pinjam ({{ $peminjaman->tanggal_pinjam?->format('d M Y, H:i') }}).</p>
        </div>

        <div>
            <h3 class="text-sm font-bold text-slate-900 mb-3">Pemeriksaan Kondisi Alat Saat Kembali</h3>
            <div class="space-y-3">
                @foreach ($peminjaman->details as $detail)
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                        <div class="md:col-span-5">
                            <p class="font-semibold text-slate-900 text-sm">{{ $detail->alat?->nama_alat }}</p>
                            <p class="text-xs text-slate-500 font-mono">
                                {{ $detail->alat?->kode_alat }} &middot; {{ $detail->alat?->laboratorium?->nama_lab }}
                            </p>
                        </div>

                        <div class="md:col-span-3 text-xs">
                            <p class="text-slate-500">Jumlah: <span class="font-bold text-indigo-700">{{ $detail->jumlah_pinjam }} unit</span></p>
                            <p class="text-slate-500 mt-0.5">Saat Pinjam: <span class="font-semibold text-slate-700">{{ $detail->kondisi_saat_pinjam }}</span></p>
                        </div>

                        <div class="md:col-span-4">
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Kondisi Saat Kembali <span class="text-rose-500">*</span></label>
                            <input type="text" name="kondisi_kembali[{{ $detail->id_detail }}]"
                                   value="{{ old('kondisi_kembali.' . $detail->id_detail, $detail->kondisi_saat_pinjam ?: 'Baik') }}"
                                   required maxlength="50" list="kondisi-kembali-presets"
                                   placeholder="Contoh: Baik / Rusak Ringan"
                                   class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none">
                        </div>
                    </div>
                @endforeach
            </div>

            <datalist id="kondisi-kembali-presets">
                <option value="Baik">
                <option value="Rusak Ringan">
                <option value="Rusak Berat">
            </datalist>
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
            <a href="{{ route('peminjaman.show', $peminjaman) }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition">Batal</a>
            <button type="submit" class="rounded-xl bg-emerald-600 hover:bg-emerald-700 px-6 py-2.5 text-sm font-semibold text-white shadow-sm shadow-emerald-600/20 transition">
                Konfirmasi Pengembalian
            </button>
        </div>
    </form>
</div>
@endsection
