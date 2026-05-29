<?php

namespace Tests\Feature;

use App\Events\SolicitudAprobada;
use App\Events\SolicitudRechazada;
use App\Models\EstadoSolicitud;
use App\Models\SolicitudRegistro;
use App\Models\User;
use Database\Seeders\EstadoSolicitudSeeder;
use Database\Seeders\TipoUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class SolicitudRegistroTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TipoUsuarioSeeder::class);
        $this->seed(EstadoSolicitudSeeder::class);
    }

    private function datosSolicitud(array $override = []): array
    {
        return array_merge([
            'nombre' => 'Carlos',
            'apellidos' => 'Méndez Arias',
            'correo' => 'carlos@ganadero.com',
            'numero_celular' => '88001234',
        ], $override);
    }

    // ── Enviar solicitud (HU-01.1 / RF-01) ───────────────────────────────────

    public function test_usuario_externo_puede_enviar_solicitud(): void
    {
        $response = $this->postJson('/api/solicitudes', $this->datosSolicitud());

        $response->assertStatus(201)
            ->assertJsonPath('correo', 'carlos@ganadero.com')
            ->assertJsonPath('estado', 'Pendiente');

        $this->assertDatabaseHas('solicitud_registros', ['correo' => 'carlos@ganadero.com']);
    }

    public function test_solicitud_sin_nombre_devuelve_422(): void
    {
        $this->postJson('/api/solicitudes', $this->datosSolicitud(['nombre' => '']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nombre']);
    }

    public function test_solicitud_con_correo_invalido_devuelve_422(): void
    {
        $this->postJson('/api/solicitudes', $this->datosSolicitud(['correo' => 'no-es-correo']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['correo']);
    }

    public function test_solicitud_duplicada_devuelve_409(): void
    {
        $this->postJson('/api/solicitudes', $this->datosSolicitud());
        $this->postJson('/api/solicitudes', $this->datosSolicitud())
            ->assertStatus(409);
    }

    public function test_solicitud_con_correo_ya_registrado_devuelve_409(): void
    {
        User::factory()->create(['correo' => 'carlos@ganadero.com']);

        $this->postJson('/api/solicitudes', $this->datosSolicitud())
            ->assertStatus(409);
    }

    // ── Listar solicitudes (admin) ────────────────────────────────────────────

    public function test_admin_puede_listar_todas_las_solicitudes(): void
    {
        $admin = User::factory()->administrador()->create();
        SolicitudRegistro::factory()->count(3)->create();

        $this->actingAs($admin)
            ->getJson('/api/solicitudes')
            ->assertStatus(200)
            ->assertJsonCount(3);
    }

    public function test_admin_puede_listar_solicitudes_pendientes(): void
    {
        $admin = User::factory()->administrador()->create();
        SolicitudRegistro::factory()->count(2)->pendiente()->create();
        SolicitudRegistro::factory()->aprobada()->create();

        $this->actingAs($admin)
            ->getJson('/api/solicitudes/pendientes')
            ->assertStatus(200)
            ->assertJsonCount(2);
    }

    public function test_ganadero_no_puede_listar_solicitudes(): void
    {
        $ganadero = User::factory()->ganadero()->create();

        $this->actingAs($ganadero)
            ->getJson('/api/solicitudes')
            ->assertStatus(403);
    }

    // ── Revisar solicitud (HU-01.8 / RF-05) ──────────────────────────────────

    public function test_admin_puede_aprobar_solicitud_y_dispara_evento(): void
    {
        Event::fake([SolicitudAprobada::class]);

        $admin = User::factory()->administrador()->create();
        $solicitud = SolicitudRegistro::factory()->pendiente()->create();

        $response = $this->actingAs($admin)
            ->putJson("/api/solicitudes/{$solicitud->id}/revisar", [
                'decision' => 'aprobar',
                'tipo_usuario' => 'Ganadero',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('estado', 'Aprobado');

        $this->assertDatabaseHas('solicitud_registros', [
            'id' => $solicitud->id,
            'estado_id' => EstadoSolicitud::where('nombre', 'Aprobado')->first()->id,
        ]);

        $this->assertDatabaseHas('users', ['correo' => $solicitud->correo]);

        Event::assertDispatched(SolicitudAprobada::class);
    }

    public function test_admin_puede_rechazar_solicitud_con_motivo_y_dispara_evento(): void
    {
        Event::fake([SolicitudRechazada::class]);

        $admin = User::factory()->administrador()->create();
        $solicitud = SolicitudRegistro::factory()->pendiente()->create();

        $response = $this->actingAs($admin)
            ->putJson("/api/solicitudes/{$solicitud->id}/revisar", [
                'decision' => 'rechazar',
                'motivo' => 'Documentación incompleta.',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('estado', 'Rechazado')
            ->assertJsonPath('motivo_rechazo', 'Documentación incompleta.');

        Event::assertDispatched(SolicitudRechazada::class);
    }

    public function test_revisar_solicitud_ya_revisada_devuelve_422(): void
    {
        $admin = User::factory()->administrador()->create();
        $solicitud = SolicitudRegistro::factory()->aprobada()->create();

        $this->actingAs($admin)
            ->putJson("/api/solicitudes/{$solicitud->id}/revisar", [
                'decision' => 'aprobar',
                'tipo_usuario' => 'Ganadero',
            ])
            ->assertStatus(422);
    }

    public function test_rechazar_sin_motivo_devuelve_422(): void
    {
        $admin = User::factory()->administrador()->create();
        $solicitud = SolicitudRegistro::factory()->pendiente()->create();

        $this->actingAs($admin)
            ->putJson("/api/solicitudes/{$solicitud->id}/revisar", [
                'decision' => 'rechazar',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['motivo']);
    }

    public function test_revisar_solicitud_inexistente_devuelve_404(): void
    {
        $admin = User::factory()->administrador()->create();

        $this->actingAs($admin)
            ->putJson('/api/solicitudes/9999/revisar', [
                'decision' => 'aprobar',
                'tipo_usuario' => 'Ganadero',
            ])
            ->assertStatus(404);
    }
}
