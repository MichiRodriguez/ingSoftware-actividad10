<?php

namespace Tests\Feature;

use App\Events\UsuarioCreado;
use App\Models\User;
use Database\Seeders\TipoUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UsuarioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TipoUsuarioSeeder::class);
    }

    // ── Autorización ──────────────────────────────────────────────────────────

    public function test_no_autenticado_no_puede_listar_usuarios(): void
    {
        $this->getJson('/api/usuarios')->assertStatus(401);
    }

    public function test_ganadero_no_puede_listar_usuarios(): void
    {
        $ganadero = User::factory()->ganadero()->create();

        $this->actingAs($ganadero)
            ->getJson('/api/usuarios')
            ->assertStatus(403);
    }

    // ── Index (HU-01.5) ───────────────────────────────────────────────────────

    public function test_admin_puede_listar_usuarios(): void
    {
        $admin = User::factory()->administrador()->create();
        User::factory()->ganadero()->count(3)->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/usuarios');

        $response->assertStatus(200)
            ->assertJsonCount(4); // 3 ganaderos + el admin mismo
    }

    public function test_admin_puede_buscar_usuarios_por_nombre(): void
    {
        $admin = User::factory()->administrador()->create();
        User::factory()->ganadero()->create(['nombre' => 'Juan Perez']);
        User::factory()->ganadero()->create(['nombre' => 'Maria Lopez']);

        $response = $this->actingAs($admin)
            ->getJson('/api/usuarios?buscar=Juan');

        $response->assertStatus(200)
            ->assertJsonCount(1);
    }

    // ── Show (HU-01.5) ────────────────────────────────────────────────────────

    public function test_admin_puede_ver_usuario_por_id(): void
    {
        $admin = User::factory()->administrador()->create();
        $ganadero = User::factory()->ganadero()->create();

        $this->actingAs($admin)
            ->getJson("/api/usuarios/{$ganadero->id}")
            ->assertStatus(200)
            ->assertJsonPath('correo', $ganadero->correo);
    }

    public function test_show_usuario_inexistente_devuelve_404(): void
    {
        $admin = User::factory()->administrador()->create();

        $this->actingAs($admin)
            ->getJson('/api/usuarios/9999')
            ->assertStatus(404);
    }

    // ── Store (HU-01.4) ───────────────────────────────────────────────────────

    public function test_admin_puede_crear_usuario_y_dispara_evento(): void
    {
        Event::fake([UsuarioCreado::class]);

        $admin = User::factory()->administrador()->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/usuarios', [
                'nombre' => 'Nuevo Ganadero',
                'correo' => 'nuevo@bovweight.com',
                'contrasena' => 'secreta123',
                'tipo_nombre' => 'Ganadero',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('nombre', 'Nuevo Ganadero');

        $this->assertDatabaseHas('users', ['correo' => 'nuevo@bovweight.com']);
        Event::assertDispatched(UsuarioCreado::class);
    }

    public function test_crear_usuario_con_correo_duplicado_devuelve_409(): void
    {
        $admin = User::factory()->administrador()->create();
        User::factory()->create(['correo' => 'existente@test.com']);

        $this->actingAs($admin)
            ->postJson('/api/usuarios', [
                'nombre' => 'Otro',
                'correo' => 'existente@test.com',
                'contrasena' => 'password123',
                'tipo_nombre' => 'Ganadero',
            ])
            ->assertStatus(409);
    }

    public function test_crear_usuario_sin_tipo_devuelve_422(): void
    {
        $admin = User::factory()->administrador()->create();

        $this->actingAs($admin)
            ->postJson('/api/usuarios', [
                'nombre' => 'Sin Tipo',
                'correo' => 'sintipo@test.com',
                'contrasena' => 'password123',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['tipo_nombre']);
    }

    public function test_crear_usuario_con_tipo_invalido_devuelve_422(): void
    {
        $admin = User::factory()->administrador()->create();

        $this->actingAs($admin)
            ->postJson('/api/usuarios', [
                'nombre' => 'Tipo Malo',
                'correo' => 'tipomalo@test.com',
                'contrasena' => 'password123',
                'tipo_nombre' => 'TipoInexistente',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['tipo_nombre']);
    }

    // ── Update (HU-01.6) ──────────────────────────────────────────────────────

    public function test_admin_puede_actualizar_nombre_de_usuario(): void
    {
        $admin = User::factory()->administrador()->create();
        $ganadero = User::factory()->ganadero()->create();

        $this->actingAs($admin)
            ->putJson("/api/usuarios/{$ganadero->id}", ['nombre' => 'Nombre Actualizado'])
            ->assertStatus(200)
            ->assertJsonPath('nombre', 'Nombre Actualizado');
    }

    public function test_actualizar_usuario_con_correo_en_uso_devuelve_409(): void
    {
        $admin = User::factory()->administrador()->create();
        $ganadero = User::factory()->ganadero()->create();
        User::factory()->create(['correo' => 'en-uso@test.com']);

        $this->actingAs($admin)
            ->putJson("/api/usuarios/{$ganadero->id}", ['correo' => 'en-uso@test.com'])
            ->assertStatus(409);
    }

    // ── Destroy (HU-01.7) ────────────────────────────────────────────────────

    public function test_admin_puede_eliminar_usuario(): void
    {
        $admin = User::factory()->administrador()->create();
        $ganadero = User::factory()->ganadero()->create();

        $this->actingAs($admin)
            ->deleteJson("/api/usuarios/{$ganadero->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('users', ['id' => $ganadero->id]);
    }

    public function test_eliminar_usuario_inexistente_devuelve_404(): void
    {
        $admin = User::factory()->administrador()->create();

        $this->actingAs($admin)
            ->deleteJson('/api/usuarios/9999')
            ->assertStatus(404);
    }
}
