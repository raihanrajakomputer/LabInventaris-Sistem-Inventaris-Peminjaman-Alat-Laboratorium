<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Laboratorium extends Model
{
    protected $table = 'laboratorium';

    protected $primaryKey = 'id_lab';

    public $timestamps = false;

    protected $fillable = [
        'nama_lab',
        'lokasi',
    ];

    public function alat(): HasMany
    {
        return $this->hasMany(Alat::class, 'id_lab', 'id_lab');
    }
}
