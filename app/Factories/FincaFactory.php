<?php

namespace App\Factories;

use App\Contracts\IFincaFactory;
use App\Models\Finca;

/**
 * PATRÓN FACTORY — ConcreteCreator para Finca.
 */
class FincaFactory implements IFincaFactory
{
    public function make(array $datos): Finca
    {
        return new Finca($datos);
    }
}
