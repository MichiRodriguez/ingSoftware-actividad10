<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EstadoSaludGanado;
use App\Models\EstadoComercialGanado;

class CatalogoController extends Controller
{
    public function estadosSalud()
    {
        return response()->json(EstadoSaludGanado::all());
    }

    public function estadosComerciales()
    {
        return response()->json(EstadoComercialGanado::all());
    }
}
