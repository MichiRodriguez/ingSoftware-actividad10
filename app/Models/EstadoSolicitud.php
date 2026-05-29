<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadoSolicitud extends Model
{
    protected $table = 'estado_solicitud';
    
    protected $fillable = ['nombre'];

    public function solicitudes()
    {
        return $this->hasMany(SolicitudRegistro::class, 'estado_id');
    }
}