<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Read-only Eloquent model untuk SQL View `v_daftar_alat`.
 */
class DaftarAlatView extends Model
{
    protected $table = 'v_daftar_alat';

    protected $primaryKey = 'id_alat';

    public $incrementing = false;

    public $timestamps = false;

    protected $casts = [
        'id_alat' => 'integer',
        'stok_tersedia' => 'integer',
    ];
}
