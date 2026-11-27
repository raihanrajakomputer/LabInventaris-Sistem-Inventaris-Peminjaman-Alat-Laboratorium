<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi OTP &amp; Reset Password — LabInventaris</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                },
            },
        };
    </script>
</head>
<body class="min-h-full font-sans antialiased bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 flex items-center justify-center p-4 sm:p-6">
<div class="w-full max-w-md space-y-6">
    <div class="text-center">
        <div class="mx-auto w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-500 flex items-center justify-center shadow-xl shadow-emerald-500/30">
            <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
            </svg>
        </div>
        <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-white">Verifikasi OTP</h1>
        <p class="mt-1 text-sm text-slate-400">Langkah 2 dari 2 &middot; Masukkan kode OTP &amp; buat password baru</p>
    </div>

    <div class="bg-white rounded-3xl shadow-2xl shadow-black/40 border border-slate-200/80 p-6 sm:p-7 space-y-5">
        <!-- Kotak Simulasi Mode Demo OTP -->
        @if (! empty($demoOtp))
            <div class="rounded-2xl border border-yellow-300 bg-yellow-50 p-4 text-yellow-950 space-y-1.5">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-yellow-800">Mode Demo — Simulasi OTP</span>
                    @if ($expiresAt)
                        <span class="text-[11px] font-semibold text-yellow-700">Berlaku s/d {{ $expiresAt }}</span>
                    @endif
                </div>
                <p class="text-sm font-medium">
                    Kode OTP Anda:
                    <span class="ml-1 inline-block rounded-lg bg-yellow-200/90 px-2.5 py-0.5 font-mono text-lg font-extrabold tracking-widest text-slate-950">{{ $demoOtp }}</span>
                </p>
                @if ($kontak)
                    <p class="text-[11px] text-yellow-800">
                        Tujuan ({{ $metode === 'email' ? 'Email' : 'No. HP' }}): <span class="font-mono font-semibold">{{ $kontak }}</span>
                    </p>
                @endif
            </div>
        @endif

        @if (session('info'))
            <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-xs font-medium text-sky-800">
                {{ session('info') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-800 space-y-1">
                @foreach ($errors->all() as $error)
                    <p class="font-medium">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.reset.submit') }}" class="space-y-4">
            @csrf

            <div>
                <label for="nim_nip" class="block text-sm font-semibold text-slate-700 mb-1.5">NIM Mahasiswa <span class="text-rose-500">*</span></label>
                <input type="text" id="nim_nip" name="nim_nip" value="{{ old('nim_nip', $nim) }}" required maxlength="30"
                       placeholder="Masukkan NIM Anda"
                       class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-mono focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none">
            </div>

            <div>
                <label for="otp" class="block text-sm font-semibold text-slate-700 mb-1.5">Kode OTP (6 Digit) <span class="text-rose-500">*</span></label>
                <input type="text" id="otp" name="otp" value="{{ old('otp') }}" required maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autofocus
                       placeholder="000000"
                       class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-center font-mono text-lg font-bold tracking-[0.4em] focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none">
            </div>

            <div>
                <label for="password" class="block text-sm font-semibold text-slate-700 mb-1.5">Password Baru <span class="text-rose-500">*</span></label>
                <input type="password" id="password" name="password" required minlength="4" maxlength="100"
                       placeholder="Minimal 4 karakter"
                       class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none">
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-semibold text-slate-700 mb-1.5">Konfirmasi Password Baru <span class="text-rose-500">*</span></label>
                <input type="password" id="password_confirmation" name="password_confirmation" required minlength="4" maxlength="100"
                       placeholder="Ulangi password baru"
                       class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none">
            </div>

            <button type="submit"
                    class="w-full rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm py-3 shadow-md shadow-emerald-600/25 transition mt-2">
                Verifikasi OTP &amp; Ubah Password
            </button>
        </form>

        <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
            <a href="{{ route('password.request') }}" class="font-semibold text-indigo-600 hover:text-indigo-700">&larr; Minta OTP Baru</a>
            <a href="{{ route('login') }}" class="font-semibold text-slate-600 hover:text-slate-900">Kembali ke Login</a>
        </div>
    </div>
</div>
</body>
</html>
