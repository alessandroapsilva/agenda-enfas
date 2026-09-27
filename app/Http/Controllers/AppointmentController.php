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
use App\Services\Enfas\AvailabilityService;
use App\Services\Enfas\MetaWhatsAppService;
use App\Services\Enfas\RecurringAppointmentService;
use App\Services\Enfas\WaitlistService;
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


    public function store(Request $request, AvailabilityService $availability)
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

            'appointment_type' => [
                'required',
                'in:care,medication_pickup',
            ],

            'medication_name' => [
                'nullable',
                'required_if:appointment_type,medication_pickup',
                'string',
                'max:255',
            ],

            'medication_quantity' => [
                'nullable',
                'required_if:appointment_type,medication_pickup',
                'string',
                'max:120',
            ],

            'medication_notes' => [
                'nullable',
                'string',
                'max:5000',
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

        if (! $availability->isBookable($professional->id, $start, $end)) {
            throw ValidationException::withMessages([
                'start_at' => 'Esse horário está fora da disponibilidade do profissional ou já foi ocupado.',
            ]);
        }

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

            'appointment_type' => $data['appointment_type'],
            'medication_name' => $data['medication_name'] ?? null,
            'medication_quantity' => $data['medication_quantity'] ?? null,
            'medication_notes' => $data['medication_notes'] ?? null,

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



    public function storeRecurring(
        Request $request,
        RecurringAppointmentService $recurring
    ) {
        $data = $request->validate([
            'patient_id' => ['required','integer','exists:patients,id'],
            'professional_id' => ['required','integer','exists:professionals,id'],
            'service_id' => ['required','integer','exists:services,id'],
            'frequency' => ['required','in:daily,weekly,monthly'],
            'interval' => ['required','integer','min:1','max:52'],
            'starts_on' => ['required','date'],
            'time' => ['required','date_format:H:i'],
            'ends_on' => ['nullable','date','after_or_equal:starts_on'],
            'max_occurrences' => ['nullable','integer','min:1','max:365'],
            'week_days' => ['nullable','array'],
            'week_days.*' => ['integer','between:0,6'],
        ]);

        $service = Service::findOrFail($data['service_id']);

        if (! $service->allow_recurrence) {
            throw ValidationException::withMessages([
                'frequency' => 'Este serviço não permite agendamento recorrente.',
            ]);
        }

        $series = $recurring->create($data);

        return redirect()
            ->route('agenda.index')
            ->with('success', 'Série recorrente criada com sucesso. Código interno #'.$series->id.'.');
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

                'journey_url' =>
                    route('patient-journey.show', $appointment->public_token),

                'service' =>
                    $appointment->service->name,

                'appointment_type' =>
                    $appointment->appointment_type,

                'appointment_type_label' =>
                    $appointment->appointment_type === 'medication_pickup'
                        ? 'Retirada de medicamento'
                        : 'Atendimento',

                'medication_name' =>
                    $appointment->medication_name,

                'medication_quantity' =>
                    $appointment->medication_quantity,

                'medication_notes' =>
                    $appointment->medication_notes,

                'patient_rgea' =>
                    $appointment->patient->rgea_number,

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
        Appointment $appointment,
        WaitlistService $waitlist
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

        $oldStatus = $appointment->status;
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

        if ($data['status'] === 'completed') {
            $appointment->completed_at = now();

            $returnAfterDays = (int) ($appointment->service?->return_after_days ?? 0);

            if ($returnAfterDays > 0) {
                $appointment->return_due_at = now()->addDays($returnAfterDays)->toDateString();
            }
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

        if ($oldStatus !== 'cancelled' && $data['status'] === 'cancelled') {
            try {
                $waitlist->offerFreedSlot($appointment->fresh(['service','professional']));
            } catch (\Throwable $e) {
                report($e);
            }
        }

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

}
