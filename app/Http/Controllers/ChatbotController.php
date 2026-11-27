<?php

namespace App\Http\Controllers;

use App\Models\DaftarAlatView;
use App\Models\LaporanPeminjamanView;
use App\Models\Peminjaman;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ChatbotController extends Controller
{
    public function ask(Request $request): JsonResponse
    {
        // Reset riwayat percakapan di server session bila diminta widget
        if ($request->boolean('reset')) {
            $request->session()->forget('labbot_history');

            return response()->json(['success' => true, 'reset' => true]);
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ], [
            'message.required' => 'Silakan ketik pertanyaan terlebih dahulu.',
            'message.max' => 'Pertanyaan maksimal 1000 karakter.',
        ]);

        $message = trim($validated['message']);
        // Riwayat percakapan dikelola sepenuhnya di server session (mencegah client spoofing role 'model')
        $history = (array) $request->session()->get('labbot_history', []);
        $authUser = $request->session()->get('auth_user', []);
        $role = $authUser['role'] ?? 'mahasiswa';
        $isMahasiswa = $role === 'mahasiswa';

        // 1. Ambil konteks real-time dari database MySQL (read-only)
        $alatList = DaftarAlatView::query()
            ->select(['kode_alat', 'nama_alat', 'nama_lab', 'stok_tersedia', 'kondisi_umum'])
            ->orderBy('nama_alat')
            ->get();

        $riwayatMahasiswa = collect();
        $ringkasanPetugas = [
            'menunggu' => 0,
            'dipinjam' => 0,
            'terlambat' => 0,
            'antrean_menunggu' => collect(),
        ];

        if ($isMahasiswa) {
            $nimNip = (string) ($authUser['identifier'] ?? '');
            if ($nimNip !== '') {
                $riwayatMahasiswa = LaporanPeminjamanView::query()
                    ->where('nim_nip', $nimNip)
                    ->orderByDesc('id_peminjaman')
                    ->limit(5)
                    ->get();
            }
        } else {
            $ringkasanPetugas['menunggu'] = Peminjaman::where('status_peminjaman', 'Menunggu')->count();
            $ringkasanPetugas['dipinjam'] = Peminjaman::where('status_peminjaman', 'Dipinjam')->count();
            $ringkasanPetugas['terlambat'] = Peminjaman::where('status_peminjaman', 'Terlambat')->count();
            $ringkasanPetugas['antrean_menunggu'] = LaporanPeminjamanView::query()
                ->where('status_peminjaman', 'Menunggu')
                ->orderByDesc('id_peminjaman')
                ->limit(5)
                ->get();
        }

        // 2. Konfigurasi Gemini API dari config/services.php atau .env
        $apiKey = trim((string) (config('services.gemini.key') ?: env('GEMINI_API_KEY', '')));
        $model = trim((string) (config('services.gemini.model') ?: env('GEMINI_MODEL', 'gemini-3.8-flash')));
        if ($model === '') {
            $model = 'gemini-3.8-flash';
        }

        // 3. Panggil Gemini API jika API Key tersedia; otomatis fallback ke pencarian lokal jika gagal/offline
        if ($apiKey !== '' && $apiKey !== 'masukkan_api_key_kamu_di_sini') {
            try {
                $systemInstruction = $this->buildSystemInstruction(
                    $authUser,
                    $isMahasiswa,
                    $alatList,
                    $riwayatMahasiswa,
                    $ringkasanPetugas,
                    $message
                );

                $reply = $this->callGeminiApi($apiKey, $model, $systemInstruction, $history, $message);

                if ($reply !== null && $reply !== '') {
                    $this->appendServerHistory($request, $message, $reply);

                    return response()->json([
                        'success' => true,
                        'mode' => 'ai',
                        'reply' => $reply,
                    ]);
                }
            } catch (Throwable $e) {
                Log::error('LabBot Gemini API exception, beralih ke mode fallback lokal.', [
                    'model' => $model,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            Log::error('LabBot Gemini API key kosong atau belum dikonfigurasi.');
        }

        // 4. Mode Fallback Otomatis (Anti-Error saat Offline / API tidak tersedia)
        $fallbackReply = $this->buildLocalFallbackReply(
            $message,
            $isMahasiswa,
            $alatList,
            $riwayatMahasiswa,
            $ringkasanPetugas
        );

        $this->appendServerHistory($request, $message, $fallbackReply);

        return response()->json([
            'success' => true,
            'mode' => 'fallback',
            'reply' => $fallbackReply,
        ]);
    }

    private function appendServerHistory(Request $request, string $userMessage, string $botReply): void
    {
        $history = (array) $request->session()->get('labbot_history', []);
        $history[] = ['role' => 'user', 'text' => mb_substr($userMessage, 0, 500)];
        $history[] = ['role' => 'model', 'text' => mb_substr($botReply, 0, 700)];
        $request->session()->put('labbot_history', array_slice($history, -6));
    }

    /**
     * Susun System Instruction ramah berbahasa Indonesia dengan konteks real-time database (terbatas maks 60 alat relevan).
     */
    private function buildSystemInstruction(
        array $authUser,
        bool $isMahasiswa,
        Collection $alatList,
        Collection $riwayatMahasiswa,
        array $ringkasanPetugas,
        string $userMessage = ''
    ): string {
        $namaUser = $authUser['name'] ?? 'Pengguna';
        $roleLabel = $isMahasiswa ? 'Mahasiswa (Peminjam)' : 'Petugas / Asisten Lab';
        $identifier = $authUser['identifier'] ?? '-';

        // Batasi daftar alat di prompt (maks 60 item) dengan memprioritaskan alat yang cocok kata kunci pertanyaan
        $promptAlatList = $alatList;
        $totalAlatCount = $alatList->count();
        if ($totalAlatCount > 60) {
            $lowerMsg = mb_strtolower($userMessage);
            $promptAlatList = $alatList->sortByDesc(function ($item) use ($lowerMsg): int {
                $score = $item->stok_tersedia > 0 ? 1 : 0;
                $name = mb_strtolower((string) $item->nama_alat);
                $code = mb_strtolower((string) $item->kode_alat);
                $lab = mb_strtolower((string) $item->nama_lab);
                if ($lowerMsg !== '' && (str_contains($lowerMsg, $name) || str_contains($lowerMsg, $code) || str_contains($lowerMsg, $lab))) {
                    $score += 10;
                }

                return $score;
            })->take(60);
        }

        $alatLines = $promptAlatList->map(function ($item): string {
            return sprintf(
                '- [%s] %s | Lab: %s | Stok tersedia: %d unit | Kondisi: %s',
                $item->kode_alat,
                $item->nama_alat,
                $item->nama_lab,
                (int) $item->stok_tersedia,
                $item->kondisi_umum
            );
        })->implode("\n");

        if ($totalAlatCount > 60) {
            $alatLines .= "\n(Menampilkan 60 alat paling relevan dari total {$totalAlatCount} jenis alat di database.)";
        }

        if ($alatLines === '') {
            $alatLines = '- Belum ada data alat di database.';
        }

        if ($isMahasiswa) {
            $riwayatLines = $riwayatMahasiswa->map(function ($row): string {
                $jadwal = $row->jadwal_kembali ? $row->jadwal_kembali->format('d M Y H:i') : '-';
                $kembaliAktual = $row->tanggal_kembali_aktual ? $row->tanggal_kembali_aktual->format('d M Y H:i') : 'Belum dikembalikan';

                return sprintf(
                    '- Transaksi #%d | %s (%s) x%d unit | Status: %s | Jadwal kembali: %s | Kembali aktual: %s | Petugas: %s',
                    $row->id_peminjaman,
                    $row->nama_alat,
                    $row->kode_alat,
                    (int) $row->jumlah_pinjam,
                    $row->status_peminjaman,
                    $jadwal,
                    $kembaliAktual,
                    $row->nama_petugas ?? 'Belum Diverifikasi'
                );
            })->implode("\n");

            if ($riwayatLines === '') {
                $riwayatLines = '- Mahasiswa ini belum memiliki riwayat peminjaman.';
            }

            $konteksPengguna = "5 RIWAYAT PEMINJAMAN TERAKHIR MAHASISWA ({$identifier}):\n{$riwayatLines}";
        } else {
            $antreanLines = $ringkasanPetugas['antrean_menunggu']->map(function ($row): string {
                return sprintf(
                    '- #%d | %s (%s) meminjam %s x%d unit | Jadwal kembali: %s',
                    $row->id_peminjaman,
                    $row->nama_peminjam,
                    $row->nim_nip,
                    $row->nama_alat,
                    (int) $row->jumlah_pinjam,
                    $row->jadwal_kembali ? $row->jadwal_kembali->format('d M Y H:i') : '-'
                );
            })->implode("\n");

            $konteksPengguna = sprintf(
                "RINGKASAN PEMINJAMAN LAB REAL-TIME:\n- Status Menunggu ACC: %d transaksi\n- Status Sedang Dipinjam: %d transaksi\n- Status Terlambat: %d transaksi",
                $ringkasanPetugas['menunggu'],
                $ringkasanPetugas['dipinjam'],
                $ringkasanPetugas['terlambat']
            );

            if ($antreanLines !== '') {
                $konteksPengguna .= "\n\nDAFTAR PENGAJUAN MENUNGGU ACC TERBARU:\n{$antreanLines}";
            }
        }

        $waktuSekarang = now()->format('d M Y, H:i');

        return <<<PROMPT
Kamu adalah **LabBot (Asisten Lab)**, asisten virtual yang ramah, sigap, dan membantu untuk aplikasi **LabInventaris** (Sistem Inventaris & Peminjaman Alat Laboratorium).

ATURAN MENJAWAB:
1. Selalu gunakan Bahasa Indonesia yang ramah, sopan, jelas, dan ringkas.
2. Untuk pertanyaan tentang stok alat, lokasi laboratorium, kondisi alat, dan status peminjaman di LabInventaris, WAJIB jawab secara akurat berdasarkan DATA REAL-TIME DATABASE di bawah ini. Jangan mengarang angka stok atau status transaksi.
3. Untuk pertanyaan di luar database lab — seperti perkiraan harga alat di online shop/pasaran, perbandingan merek/spesifikasi, kegunaan alat laboratorium, cara pemakaian, keselamatan kerja (K3), atau pertanyaan pengetahuan umum lainnya — WAJIB jawab secara langsung, informatif, dan membantu (jika berupa harga pasar, sebutkan bahwa itu estimasi kisaran harga).
4. Jika ditanya cara meminjam alat di LabInventaris: arahkan ke menu "Buat Peminjaman Baru" -> pilih alat, jumlah, dan jadwal kembali -> kirim pengajuan (status Menunggu) -> tunggu ACC Petugas -> setelah selesai digunakan, bisa dikembalikan mandiri lewat tombol "Kembalikan Barang Sekarang" di halaman Detail Transaksi atau melalui Petugas Lab.
5. Format jawaban dengan rapi (gunakan poin bullet singkat dan cetak tebal pada info penting bila perlu), jangan terlalu panjang agar nyaman dibaca di jendela chat kecil.

INFORMASI PENGGUNA SAAT INI:
- Nama: {$namaUser}
- Role: {$roleLabel}
- NIM/Username: {$identifier}
- Waktu server: {$waktuSekarang}

DATA REAL-TIME DAFTAR ALAT & STOK (v_daftar_alat):
{$alatLines}

{$konteksPengguna}
PROMPT;
    }

    /**
     * Kirim request ke Gemini API dengan auto-fallback ke model Flash lain yang tersedia saat model utama sibuk (503/timeout).
     */
    private function callGeminiApi(
        string $apiKey,
        string $model,
        string $systemInstruction,
        array $history,
        string $message
    ): ?string {
        $contents = [];

        foreach (array_slice($history, -6) as $turn) {
            $text = trim((string) ($turn['text'] ?? ''));
            if ($text === '') {
                continue;
            }
            $contents[] = [
                'role' => ($turn['role'] ?? 'user') === 'model' ? 'model' : 'user',
                'parts' => [
                    ['text' => $text],
                ],
            ];
        }

        $contents[] = [
            'role' => 'user',
            'parts' => [
                ['text' => $message],
            ],
        ];

        $payload = [
            'systemInstruction' => [
                'parts' => [
                    ['text' => $systemInstruction],
                ],
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.4,
                'maxOutputTokens' => 700,
            ],
        ];

        // 1. Coba model utama dari .env terlebih dahulu
        $reply = $this->requestGeminiModel($apiKey, $model, $payload);
        if ($reply !== null) {
            return $reply;
        }

        // 2. Jika model utama sibuk (503), timeout, atau 404, cari daftar model alternatif yang aktif untuk API key ini
        $fallbackModels = $this->discoverAlternativeGeminiModels($apiKey, $model);

        foreach ($fallbackModels as $altModel) {
            $reply = $this->requestGeminiModel($apiKey, $altModel, $payload);
            if ($reply !== null) {
                return $reply;
            }
        }

        return null;
    }

    /**
     * Eksekusi satu request ke model Gemini tertentu di generativelanguage.googleapis.com.
     */
    private function requestGeminiModel(string $apiKey, string $model, array $payload): ?string
    {
        $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/'
            . rawurlencode($model)
            . ':generateContent?key='
            . rawurlencode($apiKey);

        try {
            $response = Http::withoutVerifying()
                ->timeout(18)
                ->connectTimeout(8)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->acceptJson()
                ->post($endpoint, $payload);

            if ($response->successful()) {
                $parts = $response->json('candidates.0.content.parts', []);
                $textChunks = [];

                foreach ($parts as $part) {
                    if (is_array($part) && isset($part['text']) && is_string($part['text'])) {
                        $textChunks[] = $part['text'];
                    }
                }

                $reply = trim(implode("\n", $textChunks));
                if ($reply !== '') {
                    return $reply;
                }

                Log::error('LabBot Gemini API mengembalikan respons kosong.', [
                    'model' => $model,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            Log::error('LabBot Gemini API gagal merespons.', [
                'model' => $model,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (Throwable $e) {
            Log::error('LabBot Gemini API exception pada model.', [
                'model' => $model,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Ambil daftar model Gemini Flash/text aktif dari API Google (dicache 1 jam) sebagai cadangan bila model utama 503/sibuk.
     *
     * @return array<int, string>
     */
    private function discoverAlternativeGeminiModels(string $apiKey, string $excludeModel): array
    {
        $cacheKey = 'labbot_gemini_models_' . md5($apiKey);

        $models = Cache::remember($cacheKey, now()->addHour(), function () use ($apiKey): array {
            try {
                $response = Http::withoutVerifying()
                    ->timeout(10)
                    ->connectTimeout(6)
                    ->acceptJson()
                    ->get('https://generativelanguage.googleapis.com/v1beta/models?key=' . rawurlencode($apiKey));

                if (! $response->successful()) {
                    Log::error('LabBot gagal mengambil daftar model Gemini.', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);

                    return [];
                }

                $items = $response->json('models', []);
                $candidates = [];

                foreach ($items as $item) {
                    $name = (string) ($item['name'] ?? '');
                    $methods = (array) ($item['supportedGenerationMethods'] ?? []);

                    if (! str_starts_with($name, 'models/gemini-') || ! in_array('generateContent', $methods, true)) {
                        continue;
                    }

                    $shortName = substr($name, strlen('models/'));
                    $lower = strtolower($shortName);

                    // Hanya ambil model teks umum (skip model gambar/audio/tts/embedding/robotics/computer-use)
                    if (preg_match('/(image|audio|tts|embed|robotics|computer-use|thinking|exp-image|veo|lyria|gemma)/i', $lower)) {
                        continue;
                    }

                    $candidates[] = $shortName;
                }

                // Prioritaskan model flash-lite dan flash agar respons cepat & jarang 503
                usort($candidates, function (string $a, string $b): int {
                    $score = static function (string $m): int {
                        $s = 0;
                        if (str_contains($m, 'flash-lite')) {
                            $s += 30;
                        } elseif (str_contains($m, 'flash')) {
                            $s += 20;
                        }
                        if (str_contains($m, 'latest')) {
                            $s += 10;
                        }
                        if (str_contains($m, 'preview')) {
                            $s -= 5;
                        }

                        return $s;
                    };

                    return $score($b) <=> $score($a);
                });

                return $candidates;
            } catch (Throwable $e) {
                Log::error('LabBot exception saat mengambil daftar model Gemini.', [
                    'error' => $e->getMessage(),
                ]);

                return [];
            }
        });

        $filtered = array_values(array_filter(
            $models,
            fn (string $m): bool => $m !== $excludeModel
        ));

        return array_slice($filtered, 0, 3);
    }

    /**
     * Mode Fallback Lokal berbasis pencarian kata kunci ke v_daftar_alat & v_laporan_peminjaman.
     */
    private function buildLocalFallbackReply(
        string $message,
        bool $isMahasiswa,
        Collection $alatList,
        Collection $riwayatMahasiswa,
        array $ringkasanPetugas
    ): string {
        $lower = mb_strtolower($message);

        // 1. Sapaan
        if (preg_match('/^(halo|hai|hi|hello|selamat\s+(pagi|siang|sore|malam)|assalamualaikum|p|permisi)\b/i', $lower)) {
            return "Halo! Saya **LabBot (Asisten Lab)**.\n\nSaat ini saya berjalan dalam **mode data lokal** dan tetap siap membantu Anda mengecek:\n- **Stok & lokasi alat** laboratorium secara real-time\n- **Status peminjaman** Anda\n- **Cara meminjam & mengembalikan** alat\n\nSilakan ketik nama alat atau pilih tombol cepat yang tersedia.";
        }

        // 2. Panduan cara meminjam / mengembalikan
        if (preg_match('/(cara\s+(meminjam|pinjam|mengembalikan|kembali)|prosedur|alur\s+pinjam|panduan)/i', $lower)) {
            return "**Cara Meminjam & Mengembalikan Alat di LabInventaris:**\n"
                . "1. Buka menu **Buat Peminjaman Baru** di sidebar atau header.\n"
                . "2. Pilih alat yang tersedia, tentukan jumlah unit, kondisi awal, dan jadwal kembali.\n"
                . "3. Kirim pengajuan — status awal adalah **Menunggu** sampai diverifikasi (ACC) oleh Petugas Lab.\n"
                . "4. Setelah disetujui (**Dipinjam**), Anda dapat mengembalikan alat kapan saja lewat tombol **Kembalikan Barang Sekarang** di halaman Detail Transaksi atau melalui Petugas Lab.";
        }

        // 3. Status peminjaman / pengajuan menunggu
        if (preg_match('/(status|peminjaman|riwayat|pengajuan|menunggu|acc|terlambat|dipinjam)/i', $lower)) {
            if ($isMahasiswa) {
                if ($riwayatMahasiswa->isEmpty()) {
                    return "Anda belum memiliki riwayat peminjaman alat laboratorium.\n\nAnda dapat mengajukan peminjaman melalui menu **Buat Peminjaman Baru**.";
                }

                $lines = ["**5 Riwayat Peminjaman Terakhir Anda:**"];
                foreach ($riwayatMahasiswa as $row) {
                    $jadwal = $row->jadwal_kembali ? $row->jadwal_kembali->format('d M Y, H:i') : '-';
                    $lines[] = "- **#{$row->id_peminjaman}** {$row->nama_alat} ({$row->jumlah_pinjam} unit) — Status: **{$row->status_peminjaman}** (Jadwal kembali: {$jadwal})";
                }

                return implode("\n", $lines);
            }

            $lines = [
                "**Ringkasan Peminjaman Laboratorium Saat Ini:**",
                "- Menunggu ACC: **{$ringkasanPetugas['menunggu']}** transaksi",
                "- Sedang Dipinjam: **{$ringkasanPetugas['dipinjam']}** transaksi",
                "- Terlambat: **{$ringkasanPetugas['terlambat']}** transaksi",
            ];

            if ($ringkasanPetugas['antrean_menunggu']->isNotEmpty()) {
                $lines[] = "\n**Pengajuan Menunggu ACC Terbaru:**";
                foreach ($ringkasanPetugas['antrean_menunggu'] as $row) {
                    $lines[] = "- **#{$row->id_peminjaman}** {$row->nama_peminjam} — {$row->nama_alat} ({$row->jumlah_pinjam} unit)";
                }
            }

            return implode("\n", $lines);
        }

        // 4. Pencarian alat spesifik berdasarkan kata kunci di v_daftar_alat
        $stopwords = [
            'cek', 'stok', 'stock', 'alat', 'yang', 'ada', 'berapa', 'di', 'lab', 'laboratorium',
            'apa', 'apakah', 'tolong', 'saya', 'aku', 'mau', 'lihat', 'info', 'informasi', 'daftar',
            'tersedia', 'ready', 'kegunaan', 'fungsi', 'cara', 'pakai', 'pemakaian', 'untuk', 'dari',
            'ini', 'itu', 'dan', 'atau', 'dengan', 'bisa', 'dipinjam', 'pinjam', 'mana', 'dimana',
            'lokasi', 'kondisi', 'masih', 'tidak', 'habis', 'kosong', 'rusak', 'baik', 'sekarang',
        ];

        $tokens = preg_split('/[\s,?.!\/\-]+/u', $lower, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $keywords = array_values(array_filter($tokens, function (string $word) use ($stopwords): bool {
            return mb_strlen($word) >= 2 && ! in_array($word, $stopwords, true);
        }));

        if (! empty($keywords)) {
            $matchedAlat = $alatList->filter(function ($item) use ($keywords): bool {
                $haystack = mb_strtolower("{$item->kode_alat} {$item->nama_alat} {$item->nama_lab} {$item->kondisi_umum}");
                foreach ($keywords as $kw) {
                    if (str_contains($haystack, $kw)) {
                        return true;
                    }
                }

                return false;
            });

            if ($matchedAlat->isNotEmpty()) {
                $lines = ["**Hasil Pencarian Alat di Database ({$matchedAlat->count()} ditemukan):**"];
                foreach ($matchedAlat->take(8) as $item) {
                    $lines[] = "- **{$item->nama_alat}** (`{$item->kode_alat}`) — Stok: **{$item->stok_tersedia} unit** | {$item->nama_lab} | Kondisi: {$item->kondisi_umum}";
                }
                if ($matchedAlat->count() > 8) {
                    $sisa = $matchedAlat->count() - 8;
                    $lines[] = "- ...dan {$sisa} alat lainnya di menu **Daftar Alat**.";
                }

                return implode("\n", $lines);
            }
        }

        // 5. Permintaan stok habis / stok tersedia umum
        if (preg_match('/(habis|kosong)/i', $lower)) {
            $habis = $alatList->where('stok_tersedia', '<=', 0);
            if ($habis->isEmpty()) {
                return "Semua jenis alat laboratorium saat ini masih memiliki stok tersedia.";
            }

            $lines = ["**Alat dengan Stok Kosong (0 unit):**"];
            foreach ($habis->take(8) as $item) {
                $lines[] = "- **{$item->nama_alat}** (`{$item->kode_alat}`) — {$item->nama_lab}";
            }

            return implode("\n", $lines);
        }

        if (preg_match('/(stok|tersedia|daftar|alat|inventaris|ready)/i', $lower)) {
            $tersedia = $alatList->where('stok_tersedia', '>', 0);
            if ($tersedia->isEmpty()) {
                return "Saat ini belum ada alat laboratorium yang tersedia untuk dipinjam.";
            }

            $lines = ["**Daftar Alat Tersedia ({$tersedia->count()} jenis alat siap dipinjam):**"];
            foreach ($tersedia->take(8) as $item) {
                $lines[] = "- **{$item->nama_alat}** (`{$item->kode_alat}`) — **{$item->stok_tersedia} unit** ({$item->nama_lab})";
            }
            if ($tersedia->count() > 8) {
                $sisa = $tersedia->count() - 8;
                $lines[] = "- ...dan {$sisa} jenis alat lainnya. Lihat lengkapnya di menu **Daftar Alat**.";
            }

            return implode("\n", $lines);
        }

        // 6. Fallback umum saat AI offline dan kata kunci tidak cocok dengan data lokal
        $totalTersedia = $alatList->where('stok_tersedia', '>', 0)->count();

        return "Maaf, koneksi ke layanan AI sedang tidak tersedia sehingga **LabBot** berjalan dalam **mode data lokal**.\n\n"
            . "Saat ini tersedia **{$totalTersedia} jenis alat** siap dipinjam. Coba tanyakan dengan kata kunci seperti:\n"
            . "- **Cek stok alat tersedia**\n"
            . "- **Nama alat** (misal: *Mikroskop*, *Timbangan*, *Osiloskop*)\n"
            . "- **" . ($isMahasiswa ? 'Status peminjaman saya' : 'Ada pengajuan menunggu?') . "**\n"
            . "- **Cara meminjam alat**";
    }
}
