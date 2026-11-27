@extends('layouts.app')

@section('title', 'Detail Peminjaman #' . $peminjaman->id_peminjaman)
@section('header', 'Detail Transaksi #' . $peminjaman->id_peminjaman)
@section('subheader', 'Rincian peminjam, petugas, jadwal, dan daftar alat yang dipinjam')

@section('content')
@php
    $sessionPeminjamId = (int) (session('id_peminjam') ?? ($isMahasiswa ? (session('auth_user.id') ?? 0) : 0));
    $canMahasiswaReturn = $isMahasiswa
        && (int) $peminjaman->id_peminjam === $sessionPeminjamId
        && in_array($peminjaman->status_peminjaman, ['Dipinjam', 'Terlambat'], true);

    $defaultReturnTime = now();
    if ($peminjaman->tanggal_pinjam && $defaultReturnTime->lt($peminjaman->tanggal_pinjam)) {
        $defaultReturnTime = $peminjaman->tanggal_pinjam->copy();
    }

    $presetKondisi = ['Baik', 'Rusak Ringan', 'Rusak Berat'];
    $hasReturnErrors = $errors->has('tanggal_kembali_aktual') || $errors->has('kondisi_kembali') || $errors->has('kondisi_kembali.*');
    $isOverdue = $peminjaman->isOverdue();
