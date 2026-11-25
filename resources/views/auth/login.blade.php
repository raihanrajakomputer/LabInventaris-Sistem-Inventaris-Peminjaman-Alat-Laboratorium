<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — LabInventaris</title>
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
    $activeRole = old('role', 'mahasiswa');
@endphp

<div class="w-full max-w-md space-y-6">
    <!-- Brand -->
    <div class="text-center">
        <div class="mx-auto w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-500 to-cyan-500 flex items-center justify-center shadow-xl shadow-indigo-500/30">
            <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" />
            </svg>
        </div>
        <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-white">LabInventaris</h1>
        <p class="mt-1 text-sm text-slate-400">Sistem Inventaris &amp; Peminjaman Alat Laboratorium</p>
    </div>

    <!-- Card -->
    <div class="bg-white rounded-3xl shadow-2xl shadow-black/40 border border-slate-200/80 overflow-hidden">
        <!-- Role Tabs -->
        <div class="p-2 bg-slate-100 grid grid-cols-2 gap-1.5">
            <button type="button" id="tab-mahasiswa" onclick="switchRole('mahasiswa')"
                    class="role-tab rounded-2xl py-2.5 px-3 text-xs sm:text-sm font-bold transition flex items-center justify-center gap-2">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" />
                </svg>
                Peminjam (Mahasiswa)
            </button>
            <button type="button" id="tab-petugas" onclick="switchRole('petugas')"
                    class="role-tab rounded-2xl py-2.5 px-3 text-xs sm:text-sm font-bold transition flex items-center justify-center gap-2">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                </svg>
                Petugas / Asisten Lab
            </button>
        </div>

        <div class="p-6 sm:p-7 space-y-5">
            <!-- Flash messages -->
            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-medium text-emerald-800">
                    {{ session('success') }}
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

            <!-- Role Hint -->
            <div id="role-hint" class="rounded-xl border px-3.5 py-2.5 text-xs"></div>

            <form method="POST" action="{{ route('login.submit') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="role" id="role-input" value="{{ $activeRole }}">

                <div>
                    <label for="identifier" id="identifier-label" class="block text-sm font-semibold text-slate-700 mb-1.5">
                        NIM Mahasiswa
                    </label>
                    <input type="text" id="identifier" name="identifier" value="{{ old('identifier') }}" required autofocus
                           placeholder="Masukkan NIM Anda (contoh: F1G125001)"
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none transition">
                </div>

                <div>
                    <label for="password" class="block text-sm font-semibold text-slate-700 mb-1.5">Password</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required
                               placeholder="Masukkan password"
                               class="w-full rounded-xl border border-slate-300 pl-3.5 pr-11 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none transition">
                        <button type="button" onclick="togglePassword()"
                                class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 text-xs font-semibold px-1 py-0.5">
                            <span id="pw-toggle-text">Lihat</span>
                        </button>
                    </div>
                </div>

                <button type="submit" id="submit-btn"
                        class="w-full rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm py-3 shadow-md shadow-indigo-500/25 transition mt-2">
                    Masuk sebagai Mahasiswa
                </button>
            </form>
        </div>
    </div>

    <p class="text-center text-xs text-slate-500">
        &copy; {{ date('Y') }} LabInventaris &middot; Database <span class="font-mono text-slate-400">db_inventaris_lab</span>
    </p>
</div>

<script>
    const ROLE_CONFIG = {
        mahasiswa: {
            label: 'NIM Mahasiswa',
            placeholder: 'Masukkan NIM Anda (contoh: F1G125001)',
            button: 'Masuk sebagai Mahasiswa',
            hint: '<strong>Portal Peminjam (Khusus Mahasiswa):</strong> Login menggunakan NIM &amp; password untuk melihat alat tersedia dan mengajukan peminjaman.',
            hintClass: 'rounded-xl border border-sky-200 bg-sky-50/80 text-sky-800 px-3.5 py-2.5 text-xs',
            btnClass: 'w-full rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm py-3 shadow-md shadow-indigo-500/25 transition mt-2',
        },
        petugas: {
            label: 'Username Petugas',
            placeholder: 'Masukkan username petugas (contoh: admin_utama)',
            button: 'Masuk sebagai Petugas / Asisten Lab',
            hint: '<strong>Portal Petugas / Asisten Lab:</strong> Akses penuh manajemen data master, laporan peminjaman, dan konfirmasi pengembalian alat.',
            hintClass: 'rounded-xl border border-emerald-200 bg-emerald-50/80 text-emerald-800 px-3.5 py-2.5 text-xs',
            btnClass: 'w-full rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-sm py-3 shadow-md shadow-slate-900/25 transition mt-2',
        },
    };

    function switchRole(role) {
        const cfg = ROLE_CONFIG[role] || ROLE_CONFIG.mahasiswa;
        document.getElementById('role-input').value = role;
        document.getElementById('identifier-label').textContent = cfg.label;
        document.getElementById('identifier').placeholder = cfg.placeholder;
        document.getElementById('submit-btn').textContent = cfg.button;
        document.getElementById('submit-btn').className = cfg.btnClass;

        const hintEl = document.getElementById('role-hint');
        hintEl.innerHTML = cfg.hint;
        hintEl.className = cfg.hintClass;

        const tabMhs = document.getElementById('tab-mahasiswa');
        const tabPtg = document.getElementById('tab-petugas');

        if (role === 'mahasiswa') {
            tabMhs.className = 'role-tab rounded-2xl py-2.5 px-3 text-xs sm:text-sm font-bold transition flex items-center justify-center gap-2 bg-white text-indigo-700 shadow-sm';
            tabPtg.className = 'role-tab rounded-2xl py-2.5 px-3 text-xs sm:text-sm font-semibold transition flex items-center justify-center gap-2 text-slate-500 hover:text-slate-700';
        } else {
            tabPtg.className = 'role-tab rounded-2xl py-2.5 px-3 text-xs sm:text-sm font-bold transition flex items-center justify-center gap-2 bg-white text-slate-900 shadow-sm';
            tabMhs.className = 'role-tab rounded-2xl py-2.5 px-3 text-xs sm:text-sm font-semibold transition flex items-center justify-center gap-2 text-slate-500 hover:text-slate-700';
        }
    }

    function togglePassword() {
        const input = document.getElementById('password');
        const label = document.getElementById('pw-toggle-text');
        if (input.type === 'password') {
            input.type = 'text';
            label.textContent = 'Tutup';
        } else {
            input.type = 'password';
            label.textContent = 'Lihat';
        }
    }

    switchRole(@json($activeRole));
</script>
</body>
</html>
