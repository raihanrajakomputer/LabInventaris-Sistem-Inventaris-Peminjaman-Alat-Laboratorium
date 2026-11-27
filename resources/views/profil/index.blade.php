@extends('layouts.app')

@section('title', 'Profil Mahasiswa')
@section('header', 'Profil Saya')
@section('subheader', 'Kelola foto profil, email pribadi, nomor HP, dan keamanan password akun mahasiswa Anda')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
<style>
    .cropper-stage {
        height: 360px;
        max-height: 55vh;
        width: 100%;
    }
    .cropper-stage img {
        display: block;
        max-width: 100%;
    }
    .cropper-preview-box {
        overflow: hidden;
        background-color: #f1f5f9;
        transition: width 0.2s ease, height 0.2s ease, border-radius 0.2s ease;
    }
    .cropper-preview-box.ratio-1-1 {
        width: 128px;
        height: 128px;
        border-radius: 9999px;
    }
    .cropper-preview-box.ratio-3-4 {
        width: 114px;
        height: 152px;
        border-radius: 0.75rem;
    }
    .cropper-view-box,
    .cropper-face {
        outline-color: rgba(79, 70, 229, 0.85);
    }
</style>
@endpush

@section('content')
@php
    $butuhFoto = empty($peminjam->foto_profil);
    $butuhEmail = empty($peminjam->email);
@endphp

