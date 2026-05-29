<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FincaService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FincaController extends Controller
{
    public function __construct(
        private readonly FincaService $fincaService,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $fincas = $this->fincaService->listar(auth()->user());

        return response()->json($fincas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
        'usuario_id' => 'required|exists:users,id',
        'nombre' => 'required|string|max:255',
        'ubicacion' => 'required|string|max:255',
        'area' => 'required|numeric|min:0',
        'numero_finca' => 'required|string|unique:fincas,numero_finca'
    ]);

        try {
            $finca = $this->fincaService->crear($validated);

            return response()->json([
                'message' => 'Finca registrada correctamente',
                'data' => $finca
            ], 201);
        } catch (ConflictHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $finca = $this->fincaService->obtener((int) $id);

            return response()->json($finca);
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'usuario_id' => 'required|exists:users,id',
            'nombre' => 'required|string|max:255',
            'ubicacion' => 'required|string|max:255',
            'area' => 'required|numeric|min:0',
            'numero_finca' => 'required|string|unique:fincas,numero_finca,' . $id,
        ]);
        try {
            $finca = $this->fincaService->actualizar((int) $id, $validated);

            return response()->json([
                'message' => 'Finca actualizada correctamente',
                'data' => $finca
            ]);
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (ConflictHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $this->fincaService->eliminar((int) $id);

            return response()->json([
                'message' => 'Finca eliminada correctamente'
            ]);
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (BadRequestHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
