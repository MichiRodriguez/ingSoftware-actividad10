<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUsuarioRequest;
use App\Http\Requests\UpdateUsuarioRequest;
use App\Http\Resources\UserResource;
use App\Services\UsuarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * CRUD de usuarios para administradores (HU-01.4 a HU-01.7).
 * Solo orquesta; la lógica vive en UsuarioService (SRP).
 * Protegido por middleware EsAdministrador.
 */
class UsuarioController extends Controller
{
    public function __construct(
        private readonly UsuarioService $usuarioService,
    ) {}

    /**
     * GET /api/usuarios
     * Lista todos los usuarios con búsqueda opcional (HU-01.5).
     */
    public function index(Request $request): JsonResponse
    {
        $usuarios = $this->usuarioService->listar($request->query('buscar'));

        return response()->json(UserResource::collection($usuarios));
    }

    /**
     * GET /api/usuarios/{id}
     * Devuelve un usuario específico (HU-01.5).
     */
    public function show(int $id): JsonResponse
    {
        try {
            $usuario = $this->usuarioService->obtener($id);

            return response()->json(new UserResource($usuario));
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    /**
     * POST /api/usuarios
     * Crea un usuario directamente (HU-01.4).
     * Usa el Factory para la creación por tipo y dispara UsuarioCreado (Observer).
     */
    public function store(StoreUsuarioRequest $request): JsonResponse
    {
        try {
            $usuario = $this->usuarioService->crear(
                $request->tipo_nombre,
                $request->only(['nombre', 'correo', 'contrasena']),
            );

            return response()->json(new UserResource($usuario), 201);
        } catch (ConflictHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }

    /**
     * PUT /api/usuarios/{id}
     * Actualiza datos de un usuario (HU-01.6).
     */
    public function update(UpdateUsuarioRequest $request, int $id): JsonResponse
    {
        try {
            $datos = $request->only(['nombre', 'correo', 'contrasena']);

            if (isset($datos['contrasena'])) {
                // La contraseña se pasa en texto plano; el cast 'hashed' la cifra al guardar
            }

            $usuario = $this->usuarioService->actualizar($id, $datos);

            return response()->json(new UserResource($usuario));
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (ConflictHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }

    /**
     * DELETE /api/usuarios/{id}
     * Elimina un usuario y revoca sus tokens (HU-01.7).
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->usuarioService->eliminar($id);

            return response()->json(['message' => 'Usuario eliminado correctamente.']);
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }
}
