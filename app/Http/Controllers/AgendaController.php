<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentEvent;
use App\Models\CustomField;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\Service;
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
            ])
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
                        $appointment->patient->name
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
                    ],
                ]
            )
        );
    }


    public function move(
        Request $request,
        Appointment $appointment
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

        $this->assertAvailable(
            $appointment->professional_id,
            $start,
            $end,
            $appointment->id
        );

        $appointment->update([
            'start_at' => $start,
            'end_at' => $end,

            'duration_minutes' =>
                $start->diffInMinutes($end),

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


    private function assertAvailable(
        int $professionalId,
        Carbon $start,
        Carbon $end,
        ?int $ignoreAppointment = null
    ): void {
        $query = Appointment::query()
            ->where(
                'professional_id',
                $professionalId
            )
            ->whereNotIn(
                'status',
                ['cancelled']
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
            );

        if ($ignoreAppointment) {
            $query->where(
                'id',
                '!=',
                $ignoreAppointment
            );
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'appointment' =>
                    'Existe outro agendamento nesse horário.',
            ]);
        }
    }
}
