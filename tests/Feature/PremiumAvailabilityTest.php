<?php

namespace Tests\Feature;

use App\Http\Controllers\AgendaController;
use App\Services\Enfas\AccessScopeService;
use App\Services\Enfas\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PremiumAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_bookable_slot_respects_professional_window_and_blocks(): void
    {
        $professionalId = DB::table('professionals')->insertGetId([
            'name' => 'Profissional Teste',
            'work_start' => '08:00:00',
            'work_end' => '18:00:00',
            'active_days' => json_encode([1,2,3,4,5]),
            'slot_interval' => 30,
            'color' => '#2563eb',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('professional_availabilities')->insert([
            'professional_id' => $professionalId,

            // Colunas legadas ainda obrigatórias no schema de produção.
            'weekday' => 1,
            'starts_at' => '08:00:00',
            'ends_at' => '12:00:00',
            'slot_minutes' => 30,

            // Colunas premium adicionadas pela migration de compatibilidade.
            'day_of_week' => 1,
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'break_start' => '10:00:00',
            'break_end' => '10:30:00',

            'location_id' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(AvailabilityService::class);
        $monday = Carbon::parse('2026-09-28 09:00:00', config('app.timezone'));

        $this->assertTrue(
            $service->isBookable($professionalId, $monday, $monday->copy()->addMinutes(30))
        );

        $break = Carbon::parse('2026-09-28 10:00:00', config('app.timezone'));

        $this->assertFalse(
            $service->isBookable($professionalId, $break, $break->copy()->addMinutes(30))
        );

        $outside = Carbon::parse('2026-09-28 13:00:00', config('app.timezone'));

        $this->assertFalse(
            $service->isBookable($professionalId, $outside, $outside->copy()->addMinutes(30))
        );

        DB::table('professional_blocks')->insert([
            'professional_id' => $professionalId,
            'starts_at' => '2026-09-28 09:00:00',
            'ends_at' => '2026-09-28 09:30:00',
            'type' => 'block',
            'title' => 'Bloqueio',
            'reason' => null,
            'is_all_day' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertFalse(
            $service->isBookable($professionalId, $monday, $monday->copy()->addMinutes(30))
        );
    }
    public function test_best_slot_search_can_find_across_available_professionals(): void
    {
        Carbon::setTestNow(
            Carbon::parse(
                '2026-10-10 10:00:00',
                config('app.timezone')
            )
        );

        $serviceId = DB::table(
            'services'
        )->insertGetId([
            'name' => 'Avaliação funcional',
            'duration_minutes' => 30,
            'color' => '#2563eb',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $professionalIds = [];

        foreach ([
            'Ana',
            'Bruno',
        ] as $name) {
            $professionalId = DB::table(
                'professionals'
            )->insertGetId([
                'name' => $name,
                'work_start' => '08:00:00',
                'work_end' => '12:00:00',
                'active_days' => json_encode([1]),
                'slot_interval' => 30,
                'color' => '#2563eb',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $professionalIds[] =
                $professionalId;

            DB::table(
                'professional_service'
            )->insert([
                'professional_id' =>
                    $professionalId,
                'service_id' =>
                    $serviceId,
            ]);
        }

        $request = Request::create(
            '/agenda/melhores-horarios',
            'GET',
            [
                'service_id' =>
                    $serviceId,
                'from' =>
                    '2026-10-12',
                'period' =>
                    'morning',
                'limit' =>
                    4,
            ]
        );

        $response = app(
            AgendaController::class
        )->bestSlots(
            $request,
            app(
                AvailabilityService::class
            ),
            app(
                AccessScopeService::class
            )
        );

        $payload = $response
            ->getData(true);

        $this->assertTrue(
            $payload['success']
        );

        $this->assertSame(
            2,
            $payload['meta'][
                'professionals_checked'
            ]
        );

        $this->assertNotEmpty(
            $payload['slots']
        );

        $returnedProfessionals = collect(
            $payload['slots']
        )
            ->pluck(
                'professional_id'
            )
            ->unique()
            ->sort()
            ->values()
            ->all();

        $this->assertSame(
            collect($professionalIds)
                ->sort()
                ->values()
                ->all(),
            $returnedProfessionals
        );

        Carbon::setTestNow();
    }

}
