<?php

namespace Tests\Feature\Admin;

use App\Models\AIGeneration;
use App\Models\AITraining;
use App\Models\Chapter;
use App\Models\ReportType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsValidLicense;
use Tests\TestCase;

class UserCapacityTest extends TestCase
{
    use RefreshDatabase;
    use SeedsValidLicense;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true, 'is_active' => true]);
    }

    private function userPayload(string $email): array
    {
        return [
            'name' => 'Usuario nuevo',
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
    }

    public function test_crear_usuario_por_debajo_del_tope_es_exitoso(): void
    {
        $this->seedValidLicense(['max_users' => 3]);
        $admin = $this->admin(); // ocupa 1 de los 3 cupos activos

        $this->actingAs($admin)
            ->post(route('admin.users.store'), $this->userPayload('nuevo@test.com'))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('users', ['email' => 'nuevo@test.com']);
    }

    public function test_crear_usuario_al_llegar_al_tope_es_rechazado(): void
    {
        $this->seedValidLicense(['max_users' => 1]);
        $admin = $this->admin(); // ya ocupa el único cupo activo permitido

        $this->actingAs($admin)
            ->post(route('admin.users.store'), $this->userPayload('otro@test.com'))
            ->assertSessionHasErrors('max_users');

        $this->assertDatabaseMissing('users', ['email' => 'otro@test.com']);
    }

    public function test_reactivar_usuario_al_llegar_al_tope_es_rechazado(): void
    {
        $this->seedValidLicense(['max_users' => 1]);
        $admin = $this->admin(); // ocupa el único cupo activo
        $inactivo = User::factory()->create(['is_active' => false]);

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle-active', $inactivo))
            ->assertSessionHasErrors('max_users');

        $this->assertFalse($inactivo->fresh()->is_active);
    }

    public function test_reactivar_usuario_por_debajo_del_tope_es_exitoso(): void
    {
        $this->seedValidLicense(['max_users' => 3]);
        $admin = $this->admin(); // ocupa 1 de los 3 cupos
        $inactivo = User::factory()->create(['is_active' => false]);

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle-active', $inactivo))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionDoesntHaveErrors();

        $this->assertTrue($inactivo->fresh()->is_active);
    }

    public function test_desactivar_usuario_siempre_esta_permitido_aunque_no_haya_cupo(): void
    {
        $this->seedValidLicense(['max_users' => 1]);
        $admin = $this->admin();
        // Segundo usuario activo, ya por encima del cupo declarado (escenario posible
        // si la licencia se redujo después de la activación). Desactivar SIEMPRE
        // debe estar permitido porque solo libera capacidad, nunca la consume.
        $activo = User::factory()->create(['is_active' => true]);

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle-active', $activo))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionDoesntHaveErrors();

        $this->assertFalse($activo->fresh()->is_active);
    }

    public function test_sin_licencia_activada_no_permite_crear_usuarios(): void
    {
        // Sin ninguna LicenseState persistida, el middleware `license` ya
        // bloquea todo el grupo admin (estado Unlicensed -> block). El admin
        // es redirigido a la pantalla de activación en lugar de crear el
        // usuario: comportamiento seguro y consistente con el resto del
        // sistema (Batch 3), sin necesidad de una excepción especial aquí.
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), $this->userPayload('sinlicencia@test.com'))
            ->assertRedirect(route('license.activation.show'));

        $this->assertDatabaseMissing('users', ['email' => 'sinlicencia@test.com']);
    }

    public function test_desactivar_usuario_no_borra_sus_ai_generations_historicas(): void
    {
        // Spec user-capacity, "No Hard-Delete for Capacity": desactivar un
        // usuario (is_active -> false) NUNCA debe borrar ni cascadear el
        // borrado de su historial de ai_generations. El mecanismo real es un
        // flag, no un DELETE de la fila users, así que la FK con
        // onDelete('cascade') de ai_generations.user_id nunca debería
        // dispararse en este flujo.
        $this->seedValidLicense(['max_users' => 3]);
        $admin = $this->admin(); // ocupa 1 de los 3 cupos
        $usuario = User::factory()->create(['is_active' => true]);
        $generation = $this->createAiGenerationFor($usuario);

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle-active', $usuario))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionDoesntHaveErrors();

        $this->assertFalse($usuario->fresh()->is_active);
        $this->assertDatabaseHas('ai_generations', [
            'id' => $generation->id,
            'user_id' => $usuario->id,
        ]);
    }

    private function createAiGenerationFor(User $user): AIGeneration
    {
        $reportType = ReportType::create(['nombre' => 'Reporte con historial']);
        $chapter = Chapter::create([
            'report_type_id' => $reportType->id,
            'nombre' => 'Capítulo',
            'orden' => 1,
        ]);
        $training = AITraining::create([
            'report_type_id' => $reportType->id,
            'status' => 'ready',
            'system_prompt' => 'Prompt',
            'examples_count' => 1,
        ]);

        return AIGeneration::create([
            'ai_training_id' => $training->id,
            'user_id' => $user->id,
            'chapter_id' => $chapter->id,
            'titulo' => 'Generación histórica',
            'input_content' => 'input',
            'output_content' => 'output',
            'status' => 'completed',
            'generated_at' => now(),
        ]);
    }
}
