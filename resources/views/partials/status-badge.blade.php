@php
    $size = $size ?? 'sm';
    $padding = $size === 'lg' ? 'px-3.5 py-1.5 text-sm font-bold' : 'px-2.5 py-1 text-xs font-semibold';
@endphp

@if ($status === 'Menunggu')
    <span class="inline-flex items-center gap-1.5 rounded-full bg-yellow-50 {{ $padding }} text-yellow-800 ring-1 ring-inset ring-yellow-600/25">
        <span class="w-1.5 h-1.5 rounded-full bg-yellow-500 animate-pulse"></span>
        Menunggu
    </span>
@elseif ($status === 'Dipinjam')
    <span class="inline-flex items-center rounded-full bg-blue-50 {{ $padding }} text-blue-700 ring-1 ring-inset ring-blue-600/20">
        Dipinjam
    </span>
@elseif ($status === 'Dikembalikan')
    <span class="inline-flex items-center rounded-full bg-emerald-50 {{ $padding }} text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
        Dikembalikan
    </span>
@elseif ($status === 'Terlambat')
    <span class="inline-flex items-center rounded-full bg-rose-50 {{ $padding }} text-rose-700 ring-1 ring-inset ring-rose-600/20">
        Terlambat
    </span>
@elseif ($status === 'Ditolak')
    <span class="inline-flex items-center rounded-full bg-red-50 {{ $padding }} text-red-700 ring-1 ring-inset ring-red-600/25">
        Ditolak
    </span>
@else
    <span class="inline-flex items-center rounded-full bg-slate-100 {{ $padding }} text-slate-700">
        {{ $status }}
    </span>
@endif
