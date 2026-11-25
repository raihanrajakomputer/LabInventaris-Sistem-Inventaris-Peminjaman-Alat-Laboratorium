<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Read-only Eloquent model untuk SQL View `v_laporan_peminjaman`.
 */
class LaporanPeminjamanView extends Model
{
    protected $table = 'v_laporan_peminjaman';

    protected $primaryKey = 'id_peminjaman';

    public $incrementing = false;

    public $timestamps = false;

    protected $casts = [
        'id_peminjaman' => 'integer',
        'jumlah_pinjam' => 'integer',
        'tanggal_pinjam' => 'datetime',
        'jadwal_kembali' => 'datetime',
        'tanggal_kembali_aktual' => 'datetime',
    ];
}
