<?php

namespace App\Models;

use App\Models\Concerns\BelongsToNegocio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    use BelongsToNegocio;

    protected $guarded = [];

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }
}
