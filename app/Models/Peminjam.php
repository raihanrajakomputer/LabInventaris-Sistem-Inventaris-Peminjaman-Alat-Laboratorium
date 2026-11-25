<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Peminjam extends Model
{
    protected $table = 'peminjam';

    protected $primaryKey = 'id_peminjam';

    public $timestamps = false;

    protected $fillable = [
        'nim_nip',
        'password',
        'nama_peminjam',
        'status_peminjam',
        'no_hp',
    ];

    protected $hidden = [
        'password',
    ];

    public function peminjaman(): HasMany
    {
        return $this->hasMany(Peminjaman::class, 'id_peminjam', 'id_peminjam');
    }
}
