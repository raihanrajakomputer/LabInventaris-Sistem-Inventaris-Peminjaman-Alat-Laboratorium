<?php

namespace App\Http\Controllers;

use App\Models\Peminjam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    private const MAX_OTP_ATTEMPTS = 5;

    public function showRequestForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nim_nip' => ['required', 'string', 'max:30'],
            'metode' => ['required', Rule::in(['email', 'no_hp'])],
            'kontak' => ['required', 'string', 'max:100'],
        ], [
            'nim_nip.required' => 'NIM mahasiswa wajib diisi.',
            'metode.required' => 'Pilih metode verifikasi terlebih dahulu.',
            'kontak.required' => 'Alamat email atau nomor HP wajib diisi.',
        ]);

        $nim = trim($validated['nim_nip']);
        $metode = $validated['metode'];
        $kontak = trim($validated['kontak']);

        $genericError = 'NIM atau data kontak yang Anda masukkan tidak cocok dengan akun mahasiswa aktif.';

        $peminjam = Peminjam::where('nim_nip', $nim)
            ->where('status_peminjam', 'Mahasiswa')
            ->first();

        if (! $peminjam) {
            return back()
                ->withInput($request->only('nim_nip', 'metode', 'kontak'))
                ->withErrors(['kontak' => $genericError]);
        }

        if ($metode === 'email') {
            if (empty($peminjam->email) || strtolower(trim($peminjam->email)) !== strtolower($kontak)) {
                return back()
                    ->withInput($request->only('nim_nip', 'metode', 'kontak'))
                    ->withErrors(['kontak' => $genericError]);
            }
        } else {
            $dbPhone = $this->normalizeIndonesianPhone((string) $peminjam->no_hp);
            $inputPhone = $this->normalizeIndonesianPhone($kontak);

            if ($dbPhone === '' || $dbPhone !== $inputPhone) {
                return back()
                    ->withInput($request->only('nim_nip', 'metode', 'kontak'))
                    ->withErrors(['kontak' => $genericError]);
            }
        }

        // Buat kode OTP 6 digit acak dan berlaku 10 menit
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(10);

        // Simpan hash OTP di cache server (bukan plaintext di DB) & reset penghitung percobaan gagal
        Cache::put($this->otpCacheKey($peminjam->id_peminjam), Hash::make($otp), $expiresAt);
        Cache::forget($this->otpAttemptsKey($peminjam->id_peminjam));

        // Catat masa berlaku di tabel peminjam tanpa menyimpan OTP plaintext
        $peminjam->update([
            'reset_otp' => null,
            'reset_otp_expires_at' => $expiresAt,
        ]);

        $mailStatus = null;
        if ($metode === 'email') {
            try {
                Mail::raw(
                    "Halo {$peminjam->nama_peminjam},\n\n"
                    . "Kode OTP reset password akun LabInventaris Anda adalah: {$otp}\n"
                    . "Kode ini berlaku selama 10 menit (hingga {$expiresAt->format('d M Y H:i')}).\n\n"
                    . "Jika Anda tidak meminta reset password, abaikan pesan ini.",
                    function ($message) use ($peminjam) {
                        $message->to($peminjam->email)
                            ->subject('Kode OTP Reset Password — LabInventaris');
                    }
                );
                $mailStatus = 'Kode OTP telah diproses untuk dikirim ke email terdaftar Anda.';
            } catch (\Throwable $e) {
                Log::warning('Gagal mengirim email OTP (SMTP belum aktif): ' . $e->getMessage());
                $mailStatus = 'Pengiriman email SMTP belum aktif pada lingkungan ini.';
            }
        }

        // Hanya tampilkan kode OTP simulasi pada lingkungan lokal + debug aktif (tidak pernah di production)
        $allowDemoOtp = app()->isLocal() && (bool) config('app.debug', false);

        $sessionData = [
            'otp_reset_nim' => $peminjam->nim_nip,
            'otp_reset_nama' => $peminjam->nama_peminjam,
            'otp_reset_metode' => $metode,
            'otp_reset_kontak' => $this->maskContact($metode === 'email' ? (string) $peminjam->email : (string) $peminjam->no_hp, $metode),
            'otp_expires_at' => $expiresAt->format('H:i'),
        ];

        if ($allowDemoOtp) {
            $sessionData['otp_demo_code'] = $otp;
        } else {
            $request->session()->forget('otp_demo_code');
        }

        $request->session()->put($sessionData);

        return redirect()
            ->route('password.verify.form')
            ->with('info', $mailStatus ?? 'Kode OTP 6 digit telah dibuat dan berlaku selama 10 menit.');
    }

    public function showVerifyForm(Request $request): View
    {
        $nim = $request->session()->get('otp_reset_nim');
        $nama = $request->session()->get('otp_reset_nama');
        $metode = $request->session()->get('otp_reset_metode');
        $kontak = $request->session()->get('otp_reset_kontak');
        $demoOtp = (app()->isLocal() && (bool) config('app.debug', false))
            ? $request->session()->get('otp_demo_code')
            : null;
        $expiresAt = $request->session()->get('otp_expires_at');

        return view('auth.verify-otp', compact('nim', 'nama', 'metode', 'kontak', 'demoOtp', 'expiresAt'));
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nim_nip' => ['required', 'string', 'max:30'],
            'otp' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:4', 'max:100', 'confirmed'],
        ], [
            'nim_nip.required' => 'NIM mahasiswa wajib diisi.',
            'otp.required' => 'Kode OTP 6 digit wajib diisi.',
            'otp.digits' => 'Kode OTP harus terdiri dari tepat 6 digit angka.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal 4 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        $nim = trim($validated['nim_nip']);
        $otp = trim($validated['otp']);

        $peminjam = Peminjam::where('nim_nip', $nim)
            ->where('status_peminjam', 'Mahasiswa')
            ->first();

        if (! $peminjam) {
            return back()
                ->withInput($request->only('nim_nip'))
                ->withErrors(['otp' => 'Permintaan OTP tidak ditemukan atau sudah kedaluwarsa. Silakan minta kode OTP baru.']);
        }

        $hashedOtp = Cache::get($this->otpCacheKey($peminjam->id_peminjam));
        $legacyPlainOtp = ! empty($peminjam->reset_otp) ? (string) $peminjam->reset_otp : null;

        if (empty($hashedOtp) && $legacyPlainOtp === null) {
            return back()
                ->withInput($request->only('nim_nip'))
                ->withErrors(['otp' => 'Permintaan OTP tidak ditemukan atau sudah digunakan. Silakan minta kode OTP baru.']);
        }

        if (! $peminjam->reset_otp_expires_at || $peminjam->reset_otp_expires_at->isPast()) {
            $this->clearOtpState($peminjam);

            return back()
                ->withInput($request->only('nim_nip'))
                ->withErrors(['otp' => 'Kode OTP sudah kedaluwarsa (melebihi 10 menit). Silakan minta kode OTP baru.']);
        }

        $isValidOtp = ! empty($hashedOtp)
            ? Hash::check($otp, (string) $hashedOtp)
            : hash_equals((string) $legacyPlainOtp, $otp);

        if (! $isValidOtp) {
            $attemptsKey = $this->otpAttemptsKey($peminjam->id_peminjam);
            $attempts = (int) Cache::get($attemptsKey, 0) + 1;
            Cache::put($attemptsKey, $attempts, now()->addMinutes(15));

            if ($attempts >= self::MAX_OTP_ATTEMPTS) {
                $this->clearOtpState($peminjam);
                $request->session()->forget('otp_demo_code');

                return back()
                    ->withInput($request->only('nim_nip'))
                    ->withErrors(['otp' => 'Terlalu banyak percobaan OTP salah. Kode OTP telah dihanguskan, silakan minta kode OTP baru.']);
            }

            $sisa = self::MAX_OTP_ATTEMPTS - $attempts;

            return back()
                ->withInput($request->only('nim_nip'))
                ->withErrors(['otp' => "Kode OTP tidak sesuai. Sisa percobaan: {$sisa} kali."]);
        }

        $peminjam->update([
            'password' => Hash::make($validated['password']),
            'reset_otp' => null,
            'reset_otp_expires_at' => null,
        ]);

        Cache::forget($this->otpCacheKey($peminjam->id_peminjam));
        Cache::forget($this->otpAttemptsKey($peminjam->id_peminjam));

        $request->session()->forget([
            'otp_reset_nim',
            'otp_reset_nama',
            'otp_reset_metode',
            'otp_reset_kontak',
            'otp_demo_code',
            'otp_expires_at',
        ]);
        $request->session()->regenerate();

        return redirect()
            ->route('login')
            ->with('success', 'Password berhasil diubah, silakan login kembali.');
    }

    private function otpCacheKey(int $peminjamId): string
    {
        return "lab_otp_hash:{$peminjamId}";
    }

    private function otpAttemptsKey(int $peminjamId): string
    {
        return "lab_otp_attempts:{$peminjamId}";
    }

    private function clearOtpState(Peminjam $peminjam): void
    {
        Cache::forget($this->otpCacheKey($peminjam->id_peminjam));
        Cache::forget($this->otpAttemptsKey($peminjam->id_peminjam));
        $peminjam->update([
            'reset_otp' => null,
            'reset_otp_expires_at' => null,
        ]);
    }

    private function normalizeIndonesianPhone(string $raw): string
    {
        $digits = preg_replace('/[^0-9]/', '', $raw) ?? '';
        if ($digits === '') {
            return '';
        }
        if (str_starts_with($digits, '0')) {
            return '62' . substr($digits, 1);
        }
        if (str_starts_with($digits, '8')) {
            return '62' . $digits;
        }

        return $digits;
    }

    private function maskContact(string $value, string $metode): string
    {
        if ($metode === 'email' && str_contains($value, '@')) {
            [$local, $domain] = explode('@', $value, 2);
            $visible = mb_substr($local, 0, min(2, mb_strlen($local)));

            return $visible . str_repeat('*', max(2, mb_strlen($local) - mb_strlen($visible))) . '@' . $domain;
        }

        $len = strlen($value);
        if ($len <= 4) {
            return str_repeat('*', $len);
        }

        return substr($value, 0, 3) . str_repeat('*', $len - 5) . substr($value, -2);
    }
}
