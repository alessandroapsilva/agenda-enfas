<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PremiumAccessManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_login_requires_password_change_and_tracks_activity(): void
    {
        $user = User::factory()->create([
            'name' => 'Atendente Teste',
            'username' => 'atendente.teste',
            'password' => 'TempPass123',
            'role' => 'attendant',
            'is_active' => true,
            'force_password_change' => true,
            'last_seen_at' => null,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('password.change'));

        $this->actingAs($user)
            ->get(route('password.change'))
            ->assertOk()
            ->assertSee('Troca obrigatória de senha');

        $this->actingAs($user)
            ->patch(route('password.update'), [
                'current_password' => 'TempPass123',
                'password' => 'NovaSenha1234',
                'password_confirmation' => 'NovaSenha1234',
            ])
            ->assertRedirect(route('dashboard'));

        $user->refresh();

        $this->assertFalse($user->force_password_change);
        $this->assertNotNull($user->last_seen_at);
        $this->assertTrue(Hash::check('NovaSenha1234', $user->password));
    }

    public function test_inactive_user_is_logged_out_on_next_request(): void
    {
        $user = User::factory()->create([
            'username' => 'bloqueado.teste',
            'role' => 'attendant',
            'is_active' => false,
            'force_password_change' => false,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_attendant_cannot_open_administration_modules(): void
    {
        $user = User::factory()->create([
            'username' => 'atendente.limitado',
            'role' => 'attendant',
            'is_active' => true,
            'force_password_change' => false,
        ]);

        $this->actingAs($user)
            ->get(route('users.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('v92.settings'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('agenda.index'))
            ->assertOk();
    }

    public function test_admin_can_create_individual_user_account(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin.teste',
            'role' => 'admin',
            'is_active' => true,
            'force_password_change' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Recepção Premium',
                'username' => 'recepcao.premium',
                'email' => 'recepcao@example.test',
                'phone' => '11999999999',
                'job_title' => 'Recepção',
                'role' => 'attendant',
                'password' => 'SenhaInicial123',
                'password_confirmation' => 'SenhaInicial123',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'username' => 'recepcao.premium',
            'role' => 'attendant',
            'is_active' => true,
            'force_password_change' => true,
        ]);
    }
}