@endphp

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

            @if ($canMahasiswaReturn)
                <button type="button" onclick="openSelfReturnForm()"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 px-4 py-2 text-xs sm:text-sm font-semibold text-white shadow-sm shadow-emerald-600/20 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                    </svg>
                    Kembalikan Barang Sekarang
                </button>
            @endif

            @if (! $isMahasiswa && $peminjaman->status_peminjaman === 'Menunggu')
                <form action="{{ route('peminjaman.setujui', $peminjaman) }}" method="POST"
                      onsubmit="return confirm('Setujui (ACC) pengajuan peminjaman #{{ $peminjaman->id_peminjaman }}? Stok alat akan berkurang otomatis.')">
                    @csrf
                    @method('PATCH')
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 px-4 py-2 text-xs sm:text-sm font-semibold text-white shadow-sm shadow-indigo-600/20 transition">
                        Setujui (ACC)
                    </button>
                </form>

                <form action="{{ route('peminjaman.tolak', $peminjaman) }}" method="POST"
                      onsubmit="return confirm('Tolak pengajuan peminjaman #{{ $peminjaman->id_peminjaman }}?')">
                    @csrf
                    @method('PATCH')
                    <button type="submit"
                            class="inline-flex items-center rounded-xl bg-red-600 hover:bg-red-700 px-4 py-2 text-xs sm:text-sm font-semibold text-white shadow-sm shadow-red-600/20 transition">
                        Tolak
                    </button>
                </form>
            @endif

            @if (! $isMahasiswa && $peminjaman->status_peminjaman === 'Dipinjam' && $isOverdue)
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

            @if (! $isMahasiswa && $isOverdue)
                <button type="button"
                        onclick="openPeringatanModal(this)"
                        data-action="{{ route('peminjaman.peringatan', $peminjaman) }}"
                        data-id="{{ $peminjaman->id_peminjaman }}"
                        data-peminjam="{{ $peminjaman->peminjam?->nama_peminjam }}"
                        data-jadwal="{{ $peminjaman->jadwal_kembali?->format('d M Y, H:i') }}"
                        data-pesan="{{ $peminjaman->pesan_peringatan ?? '' }}"
                        data-wa-phone="{{ $peminjaman->whatsapp_number ?? '' }}"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 px-4 py-2 text-xs sm:text-sm font-semibold text-white shadow-sm shadow-rose-600/20 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                    Beri Peringatan
                </button>

                @if ($peminjaman->whatsapp_warning_url)
                    <a href="{{ $peminjaman->whatsapp_warning_url }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 hover:bg-emerald-100 px-3.5 py-2 text-xs sm:text-sm font-semibold text-emerald-700 transition">
                        Hubungi via WhatsApp
                    </a>
                @endif
            @endif

            @if (! $isMahasiswa && in_array($peminjaman->status_peminjaman, ['Dipinjam', 'Terlambat']))
                <a href="{{ route('peminjaman.kembalikan.form', $peminjaman) }}"
                   class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 px-4 py-2 text-xs sm:text-sm font-semibold text-white shadow-sm shadow-emerald-600/20 transition">
                    Kembalikan Alat
                </a>
            @endif
        </div>
    </div>

    <!-- Banner Peringatan Resmi Petugas (jika ada pesan_peringatan) -->
    @if (! empty($peminjaman->pesan_peringatan))
        <div class="rounded-2xl border border-rose-300 bg-rose-50 px-5 py-4 shadow-xs flex flex-col sm:flex-row sm:items-start justify-between gap-3">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-rose-600 text-white flex items-center justify-center shrink-0 mt-0.5">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <div class="space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-sm font-extrabold text-rose-950">Peringatan Resmi Petugas Lab</p>
                        @if ($peminjaman->peringatan_dikirim_at)
                            <span class="rounded-full bg-rose-100 border border-rose-200 px-2.5 py-0.5 text-[11px] font-semibold text-rose-800">
                                Dikirim {{ $peminjaman->peringatan_dikirim_at->format('d M Y, H:i') }}
                            </span>
                        @endif
                    </div>
                    <p class="text-sm text-rose-900 leading-relaxed">{{ $peminjaman->pesan_peringatan }}</p>
                </div>
            </div>
            @if ($canMahasiswaReturn)
                <button type="button" onclick="openSelfReturnForm()"
                        class="no-print inline-flex items-center justify-center rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold px-4 py-2.5 shadow-xs transition shrink-0">
                    Kembalikan Sekarang
                </button>
            @endif
        </div>
    @endif

    <!-- Summary Card -->
    <div class="bg-white rounded-2xl border {{ $isOverdue ? 'border-rose-200/90' : 'border-slate-200/80' }} shadow-xs p-6">
        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 pb-5">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Nomor Transaksi</p>
                <h2 class="text-2xl font-extrabold text-slate-900 font-mono mt-0.5">#{{ $peminjaman->id_peminjaman }}</h2>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if ($isOverdue && $peminjaman->status_peminjaman !== 'Terlambat')
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-600 text-white px-3 py-1 text-xs font-extrabold shadow-xs">
                        <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
                        Terlambat (Lewat Batas Waktu)
                    </span>
                @endif
                @include('partials.status-badge', ['status' => $peminjaman->status_peminjaman, 'size' => 'lg'])
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
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Petugas Verifikasi / Melayani</p>
                @if ($peminjaman->petugas)
                    <p class="font-bold text-slate-900">{{ $peminjaman->petugas->nama_petugas }}</p>
                    <p class="text-xs text-slate-500 font-mono mt-0.5">{{ $peminjaman->petugas->username }}</p>
                @else
                    <p class="font-semibold italic text-slate-400">Belum Diverifikasi</p>
                    <p class="text-xs text-yellow-700 mt-0.5">Menunggu konfirmasi Petugas Lab</p>
                @endif
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
                                <span class="text-xs italic text-slate-400">
                                    {{ $peminjaman->status_peminjaman === 'Menunggu' ? 'Menunggu ACC' : ($peminjaman->status_peminjaman === 'Ditolak' ? 'Ditolak' : 'Menunggu pengembalian') }}
                                </span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section Pengembalian Mandiri oleh Mahasiswa -->
    @if ($canMahasiswaReturn)
        <div id="self-return-section" class="no-print bg-white rounded-2xl border border-emerald-200/90 shadow-xs overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-emerald-50 to-teal-50/70 border-b border-emerald-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-emerald-950">Pengembalian Barang Mandiri</h3>
                        <p class="text-xs text-emerald-800 mt-0.5">
                            Sudah selesai menggunakan alat? Anda dapat mengembalikan barang kapan saja, termasuk lebih awal sebelum jadwal kembali.
                        </p>
                    </div>
                </div>

                <button type="button" id="btn-toggle-self-return" onclick="toggleSelfReturnForm()"
                        class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs sm:text-sm font-semibold px-4 py-2.5 shadow-xs transition shrink-0">
                    <span id="btn-toggle-self-return-label">{{ $hasReturnErrors ? 'Tutup Form' : 'Kembalikan Barang Sekarang' }}</span>
                </button>
            </div>

            <form id="self-return-form"
                  method="POST"
                  action="{{ route('peminjaman.kembalikan.mahasiswa', $peminjaman) }}"
                  onsubmit="return confirm('Apakah Anda yakin ingin mengembalikan seluruh alat pada transaksi #{{ $peminjaman->id_peminjaman }} sekarang? Status akan berubah menjadi Dikembalikan dan stok alat otomatis diperbarui.')"
                  class="{{ $hasReturnErrors ? '' : 'hidden' }} p-6 space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end rounded-xl border border-slate-200/80 bg-slate-50/70 p-4">
                    <div>
                        <label for="tanggal_kembali_aktual" class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Waktu Pengembalian Aktual
                        </label>
                        <input type="datetime-local"
                               id="tanggal_kembali_aktual"
                               name="tanggal_kembali_aktual"
                               value="{{ old('tanggal_kembali_aktual', $defaultReturnTime->format('Y-m-d\TH:i')) }}"
                               min="{{ $peminjaman->tanggal_pinjam?->format('Y-m-d\TH:i') }}"
                               required
                               class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none">
                        <p class="mt-1.5 text-[11px] text-slate-500">
                            Default waktu saat ini. Tidak boleh lebih awal dari tanggal pinjam ({{ $peminjaman->tanggal_pinjam?->format('d M Y, H:i') }}).
                        </p>
                    </div>
                    <div class="flex md:justify-end">
                        <button type="button" onclick="setReturnTimeNow()"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-100 text-slate-700 text-xs font-semibold px-3.5 py-2.5 transition">
                            <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Gunakan Waktu Sekarang
                        </button>
                    </div>
                </div>

                <div>
                    <h4 class="text-sm font-bold text-slate-900 mb-1">Kondisi Setiap Alat Saat Dikembalikan</h4>
                    <p class="text-xs text-slate-500 mb-3">Pilih kondisi aktual setiap alat yang Anda kembalikan, atau pilih opsi catatan singkat jika perlu keterangan tambahan.</p>

                    <div class="space-y-3">
                        @foreach ($peminjaman->details as $detail)
                            @php
                                $initialKondisi = old('kondisi_kembali.' . $detail->id_detail, in_array($detail->kondisi_saat_pinjam, $presetKondisi, true) ? $detail->kondisi_saat_pinjam : 'Baik');
                                $isCustomInitial = ! in_array($initialKondisi, $presetKondisi, true);
                            @endphp
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-start rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                                <div class="md:col-span-5">
                                    <p class="font-semibold text-slate-900 text-sm">{{ $detail->alat?->nama_alat }}</p>
                                    <p class="text-xs text-slate-500 font-mono mt-0.5">
                                        {{ $detail->alat?->kode_alat }} &middot; {{ $detail->alat?->laboratorium?->nama_lab }}
                                    </p>
                                </div>

                                <div class="md:col-span-3 text-xs">
                                    <p class="text-slate-500">Jumlah: <span class="font-bold text-indigo-700">{{ $detail->jumlah_pinjam }} unit</span></p>
                                    <p class="text-slate-500 mt-0.5">Saat Pinjam: <span class="font-semibold text-slate-700">{{ $detail->kondisi_saat_pinjam }}</span></p>
                                </div>

                                <div class="md:col-span-4 space-y-2">
                                    <label for="kondisi_select_{{ $detail->id_detail }}" class="block text-xs font-semibold text-slate-700">
                                        Kondisi Saat Kembali <span class="text-rose-500">*</span>
                                    </label>
                                    <select id="kondisi_select_{{ $detail->id_detail }}"
                                            data-detail-id="{{ $detail->id_detail }}"
                                            onchange="handleKondisiSelectChange(this)"
                                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none">
                                        @foreach ($presetKondisi as $opsi)
                                            <option value="{{ $opsi }}" @selected(! $isCustomInitial && $initialKondisi === $opsi)>{{ $opsi }}</option>
                                        @endforeach
                                        <option value="__custom__" @selected($isCustomInitial)>Lainnya (Tulis Catatan Singkat)</option>
                                    </select>

                                    <input type="text"
                                           id="kondisi_input_{{ $detail->id_detail }}"
                                           name="kondisi_kembali[{{ $detail->id_detail }}]"
                                           value="{{ $initialKondisi }}"
                                           required
                                           maxlength="50"
                                           placeholder="Contoh: Kabel sedikit lecet / Lengkap"
                                           class="{{ $isCustomInitial ? '' : 'hidden' }} w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none">
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex flex-col-reverse sm:flex-row sm:items-center justify-end gap-2.5">
                    <button type="button" onclick="toggleSelfReturnForm(false)"
                            class="rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-sm font-semibold px-4 py-2.5 transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-6 py-2.5 shadow-sm shadow-emerald-600/20 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        Konfirmasi Pengembalian Sekarang
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>

