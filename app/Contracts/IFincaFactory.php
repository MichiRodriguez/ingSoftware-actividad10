<?php

namespace App\Contracts;

use App\Models\Finca;

/**
 * Factory contract for Finca creation.
 */
interface IFincaFactory
{
    public function make(array $datos): Finca;
}




//para prueba