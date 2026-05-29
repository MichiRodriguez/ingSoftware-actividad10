<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\TipoUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TipoUsuarioSeeder::class);
    }

    // ── Login ─────────────────────────────────────────────────────────────────

    public function test_login_exitoso_devuelve_token(): void
    {
        $usuario = User::factory()->create([
            'correo' => 'ganadero@test.com',
            'contrasena' => 'password123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'correo' => 'ganadero@test.com',
            'contrasena' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'usuario', 'message'])
            ->assertJsonPath('usuario.correo', 'ganadero@test.com');
    }

    public function test_login_con_contrasena_incorrecta_devuelve_401(): void
    {
        User::factory()->create(['correo' => 'test@test.com', 'contrasena' => 'correcta']);

        $response = $this->postJson('/api/auth/login', [
            'correo' => 'test@test.com',
            'contrasena' => 'incorrecta',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_con_correo_inexistente_devuelve_401(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'correo' => 'noexiste@test.com',
            'contrasena' => 'cualquiera',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_sin_correo_devuelve_422(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'contrasena' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['correo']);
    }

    public function test_login_con_correo_invalido_devuelve_422(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'correo' => 'no-es-un-correo',
            'contrasena' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['correo']);
    }

    // ── Me ────────────────────────────────────────────────────────────────────

    public function test_me_devuelve_usuario_autenticado(): void
    {
        $usuario = User::factory()->create();

        $response = $this->actingAs($usuario)
            ->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('correo', $usuario->correo);
    }

    public function test_me_sin_autenticacion_devuelve_401(): void
    {
        $this->getJson('/api/auth/me')->assertStatus(401);
    }

    // ── Logout ────────────────────────────────────────────────────────────────

    public function test_logout_revoca_token(): void
    {
        $usuario = User::factory()->create();
        $token = $usuario->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson('/api/auth/logout');

        $response->assertStatus(200);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_sin_autenticacion_devuelve_401(): void
    {
        $this->postJson('/api/auth/logout')->assertStatus(401);
    }

    // ── Forgot password ───────────────────────────────────────────────────────

    public function test_forgot_password_con_correo_valido_devuelve_200(): void
    {
        User::factory()->create(['correo' => 'usuario@test.com']);

        $response = $this->postJson('/api/auth/forgot-password', [
            'correo' => 'usuario@test.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['message']);
    }

    public function test_forgot_password_con_correo_inexistente_devuelve_200(): void
    {
        // No revelar si el correo existe (seguridad)
        $response = $this->postJson('/api/auth/forgot-password', [
            'correo' => 'noexiste@test.com',
        ]);

        $response->assertStatus(200);
    }

    public function test_forgot_password_sin_correo_devuelve_422(): void
    {
        $this->postJson('/api/auth/forgot-password', [])->assertStatus(422);
    }
}
