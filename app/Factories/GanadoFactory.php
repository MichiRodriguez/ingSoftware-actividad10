<?php

namespace App\Factories;

use App\Contracts\IGanadoFactory;
use App\Models\Ganado;

class GanadoFactory implements IGanadoFactory
{
    public function make(array $datos): Ganado
    {
        return new Ganado($datos);
    }
}
