<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Services\Enfas\BusinessHoursService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EnterpriseAccessAndSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_enterprise_auth_and_settings_routes_are_registered(): void
    {
        foreach ([
            'microsoft.redirect',
            'microsoft.callback',
            'password.request',
            'password.email',
            'password.reset.form',
            'password.reset',
            'system-settings.index',
            'system-settings.update',
        ] as $name) {
            $this->assertNotNull(
                app('router')->getRoutes()->getByName($name),
                'Missing route '.$name
            );
        }
    }

    public function test_password_reset_sends_branded_notification_for_active_user(): void
    {
        Notification::fake();

        $user = User::create([
            'name' => 'Usuário ENFAS',
            'username' => 'usuario.teste',
            'email' => 'usuario@example.com',
            'password' => 'Senha123456',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->post(route('password.email'), [
            'email' => 'usuario@example.com',
        ])->assertSessionHas('status');

        Notification::assertSentTo(
            $user,
            ResetPasswordNotification::class
        );
    }

    public function test_business_hours_can_close_a_day_and_split_a_break(): void
    {
        AppSetting::setValue(
            'system',
            'business_hours',
            json_encode([
                '1' => [
                    'enabled' => true,
                    'start' => '08:00',
                    'end' => '18:00',
                    'break_start' => '12:00',
                    'break_end' => '13:00',
                ],
                '0' => [
                    'enabled' => false,
                    'start' => '08:00',
                    'end' => '18:00',
                    'break_start' => null,
                    'break_end' => null,
                ],
            ])
        );

        $service = app(BusinessHoursService::class);

        $monday = Carbon::parse('2026-10-12 00:00:00');
        $sunday = Carbon::parse('2026-10-11 00:00:00');

        $windows = $service->constrain([
            [
                'start' => '07:00:00',
                'end' => '20:00:00',
                'break_start' => null,
                'break_end' => null,
                'step' => 30,
            ],
        ], $monday);

        $this->assertCount(2, $windows);
        $this->assertSame('08:00:00', $windows[0]['start']);
        $this->assertSame('12:00:00', $windows[0]['end']);
        $this->assertSame('13:00:00', $windows[1]['start']);
        $this->assertSame('18:00:00', $windows[1]['end']);
        $this->assertNull($service->windowFor($sunday));
    }

    public function test_microsoft_login_redirect_uses_pkce_and_state(): void
    {
        config()->set('services.microsoft.enabled', true);
        config()->set('services.microsoft.tenant', 'organizations');
        config()->set('services.microsoft.client_id', 'client-id');
        config()->set('services.microsoft.client_secret', 'client-secret');
        config()->set(
            'services.microsoft.redirect_uri',
            'https://agenda.example.com/auth/microsoft/callback'
        );

        $response = $this->get(route('microsoft.redirect'));

        $response->assertRedirect();
        $location = $response->headers->get('Location');

        $this->assertStringContainsString(
            'login.microsoftonline.com/organizations/oauth2/v2.0/authorize',
            $location
        );
        $this->assertStringContainsString('code_challenge=', $location);
        $this->assertStringContainsString('state=', $location);
    }
}
