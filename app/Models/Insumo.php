<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Insumo extends Model
{
    protected $guarded = [];

    public function negocio()
   {
       return $this->belongsTo(Negocio::class);
   }
}