@if ($butuhFoto || $butuhEmail)
    <div class="rounded-2xl border border-amber-200/90 bg-gradient-to-r from-amber-50 via-amber-50/90 to-sky-50 px-5 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-xs">
        <div class="flex items-start gap-3">
            <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 mt-0.5">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-bold text-amber-950">Profil Anda Belum Lengkap (Disarankan)</p>
                <p class="text-xs text-amber-800 mt-0.5 leading-relaxed">
                    Disarankan untuk melengkapi foto profil dan email pribadi Anda (mis. @gmail.com / @yahoo.com) agar memudahkan verifikasi akun serta pemulihan password. Anda tetap dapat menggunakan seluruh layanan peminjaman kapan saja.
                </p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-bold {{ $butuhFoto ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-700' }}">
                {{ $butuhFoto ? '○ Foto Profil' : '✓ Foto Profil' }}
            </span>
            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-bold {{ $butuhEmail ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-700' }}">
                {{ $butuhEmail ? '○ Email Pribadi' : '✓ Email Pribadi' }}
            </span>
        </div>
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Kolom Kiri: Foto Profil + Editor Crop -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 flex flex-col items-center text-center space-y-5">
        <div class="relative">
            @if ($peminjam->foto_profil_url)
                <img id="avatar-preview" src="{{ $peminjam->foto_profil_url }}" alt="Foto Profil {{ $peminjam->nama_peminjam }}"
                     class="w-36 h-36 rounded-full object-cover ring-4 ring-indigo-100 shadow-md">
            @else
                <div id="avatar-fallback"
                     class="w-36 h-36 rounded-full bg-gradient-to-br from-indigo-500 to-sky-500 text-white flex items-center justify-center text-4xl font-extrabold ring-4 ring-indigo-100 shadow-md">
                    {{ strtoupper(substr($peminjam->nama_peminjam, 0, 1)) }}
                </div>
                <img id="avatar-preview" src="" alt="Preview Foto Profil"
                     class="hidden w-36 h-36 rounded-full object-cover ring-4 ring-indigo-100 shadow-md">
            @endif
        </div>

        <div>
            <h2 class="text-lg font-bold text-slate-900">{{ $peminjam->nama_peminjam }}</h2>
            <p class="text-xs font-mono text-slate-500 mt-0.5">{{ $peminjam->nim_nip }} &middot; {{ $peminjam->status_peminjam }}</p>
            <div class="mt-2">
                @if ($peminjam->foto_profil)
                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                        Foto Profil Terpasang
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-semibold text-amber-800 ring-1 ring-inset ring-amber-600/25">
                        Belum Ada Foto Profil
                    </span>
                @endif
            </div>
        </div>

        <form id="photo-upload-form" method="POST" action="{{ route('profil.foto.update') }}" enctype="multipart/form-data" class="w-full space-y-3 pt-3 border-t border-slate-100">
            @csrf
            <input type="hidden" id="cropped_image_input" name="cropped_image" value="">

            <div class="text-left">
                <label for="foto_profil" class="block text-xs font-semibold text-slate-700 mb-1.5">
                    {{ $peminjam->foto_profil ? 'Ganti & Potong Foto Profil' : 'Unggah & Potong Foto Profil' }}
                </label>
                <input type="file" id="foto_profil" name="foto_profil" accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                       class="block w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer border border-slate-200 rounded-xl p-1.5">
                <p class="mt-1.5 text-[11px] text-slate-400 leading-relaxed">
                    Format JPG, JPEG, atau PNG. Setelah memilih foto, editor potong (1:1 atau 3:4) akan terbuka otomatis.
                </p>
            </div>

            <div class="space-y-2 pt-1">
                <button type="button" id="btn-choose-photo"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold py-2.5 px-4 shadow-sm shadow-indigo-500/20 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" />
                    </svg>
                    {{ $peminjam->foto_profil ? 'Pilih & Atur Foto Baru' : 'Pilih & Unggah Foto' }}
                </button>

                @if ($peminjam->foto_profil_url)
                    <button type="button" id="btn-edit-existing-photo"
                            data-photo-url="{{ $peminjam->foto_profil_url }}"
                            class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 text-xs font-semibold py-2 px-3 transition">
                        <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 3.75H6A2.25 2.25 0 003.75 6v1.5M16.5 3.75H18A2.25 2.25 0 0120.25 6v1.5m0 9V18A2.25 2.25 0 0118 20.25h-1.5m-9 0H6A2.25 2.25 0 013.75 18v-1.5" />
                        </svg>
                        Atur Ulang / Potong Foto Saat Ini
                    </button>
                @endif
            </div>
        </form>
    </div>

    <!-- Kolom Kanan: Data Profil & Ubah Password -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Informasi Profil -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6">
            <div class="border-b border-slate-100 pb-3 mb-5">
                <h3 class="text-base font-bold text-slate-900">Informasi Mahasiswa &amp; Kontak</h3>
                <p class="text-xs text-slate-500">NIM dan Nama bersifat tetap. Masukkan alamat email pribadi aktif (Gmail, Yahoo, Outlook, dll.) beserta konfirmasinya.</p>
            </div>

            <form id="profile-contact-form" method="POST" action="{{ route('profil.update') }}"
                  data-original-email="{{ strtolower((string) ($peminjam->email ?? '')) }}"
                  onsubmit="return confirmProfileSave(event)"
                  class="space-y-4">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5">NIM (Tidak dapat diubah)</label>
                        <input type="text" value="{{ $peminjam->nim_nip }}" readonly
                               class="w-full rounded-xl border border-slate-200 bg-slate-100 px-3.5 py-2.5 text-sm font-mono text-slate-600 cursor-not-allowed">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5">Nama Lengkap (Tidak dapat diubah)</label>
                        <input type="text" value="{{ $peminjam->nama_peminjam }}" readonly
                               class="w-full rounded-xl border border-slate-200 bg-slate-100 px-3.5 py-2.5 text-sm text-slate-600 cursor-not-allowed">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="email" class="block text-sm font-semibold text-slate-700 mb-1.5">Alamat Email Pribadi <span class="text-rose-500">*</span></label>
                        <input type="email" id="email" name="email" value="{{ old('email', $peminjam->email) }}" required maxlength="100"
                               placeholder="namaanda@gmail.com"
                               class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none">
                        <p class="mt-1 text-[11px] text-slate-400">Bebas semua domain email valid (@gmail.com, @yahoo.com, dll.).</p>
                    </div>

                    <div>
                        <label for="email_confirmation" class="block text-sm font-semibold text-slate-700 mb-1.5">Konfirmasi Email <span class="text-rose-500">*</span></label>
                        <input type="email" id="email_confirmation" name="email_confirmation" value="{{ old('email_confirmation', $peminjam->email) }}" required maxlength="100"
                               placeholder="Ketik ulang alamat email yang sama"
                               class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none">
                        <p id="email-match-hint" class="mt-1 text-[11px] text-slate-400">Harus sama persis dengan alamat email di sebelah kiri.</p>
                    </div>
                </div>

                <div class="max-w-sm">
                    <label for="no_hp" class="block text-sm font-semibold text-slate-700 mb-1.5">Nomor HP / WhatsApp <span class="text-rose-500">*</span></label>
                    <input type="text" id="no_hp" name="no_hp" value="{{ old('no_hp', $peminjam->no_hp) }}" required maxlength="20"
                           placeholder="081234567890"
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-mono focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none">
                </div>

                <div class="pt-3 flex justify-end border-t border-slate-100">
                    <button type="submit"
                            class="rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold px-5 py-2.5 transition">
                        Simpan Informasi Kontak
                    </button>
                </div>
            </form>
        </div>

        <!-- Ubah Password -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6">
            <div class="border-b border-slate-100 pb-3 mb-5">
                <h3 class="text-base font-bold text-slate-900">Ubah Password</h3>
                <p class="text-xs text-slate-500">Pastikan Anda memasukkan password lama yang benar sebelum mengganti ke password baru.</p>
            </div>

            <form method="POST" action="{{ route('profil.password.update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="current_password" class="block text-sm font-semibold text-slate-700 mb-1.5">Password Lama <span class="text-rose-500">*</span></label>
                    <input type="password" id="current_password" name="current_password" required
                           placeholder="Masukkan password saat ini"
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="new_password" class="block text-sm font-semibold text-slate-700 mb-1.5">Password Baru <span class="text-rose-500">*</span></label>
                        <input type="password" id="new_password" name="new_password" required minlength="4" maxlength="100"
                               placeholder="Minimal 4 karakter"
                               class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none">
                    </div>

                    <div>
                        <label for="new_password_confirmation" class="block text-sm font-semibold text-slate-700 mb-1.5">Konfirmasi Password Baru <span class="text-rose-500">*</span></label>
                        <input type="password" id="new_password_confirmation" name="new_password_confirmation" required minlength="4" maxlength="100"
                               placeholder="Ulangi password baru"
                               class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none">
                    </div>
                </div>

                <div class="pt-3 flex justify-end border-t border-slate-100">
                    <button type="submit"
                            class="rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-5 py-2.5 shadow-sm shadow-indigo-500/20 transition">
                        Ubah Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editor & Crop Pas Foto (Cropper.js) -->
<div id="photo-crop-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/75 backdrop-blur-xs p-3 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="crop-modal-title">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-4xl max-h-[92vh] flex flex-col overflow-hidden">
        <!-- Modal Header -->
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-4">
            <div>
                <h3 id="crop-modal-title" class="text-base font-bold text-slate-900">Edit, Potong &amp; Atur Pas Foto</h3>
                <p class="text-xs text-slate-500 mt-0.5">Geser foto atau atur bingkai potong agar posisi wajah terlihat jelas dan proporsional.</p>
            </div>
            <button type="button" id="btn-close-crop-modal" aria-label="Tutup editor foto"
                    class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="p-5 overflow-y-auto flex-1 space-y-4">
            <div id="crop-error-alert" class="hidden rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-xs font-medium text-rose-700"></div>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-5 items-start">
                <!-- Area Cropper Interaktif -->
                <div class="md:col-span-8 space-y-3">
                    <div class="cropper-stage rounded-xl overflow-hidden bg-slate-950 border border-slate-200 flex items-center justify-center">
                        <img id="cropper-image" src="" alt="Pratinjau Editor Foto">
                    </div>

                    <!-- Toolbar Kontrol Praktis -->
                    <div class="flex flex-wrap items-center justify-center sm:justify-between gap-2 rounded-xl bg-slate-50 border border-slate-200/80 p-2.5">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <button type="button" id="btn-zoom-in" title="Perbesar (Zoom In)"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white hover:bg-indigo-50 hover:border-indigo-200 hover:text-indigo-700 text-slate-700 text-xs font-semibold px-3 py-2 transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM10.5 7.5v6m3-3h-6" />
                                </svg>
                                <span>Zoom In</span>
                            </button>

                            <button type="button" id="btn-zoom-out" title="Perkecil (Zoom Out)"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white hover:bg-indigo-50 hover:border-indigo-200 hover:text-indigo-700 text-slate-700 text-xs font-semibold px-3 py-2 transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM13.5 10.5h-6" />
                                </svg>
                                <span>Zoom Out</span>
                            </button>

                            <button type="button" id="btn-rotate-left" title="Putar Kiri 90 derajat"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white hover:bg-indigo-50 hover:border-indigo-200 hover:text-indigo-700 text-slate-700 text-xs font-semibold px-3 py-2 transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                                </svg>
                                <span>Putar Kiri</span>
                            </button>

                            <button type="button" id="btn-rotate-right" title="Putar Kanan 90 derajat"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white hover:bg-indigo-50 hover:border-indigo-200 hover:text-indigo-700 text-slate-700 text-xs font-semibold px-3 py-2 transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 15l6-6m0 0l-6-6m6 6H9a6 6 0 000 12h3" />
                                </svg>
                                <span>Putar Kanan</span>
                            </button>
                        </div>

                        <button type="button" id="btn-crop-reset" title="Kembalikan ke posisi awal"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white hover:bg-rose-50 hover:border-rose-200 hover:text-rose-700 text-slate-600 text-xs font-semibold px-3 py-2 transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                            <span>Reset</span>
                        </button>
                    </div>
                </div>

                <!-- Panel Kanan: Pilihan Rasio & Live Preview -->
                <div class="md:col-span-4 space-y-4">
                    <div class="rounded-xl border border-slate-200/80 bg-slate-50/70 p-4 space-y-3">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Rasio Potong</p>
                        <div class="grid grid-cols-1 gap-2">
                            <button type="button" data-ratio-btn="1:1"
                                    class="ratio-btn flex items-center justify-between rounded-xl border px-3.5 py-2.5 text-left text-xs font-semibold transition border-indigo-600 bg-indigo-50 text-indigo-800 ring-1 ring-indigo-600/20">
                                <span>
                                    <span class="block font-bold">1:1 (Persegi / Avatar)</span>
                                    <span class="block text-[11px] font-normal text-indigo-600/80">Default foto profil bulat</span>
                                </span>
                                <span class="w-5 h-5 rounded border-2 border-current shrink-0"></span>
                            </button>

                            <button type="button" data-ratio-btn="3:4"
                                    class="ratio-btn flex items-center justify-between rounded-xl border px-3.5 py-2.5 text-left text-xs font-semibold transition border-slate-200 bg-white text-slate-700 hover:bg-slate-50">
                                <span>
                                    <span class="block font-bold">3:4 (Pas Foto)</span>
                                    <span class="block text-[11px] font-normal text-slate-500">Ukuran standar pas foto</span>
                                </span>
                                <span class="w-4 h-5 rounded border-2 border-current shrink-0"></span>
                            </button>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200/80 bg-slate-50/70 p-4 flex flex-col items-center text-center space-y-2.5">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Pratinjau Hasil</p>
                        <div id="cropper-live-preview" class="cropper-preview-box ratio-1-1 ring-4 ring-white shadow-md"></div>
                        <p id="crop-ratio-caption" class="text-[11px] text-slate-500">Output: 600 &times; 600 px (1:1)</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="px-5 py-4 border-t border-slate-100 bg-slate-50/60 flex flex-col-reverse sm:flex-row sm:items-center justify-end gap-2.5">
            <button type="button" id="btn-cancel-crop"
                    class="rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-sm font-semibold px-4 py-2.5 transition">
                Batal
            </button>
            <button type="button" id="btn-apply-crop"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 disabled:cursor-not-allowed text-white text-sm font-semibold px-5 py-2.5 shadow-sm shadow-indigo-500/20 transition">
                <svg id="apply-crop-spinner" class="hidden w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span id="apply-crop-label">Terapkan &amp; Simpan</span>
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
    (function () {
        const photoInput = document.getElementById('foto_profil');
        const photoForm = document.getElementById('photo-upload-form');
        const croppedImageInput = document.getElementById('cropped_image_input');
        const choosePhotoBtn = document.getElementById('btn-choose-photo');
        const editExistingBtn = document.getElementById('btn-edit-existing-photo');

        const modal = document.getElementById('photo-crop-modal');
        const cropperImage = document.getElementById('cropper-image');
        const livePreview = document.getElementById('cropper-live-preview');
        const ratioCaption = document.getElementById('crop-ratio-caption');
        const cropErrorAlert = document.getElementById('crop-error-alert');

        const btnCloseModal = document.getElementById('btn-close-crop-modal');
        const btnCancelCrop = document.getElementById('btn-cancel-crop');
        const btnApplyCrop = document.getElementById('btn-apply-crop');
        const applySpinner = document.getElementById('apply-crop-spinner');
        const applyLabel = document.getElementById('apply-crop-label');

        const btnZoomIn = document.getElementById('btn-zoom-in');
        const btnZoomOut = document.getElementById('btn-zoom-out');
        const btnRotateLeft = document.getElementById('btn-rotate-left');
        const btnRotateRight = document.getElementById('btn-rotate-right');
        const btnReset = document.getElementById('btn-crop-reset');
        const ratioButtons = document.querySelectorAll('[data-ratio-btn]');

        let cropper = null;
        let currentObjectUrl = null;
        let currentRatioKey = '1:1';
        let isUploading = false;

        const RATIOS = {
            '1:1': { ratio: 1, width: 600, height: 600, previewClass: 'ratio-1-1', caption: 'Output: 600 × 600 px (1:1)' },
            '3:4': { ratio: 3 / 4, width: 600, height: 800, previewClass: 'ratio-3-4', caption: 'Output: 600 × 800 px (3:4 Pas Foto)' },
        };

        function showCropError(message) {
            cropErrorAlert.textContent = message;
            cropErrorAlert.classList.remove('hidden');
        }

        function clearCropError() {
            cropErrorAlert.textContent = '';
            cropErrorAlert.classList.add('hidden');
        }

        function setUploadingState(uploading) {
            isUploading = uploading;
            btnApplyCrop.disabled = uploading;
            btnCancelCrop.disabled = uploading;
            btnCloseModal.disabled = uploading;
            applySpinner.classList.toggle('hidden', !uploading);
            applyLabel.textContent = uploading ? 'Menyimpan...' : 'Terapkan & Simpan';
        }

        function updateRatioUI(ratioKey) {
            currentRatioKey = ratioKey;
            const cfg = RATIOS[ratioKey];

            ratioButtons.forEach((btn) => {
                const isActive = btn.dataset.ratioBtn === ratioKey;
                btn.className = isActive
                    ? 'ratio-btn flex items-center justify-between rounded-xl border px-3.5 py-2.5 text-left text-xs font-semibold transition border-indigo-600 bg-indigo-50 text-indigo-800 ring-1 ring-indigo-600/20'
                    : 'ratio-btn flex items-center justify-between rounded-xl border px-3.5 py-2.5 text-left text-xs font-semibold transition border-slate-200 bg-white text-slate-700 hover:bg-slate-50';
            });

            livePreview.classList.remove('ratio-1-1', 'ratio-3-4');
            livePreview.classList.add(cfg.previewClass);
            ratioCaption.textContent = cfg.caption;

            if (cropper) {
                cropper.setAspectRatio(cfg.ratio);
            }
        }

        function destroyCropper() {
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            if (currentObjectUrl) {
                URL.revokeObjectURL(currentObjectUrl);
                currentObjectUrl = null;
            }
        }

        function closeModal() {
            if (isUploading) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
            destroyCropper();
            cropperImage.removeAttribute('src');
            photoInput.value = '';
            clearCropError();
        }

        function openModalWithSource(src, isObjectUrl) {
            clearCropError();
            destroyCropper();

            if (isObjectUrl) {
                currentObjectUrl = src;
            }

            // Reset rasio default ke 1:1 setiap kali modal dibuka
            updateRatioUI('1:1');

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');

            cropperImage.onload = function () {
                if (typeof Cropper === 'undefined') {
                    showCropError('Library Cropper.js gagal dimuat. Pastikan koneksi internet aktif lalu muat ulang halaman.');
                    return;
                }

                cropper = new Cropper(cropperImage, {
                    aspectRatio: RATIOS[currentRatioKey].ratio,
                    viewMode: 1,
                    dragMode: 'move',
                    autoCropArea: 0.85,
                    responsive: true,
                    restore: false,
                    guides: true,
                    center: true,
                    highlight: false,
                    cropBoxMovable: true,
                    cropBoxResizable: true,
                    toggleDragModeOnDblclick: false,
                    preview: '#cropper-live-preview',
                });
            };

            cropperImage.onerror = function () {
                showCropError('Gagal memuat gambar yang dipilih. Silakan pilih file JPG atau PNG yang valid.');
            };

            cropperImage.src = src;
        }

        choosePhotoBtn.addEventListener('click', function () {
            photoInput.click();
        });

        if (editExistingBtn) {
            editExistingBtn.addEventListener('click', function () {
                const url = this.dataset.photoUrl;
                if (url) {
                    openModalWithSource(url, false);
                }
            });
        }

        photoInput.addEventListener('change', function () {
            if (!this.files || !this.files[0]) return;

            const file = this.files[0];
            const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];

            if (!allowedTypes.includes(file.type)) {
                alert('Format foto tidak didukung. Silakan pilih file berformat JPG, JPEG, atau PNG.');
                this.value = '';
                return;
            }

            if (file.size > 10 * 1024 * 1024) {
                alert('Ukuran file sumber terlalu besar (maksimal 10 MB sebelum dipotong).');
                this.value = '';
                return;
            }

            const objectUrl = URL.createObjectURL(file);
            openModalWithSource(objectUrl, true);
        });

        ratioButtons.forEach((btn) => {
            btn.addEventListener('click', function () {
                updateRatioUI(this.dataset.ratioBtn);
            });
        });

        btnZoomIn.addEventListener('click', function () {
            if (cropper) cropper.zoom(0.1);
        });

        btnZoomOut.addEventListener('click', function () {
            if (cropper) cropper.zoom(-0.1);
        });

        btnRotateLeft.addEventListener('click', function () {
            if (cropper) cropper.rotate(-90);
        });

        btnRotateRight.addEventListener('click', function () {
            if (cropper) cropper.rotate(90);
        });

        btnReset.addEventListener('click', function () {
            if (cropper) {
                cropper.reset();
                cropper.setAspectRatio(RATIOS[currentRatioKey].ratio);
            }
        });

        btnCloseModal.addEventListener('click', closeModal);
        btnCancelCrop.addEventListener('click', closeModal);

        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeModal();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });

        async function uploadCroppedBlob(blob) {
            const csrfToken = photoForm.querySelector('input[name="_token"]').value;
            const formData = new FormData();
            formData.append('_token', csrfToken);
            formData.append('foto_profil', blob, 'foto-profil.jpg');

            const response = await fetch(photoForm.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                const firstError = data.errors && Object.values(data.errors)[0]
                    ? Object.values(data.errors)[0][0]
                    : (data.message || 'Gagal mengunggah foto profil. Silakan coba lagi.');
                throw new Error(firstError);
            }

            window.location.href = data.redirect || window.location.href;
        }

        btnApplyCrop.addEventListener('click', function () {
            if (!cropper || isUploading) return;

            clearCropError();
            setUploadingState(true);

            const cfg = RATIOS[currentRatioKey];
            const canvas = cropper.getCroppedCanvas({
                width: cfg.width,
                height: cfg.height,
                fillColor: '#ffffff',
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high',
            });

            if (!canvas) {
                setUploadingState(false);
                showCropError('Gagal memproses area potongan foto. Silakan coba lagi.');
                return;
            }

            if (typeof canvas.toBlob === 'function') {
                canvas.toBlob(async function (blob) {
                    if (!blob) {
                        setUploadingState(false);
                        showCropError('Gagal mengonversi hasil potongan foto ke file gambar.');
                        return;
                    }

                    try {
                        await uploadCroppedBlob(blob);
                    } catch (error) {
                        setUploadingState(false);
                        showCropError(error.message || 'Terjadi kesalahan saat menyimpan foto.');
                    }
                }, 'image/jpeg', 0.9);
            } else {
                // Fallback Base64 jika browser tidak mendukung canvas.toBlob
                croppedImageInput.value = canvas.toDataURL('image/jpeg', 0.9);
                photoInput.value = '';
                photoForm.submit();
            }
        });
    })();

    const emailInput = document.getElementById('email');
    const emailConfirmInput = document.getElementById('email_confirmation');
    const emailMatchHint = document.getElementById('email-match-hint');

    function updateEmailMatchHint() {
        const a = (emailInput.value || '').trim().toLowerCase();
        const b = (emailConfirmInput.value || '').trim().toLowerCase();
        if (!b) {
            emailMatchHint.textContent = 'Harus sama persis dengan alamat email di sebelah kiri.';
            emailMatchHint.className = 'mt-1 text-[11px] text-slate-400';
            return;
        }
        if (a === b) {
            emailMatchHint.textContent = '✓ Alamat email sudah cocok.';
            emailMatchHint.className = 'mt-1 text-[11px] font-semibold text-emerald-600';
        } else {
            emailMatchHint.textContent = '✕ Konfirmasi email belum cocok.';
            emailMatchHint.className = 'mt-1 text-[11px] font-semibold text-rose-600';
        }
    }

    emailInput.addEventListener('input', updateEmailMatchHint);
    emailConfirmInput.addEventListener('input', updateEmailMatchHint);

    function confirmProfileSave(event) {
        const form = event.target;
        const originalEmail = (form.dataset.originalEmail || '').trim().toLowerCase();
        const newEmail = (emailInput.value || '').trim().toLowerCase();
        const confirmEmail = (emailConfirmInput.value || '').trim().toLowerCase();

        if (newEmail !== confirmEmail) {
            alert('Konfirmasi email tidak cocok. Mohon ketik ulang alamat email yang sama persis.');
            emailConfirmInput.focus();
            return false;
        }

        if (newEmail && newEmail !== originalEmail) {
            return confirm(`Pastikan email ${newEmail} sudah benar dan aktif untuk keperluan verifikasi lupa password. Lanjutkan simpan?`);
        }

        return true;
    }
</script>
@endpush
