<?php

namespace App\Providers;

use App\Contracts\ISolicitudRegistroRepository;
use App\Contracts\IFincaFactory;
use App\Contracts\IFincaRepository;
use App\Contracts\IGanadoFactory;
use App\Contracts\IGanadoRepository;
use App\Contracts\IUserFactory;
use App\Contracts\IUserRepository;
use App\Events\SolicitudAprobada;
use App\Events\SolicitudRechazada;
use App\Events\UsuarioCreado;
use App\Factories\UserFactory;
use App\Factories\FincaFactory;
use App\Factories\GanadoFactory;
use App\Listeners\NotificarAprobacionSolicitud;
use App\Listeners\NotificarBienvenidaUsuario;
use App\Listeners\NotificarRechazoSolicitud;
use App\Repositories\EloquentFincaRepository;
use App\Repositories\EloquentGanadoRepository;
use App\Repositories\EloquentSolicitudRegistroRepository;
use App\Repositories\EloquentUserRepository;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Registra los bindings del Service Container.
     *
     * PATRÓN REPOSITORY (DIP):
     *   Las interfaces se ligan a sus implementaciones Eloquent.
     *   Para tests se puede hacer ->bind() a InMemory* sin tocar ningún servicio.
     *
     * PATRÓN FACTORY (singleton):
     *   UserFactory se registra como singleton para reutilizar la misma instancia.
     */
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(IUserRepository::class, EloquentUserRepository::class);
        $this->app->bind(ISolicitudRegistroRepository::class, EloquentSolicitudRegistroRepository::class);
        $this->app->bind(IFincaRepository::class, EloquentFincaRepository::class);
        $this->app->bind(IGanadoRepository::class, EloquentGanadoRepository::class);

        // Factory binding (singleton: se crea una sola instancia)
        $this->app->singleton(IUserFactory::class, UserFactory::class);
        $this->app->singleton(IFincaFactory::class, FincaFactory::class);
        $this->app->singleton(IGanadoFactory::class, GanadoFactory::class);
    }

    /**
     * PATRÓN OBSERVER:
     *   Registra los Listeners a sus Events correspondientes.
     *   Agregar un nuevo observer solo requiere añadir una línea aquí (OCP).
     */
    public function boot(): void
    {
        Event::listen(SolicitudAprobada::class, NotificarAprobacionSolicitud::class);
        Event::listen(SolicitudRechazada::class, NotificarRechazoSolicitud::class);
        Event::listen(UsuarioCreado::class, NotificarBienvenidaUsuario::class);

        // Como el proyecto es API-only no existe la ruta nombrada 'password.reset'.
        // La URL apunta al frontend configurado en FRONTEND_URL.
        // Parámetros como query params para que el router SPA los lea con route.query.
        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            $base = rtrim(config('app.frontend_url', config('app.url')), '/');

            return $base.'/reset-password'
                .'?token='.urlencode($token)
                .'&email='.urlencode($notifiable->getEmailForPasswordReset());
        });
    }
}
