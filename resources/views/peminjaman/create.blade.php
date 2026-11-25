@extends('layouts.app')

@section('title', 'Buat Peminjaman Baru')
@section('header', 'Transaksi Peminjaman Baru')
@section('subheader', $isMahasiswa ? 'Ajukan peminjaman alat laboratorium dengan memilih petugas, jadwal kembali, dan alat yang dibutuhkan' : 'Pilih peminjam, petugas, jadwal kembali, serta satu atau lebih alat laboratorium')

@section('content')
@php
    $missingPrereq = $peminjamList->isEmpty() || $petugasList->isEmpty() || $alatList->isEmpty();
    $oldItems = old('items', [
        ['id_alat' => '', 'jumlah_pinjam' => 1, 'kondisi_saat_pinjam' => 'Baik'],
    ]);
    $alatMeta = $alatList->mapWithKeys(fn ($a) => [
        $a->id_alat => [
            'kode' => $a->kode_alat,
            'nama' => $a->nama_alat,
            'lab' => $a->nama_lab,
            'stok' => (int) $a->stok_tersedia,
            'kondisi' => $a->kondisi_umum,
        ],
    ]);
@endphp

@if ($missingPrereq)
    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900 space-y-2">
        <p class="font-bold">Data Master Belum Lengkap</p>
        <p>Untuk membuat transaksi peminjaman, pastikan data berikut sudah tersedia:</p>
        <ul class="list-disc list-inside space-y-1 text-xs">
            @if ($peminjamList->isEmpty())
                <li>Data Peminjam masih kosong.</li>
            @endif
            @if ($petugasList->isEmpty())
                <li>Data Petugas masih kosong.</li>
            @endif
            @if ($alatList->isEmpty())
                <li>Belum ada Alat dengan stok tersedia (&gt; 0).</li>
            @endif
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('peminjaman.store') }}" class="space-y-6">
    @csrf

    <!-- Info Transaksi -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-5">
        <div class="border-b border-slate-100 pb-3">
            <h2 class="text-base font-bold text-slate-900">1. Informasi Peminjaman</h2>
            <p class="text-xs text-slate-500">
                {{ $isMahasiswa ? 'Identitas peminjam otomatis terkunci ke akun Anda. Pilih petugas yang melayani dan jadwal pengembalian.' : 'Tentukan peminjam, petugas yang melayani, serta jadwal pengembalian.' }}
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Peminjam -->
            <div>
                <label for="id_peminjam" class="block text-sm font-semibold text-slate-700 mb-1.5">Peminjam <span class="text-rose-500">*</span></label>
                @if ($isMahasiswa && $currentPeminjam)
                    <input type="hidden" name="id_peminjam" value="{{ $currentPeminjam->id_peminjam }}">
                    <div class="w-full rounded-xl border border-sky-200 bg-sky-50/70 px-3.5 py-2.5 text-sm text-slate-800 flex items-center justify-between gap-2">
                        <div class="truncate">
                            <span class="font-bold text-slate-900">{{ $currentPeminjam->nama_peminjam }}</span>
                            <span class="text-xs text-slate-600 font-mono">({{ $currentPeminjam->nim_nip }})</span>
                        </div>
                        <span class="inline-flex items-center gap-1 rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-bold text-sky-700 shrink-0">
                            Terkunci
                        </span>
                    </div>
                @else
                    <select id="id_peminjam" name="id_peminjam" required
                            class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none">
                        <option value="">-- Pilih Peminjam --</option>
                        @foreach ($peminjamList as $p)
                            <option value="{{ $p->id_peminjam }}" @selected(old('id_peminjam') == $p->id_peminjam)>
                                {{ $p->nama_peminjam }} ({{ $p->nim_nip }} — {{ $p->status_peminjam }})
                            </option>
                        @endforeach
                    </select>
                @endif
            </div>

            <!-- Petugas Melayani -->
            <div>
                <label for="id_petugas" class="block text-sm font-semibold text-slate-700 mb-1.5">
                    Petugas Melayani <span class="text-rose-500">*</span>
                    @if (! $isMahasiswa && $defaultPetugasId)
                        <span class="text-[11px] font-normal text-emerald-600">(otomatis terisi akun Anda)</span>
                    @endif
                </label>
                <select id="id_petugas" name="id_petugas" required
                        class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none">
                    <option value="">-- Pilih Petugas --</option>
                    @foreach ($petugasList as $pt)
                        <option value="{{ $pt->id_petugas }}" @selected(old('id_petugas', $defaultPetugasId) == $pt->id_petugas)>
                            {{ $pt->nama_petugas }} ({{ $pt->username }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="tanggal_pinjam" class="block text-sm font-semibold text-slate-700 mb-1.5">Tanggal &amp; Jam Pinjam <span class="text-rose-500">*</span></label>
                <input type="datetime-local" id="tanggal_pinjam" name="tanggal_pinjam"
                       value="{{ old('tanggal_pinjam', now()->format('Y-m-d\TH:i')) }}" required
                       class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none">
            </div>

            <div>
                <label for="jadwal_kembali" class="block text-sm font-semibold text-slate-700 mb-1.5">Jadwal Kembali <span class="text-rose-500">*</span></label>
                <input type="datetime-local" id="jadwal_kembali" name="jadwal_kembali"
                       value="{{ old('jadwal_kembali', now()->addDays(2)->format('Y-m-d\TH:i')) }}" required
                       class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none">
            </div>
        </div>
    </div>

    <!-- Daftar Alat (detail_peminjaman) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-3">
            <div>
                <h2 class="text-base font-bold text-slate-900">2. Daftar Alat yang Dipinjam</h2>
                <p class="text-xs text-slate-500">Stok alat akan berkurang otomatis melalui Trigger MySQL saat transaksi disimpan.</p>
            </div>
            <button type="button" id="add-item-btn"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold px-3.5 py-2 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Baris Alat
            </button>
        </div>

        <div id="items-container" class="space-y-3">
            @foreach ($oldItems as $idx => $item)
                <div class="item-row grid grid-cols-1 md:grid-cols-12 gap-3 items-start rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                    <div class="md:col-span-5">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Pilih Alat <span class="text-rose-500">*</span></label>
                        <select name="items[{{ $idx }}][id_alat]" required onchange="onAlatChange(this)"
                                class="alat-select w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 outline-none">
                            <option value="">-- Pilih Alat Tersedia --</option>
                            @foreach ($alatList as $a)
                                <option value="{{ $a->id_alat }}" @selected(($item['id_alat'] ?? '') == $a->id_alat)>
                                    [{{ $a->kode_alat }}] {{ $a->nama_alat }} — {{ $a->nama_lab }} (Stok: {{ $a->stok_tersedia }})
                                </option>
                            @endforeach
                        </select>
                        <p class="stok-hint mt-1 text-[11px] text-slate-500"></p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Jumlah <span class="text-rose-500">*</span></label>
                        <input type="number" name="items[{{ $idx }}][jumlah_pinjam]" value="{{ $item['jumlah_pinjam'] ?? 1 }}" min="1" required
                               class="qty-input w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 outline-none">
                    </div>

                    <div class="md:col-span-4">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Kondisi Saat Pinjam <span class="text-rose-500">*</span></label>
                        <input type="text" name="items[{{ $idx }}][kondisi_saat_pinjam]" value="{{ $item['kondisi_saat_pinjam'] ?? 'Baik' }}" required maxlength="50"
                               list="kondisi-presets" placeholder="Contoh: Baik, lengkap dengan kabel"
                               class="kondisi-input w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 outline-none">
                    </div>

                    <div class="md:col-span-1 flex md:justify-end md:pt-6">
                        <button type="button" onclick="removeItemRow(this)" title="Hapus baris"
                                class="remove-row-btn rounded-xl border border-rose-200 bg-white hover:bg-rose-50 text-rose-600 px-3 py-2 text-xs font-semibold transition">
                            Hapus
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <datalist id="kondisi-presets">
            <option value="Baik">
            <option value="Baik (Lengkap)">
            <option value="Rusak Ringan">
        </datalist>

        <div class="pt-4 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100">
            <p class="text-xs text-slate-500">
                Pastikan alat yang sama tidak dipilih lebih dari satu baris.
            </p>
            <div class="flex items-center gap-3">
                <a href="{{ route('peminjaman.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition">Batal</a>
                <button type="submit" @disabled($missingPrereq)
                        class="rounded-xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed px-6 py-2.5 text-sm font-semibold text-white shadow-sm shadow-indigo-500/20 transition">
                    Simpan Transaksi Peminjaman
                </button>
            </div>
        </div>
    </div>
</form>

<!-- Template Baris Alat Baru -->
<template id="item-row-template">
    <div class="item-row grid grid-cols-1 md:grid-cols-12 gap-3 items-start rounded-xl border border-slate-200 bg-slate-50/60 p-4">
        <div class="md:col-span-5">
            <label class="block text-xs font-semibold text-slate-600 mb-1">Pilih Alat <span class="text-rose-500">*</span></label>
            <select name="items[__INDEX__][id_alat]" required onchange="onAlatChange(this)"
                    class="alat-select w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 outline-none">
                <option value="">-- Pilih Alat Tersedia --</option>
                @foreach ($alatList as $a)
                    <option value="{{ $a->id_alat }}">
                        [{{ $a->kode_alat }}] {{ $a->nama_alat }} — {{ $a->nama_lab }} (Stok: {{ $a->stok_tersedia }})
                    </option>
                @endforeach
            </select>
            <p class="stok-hint mt-1 text-[11px] text-slate-500"></p>
        </div>

        <div class="md:col-span-2">
            <label class="block text-xs font-semibold text-slate-600 mb-1">Jumlah <span class="text-rose-500">*</span></label>
            <input type="number" name="items[__INDEX__][jumlah_pinjam]" value="1" min="1" required
                   class="qty-input w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 outline-none">
        </div>

        <div class="md:col-span-4">
            <label class="block text-xs font-semibold text-slate-600 mb-1">Kondisi Saat Pinjam <span class="text-rose-500">*</span></label>
            <input type="text" name="items[__INDEX__][kondisi_saat_pinjam]" value="Baik" required maxlength="50"
                   list="kondisi-presets" placeholder="Contoh: Baik, lengkap dengan kabel"
                   class="kondisi-input w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 outline-none">
        </div>

        <div class="md:col-span-1 flex md:justify-end md:pt-6">
            <button type="button" onclick="removeItemRow(this)" title="Hapus baris"
                    class="remove-row-btn rounded-xl border border-rose-200 bg-white hover:bg-rose-50 text-rose-600 px-3 py-2 text-xs font-semibold transition">
                Hapus
            </button>
        </div>
    </div>
</template>
@endsection

@push('scripts')
<script>
    const ALAT_META = @json($alatMeta);
    let nextRowIndex = {{ count($oldItems) }};

    function onAlatChange(selectEl, preserveKondisi = false) {
        const row = selectEl.closest('.item-row');
        const qtyInput = row.querySelector('.qty-input');
        const kondisiInput = row.querySelector('.kondisi-input');
        const hint = row.querySelector('.stok-hint');
        const meta = ALAT_META[selectEl.value];

        if (meta) {
            qtyInput.max = meta.stok;
            if (parseInt(qtyInput.value || '1', 10) > meta.stok) {
                qtyInput.value = meta.stok;
            }
            if (!preserveKondisi && meta.kondisi) {
                kondisiInput.value = meta.kondisi;
            }
            hint.textContent = `Tersedia: ${meta.stok} unit • Lab: ${meta.lab} • Kondisi umum: ${meta.kondisi}`;
        } else {
            qtyInput.removeAttribute('max');
            hint.textContent = '';
        }
    }

    function updateRemoveButtons() {
        const rows = document.querySelectorAll('#items-container .item-row');
        rows.forEach((row) => {
            const btn = row.querySelector('.remove-row-btn');
            if (rows.length <= 1) {
                btn.disabled = true;
                btn.classList.add('opacity-40', 'cursor-not-allowed');
            } else {
                btn.disabled = false;
                btn.classList.remove('opacity-40', 'cursor-not-allowed');
            }
        });
    }

    function removeItemRow(btn) {
        const rows = document.querySelectorAll('#items-container .item-row');
        if (rows.length <= 1) return;
        btn.closest('.item-row').remove();
        updateRemoveButtons();
    }

    document.getElementById('add-item-btn').addEventListener('click', () => {
        const tpl = document.getElementById('item-row-template').innerHTML;
        const html = tpl.replaceAll('__INDEX__', String(nextRowIndex++));
        document.getElementById('items-container').insertAdjacentHTML('beforeend', html);
        updateRemoveButtons();
    });

    document.querySelectorAll('#items-container .alat-select').forEach((sel) => {
        if (sel.value) onAlatChange(sel, true);
    });
    updateRemoveButtons();
</script>
@endpush
