<?php

use App\Http\Controllers\AlatController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\LaboratoriumController;
use App\Http\Controllers\PeminjamController;
use App\Http\Controllers\PeminjamanController;
use App\Http\Controllers\PetugasController;
use App\Http\Controllers\ProfilController;
use Illuminate\Support\Facades\Route;

// Halaman Login & Lupa Password OTP (khusus user yang belum login)
Route::middleware('guest.session')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.submit');

    Route::get('/lupa-password', [ForgotPasswordController::class, 'showRequestForm'])->name('password.request');
    Route::post('/lupa-password', [ForgotPasswordController::class, 'sendOtp'])->middleware('throttle:5,1')->name('password.send-otp');
    Route::get('/verifikasi-otp', [ForgotPasswordController::class, 'showVerifyForm'])->name('password.verify.form');
    Route::post('/verifikasi-otp', [ForgotPasswordController::class, 'resetPassword'])->middleware('throttle:5,1')->name('password.reset.submit');
});

// Semua halaman aplikasi wajib login terlebih dahulu
Route::middleware('auth.session')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Chatbot Hybrid LabBot (Asisten Lab) dengan rate limiting
    Route::post('/chatbot/ask', [ChatbotController::class, 'ask'])->middleware('throttle:15,1')->name('chatbot.ask');

    // Profil Mahasiswa
    Route::middleware('role:mahasiswa')->group(function () {
        Route::get('/profil', [ProfilController::class, 'index'])->name('profil.index');
        Route::post('/profil/foto', [ProfilController::class, 'updatePhoto'])->name('profil.foto.update');
        Route::put('/profil', [ProfilController::class, 'updateProfile'])->name('profil.update');
        Route::put('/profil/password', [ProfilController::class, 'updatePassword'])->name('profil.password.update');
    });

    // Halaman layanan utama (dapat diakses bebas tanpa blokir kelengkapan profil)
    // Dashboard (menyesuaikan role Mahasiswa vs Petugas)
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Daftar Alat (bisa dilihat oleh Mahasiswa read-only & Petugas)
    Route::get('/alat', [AlatController::class, 'index'])->name('alat.index');

    // Transaksi Peminjaman (Mahasiswa hanya lihat, ajukan & kembalikan milik sendiri; Petugas akses penuh)
    Route::get('/peminjaman', [PeminjamanController::class, 'index'])->name('peminjaman.index');
    Route::get('/peminjaman/create', [PeminjamanController::class, 'create'])->name('peminjaman.create');
    Route::post('/peminjaman', [PeminjamanController::class, 'store'])->name('peminjaman.store');
    Route::get('/peminjaman/{peminjaman}', [PeminjamanController::class, 'show'])->name('peminjaman.show');
    Route::put('/peminjaman/{peminjaman}/kembalikan-mandiri', [PeminjamanController::class, 'kembalikanOlehMahasiswa'])
        ->name('peminjaman.kembalikan.mahasiswa');

    // Khusus Petugas / Asisten Lab
    Route::middleware('role:petugas')->group(function () {
        // CRUD Alat (create/store/edit/update/destroy)
        Route::get('/alat/create', [AlatController::class, 'create'])->name('alat.create');
        Route::post('/alat', [AlatController::class, 'store'])->name('alat.store');
        Route::get('/alat/{alat}/edit', [AlatController::class, 'edit'])->name('alat.edit');
        Route::put('/alat/{alat}', [AlatController::class, 'update'])->name('alat.update');
        Route::patch('/alat/{alat}', [AlatController::class, 'update']);
        Route::delete('/alat/{alat}', [AlatController::class, 'destroy'])->name('alat.destroy');

        // Data Master CRUD penuh
        Route::resource('laboratorium', LaboratoriumController::class)
            ->except(['show'])
            ->parameters(['laboratorium' => 'laboratorium']);

        Route::resource('peminjam', PeminjamController::class)
            ->except(['show'])
            ->parameters(['peminjam' => 'peminjam']);

        Route::resource('petugas', PetugasController::class)
            ->except(['show'])
            ->parameters(['petugas' => 'petugas']);

        // Verifikasi / ACC, Penolakan, Pengembalian & Tandai Terlambat
        Route::patch('/peminjaman/{peminjaman}/setujui', [PeminjamanController::class, 'setujui'])
            ->name('peminjaman.setujui');
        Route::patch('/peminjaman/{peminjaman}/tolak', [PeminjamanController::class, 'tolak'])
            ->name('peminjaman.tolak');
        Route::get('/peminjaman/{peminjaman}/kembalikan', [PeminjamanController::class, 'kembalikanForm'])
            ->name('peminjaman.kembalikan.form');
        Route::put('/peminjaman/{peminjaman}/kembalikan', [PeminjamanController::class, 'kembalikanProcess'])
            ->name('peminjaman.kembalikan.process');
        Route::patch('/peminjaman/{peminjaman}/terlambat', [PeminjamanController::class, 'tandaiTerlambat'])
            ->name('peminjaman.terlambat');
        Route::post('/peminjaman/{peminjaman}/peringatan', [PeminjamanController::class, 'kirimPeringatan'])
            ->name('peminjaman.peringatan');
    });
});
