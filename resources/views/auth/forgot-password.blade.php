<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password — LabInventaris</title>
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
@php
    $activeMetode = old('metode', 'no_hp');
@endphp

<div class="w-full max-w-md space-y-6">
    <div class="text-center">
        <div class="mx-auto w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-500 to-cyan-500 flex items-center justify-center shadow-xl shadow-indigo-500/30">
            <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
            </svg>
        </div>
        <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-white">Lupa Password</h1>
        <p class="mt-1 text-sm text-slate-400">Langkah 1 dari 2 &middot; Verifikasi identitas Mahasiswa via OTP</p>
    </div>

    <div class="bg-white rounded-3xl shadow-2xl shadow-black/40 border border-slate-200/80 p-6 sm:p-7 space-y-5">
        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-800 space-y-1">
                @foreach ($errors->all() as $error)
                    <p class="font-medium">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.send-otp') }}" class="space-y-4">
            @csrf

            <div>
                <label for="nim_nip" class="block text-sm font-semibold text-slate-700 mb-1.5">NIM Mahasiswa <span class="text-rose-500">*</span></label>
                <input type="text" id="nim_nip" name="nim_nip" value="{{ old('nim_nip') }}" required autofocus maxlength="30"
                       placeholder="Masukkan NIM Anda (contoh: F1G125001)"
                       class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-mono focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Metode Verifikasi OTP <span class="text-rose-500">*</span></label>
                <input type="hidden" name="metode" id="metode-input" value="{{ $activeMetode }}">
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" id="btn-metode-email" onclick="switchMetode('email')"
                            class="rounded-xl border py-2.5 px-3 text-xs font-bold transition text-center">
                        Email
                    </button>
                    <button type="button" id="btn-metode-no_hp" onclick="switchMetode('no_hp')"
                            class="rounded-xl border py-2.5 px-3 text-xs font-bold transition text-center">
                        Nomor Telepon (No. HP)
                    </button>
                </div>
            </div>

            <div>
                <label for="kontak" id="kontak-label" class="block text-sm font-semibold text-slate-700 mb-1.5">Nomor HP Terdaftar <span class="text-rose-500">*</span></label>
                <input type="text" id="kontak" name="kontak" value="{{ old('kontak') }}" required maxlength="100"
                       placeholder="Masukkan nomor HP terdaftar"
                       class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none">
                <p id="kontak-hint" class="mt-1 text-[11px] text-slate-400">Harus cocok dengan data pada akun mahasiswa Anda.</p>
            </div>

            <button type="submit"
                    class="w-full rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm py-3 shadow-md shadow-indigo-500/25 transition mt-2">
                Kirim Kode OTP 6 Digit
            </button>
        </form>

        <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
            <a href="{{ route('login') }}" class="font-semibold text-slate-600 hover:text-indigo-600">&larr; Kembali ke Login</a>
            <a href="{{ route('password.verify.form') }}" class="font-semibold text-indigo-600 hover:text-indigo-700">Sudah punya OTP?</a>
        </div>
    </div>
</div>

<script>
    function switchMetode(metode) {
        document.getElementById('metode-input').value = metode;
        const label = document.getElementById('kontak-label');
        const input = document.getElementById('kontak');
        const btnEmail = document.getElementById('btn-metode-email');
        const btnHp = document.getElementById('btn-metode-no_hp');

        if (metode === 'email') {
            label.innerHTML = 'Alamat Email Terdaftar <span class="text-rose-500">*</span>';
            input.type = 'email';
            input.placeholder = 'contoh@mahasiswa.ac.id';
            btnEmail.className = 'rounded-xl border border-indigo-600 bg-indigo-50 text-indigo-700 py-2.5 px-3 text-xs font-bold transition text-center';
            btnHp.className = 'rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 py-2.5 px-3 text-xs font-semibold transition text-center';
        } else {
            label.innerHTML = 'Nomor Telepon (No. HP) Terdaftar <span class="text-rose-500">*</span>';
            input.type = 'text';
            input.placeholder = 'Contoh: 081234567890';
            btnHp.className = 'rounded-xl border border-indigo-600 bg-indigo-50 text-indigo-700 py-2.5 px-3 text-xs font-bold transition text-center';
            btnEmail.className = 'rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 py-2.5 px-3 text-xs font-semibold transition text-center';
        }
    }

    switchMetode(@json($activeMetode));
</script>
</body>
</html>
