<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 — Akses Ditolak | LabInventaris</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-full flex items-center justify-center p-6 font-sans text-slate-800">
<div class="max-w-md w-full bg-white rounded-3xl border border-slate-200 shadow-sm p-8 text-center space-y-4">
    <div class="mx-auto w-14 h-14 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center">
        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
        </svg>
    </div>
    <p class="text-xs font-bold uppercase tracking-widest text-rose-600">Error 403</p>
    <h1 class="text-2xl font-extrabold text-slate-900">Akses Ditolak</h1>
    <p class="text-sm text-slate-600">
        {{ $exception->getMessage() ?: 'Anda tidak memiliki hak akses untuk membuka halaman atau menjalankan aksi ini.' }}
    </p>
    <div class="pt-3 flex items-center justify-center gap-3">
        <a href="{{ route('dashboard') }}" class="rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-5 py-2.5 transition">
            Kembali ke Dashboard
        </a>
    </div>
</div>
</body>
</html>
