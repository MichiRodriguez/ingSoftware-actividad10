<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reporte extends Model
{
    protected $fillable = ['usuario_id', 'fecha_generacion', 'formato'];

    protected $casts = ['fecha_generacion' => 'datetime'];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
