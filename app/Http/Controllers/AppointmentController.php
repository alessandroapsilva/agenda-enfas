<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentEvent;
use App\Models\CustomField;
use App\Models\CustomFieldValue;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\Service;
use App\Models\WaMessage;
use App\Services\Enfas\MetaWhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
    public function index()
    {
        $appointments = Appointment::query()
            ->with([
                'patient',
                'professional',
                'service',
            ])
            ->orderByDesc('start_at')
            ->paginate(40);

        return view(
            'appointments.index',
            compact('appointments')
        );
    }


    public function store(Request $request)
    {
        $fields = CustomField::query()
            ->where('entity_type', 'appointment')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $rules = [
            'patient_id' => [
                'required',
                'exists:patients,id',
            ],

            'professional_id' => [
                'required',
                'exists:professionals,id',
            ],

            'service_id' => [
                'required',
                'exists:services,id',
            ],

            'start_at' => [
                'required',
                'date',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];

        foreach ($fields as $field) {

            $fieldRules = [
                $field->is_required
                    ? 'required'
                    : 'nullable',
            ];

            if ($field->field_type === 'number') {
                $fieldRules[] = 'numeric';
            }

            if ($field->field_type === 'date') {
                $fieldRules[] = 'date';
            }

            if ($field->field_type === 'select') {
                $fieldRules[] = 'string';
            }

            $rules[
                'custom_fields.' . $field->id
            ] = $fieldRules;
        }

        $data = $request->validate($rules);

        $patient = Patient::findOrFail(
            $data['patient_id']
        );

        $professional = Professional::findOrFail(
            $data['professional_id']
        );

        $service = Service::findOrFail(
            $data['service_id']
        );

        $start = Carbon::parse(
            $data['start_at']
        );

        $end = (clone $start)
            ->addMinutes(
                $service->duration_minutes
            );

        $this->assertAvailable(
            $professional->id,
            $start,
            $end
        );

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'professional_id' => $professional->id,
            'service_id' => $service->id,

            'start_at' => $start,
            'end_at' => $end,

            'duration_minutes' =>
                $service->duration_minutes,

            'status' =>
                'awaiting_confirmation',

            'confirmation_status' =>
                'pending',

            'source' => 'internal',

            'notes' => $data['notes'] ?? null,

            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);


        foreach ($fields as $field) {

            $value = $request->input(
                'custom_fields.' . $field->id
            );

            if ($field->field_type === 'checkbox') {
                $value = $value ? '1' : '0';
            }

            if (
                $value === null
                || $value === ''
            ) {
                continue;
            }

            CustomFieldValue::create([
                'custom_field_id' => $field->id,
                'entity_type' => 'appointment',
                'entity_id' => $appointment->id,
                'value' => is_array($value)
                    ? json_encode($value)
                    : (string) $value,
            ]);
        }


        AppointmentEvent::create([
            'appointment_id' => $appointment->id,
            'user_id' => auth()->id(),

            'event_type' => 'created',

            'title' =>
                'Agendamento criado',

            'description' =>
                "Agendamento {$appointment->code} criado para "
                . $start->format('d/m/Y H:i')
                . '.',

            'occurred_at' => now(),
        ]);


        return redirect()
            ->route('agenda.index')
            ->with(
                'success',
                "Agendamento {$appointment->code} criado."
            );
    }


    public function show(Appointment $appointment)
    {
        $appointment->load([
            'patient',
            'professional',
            'service',
            'creator',
            'events.user',
        ]);

        $values = CustomFieldValue::query()
            ->with('field')
            ->where(
                'entity_type',
                'appointment'
            )
            ->where(
                'entity_id',
                $appointment->id
            )
            ->get();

        $communications = WaMessage::query()
            ->where('appointment_id', $appointment->id)
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(fn ($message) => [
                'id' => $message->id,
                'direction' => $message->direction,
                'type' => $message->message_type,
                'status' => $message->status,
                'body' => $message->body,
                'recipient' => $message->recipient,
                'date' => $message->created_at?->format('d/m/Y H:i'),
            ])
            ->values();

        return response()->json([
            'appointment' => [
                'id' => $appointment->id,
                'code' => $appointment->code,

                'status' => $appointment->status,
                'status_label' =>
                    $appointment->statusLabel(),

                'status_color' =>
                    $appointment->statusColor(),

                'patient' =>
                    $appointment->patient->name,

                'phone' =>
                    $appointment->patient->phone,

                'email' =>
                    $appointment->patient->email,

                'whatsapp_link' =>
                    $appointment->patient->phone
                        ? 'https://wa.me/' . preg_replace('/\D+/', '', $appointment->patient->phone)
                        : null,

                'tel_link' =>
                    $appointment->patient->phone
                        ? 'tel:' . preg_replace('/[^0-9+]/', '', $appointment->patient->phone)
                        : null,

                'email_link' =>
                    $appointment->patient->email
                        ? 'mailto:' . $appointment->patient->email
                        : null,

                'service' =>
                    $appointment->service->name,

                'professional' =>
                    $appointment->professional->name,

                'start' =>
                    $appointment->start_at
                        ->format('d/m/Y H:i'),

                'end' =>
                    $appointment->end_at
                        ->format('H:i'),

                'notes' =>
                    $appointment->notes,

                'created_by' =>
                    $appointment->creator?->name,

                'custom_fields' =>
                    $values->map(fn ($value) => [
                        'name' =>
                            $value->field->name,

                        'value' =>
                            $value->value,
                    ])->values(),
            ],

            'communications' => $communications,

            'timeline' =>
                $appointment->events
                    ->map(fn ($event) => [
                        'title' =>
                            $event->title,

                        'description' =>
                            $event->description,

                        'user' =>
                            $event->user?->name,

                        'date' =>
                            $event->occurred_at
                                ->format('d/m/Y H:i'),

                        'type' =>
                            $event->event_type,
                    ])
                    ->values(),
        ]);
    }


    public function status(
        Request $request,
        Appointment $appointment
    ) {
        $data = $request->validate([
            'status' => [
                'required',
                'in:awaiting_confirmation,confirmed,completed,cancelled,no_show',
            ],

            'reason' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $oldLabel = $appointment->statusLabel();

        $appointment->status = $data['status'];
        $appointment->updated_by = auth()->id();

        if ($data['status'] === 'confirmed') {
            $appointment->confirmed_at = now();
            $appointment->confirmation_status =
                'confirmed';
        }

        if ($data['status'] === 'cancelled') {
            $appointment->cancelled_at = now();

            $appointment->cancellation_reason =
                $data['reason'] ?? null;
        }

        $appointment->save();

        AppointmentEvent::create([
            'appointment_id' => $appointment->id,
            'user_id' => auth()->id(),

            'event_type' => 'status_changed',

            'title' => 'Status atualizado',

            'description' =>
                $oldLabel
                . ' → '
                . $appointment->statusLabel(),

            'occurred_at' => now(),
        ]);

        return response()->json([
            'success' => true,

            'status' =>
                $appointment->status,

            'label' =>
                $appointment->statusLabel(),

            'color' =>
                $appointment->statusColor(),
        ]);
    }



    public function contact(
        Request $request,
        Appointment $appointment,
        MetaWhatsAppService $meta
    ) {
        $data = $request->validate([
            'channel' => ['required', 'in:whatsapp'],
            'message' => ['required', 'string', 'min:1', 'max:4000'],
        ]);

        $appointment->load('patient');

        if (! $appointment->patient?->phone) {
            throw ValidationException::withMessages([
                'message' => 'O paciente não possui telefone/WhatsApp cadastrado.',
            ]);
        }

        try {
            $message = $meta->sendTextMessage(
                $appointment->patient->phone,
                trim($data['message']),
                $appointment->id,
                $appointment->patient_id
            );
        } catch (\Throwable $e) {
            report($e);

            throw ValidationException::withMessages([
                'message' => $e->getMessage(),
            ]);
        }

        AppointmentEvent::create([
            'appointment_id' => $appointment->id,
            'user_id' => auth()->id(),
            'event_type' => 'patient_contact',
            'title' => 'Contato enviado ao paciente',
            'description' => 'Mensagem enviada pelo WhatsApp por '.auth()->user()->name.'.',
            'metadata' => [
                'channel' => 'whatsapp',
                'wa_message_id' => $message->id,
            ],
            'occurred_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mensagem enviada com sucesso.',
            'communication' => [
                'id' => $message->id,
                'direction' => $message->direction,
                'type' => $message->message_type,
                'status' => $message->status,
                'body' => $message->body,
                'recipient' => $message->recipient,
                'date' => $message->created_at?->format('d/m/Y H:i'),
            ],
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
                'start_at' =>
                    'Este profissional já possui um agendamento nesse horário.',
            ]);
        }
    }
}
