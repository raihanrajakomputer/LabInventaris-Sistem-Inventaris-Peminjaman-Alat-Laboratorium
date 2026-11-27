<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Peminjaman extends Model
{
    protected $table = 'peminjaman';

    protected $primaryKey = 'id_peminjaman';

    public $timestamps = false;

    protected $fillable = [
        'id_peminjam',
        'id_petugas',
        'tanggal_pinjam',
        'jadwal_kembali',
        'tanggal_kembali_aktual',
        'status_peminjaman',
        'pesan_peringatan',
        'peringatan_dikirim_at',
    ];

    protected $casts = [
        'id_peminjam' => 'integer',
        'id_petugas' => 'integer',
        'tanggal_pinjam' => 'datetime',
        'jadwal_kembali' => 'datetime',
        'tanggal_kembali_aktual' => 'datetime',
        'peringatan_dikirim_at' => 'datetime',
    ];

    public function peminjam(): BelongsTo
    {
        return $this->belongsTo(Peminjam::class, 'id_peminjam', 'id_peminjam');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(Petugas::class, 'id_petugas', 'id_petugas');
    }

    public function details(): HasMany
    {
        return $this->hasMany(DetailPeminjaman::class, 'id_peminjaman', 'id_peminjaman');
    }

    public function isOverdue(): bool
    {
        return in_array($this->status_peminjaman, ['Dipinjam', 'Terlambat'], true)
            && $this->jadwal_kembali !== null
            && $this->jadwal_kembali->isPast();
    }

    public function getWhatsappNumberAttribute(): ?string
    {
        $raw = preg_replace('/[^0-9]/', '', (string) ($this->peminjam?->no_hp ?? ''));
        if ($raw === '' || strlen($raw) < 8) {
            return null;
        }

        if (str_starts_with($raw, '0')) {
            return '62' . substr($raw, 1);
        }

        if (str_starts_with($raw, '8')) {
            return '62' . $raw;
        }

        return $raw;
    }

    public function getWhatsappWarningUrlAttribute(): ?string
    {
        $phone = $this->whatsapp_number;
        if ($phone === null) {
            return null;
        }

        $nama = $this->peminjam?->nama_peminjam ?? 'Mahasiswa';
        $pesan = trim((string) ($this->pesan_peringatan ?: 'Mohon segera mengembalikan alat yang dipinjam karena sudah melewati batas waktu pengembalian.'));
        $teks = "Halo {$nama}, terkait peminjaman alat laboratorium LabInventaris (#{$this->id_peminjaman}): {$pesan}";

        return 'https://wa.me/' . $phone . '?text=' . rawurlencode($teks);
    }
}
