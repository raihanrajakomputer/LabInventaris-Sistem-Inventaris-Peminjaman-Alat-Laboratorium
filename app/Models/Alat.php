<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alat extends Model
{
    protected $table = 'alat';

    protected $primaryKey = 'id_alat';

    public $timestamps = false;

    protected $fillable = [
        'id_lab',
        'kode_alat',
        'nama_alat',
        'stok_tersedia',
        'kondisi_umum',
    ];

    protected $casts = [
        'id_lab' => 'integer',
        'stok_tersedia' => 'integer',
    ];

    public function laboratorium(): BelongsTo
    {
        return $this->belongsTo(Laboratorium::class, 'id_lab', 'id_lab');
    }

    public function detailPeminjaman(): HasMany
    {
        return $this->hasMany(DetailPeminjaman::class, 'id_alat', 'id_alat');
    }
}
