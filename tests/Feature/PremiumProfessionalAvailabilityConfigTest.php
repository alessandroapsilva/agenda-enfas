<?php

namespace Tests\Feature;

use App\Models\Professional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PremiumProfessionalAvailabilityConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_daily_availability_with_custom_slot_interval(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin.availability',
            'role' => 'admin',
            'is_active' => true,
            'force_password_change' => false,
        ]);

        $professional = Professional::create([
            'name' => 'Profissional Teste',
            'work_start' => '08:00',
            'work_end' => '18:00',
            'active_days' => [1,2,3,4,5],
            'slot_interval' => 30,
            'color' => '#2563eb',
            'is_active' => true,
            'whatsapp_notifications_enabled' => false,
            'email_notifications_enabled' => false,
            'sms_notifications_enabled' => false,
        ]);

        $this->actingAs($admin)
            ->post(
                route(
                    'professionals.availability',
                    $professional
                ),
                [
                    'availability' => [
                        1 => [
                            'enabled' => 1,
                            'start_time' => '08:00',
                            'end_time' => '12:00',
                            'slot_minutes' => 20,
                            'break_start' => '10:00',
                            'break_end' => '10:20',
                        ],
                    ],
                ]
            )
            ->assertRedirect();

        $this->assertDatabaseHas(
            'professional_availabilities',
            [
                'professional_id' =>
                    $professional->id,
                'day_of_week' => 1,
                'start_time' => '08:00:00',
                'end_time' => '12:00:00',
                'slot_minutes' => 20,
                'break_start' => '10:00:00',
                'break_end' => '10:20:00',
            ]
        );
    }

    public function test_invalid_daily_window_is_rejected(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin.availability.invalid',
            'role' => 'admin',
            'is_active' => true,
            'force_password_change' => false,
        ]);

        $professional = Professional::create([
            'name' => 'Profissional Teste',
            'work_start' => '08:00',
            'work_end' => '18:00',
            'active_days' => [1,2,3,4,5],
            'slot_interval' => 30,
            'color' => '#2563eb',
            'is_active' => true,
            'whatsapp_notifications_enabled' => false,
            'email_notifications_enabled' => false,
            'sms_notifications_enabled' => false,
        ]);

        $this->actingAs($admin)
            ->from(route('professionals.index'))
            ->post(
                route(
                    'professionals.availability',
                    $professional
                ),
                [
                    'availability' => [
                        1 => [
                            'enabled' => 1,
                            'start_time' => '12:00',
                            'end_time' => '08:00',
                            'slot_minutes' => 30,
                        ],
                    ],
                ]
            )
            ->assertRedirect(
                route('professionals.index')
            )
            ->assertSessionHasErrors(
                'availability.1.end_time'
            );

        $this->assertDatabaseMissing(
            'professional_availabilities',
            [
                'professional_id' =>
                    $professional->id,
                'day_of_week' => 1,
            ]
        );
    }
}
