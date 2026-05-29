<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifica que el usuario autenticado sea Administrador.
 * Protege todas las rutas de gestión de usuarios y solicitudes.
 */
class EsAdministrador
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->esAdministrador()) {
            return response()->json(['message' => 'Acceso denegado. Se requiere rol Administrador.'], 403);
        }

        return $next($request);
    }
}
