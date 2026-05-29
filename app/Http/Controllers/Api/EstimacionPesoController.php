<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RegistroPeso;
use App\Models\Ganado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class EstimacionPesoController extends Controller
{
    private string $mlServiceUrl;

    public function __construct()
    {
        $this->mlServiceUrl = env('ML_SERVICE_URL', 'http://127.0.0.1:5000');
    }

    public function estimar(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:10240',
            'ganado_id' => 'required|exists:ganados,id',
            'breed' => 'nullable|string|in:brahman,cebu,criollo,default',
            'distance_cm' => 'nullable|numeric',
            'camera_fov' => 'nullable|numeric',
        ]);

        $path = $request->file('image')->store('estimaciones', 'public');

        try {
            $response = Http::timeout(60)
                ->attach('image', file_get_contents($request->file('image')->path()), $request->file('image')->getClientOriginalName())
                ->attach('breed', $request->input('breed', 'default'))
                ->attach('distance_cm', (string) $request->input('distance_cm', 500))
                ->attach('camera_fov', (string) $request->input('camera_fov', 24))
                ->post("{$this->mlServiceUrl}/api/estimate");
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'No se pudo conectar con el servicio de estimacion',
                'detalle' => $e->getMessage(),
            ], 503);
        }

        if (!$response->successful()) {
            return response()->json([
                'error' => 'Error en la estimacion',
                'detalle' => $response->json(),
            ], $response->status());
        }

        $data = $response->json();

        $registro = RegistroPeso::create([
            'ganado_id' => $request->ganado_id,
            'peso_estimado' => $data['peso_estimado_kg'] ?? 0,
            'fecha' => now(),
            'confianza' => $data['confianza'] ?? 0,
            'metodo' => $data['metodo'] ?? 'unknown',
            'imagen_path' => $path,
            'medidas' => $data['medidas'] ?? null,
            'raza_estimacion' => $request->input('breed', 'default'),
        ]);

        return response()->json([
            'registro' => $registro,
            'estimacion' => $data,
            'advertencia' => $data['advertencia'] ?? 'Estimacion aproximada.',
        ], 201);
    }

    public function estimarBatch(Request $request)
    {
        $request->validate([
            'images' => 'required|array|min:2|max:5',
            'images.*' => 'image|max:10240',
            'ganado_id' => 'required|exists:ganados,id',
            'breed' => 'nullable|string|in:brahman,cebu,criollo,default',
            'distance_cm' => 'nullable|numeric',
            'camera_fov' => 'nullable|numeric',
        ]);

        $httpRequest = Http::timeout(120);

        foreach ($request->file('images') as $image) {
            $httpRequest = $httpRequest->attach(
                'images',
                file_get_contents($image->path()),
                $image->getClientOriginalName()
            );
        }

        try {
            $response = $httpRequest
                ->attach('breed', $request->input('breed', 'default'))
                ->attach('distance_cm', (string) $request->input('distance_cm', 500))
                ->attach('camera_fov', (string) $request->input('camera_fov', 24))
                ->post("{$this->mlServiceUrl}/api/estimate/batch");
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'No se pudo conectar con el servicio de estimacion',
            ], 503);
        }

        if (!$response->successful()) {
            return response()->json([
                'error' => 'Error en la estimacion',
                'detalle' => $response->json(),
            ], $response->status());
        }

        $data = $response->json();

        $path = $request->file('images')[0]->store('estimaciones', 'public');

        $registro = RegistroPeso::create([
            'ganado_id' => $request->ganado_id,
            'peso_estimado' => $data['peso_estimado_kg'],
            'fecha' => now(),
            'confianza' => 0.75,
            'metodo' => 'batch_average',
            'imagen_path' => $path,
            'medidas' => ['pesos_individuales' => $data['pesos_individuales']],
            'raza_estimacion' => $request->input('breed', 'default'),
        ]);

        return response()->json([
            'registro' => $registro,
            'estimacion' => $data,
        ], 201);
    }

    public function healthCheck()
    {
        try {
            $response = Http::timeout(5)->get("{$this->mlServiceUrl}/api/health");
            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Microservicio ML no disponible',
            ], 503);
        }
    }
}