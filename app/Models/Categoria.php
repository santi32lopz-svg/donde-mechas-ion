<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $guarded = [];
    
    public function negocio()
    {
        return $this->belongsTo(Negocio::class);
    }
}