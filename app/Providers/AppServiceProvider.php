<?php

namespace App\Providers;

use App\Models\Peminjam;
use App\Models\Peminjaman;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Share data header/notifikasi ke layout utama dan dashboard (di-memoize sekali per request agar tidak duplikat query)
        View::composer(['layouts.app', 'dashboard'], function ($view) {
            $request = request();
            $cached = $request->attributes->get('_lab_layout_shared');

            if (is_array($cached)) {
                $view->with($cached);

                return;
            }

            $pendingApprovalCount = 0;
            $authUser = session('auth_user', []);
            $missingProfilePhoto = false;
            $missingProfileEmail = false;
            $mahasiswaNotifications = [];
            $resolvedAvatarUrl = null;

            if (($authUser['role'] ?? null) === 'petugas') {
                $pendingApprovalCount = Peminjaman::where('status_peminjaman', 'Menunggu')->count();
            } elseif (($authUser['role'] ?? null) === 'mahasiswa' && ! empty($authUser['id'])) {
                $peminjamId = (int) $authUser['id'];
                $actor = $request->attributes->get('authenticated_actor');
                $peminjam = $actor instanceof Peminjam
                    ? $actor
                    : Peminjam::select('id_peminjam', 'foto_profil', 'email')->find($peminjamId);

                if ($peminjam) {
                    if (
                        ($authUser['foto_profil'] ?? null) !== $peminjam->foto_profil
                        || ($authUser['email'] ?? null) !== $peminjam->email
                    ) {
                        $authUser['foto_profil'] = $peminjam->foto_profil;
                        $authUser['email'] = $peminjam->email;
                        session()->put('auth_user', $authUser);
                    }

                    $resolvedAvatarUrl = $peminjam->foto_profil_url;
                } elseif (! empty($authUser['foto_profil']) && Storage::disk('public')->exists($authUser['foto_profil'])) {
                    $resolvedAvatarUrl = asset('storage/' . ltrim((string) $authUser['foto_profil'], '/'));
                }

                $missingProfilePhoto = empty($resolvedAvatarUrl);
                $missingProfileEmail = empty($authUser['email']);

                $mahasiswaNotifications = $this->buildMahasiswaNotifications($peminjamId);
            }

            $shared = [
                'pendingApprovalCount' => $pendingApprovalCount,
                'missingProfilePhoto' => $missingProfilePhoto,
                'missingProfileEmail' => $missingProfileEmail,
                'isProfileIncomplete' => $missingProfilePhoto || $missingProfileEmail,
                'resolvedAvatarUrl' => $resolvedAvatarUrl,
                'mahasiswaNotifications' => $mahasiswaNotifications,
                'mahasiswaNotificationCount' => count($mahasiswaNotifications),
                'hasHighPriorityNotification' => collect($mahasiswaNotifications)->contains('severity', 'danger'),
            ];

            $request->attributes->set('_lab_layout_shared', $shared);
            $view->with($shared);
        });
    }

    /**
     * Hitung notifikasi otomatis & peringatan resmi petugas untuk transaksi aktif mahasiswa.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildMahasiswaNotifications(int $peminjamId): array
    {
        $activeLoans = Peminjaman::with(['details.alat:id_alat,nama_alat'])
            ->where('id_peminjam', $peminjamId)
            ->whereIn('status_peminjaman', ['Dipinjam', 'Terlambat'])
            ->orderBy('jadwal_kembali')
            ->get();

        if ($activeLoans->isEmpty()) {
            return [];
        }

        $now = now();
        $notifications = [];

        foreach ($activeLoans as $loan) {
            $alatNames = $loan->details
                ->map(fn ($d) => $d->alat?->nama_alat)
                ->filter()
                ->values();

            $alatSummary = $alatNames->isEmpty()
                ? 'alat laboratorium'
                : ($alatNames->count() > 2
                    ? $alatNames->take(2)->implode(', ') . ' +' . ($alatNames->count() - 2) . ' lainnya'
                    : $alatNames->implode(', '));

            $detailUrl = route('peminjaman.show', $loan->id_peminjaman);

            // 1. Peringatan Resmi Petugas (Prioritas Tinggi - Merah)
            if (! empty(trim((string) $loan->pesan_peringatan))) {
                $dikirimPada = $loan->peringatan_dikirim_at
                    ? $loan->peringatan_dikirim_at->format('d M Y, H:i')
                    : '-';

                $notifications[] = [
                    'type' => 'peringatan',
                    'priority' => 1,
                    'severity' => 'danger',
                    'badge' => 'Peringatan Petugas',
                    'id_peminjaman' => $loan->id_peminjaman,
                    'title' => "Peringatan Resmi Petugas (#{$loan->id_peminjaman})",
                    'message' => trim((string) $loan->pesan_peringatan),
                    'meta' => "Dikirim {$dikirimPada} · {$alatSummary}",
                    'url' => $detailUrl,
                ];
            }

            if (! $loan->jadwal_kembali) {
                continue;
            }

            // 2. Sudah Melewati Batas Waktu (Merah)
            if ($now->gt($loan->jadwal_kembali)) {
                $durasiTerlambat = $this->formatDurasiIndonesia($loan->jadwal_kembali, $now);

                $notifications[] = [
                    'type' => 'terlambat',
                    'priority' => 2,
                    'severity' => 'danger',
                    'badge' => 'Terlambat',
                    'id_peminjaman' => $loan->id_peminjaman,
                    'title' => "Melewati Batas Waktu (#{$loan->id_peminjaman})",
                    'message' => "Peminjaman {$alatSummary} sudah terlambat {$durasiTerlambat}. Segera kembalikan alat.",
                    'meta' => 'Jadwal kembali: ' . $loan->jadwal_kembali->format('d M Y, H:i'),
                    'url' => $detailUrl,
                ];
            } elseif ($now->diffInSeconds($loan->jadwal_kembali) <= 24 * 3600) {
                // 3. Mendekati Tenggat Waktu <= 24 jam (Kuning/Oranye)
                $sisaWaktu = $this->formatDurasiIndonesia($now, $loan->jadwal_kembali);

                $notifications[] = [
                    'type' => 'tenggat',
                    'priority' => 3,
                    'severity' => 'warning',
                    'badge' => 'Tenggat < 24 Jam',
                    'id_peminjaman' => $loan->id_peminjaman,
                    'title' => "Mendekati Tenggat Waktu (#{$loan->id_peminjaman})",
                    'message' => "Pengembalian {$alatSummary} tersisa {$sisaWaktu} lagi.",
                    'meta' => 'Jadwal kembali: ' . $loan->jadwal_kembali->format('d M Y, H:i'),
                    'url' => $detailUrl,
                ];
            }
        }

        usort($notifications, fn (array $a, array $b): int => $a['priority'] <=> $b['priority']);

        return $notifications;
    }

    private function formatDurasiIndonesia(Carbon $awal, Carbon $akhir): string
    {
        $totalMenit = max(1, (int) $awal->diffInMinutes($akhir));
        $hari = intdiv($totalMenit, 1440);
        $sisaMenit = $totalMenit % 1440;
        $jam = intdiv($sisaMenit, 60);
        $menit = $sisaMenit % 60;

        $parts = [];
        if ($hari > 0) {
            $parts[] = "{$hari} hari";
        }
        if ($jam > 0) {
            $parts[] = "{$jam} jam";
        }
        if ($hari === 0 && $menit > 0) {
            $parts[] = "{$menit} menit";
        }

        return implode(' ', $parts) ?: 'kurang dari 1 menit';
    }
}
