<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'correo' => $this->correo,
            'tipo' => $this->whenLoaded('tipoUsuario', fn () => $this->tipoUsuario->nombre),
            'creado_en' => $this->created_at?->toISOString(),
        ];
    }
}
