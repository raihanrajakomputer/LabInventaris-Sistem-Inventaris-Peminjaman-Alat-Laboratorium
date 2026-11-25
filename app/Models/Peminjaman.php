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
    ];

    protected $casts = [
        'id_peminjam' => 'integer',
        'id_petugas' => 'integer',
        'tanggal_pinjam' => 'datetime',
        'jadwal_kembali' => 'datetime',
        'tanggal_kembali_aktual' => 'datetime',
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
}
