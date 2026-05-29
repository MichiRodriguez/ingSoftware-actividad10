<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadoSaludGanado extends Model
{
    protected $fillable = ['nombre'];

    public function ganados()
    {
        return $this->hasMany(Ganado::class, 'estado_salud_id');
    }
}