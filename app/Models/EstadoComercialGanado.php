<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadoComercialGanado extends Model
{
    protected $fillable = ['nombre'];

    public function ganados()
    {
        return $this->hasMany(Ganado::class, 'estado_comercial_id');
    }
}