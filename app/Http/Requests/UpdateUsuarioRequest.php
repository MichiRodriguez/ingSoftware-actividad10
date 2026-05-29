<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['sometimes', 'string', 'max:255'],
            'correo' => ['sometimes', 'email', 'max:255'],
            'contrasena' => ['sometimes', 'string', 'min:8'],
            'tipo_nombre' => ['sometimes', 'string', 'in:Ganadero,Veterinario,Administrador'],
        ];
    }

    public function messages(): array
    {
        return [
            'correo.email' => 'El correo no es válido.',
            'contrasena.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'tipo_nombre.in' => 'El tipo debe ser Ganadero, Veterinario o Administrador.',
        ];
    }
}
