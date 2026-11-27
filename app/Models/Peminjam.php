<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

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
        'email',
        'foto_profil',
        'reset_otp',
        'reset_otp_expires_at',
    ];

    protected $hidden = [
        'password',
        'reset_otp',
    ];

    protected $casts = [
        'reset_otp_expires_at' => 'datetime',
    ];

    public function peminjaman(): HasMany
    {
        return $this->hasMany(Peminjaman::class, 'id_peminjam', 'id_peminjam');
    }

    public function getFotoProfilUrlAttribute(): ?string
    {
        if (empty($this->foto_profil)) {
            return null;
        }

        $relativePath = ltrim((string) $this->foto_profil, '/');
        if (! Storage::disk('public')->exists($relativePath)) {
            return null;
        }

        return asset('storage/' . $relativePath);
    }
}
