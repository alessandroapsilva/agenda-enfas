<?php

namespace App\Http\Controllers\Enfas\V6;

use App\Http\Controllers\Controller;
use App\Jobs\SendAppointmentWhatsApp;
use App\Models\WaMessage;
use App\Models\WaTemplate;
use App\Services\Enfas\WhatsAppDispatchPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WhatsAppMessageController extends Controller
{
    public function index(
        Request $request
    ) {
        $selectedAppointment =
            $request->integer(
                'appointment_id'
            ) ?: null;

        $rows = WaMessage::with('template')
            ->when(
                $selectedAppointment,
                fn ($query) =>
                    $query->where(
                        'appointment_id',
                        $selectedAppointment
                    )
            )
            ->orderByDesc('id')
            ->paginate(80)
            ->withQueryString();

        return view(
            'enfas.v6.whatsapp.messages',
            [
                'rows' => $rows,
                'selectedAppointment' =>
                    $selectedAppointment,

                'templates' =>
                    WaTemplate::where(
                        'status',
                        'APPROVED'
                    )
                        ->where(
                            'is_active',
                            true
                        )
                        ->whereNull(
                            'archived_at'
                        )
                        ->orderBy('name')
                        ->get(),

                'appointments' =>
                    DB::table(
                        'appointments'
                    )
                        ->join(
                            'patients',
                            'patients.id',
                            '=',
                            'appointments.patient_id'
                        )
                        ->orderByDesc(
                            'appointments.start_at'
                        )
                        ->limit(100)
                        ->get([
                            'appointments.id',
                            'appointments.code',
                            'appointments.start_at',
                            'patients.name as patient_name',
                        ]),
            ]
        );
    }

    public function send(
        Request $request,
        WhatsAppDispatchPolicy $policy
    ) {
        $data = $request->validate([
            'appointment_id' => [
                'required',
                'exists:appointments,id',
            ],
            'template_id' => [
                'required',
                'exists:wa_templates,id',
            ],
        ]);

        $appointmentId =
            (int) $data['appointment_id'];

        $templateId =
            (int) $data['template_id'];

        $template = WaTemplate::query()
            ->where(
                'id',
                $templateId
            )
            ->where(
                'status',
                'APPROVED'
            )
            ->where(
                'is_active',
                true
            )
            ->whereNull(
                'archived_at'
            )
            ->first();

        if (! $template) {
            throw ValidationException::withMessages([
                'template_id' =>
                    'O template selecionado não está disponível para envio.',
            ]);
        }

        if (! $policy->patientAllowsContact(
            $appointmentId
        )) {
            throw ValidationException::withMessages([
                'appointment_id' =>
                    'O paciente optou por não receber contatos pelo sistema.',
            ]);
        }

        $dedupe = $policy
            ->manualTemplateDedupeKey(
                $appointmentId,
                $templateId
            );

        if (WaMessage::query()
            ->where(
                'dedupe_key',
                $dedupe
            )
            ->exists()) {
            return back()->with(
                'success',
                'Esse mesmo envio já foi colocado na fila agora há pouco.'
            );
        }

        SendAppointmentWhatsApp::dispatch(
            $appointmentId,
            $templateId,
            null,
            $dedupe
        );

        return back()->with(
            'success',
            'Mensagem adicionada à fila de envio.'
        );
    }
}
