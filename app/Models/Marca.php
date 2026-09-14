<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Marca extends Model
{
    protected $table = 'marca';

    protected $fillable = ['nome'];

    protected function casts(): array
    {
        return [];
    }

    public function carros(): HasMany
    {
        return $this->hasMany(Carro::class, 'marca_id');
    }
}
