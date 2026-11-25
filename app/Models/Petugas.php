<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Petugas extends Model
{
    protected $table = 'petugas';

    protected $primaryKey = 'id_petugas';

    public $timestamps = false;

    protected $fillable = [
        'username',
        'password',
        'nama_petugas',
    ];

    protected $hidden = [
        'password',
    ];

    public function peminjaman(): HasMany
    {
        return $this->hasMany(Peminjaman::class, 'id_petugas', 'id_petugas');
    }
}
