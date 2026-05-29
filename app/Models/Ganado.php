<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ganado extends Model
{
    protected $fillable = [
        'finca_id', 'estado_salud_id', 'estado_comercial_id',
        'arete', 'nombre', 'sexo', 'raza', 'imagen'
    ];

    public function finca()
    {
        return $this->belongsTo(Finca::class, 'finca_id');
    }

    public function estadoSalud()
    {
        return $this->belongsTo(EstadoSaludGanado::class, 'estado_salud_id');
    }

    public function estadoComercial()
    {
        return $this->belongsTo(EstadoComercialGanado::class, 'estado_comercial_id');
    }

    public function registrosPeso()
    {
        return $this->hasMany(RegistroPeso::class, 'ganado_id');
    }
}