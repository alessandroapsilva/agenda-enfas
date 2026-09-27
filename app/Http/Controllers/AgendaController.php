<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentEvent;
use App\Models\CustomField;
use App\Models\Location;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\Service;
use App\Services\Enfas\AvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class AgendaController extends Controller
{
    public function index()
    {
        $patients = Patient::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $professionals = Professional::query()
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

        return view(
            'agenda.index',
            compact(
                'patients',
                'professionals',
                'services',
                'locations',
                'customFields'
            )
        );
    }


    public function events(Request $request)
    {
        $start = Carbon::parse(
            $request->get('start')
        );

        $end = Carbon::parse(
            $request->get('end')
        );

        $appointments = Appointment::query()
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



    public function bestSlots(Request $request, AvailabilityService $availability)
    {
        $data = $request->validate([
            'professional_id' => ['required','integer','exists:professionals,id'],
            'service_id' => ['required','integer','exists:services,id'],
            'period' => ['nullable','in:morning,afternoon,evening'],
            'from' => ['nullable','date'],
            'limit' => ['nullable','integer','min:1','max:12'],
        ]);

        $slots = $availability->nextSlots(
            $data['professional_id'],
            $data['service_id'],
            isset($data['from']) ? Carbon::parse($data['from']) : now(),
            $data['period'] ?? null,
            $data['limit'] ?? 6
        );

        return response()->json([
            'success' => true,
            'slots' => $slots,
        ]);
    }

    public function move(
        Request $request,
        Appointment $appointment,
        AvailabilityService $availability
    ) {
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
