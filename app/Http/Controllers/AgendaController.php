<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentEvent;
use App\Models\CustomField;
use App\Models\Location;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\Service;
use App\Services\Enfas\AccessScopeService;
use App\Services\Enfas\AvailabilityService;
use App\Services\Enfas\CommunicationPolicyService;
use App\Services\Enfas\EmailChannelService;
use App\Services\Enfas\SmsChannelService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AgendaController extends Controller
{
    public function index(
        Request $request,
        AccessScopeService $access,
        CommunicationPolicyService $communication,
        EmailChannelService $email,
        SmsChannelService $sms
    ) {
        $patients = $access->patients(Patient::query(), $request->user())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $professionals = $access->professionals(
                Professional::query()
                    ->with('services:id'),
                $request->user()
            )
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $services = Service::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $locations = Location::query()
            ->where('is_active', true)
            ->orderByDesc('is_main')
            ->orderBy('name')
            ->get();

        $customFields = CustomField::query()
            ->where('entity_type', 'appointment')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $communicationChannels = [
            'email' => [
                'enabled' =>
                    $communication->patientEmailEnabled(),
                'configured' =>
                    $email->configured(),
            ],
            'sms' => [
                'enabled' =>
                    $communication->patientSmsEnabled(),
                'configured' =>
                    $sms->configured(),
            ],
        ];

        return view(
            'agenda.index',
            compact(
                'patients',
                'professionals',
                'services',
                'locations',
                'customFields',
                'communicationChannels'
            )
        );
    }


    public function events(Request $request, AccessScopeService $access)
    {
        $start = Carbon::parse(
            $request->get('start')
        );

        $end = Carbon::parse(
            $request->get('end')
        );

        $appointments = $access->appointments(Appointment::query(), $request->user())
            ->with([
                'patient',
                'professional',
                'service',
                'location',
            ])
            ->when($request->filled('professional_id'), fn ($q) =>
                $q->where('professional_id', $request->integer('professional_id'))
            )
            ->when($request->filled('service_id'), fn ($q) =>
                $q->where('service_id', $request->integer('service_id'))
            )
            ->when($request->filled('status'), fn ($q) =>
                $q->where('status', $request->string('status')->toString())
            )
            ->when($request->filled('location_id'), fn ($q) =>
                $q->where('location_id', $request->integer('location_id'))
            )
            ->where(
                'start_at',
                '<',
                $end
            )
            ->where(
                'end_at',
                '>',
                $start
            )
            ->get();

        return response()->json(
            $appointments->map(
                fn ($appointment) => [
                    'id' =>
                        (string) $appointment->id,

                    'title' =>
                        ($appointment->appointment_type === 'medication_pickup'
                            ? '💊 '
                            : '')
                        . $appointment->patient->name
                        . ' • '
                        . $appointment->service->name,

                    'start' =>
                        $appointment->start_at
                            ->toIso8601String(),

                    'end' =>
                        $appointment->end_at
                            ->toIso8601String(),

                    'backgroundColor' =>
                        $appointment->statusColor(),

                    'borderColor' =>
                        $appointment->statusColor(),

                    'textColor' =>
                        '#ffffff',

                    'editable' =>
                        ! in_array(
                            $appointment->status,
                            [
                                'cancelled',
                                'completed',
                            ]
                        ),

                    'extendedProps' => [
                        'code' =>
                            $appointment->code,

                        'status' =>
                            $appointment->status,

                        'statusLabel' =>
                            $appointment->statusLabel(),

                        'professional' =>
                            $appointment
                                ->professional
                                ->name,

                        'service' =>
                            $appointment
                                ->service
                                ->name,

                        'appointmentType' =>
                            $appointment->appointment_type,

                        'location' =>
                            $appointment->location?->name,
                    ],
                ]
            )
        );
    }



    public function daySlots(
        Request $request,
        AvailabilityService $availability,
        AccessScopeService $access
    ) {
        $data = $request->validate([
            'professional_id' => [
                'required',
                'integer',
                'exists:professionals,id',
            ],
            'service_id' => [
                'required',
                'integer',
                'exists:services,id',
            ],
            'date' => [
                'required',
                'date_format:Y-m-d',
            ],
            'exclude_appointment_id' => [
                'nullable',
                'integer',
                'exists:appointments,id',
            ],
        ]);

        $professional = $access
            ->professionals(
                Professional::query(),
                $request->user()
            )
            ->whereKey(
                (int) $data['professional_id']
            )
            ->where(
                'is_active',
                true
            )
            ->firstOrFail();

        if (
            Schema::hasTable(
                'professional_service'
            )
            && DB::table(
                'professional_service'
            )
                ->where(
                    'professional_id',
                    $professional->id
                )
                ->where(
                    'service_id',
                    (int) $data['service_id']
                )
                ->doesntExist()
        ) {
            return response()->json([
                'success' => true,
                'date' => $data['date'],
                'professional' => [
                    'id' => (int) $professional->id,
                    'name' => $professional->name,
                ],
                'slots' => [],
                'message' =>
                    'Este profissional não está habilitado para o serviço selecionado.',
            ]);
        }

        $date = Carbon::createFromFormat(
            'Y-m-d',
            $data['date'],
            config('app.timezone')
        )->startOfDay();

        $slots = $availability
            ->slotsForDay(
                (int) $professional->id,
                (int) $data['service_id'],
                $date,
                isset(
                    $data['exclude_appointment_id']
                )
                    ? (int) $data['exclude_appointment_id']
                    : null
            );

        return response()->json([
            'success' => true,
            'date' => $date->toDateString(),
            'professional' => [
                'id' => (int) $professional->id,
                'name' => $professional->name,
            ],
            'slots' => $slots,
            'message' => $slots === []
                ? 'Não há horários disponíveis para esta combinação nesta data.'
                : null,
        ]);
    }

    public function bestSlots(
        Request $request,
        AvailabilityService $availability,
        AccessScopeService $access
    ) {
        $data = $request->validate([
            'professional_id' => [
                'nullable',
                'integer',
                'exists:professionals,id',
            ],
            'service_id' => [
                'required',
                'integer',
                'exists:services,id',
            ],
            'period' => [
                'nullable',
                'in:morning,afternoon,evening',
            ],
            'from' => [
                'nullable',
                'date',
            ],
            'limit' => [
                'nullable',
                'integer',
                'min:1',
                'max:20',
            ],
        ]);

        $service = Service::query()
            ->whereKey(
                (int) $data['service_id']
            )
            ->where(
                'is_active',
                true
            )
            ->firstOrFail();

        $professionalsQuery = $access
            ->professionals(
                Professional::query(),
                $request->user()
            )
            ->where(
                'is_active',
                true
            );

        if (! empty(
            $data['professional_id']
        )) {
            $professionalsQuery->whereKey(
                (int) $data['professional_id']
            );
        }

        $linkedProfessionalIds = collect();

        if (Schema::hasTable(
            'professional_service'
        )) {
            $linkedProfessionalIds = DB::table(
                'professional_service'
            )
                ->where(
                    'service_id',
                    $service->id
                )
                ->pluck(
                    'professional_id'
                )
                ->map(
                    fn ($id) => (int) $id
                );

            if ($linkedProfessionalIds->isNotEmpty()) {
                $professionalsQuery->whereIn(
                    'id',
                    $linkedProfessionalIds->all()
                );
            }
        }

        $professionals = $professionalsQuery
            ->orderBy('name')
            ->get();

        $from = isset($data['from'])
            ? Carbon::parse(
                $data['from'],
                config('app.timezone')
            )
            : now();

        if ($from->isPast()) {
            $from = now();
        }

        $limit = (int) (
            $data['limit']
            ?? 8
        );

        $slots = collect();

        foreach ($professionals as $professional) {
            $perProfessional = $availability
                ->nextSlots(
                    (int) $professional->id,
                    (int) $service->id,
                    $from,
                    $data['period'] ?? null,
                    max(
                        3,
                        min(
                            $limit,
                            10
                        )
                    ),
                    null,
                    60
                );

            foreach ($perProfessional as $slot) {
                $startAt = Carbon::parse(
                    $slot['start'],
                    config('app.timezone')
                );

                $slots->push([
                    ...$slot,
                    'professional_id' =>
                        (int) $professional->id,
                    'professional_name' =>
                        $professional->name,
                    'service_id' =>
                        (int) $service->id,
                    'service_name' =>
                        $service->name,
                    'duration_minutes' =>
                        (int) (
                            $service->duration_minutes
                            ?: 30
                        ),
                    'day_label' =>
                        $startAt
                            ->translatedFormat(
                                'D, d/m'
                            ),
                    'time_label' =>
                        $startAt->format('H:i'),
                ]);
            }
        }

        $slots = $slots
            ->sortBy('start')
            ->unique(
                fn ($slot) =>
                    $slot['professional_id']
                    .'|'
                    .$slot['start']
            )
            ->take($limit)
            ->values();

        return response()->json([
            'success' => true,
            'slots' => $slots,
            'meta' => [
                'service' => [
                    'id' => (int) $service->id,
                    'name' => $service->name,
                    'duration_minutes' =>
                        (int) (
                            $service->duration_minutes
                            ?: 30
                        ),
                ],
                'professionals_checked' =>
                    $professionals->count(),
                'from' =>
                    $from->toIso8601String(),
                'period' =>
                    $data['period'] ?? null,
                'message' =>
                    $slots->isEmpty()
                        ? (
                            $professionals->isEmpty()
                                ? 'Nenhum profissional habilitado para este serviço.'
                                : 'Não encontramos horário disponível nos próximos 60 dias com estes filtros.'
                        )
                        : null,
            ],
        ]);
    }

    public function move(
        Request $request,
        Appointment $appointment,
        AvailabilityService $availability,
        AccessScopeService $access
    ) {
        abort_unless(
            $access->canViewAppointment($request->user(), $appointment),
            403
        );
        $data = $request->validate([
            'start' => [
                'required',
                'date',
            ],

            'end' => [
                'nullable',
                'date',
            ],
        ]);

        if (
            in_array(
                $appointment->status,
                ['cancelled', 'completed']
            )
        ) {
            throw ValidationException::withMessages([
                'appointment' =>
                    'Este agendamento não pode ser movimentado.',
            ]);
        }

        $oldStart = $appointment->start_at->copy();
        $oldEnd = $appointment->end_at->copy();

        $start = Carbon::parse(
            $data['start']
        );

        $end = ! empty($data['end'])
            ? Carbon::parse($data['end'])
            : (clone $start)->addMinutes(
                $appointment->duration_minutes
            );

        if (! $availability->isBookable(
            $appointment->professional_id,
            $start,
            $end,
            $appointment->id
        )) {
            throw ValidationException::withMessages([
                'appointment' => 'Esse horário está fora da disponibilidade do profissional ou já foi ocupado.',
            ]);
        }

        $appointment->update([
            'start_at' => $start,
            'end_at' => $end,

            'duration_minutes' =>
                $start->diffInMinutes($end),

            'rescheduled_at' => now(),
            'confirmation_status' => 'pending',
            'confirmed_at' => null,

            'updated_by' =>
                auth()->id(),
        ]);

        AppointmentEvent::create([
            'appointment_id' =>
                $appointment->id,

            'user_id' =>
                auth()->id(),

            'event_type' =>
                'rescheduled',

            'title' =>
                'Agendamento reagendado',

            'description' =>
                $oldStart->format('d/m/Y H:i')
                . '–'
                . $oldEnd->format('H:i')
                . ' → '
                . $start->format('d/m/Y H:i')
                . '–'
                . $end->format('H:i'),

            'occurred_at' => now(),
        ]);

        return response()->json([
            'success' => true,
        ]);
    }


}
