<?php

namespace App\Services;

use App\Contracts\ISolicitudRegistroRepository;
use App\Contracts\IUserFactory;
use App\Contracts\IUserRepository;
use App\Events\SolicitudAprobada;
use App\Events\SolicitudRechazada;
use App\Models\EstadoSolicitud;
use App\Models\SolicitudRegistro;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Lógica de negocio del flujo de solicitudes de registro (SRP).
 *
 * Orquesta: Repository (persistencia) + Factory (creación de usuario)
 * + Observer (notificaciones) sin que el controlador sepa de ninguno.
 */
class SolicitudRegistroService
{
    public function __construct(
        private readonly ISolicitudRegistroRepository $solicitudes,
        private readonly IUserRepository $usuarios,
        private readonly IUserFactory $userFactory,
    ) {}

    /**
     * Un nuevo ganadero/veterinario envía su solicitud (HU-01.1 / RF-01).
     */
    public function enviarSolicitud(array $datos): SolicitudRegistro
    {
        if ($this->solicitudes->existsByEmail($datos['correo'])) {
            throw new ConflictHttpException('Ya existe una solicitud con ese correo.');
        }

        if ($this->usuarios->existsByEmail($datos['correo'])) {
            throw new ConflictHttpException('Ya existe un usuario registrado con ese correo.');
        }

        $estadoPendiente = EstadoSolicitud::where('nombre', 'Pendiente')->firstOrFail();

        $solicitud = new SolicitudRegistro([
            'estado_id' => $estadoPendiente->id,
            'nombre' => $datos['nombre'],
            'apellidos' => $datos['apellidos'],
            'correo' => $datos['correo'],
            'numero_celular' => $datos['numero_celular'],
            'archivo_cedula' => $datos['archivo_cedula'] ?? null,
            'archivo_certificado' => $datos['archivo_certificado'] ?? null,
        ]);

        return $this->solicitudes->save($solicitud);
    }

    public function listar()
    {
        return $this->solicitudes->findAll();
    }

    public function listarPendientes()
    {
        return $this->solicitudes->findPendientes();
    }

    public function obtener(int $id): SolicitudRegistro
    {
        $solicitud = $this->solicitudes->findById($id);

        if (! $solicitud) {
            throw new NotFoundHttpException('Solicitud no encontrada.');
        }

        return $solicitud;
    }

    /**
     * El administrador aprueba o rechaza una solicitud (HU-01.8 / RF-05).
     * Dispara el evento Observer correspondiente.
     */
    public function revisar(int $id, string $decision, ?string $motivo, string $tipoUsuario): SolicitudRegistro
    {
        $solicitud = $this->obtener($id);

        if ($solicitud->estado->nombre !== 'Pendiente') {
            throw new UnprocessableEntityHttpException('La solicitud ya fue revisada.');
        }

        if ($decision === 'aprobar') {
            return $this->aprobar($solicitud, $tipoUsuario);
        }

        return $this->rechazar($solicitud, $motivo ?? 'Sin motivo especificado.');
    }

    // ── Privados ─────────────────────────────────────────────────────────────

    private function aprobar(SolicitudRegistro $solicitud, string $tipoUsuario): SolicitudRegistro
    {
        return DB::transaction(function () use ($solicitud, $tipoUsuario) {
            // Bloqueo pesimista: impide que dos requests concurrentes aprueben
            // la misma solicitud y envíen credenciales dos veces.
            $solicitud = SolicitudRegistro::lockForUpdate()->findOrFail($solicitud->id);

            if ($solicitud->estado->nombre !== 'Pendiente') {
                throw new UnprocessableEntityHttpException('La solicitud ya fue revisada.');
            }

            $estadoAprobado = EstadoSolicitud::where('nombre', 'Aprobado')->firstOrFail();
            $solicitud->estado_id = $estadoAprobado->id;
            $solicitud->motivo_rechazo = null;
            $this->solicitudes->save($solicitud);

            // FACTORY: crea el usuario con el tipo indicado por el admin
            $contrasenaPlana = Str::random(12);

            $usuario = $this->userFactory->make($tipoUsuario, [
                'nombre' => $solicitud->nombre.' '.$solicitud->apellidos,
                'correo' => $solicitud->correo,
                'contrasena' => $contrasenaPlana,
            ]);

            // OBSERVER: notifica aprobación enviando credenciales por correo (una sola vez)
            SolicitudAprobada::dispatch($solicitud, $usuario, $contrasenaPlana);

            return $solicitud->fresh('estado');
        });
    }

    private function rechazar(SolicitudRegistro $solicitud, string $motivo): SolicitudRegistro
    {
        $estadoRechazado = EstadoSolicitud::where('nombre', 'Rechazado')->firstOrFail();
        $solicitud->estado_id = $estadoRechazado->id;
        $solicitud->motivo_rechazo = $motivo;
        $this->solicitudes->save($solicitud);

        // OBSERVER: notifica rechazo con el motivo
        SolicitudRechazada::dispatch($solicitud, $motivo);

        return $solicitud->fresh('estado');
    }
}
