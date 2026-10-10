<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PremiumVisualSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_main_premium_screens_render_for_admin(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin.visual',
            'role' => 'admin',
            'is_active' => true,
            'force_password_change' => false,
        ]);

        $routes = [
            'dashboard',
            'agenda.index',
            'appointments.index',
            'waitlist.index',
            'v11.confirmations',
            'patients.index',
            'professionals.index',
            'services.index',
            'v9.locations.index',
            'v92.reports',
            'enfas.whatsapp',
            'v9.templates.index',
            'enfas.v6.automations',
            'activities.index',
            'users.index',
            'v92.alerts',
            'enfas.agenda-settings',
            'enfas.branding',
            'v92.health.dashboard',
        ];

        foreach ($routes as $route) {
            $this->actingAs($admin)
                ->get(route($route))
                ->assertSuccessful();
        }
    }
    public function test_agenda_and_message_studio_expose_the_new_product_workflows(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin.product',
            'role' => 'admin',
            'is_active' => true,
            'force_password_change' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('agenda.index'))
            ->assertSuccessful()
            ->assertSee('Encontrar horário')
            ->assertSee('Buscar disponibilidade');

        $this->actingAs($admin)
            ->get(route('v9.templates.index'))
            ->assertSuccessful()
            ->assertSee('Modelos de mensagem')
            ->assertSee('Novo modelo');

        $this->actingAs($admin)
            ->get(route('enfas.branding'))
            ->assertSuccessful()
            ->assertSee('cabeçalho oficial');
    }

}
