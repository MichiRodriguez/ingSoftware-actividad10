<?php

namespace App\Contracts;

use App\Models\Ganado;

interface IGanadoFactory
{
    public function make(array $datos): Ganado;
}
