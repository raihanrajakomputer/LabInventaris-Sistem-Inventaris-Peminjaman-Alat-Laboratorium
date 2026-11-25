<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') — {{ config('app.name', 'Sistem Inventaris Lab') }}</title>
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
    <style>
        @media print {
            .no-print { display: none !important; }
            body, main { background: #fff !important; padding: 0 !important; margin: 0 !important; }
            .print-full { width: 100% !important; max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
        }
    </style>
</head>
<body class="h-full font-sans text-slate-800 antialiased">
@php
    $authUser = session('auth_user', []);
    $isPetugas = ($authUser['role'] ?? null) === 'petugas';
    $isMahasiswa = ($authUser['role'] ?? null) === 'mahasiswa';
@endphp

<div class="min-h-full flex">
    <!-- Mobile Backdrop -->
    <div id="sidebar-backdrop" onclick="toggleSidebar()" class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-xs hidden lg:hidden no-print"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 -translate-x-full lg:translate-x-0 lg:static lg:inset-auto transition-transform duration-200 ease-in-out bg-slate-900 text-slate-200 flex flex-col shrink-0 no-print">
        <!-- Brand -->
        <div class="h-16 flex items-center gap-3 px-5 border-b border-slate-800">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-cyan-500 flex items-center justify-center shadow-lg shadow-indigo-500/25 shrink-0">
                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" />
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-sm font-bold tracking-tight text-white truncate">LabInventaris</p>
                <p class="text-[11px] text-slate-400 truncate">Sistem Alat Laboratorium</p>
            </div>
        </div>

        <!-- Nav -->
        <nav class="flex-1 overflow-y-auto px-3 py-5 space-y-6">
            <div>
                <p class="px-3 mb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Utama</p>
                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-500/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                    </svg>
                    Dashboard
                </a>
            </div>

            <div>
                <p class="px-3 mb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                    {{ $isMahasiswa ? 'Peminjaman Saya' : 'Transaksi' }}
                </p>
                <div class="space-y-1">
                    <a href="{{ route('peminjaman.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('peminjaman.index', 'peminjaman.show', 'peminjaman.kembalikan.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-500/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
                        </svg>
                        {{ $isMahasiswa ? 'Riwayat Peminjaman Saya' : 'Peminjaman & Laporan' }}
                    </a>
                    <a href="{{ route('peminjaman.create') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('peminjaman.create') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-500/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Buat Peminjaman Baru
                    </a>
                </div>
            </div>

            <div>
                <p class="px-3 mb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                    {{ $isPetugas ? 'Data Master' : 'Inventaris' }}
                </p>
                <div class="space-y-1">
                    <a href="{{ route('alat.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('alat.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-500/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                        </svg>
                        Daftar Alat
                    </a>

                    @if ($isPetugas)
                        <a href="{{ route('laboratorium.index') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('laboratorium.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-500/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" />
                            </svg>
                            Laboratorium
                        </a>
                        <a href="{{ route('peminjam.index') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('peminjam.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-500/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                            </svg>
                            Peminjam
                        </a>
                        <a href="{{ route('petugas.index') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('petugas.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-500/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Petugas Lab
                        </a>
                    @endif
                </div>
            </div>
        </nav>

        <!-- Sidebar User Info & Logout -->
        <div class="p-4 border-t border-slate-800 space-y-3">
            <div class="rounded-xl bg-slate-800/80 p-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-full {{ $isPetugas ? 'bg-emerald-500/20 text-emerald-300' : 'bg-sky-500/20 text-sky-300' }} flex items-center justify-center font-bold text-sm shrink-0">
                        {{ strtoupper(substr($authUser['name'] ?? 'U', 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold text-white truncate">{{ $authUser['name'] ?? '-' }}</p>
                        <p class="text-[11px] text-slate-400 font-mono truncate">{{ $authUser['identifier'] ?? '' }}</p>
                    </div>
                </div>
                <div class="mt-2">
                    <span class="inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-[10px] font-bold {{ $isPetugas ? 'bg-emerald-500/15 text-emerald-300' : 'bg-sky-500/15 text-sky-300' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $isPetugas ? 'bg-emerald-400' : 'bg-sky-400' }}"></span>
                        {{ $authUser['role_label'] ?? '' }}
                    </span>
                </div>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="w-full flex items-center justify-center gap-2 rounded-xl border border-slate-700 bg-slate-800/60 hover:bg-rose-600 hover:border-rose-600 text-slate-200 hover:text-white text-xs font-semibold py-2.5 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                    </svg>
                    Keluar (Logout)
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-w-0 print-full">
        <!-- Top Header -->
        <header class="sticky top-0 z-30 h-16 bg-white/90 backdrop-blur-md border-b border-slate-200/80 px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-4 no-print">
            <div class="flex items-center gap-3 min-w-0">
                <button type="button" onclick="toggleSidebar()" class="lg:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-700">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
                <div class="min-w-0">
                    <h1 class="text-base sm:text-lg font-bold text-slate-900 truncate">@yield('header', 'Dashboard')</h1>
                    @hasSection('subheader')
                        <p class="text-xs text-slate-500 truncate hidden sm:block">@yield('subheader')</p>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-2.5 shrink-0">
                <div class="hidden md:flex items-center gap-2 rounded-xl bg-slate-100/90 px-3 py-1.5">
                    <span class="text-xs font-bold text-slate-800 max-w-[160px] truncate">{{ $authUser['name'] ?? '' }}</span>
                    <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $isPetugas ? 'bg-emerald-100 text-emerald-700' : 'bg-sky-100 text-sky-700' }}">
                        {{ $isPetugas ? 'Petugas' : 'Mahasiswa' }}
                    </span>
                </div>

                <a href="{{ route('peminjaman.create') }}"
                   class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs sm:text-sm font-semibold px-3.5 py-2 shadow-sm shadow-indigo-500/20 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span class="hidden sm:inline">Peminjaman Baru</span>
                    <span class="sm:hidden">Pinjam</span>
                </a>

                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" title="Logout"
                            class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white hover:bg-rose-50 hover:border-rose-200 hover:text-rose-700 text-slate-600 text-xs font-semibold px-3 py-2 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                        </svg>
                        <span class="hidden xl:inline">Logout</span>
                    </button>
                </form>
            </div>
        </header>

        <!-- Page Body -->
        <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto space-y-6 print-full">
            <!-- Flash Notifications -->
            @if (session('success'))
                <div class="no-print flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3.5 text-sm text-emerald-800 shadow-xs" role="alert">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="flex-1 font-medium">{{ session('success') }}</div>
                    <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800">&times;</button>
                </div>
            @endif

            @if (session('error'))
                <div class="no-print flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3.5 text-sm text-rose-800 shadow-xs" role="alert">
                    <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <div class="flex-1 font-medium">{{ session('error') }}</div>
                    <button type="button" onclick="this.parentElement.remove()" class="text-rose-600 hover:text-rose-800">&times;</button>
                </div>
            @endif

            @if (session('info'))
                <div class="no-print flex items-start gap-3 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3.5 text-sm text-sky-800 shadow-xs" role="alert">
                    <svg class="w-5 h-5 text-sky-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                    </svg>
                    <div class="flex-1 font-medium">{{ session('info') }}</div>
                    <button type="button" onclick="this.parentElement.remove()" class="text-sky-600 hover:text-sky-800">&times;</button>
                </div>
            @endif

            @if ($errors->any())
                <div class="no-print rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3.5 text-sm text-rose-800">
                    <p class="font-semibold mb-1">Periksa kembali input Anda:</p>
                    <ul class="list-disc list-inside space-y-0.5 text-rose-700">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');
        sidebar.classList.toggle('-translate-x-full');
        backdrop.classList.toggle('hidden');
    }
</script>
@stack('scripts')
</body>
</html>
