<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Carro extends Model
{
    protected $table = 'carro';

    protected $fillable = ['pessoa_id', 'marca_id', 'modelo', 'ano', 'placa'];

    protected function casts(): array
    {
        return ['ano' => 'integer'];
    }

    public function pessoa(): BelongsTo
    {
        return $this->belongsTo(Pessoa::class, 'pessoa_id');
    }

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class, 'marca_id');
    }

    public function revisoes(): HasMany
    {
        return $this->hasMany(Revisao::class, 'carro_id');
    }
}
