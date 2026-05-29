<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewSolicitudRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'string', 'in:aprobar,rechazar'],
            'tipo_usuario' => ['required_if:decision,aprobar', 'nullable', 'string', 'in:Ganadero,Veterinario'],
            'motivo' => ['required_if:decision,rechazar', 'nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'decision.required' => 'La decisión es obligatoria.',
            'decision.in' => 'La decisión debe ser "aprobar" o "rechazar".',
            'tipo_usuario.required_if' => 'El tipo de usuario es obligatorio al aprobar.',
            'tipo_usuario.in' => 'El tipo debe ser Ganadero o Veterinario.',
            'motivo.required_if' => 'El motivo es obligatorio al rechazar.',
        ];
    }
}
