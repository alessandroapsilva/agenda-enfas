<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProfessionalWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_professional_user_is_redirected_to_own_workspace(): void
    {
        $professionalId = DB::table('professionals')->insertGetId([
            'name' => 'Profissional Workspace',
            'specialty' => 'Enfermagem',
            'work_start' => '08:00:00',
            'work_end' => '18:00:00',
            'active_days' => json_encode([1,2,3,4,5]),
            'slot_interval' => 30,
            'color' => '#2563eb',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::factory()->create([
            'username' => 'prof.workspace',
            'role' => 'professional',
            'professional_id' => $professionalId,
            'is_active' => true,
            'force_password_change' => false,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('professional.workspace'));

        $this->actingAs($user)
            ->get(route('professional.workspace'))
            ->assertOk()
            ->assertSee('Profissional Workspace')
            ->assertSee('Meu dia');
    }

    public function test_professional_role_cannot_open_global_operational_modules(): void
    {
        $professionalId = DB::table('professionals')->insertGetId([
            'name' => 'Profissional Restrito',
            'specialty' => 'Enfermagem',
            'work_start' => '08:00:00',
            'work_end' => '18:00:00',
            'active_days' => json_encode([1,2,3,4,5]),
            'slot_interval' => 30,
            'color' => '#2563eb',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::factory()->create([
            'username' => 'prof.restrito',
            'role' => 'professional',
            'professional_id' => $professionalId,
            'is_active' => true,
            'force_password_change' => false,
        ]);

        $this->actingAs($user)->get(route('agenda.index'))->assertForbidden();
        $this->actingAs($user)->get(route('appointments.index'))->assertForbidden();
        $this->actingAs($user)->get(route('patients.index'))->assertForbidden();
        $this->actingAs($user)->get(route('enfas.whatsapp'))->assertForbidden();
    }

    public function test_unlinked_professional_cannot_open_workspace(): void
    {
        $user = User::factory()->create([
            'username' => 'prof.sem.vinculo',
            'role' => 'professional',
            'professional_id' => null,
            'is_active' => true,
            'force_password_change' => false,
        ]);

        $this->actingAs($user)
            ->get(route('professional.workspace'))
            ->assertForbidden();
    }
}
