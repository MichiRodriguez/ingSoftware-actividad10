<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewSolicitudRequest;
use App\Http\Requests\StoreSolicitudRequest;
use App\Http\Resources\SolicitudRegistroResource;
use App\Services\SolicitudRegistroService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Gestiona solicitudes de registro (HU-01.1, HU-01.8).
 * La creación es pública; la revisión requiere rol Administrador.
 */
class SolicitudRegistroController extends Controller
{
    public function __construct(
        private readonly SolicitudRegistroService $solicitudService,
    ) {}

    /**
     * POST /api/solicitudes
     * Un usuario externo envía su solicitud de registro (HU-01.1 / RF-01).
     * Ruta pública — no requiere autenticación.
     */
    public function store(StoreSolicitudRequest $request): JsonResponse
    {
        try {
            $solicitud = $this->solicitudService->enviarSolicitud($request->validated());

            return response()->json(new SolicitudRegistroResource($solicitud), 201);
        } catch (ConflictHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }

    /**
     * GET /api/solicitudes
     * Lista todas las solicitudes (admin, HU-01.8).
     */
    public function index(): JsonResponse
    {
        $solicitudes = $this->solicitudService->listar();

        return response()->json(SolicitudRegistroResource::collection($solicitudes));
    }

    /**
     * GET /api/solicitudes/pendientes
     * Lista solo las solicitudes pendientes de revisión (admin).
     */
    public function pendientes(): JsonResponse
    {
        $solicitudes = $this->solicitudService->listarPendientes();

        return response()->json(SolicitudRegistroResource::collection($solicitudes));
    }

    /**
     * GET /api/solicitudes/{id}
     * Devuelve el detalle de una solicitud (admin).
     */
    public function show(int $id): JsonResponse
    {
        try {
            $solicitud = $this->solicitudService->obtener($id);

            return response()->json(new SolicitudRegistroResource($solicitud));
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    /**
     * PUT /api/solicitudes/{id}/revisar
     * Admin aprueba o rechaza una solicitud (HU-01.8 / RF-05).
     * Dispara Observer: SolicitudAprobada o SolicitudRechazada.
     */
    public function revisar(ReviewSolicitudRequest $request, int $id): JsonResponse
    {
        try {
            $solicitud = $this->solicitudService->revisar(
                $id,
                $request->decision,
                $request->motivo,
                $request->tipo_usuario ?? 'Ganadero',
            );

            return response()->json(new SolicitudRegistroResource($solicitud));
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (UnprocessableEntityHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (ConflictHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }
}
