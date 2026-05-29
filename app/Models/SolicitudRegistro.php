<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolicitudRegistro extends Model
{
    use HasFactory;

    protected $fillable = [
        'estado_id', 'nombre', 'apellidos', 'correo',
        'numero_celular', 'archivo_cedula', 'archivo_certificado',
        'motivo_rechazo',
    ];

    public function estado()
    {
        return $this->belongsTo(EstadoSolicitud::class, 'estado_id');
    }
}
