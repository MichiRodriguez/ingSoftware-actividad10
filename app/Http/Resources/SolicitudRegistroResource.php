<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SolicitudRegistroResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'apellidos' => $this->apellidos,
            'correo' => $this->correo,
            'numero_celular' => $this->numero_celular,
            'archivo_cedula' => $this->archivo_cedula,
            'archivo_certificado' => $this->archivo_certificado,
            'estado' => $this->whenLoaded('estado', fn () => $this->estado->nombre),
            'motivo_rechazo' => $this->motivo_rechazo,
            'creado_en' => $this->created_at?->toISOString(),
        ];
    }
}
