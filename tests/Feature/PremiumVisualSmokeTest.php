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
}
