<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailPeminjaman extends Model
{
    protected $table = 'detail_peminjaman';

    protected $primaryKey = 'id_detail';

    public $timestamps = false;

    protected $fillable = [
        'id_peminjaman',
        'id_alat',
        'jumlah_pinjam',
        'kondisi_saat_pinjam',
        'kondisi_saat_kembali',
    ];

    protected $casts = [
        'id_peminjaman' => 'integer',
        'id_alat' => 'integer',
        'jumlah_pinjam' => 'integer',
    ];

    public function peminjaman(): BelongsTo
    {
        return $this->belongsTo(Peminjaman::class, 'id_peminjaman', 'id_peminjaman');
    }

    public function alat(): BelongsTo
    {
        return $this->belongsTo(Alat::class, 'id_alat', 'id_alat');
    }
}
