<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pessoa extends Model
{
    protected $table = 'pessoa';

    protected $fillable = ['nome', 'genero', 'data_nascimento', 'email', 'telefone'];

    protected function casts(): array
    {
        return ['data_nascimento' => 'date:Y-m-d'];
    }

    public function carros(): HasMany
    {
        return $this->hasMany(Carro::class, 'pessoa_id');
    }
}