@if (! $isMahasiswa)
    @include('partials.peringatan-modal')
@endif
@endsection

@if ($canMahasiswaReturn)
@push('scripts')
<script>
    function toggleSelfReturnForm(forceOpen) {
        const form = document.getElementById('self-return-form');
        const label = document.getElementById('btn-toggle-self-return-label');
        if (!form) return;

        const shouldOpen = typeof forceOpen === 'boolean' ? forceOpen : form.classList.contains('hidden');
        form.classList.toggle('hidden', !shouldOpen);
        if (label) {
            label.textContent = shouldOpen ? 'Tutup Form' : 'Kembalikan Barang Sekarang';
        }
    }

    function openSelfReturnForm() {
        toggleSelfReturnForm(true);
        const section = document.getElementById('self-return-section');
        if (section) {
            section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    function handleKondisiSelectChange(selectEl) {
        const detailId = selectEl.dataset.detailId;
        const inputEl = document.getElementById('kondisi_input_' + detailId);
        if (!inputEl) return;

        if (selectEl.value === '__custom__') {
            inputEl.classList.remove('hidden');
            inputEl.value = '';
            inputEl.focus();
        } else {
            inputEl.classList.add('hidden');
            inputEl.value = selectEl.value;
        }
    }

    function setReturnTimeNow() {
        const input = document.getElementById('tanggal_kembali_aktual');
        if (!input) return;

        const now = new Date();
        const pad = (n) => String(n).padStart(2, '0');
        const formatted = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`;

        if (input.min && formatted < input.min) {
            input.value = input.min;
            return;
        }
        input.value = formatted;
    }
</script>
@endpush
@endif
