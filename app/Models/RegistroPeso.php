<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistroPeso extends Model
{
    protected $fillable = [
        'ganado_id',
        'peso_estimado',
        'peso_corregido',
        'fecha',
        'confianza',
        'metodo',
        'imagen_path',
        'medidas',
        'raza_estimacion',
    ];

    protected $casts = [
        'fecha' => 'date',
        'medidas' => 'array',
    ];

    public function ganado()
    {
        return $this->belongsTo(Ganado::class, 'ganado_id');
    }
}