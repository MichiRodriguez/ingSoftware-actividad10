<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['tipo_id', 'nombre', 'contrasena', 'correo'];

    protected $hidden = ['contrasena', 'remember_token'];

    protected $authPasswordName = 'contrasena';

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'contrasena' => 'hashed',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->contrasena;
    }

    public function getEmailForPasswordReset(): string
    {
        return $this->correo;
    }

    /**
     * Indica a los canales de notificación de Laravel que el correo
     * del usuario está en el campo 'correo', no en 'email'.
     */
    public function routeNotificationForMail(): string
    {
        return $this->correo;
    }

    public function tipoUsuario()
    {
        return $this->belongsTo(TipoUsuario::class, 'tipo_id');
    }

    public function fincas()
    {
        return $this->hasMany(Finca::class, 'usuario_id');
    }

    public function fincasAsignadas()
    {
        return $this->hasMany(Finca::class, 'veterinario_id');
    }

    public function reportes()
    {
        return $this->hasMany(Reporte::class, 'usuario_id');
    }

    public function esAdministrador(): bool
    {
        return $this->tipoUsuario?->nombre === 'Administrador';
    }
}
