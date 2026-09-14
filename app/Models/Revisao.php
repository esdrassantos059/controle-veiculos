<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Revisao extends Model
{
    protected $table = 'revisao';

    protected $fillable = ['carro_id', 'data_revisao'];

    protected function casts(): array
    {
        return ['data_revisao' => 'date:Y-m-d'];
    }

    public function carro(): BelongsTo
    {
        return $this->belongsTo(Carro::class, 'carro_id');
    }
}
