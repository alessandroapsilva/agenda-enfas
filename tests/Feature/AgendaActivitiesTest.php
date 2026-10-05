<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AgendaActivitiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendant_can_create_and_complete_activity(): void
    {
        $attendant = User::factory()->create([
            'role' => 'attendant',
            'permissions' => null,
            'is_active' => true,
            'force_password_change' => false,
        ]);

        $patient = Patient::create([
            'name' => 'Paciente Atividade',
            'phone' => '11999999999',
            'rgea_number' => 'RGEA-ATV-001',
            'preferred_contact_channel' => 'whatsapp',
            'is_active' => true,
        ]);

        $this->actingAs($attendant)
            ->get(route('activities.index'))
            ->assertOk()
            ->assertSee('Minhas atividades');

        $this->actingAs($attendant)
            ->post(route('activities.store'), [
                'title' => 'Retornar ao paciente',
                'patient_id' => $patient->id,
                'assigned_user_id' => $attendant->id,
                'priority' => 'high',
                'due_at' => now()->addHour()->format('Y-m-d H:i:s'),
            ])
            ->assertSessionHasNoErrors();

        $task = DB::table('clinic_tasks')->first();

        $this->assertNotNull($task);
        $this->assertSame($attendant->id, (int) $task->assigned_user_id);

        $this->actingAs($attendant)
            ->patch(route('activities.complete', $task->id))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('clinic_tasks', [
            'id' => $task->id,
            'status' => 'completed',
        ]);
    }

    public function test_professional_role_cannot_open_team_activities(): void
    {
        $professional = User::factory()->create([
            'role' => 'professional',
            'permissions' => null,
            'is_active' => true,
            'force_password_change' => false,
        ]);

        $this->actingAs($professional)
            ->get(route('activities.index'))
            ->assertForbidden();
    }
}
