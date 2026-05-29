<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'correo' => ['required', 'email', 'max:255'],
            'contrasena' => ['required', 'string', 'min:8'],
            'tipo_nombre' => ['required', 'string', 'in:Ganadero,Veterinario,Administrador'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'correo.required' => 'El correo es obligatorio.',
            'correo.email' => 'El correo no es válido.',
            'contrasena.required' => 'La contraseña es obligatoria.',
            'contrasena.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'tipo_nombre.required' => 'El tipo de usuario es obligatorio.',
            'tipo_nombre.in' => 'El tipo debe ser Ganadero, Veterinario o Administrador.',
        ];
    }
}
