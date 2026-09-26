<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentEvent;
use App\Services\Enfas\WaitlistService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PatientJourneyController extends Controller
{
    private function appointment(string $token): Appointment
    {
        return Appointment::query()
            ->with(['patient','professional','service'])
            ->where('public_token', $token)
            ->firstOrFail();
    }

    public function show(string $token)
    {
        $appointment = $this->appointment($token);

        return view('patient-journey.show', compact('appointment'));
    }


    public function confirm(string $token)
    {
        $appointment = $this->appointment($token);

        abort_if(
            in_array($appointment->status, ['completed','cancelled','no_show'], true),
            422,
            'Este agendamento não pode mais ser confirmado.'
        );

        if ($appointment->status !== 'confirmed') {
            $appointment->forceFill([
                'status' => 'confirmed',
                'confirmation_status' => 'confirmed',
                'confirmation_channel' => 'patient_journey',
                'confirmed_at' => now(),
            ])->save();

            AppointmentEvent::create([
                'appointment_id' => $appointment->id,
                'event_type' => 'patient_confirmed',
                'title' => 'Presença confirmada',
                'description' => 'Paciente confirmou a presença pela Jornada ENFAS.',
                'occurred_at' => now(),
            ]);
        }

        return back()->with('success', 'Presença confirmada. Até breve!');
    }

    public function cancel(Request $request, string $token, WaitlistService $waitlist)
    {
        $appointment = $this->appointment($token);

        abort_if(
            in_array($appointment->status, ['completed','cancelled','no_show'], true),
            422,
            'Este agendamento não pode mais ser cancelado.'
        );

        $data = $request->validate([
            'reason' => ['nullable','string','max:1000'],
        ]);

        $appointment->forceFill([
            'status' => 'cancelled',
            'confirmation_status' => 'cancelled',
            'confirmation_channel' => 'patient_journey',
            'cancelled_at' => now(),
            'cancellation_reason' => $data['reason'] ?? null,
        ])->save();

        AppointmentEvent::create([
            'appointment_id' => $appointment->id,
            'event_type' => 'patient_cancelled',
            'title' => 'Agendamento cancelado pelo paciente',
            'description' => filled($data['reason'] ?? null)
                ? 'Motivo: '.$data['reason']
                : 'Cancelamento realizado pela Jornada ENFAS.',
            'occurred_at' => now(),
        ]);

        try {
            $waitlist->offerFreedSlot($appointment->fresh(['service','professional']));
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('success', 'Agendamento cancelado. Se precisar, você poderá reagendar com nossa equipe.');
    }

    public function checkIn(Request $request, string $token)
    {
        $appointment = $this->appointment($token);

        abort_if(
            ! in_array($appointment->status, ['confirmed','awaiting_confirmation'], true),
            422,
            'Este agendamento não permite check-in.'
        );

        if (! $appointment->check_in_completed_at) {
            $appointment->forceFill([
                'check_in_completed_at' => now(),
            ])->save();

            AppointmentEvent::create([
                'appointment_id' => $appointment->id,
                'event_type' => 'patient_check_in',
                'title' => 'Check-in online realizado',
                'description' => 'Paciente realizou o check-in pela Jornada ENFAS.',
                'occurred_at' => now(),
            ]);
        }

        return back()->with('success', 'Check-in realizado com sucesso.');
    }

    public function satisfaction(Request $request, string $token)
    {
        $appointment = $this->appointment($token);

        $data = $request->validate([
            'score' => ['required','integer','between:0,10'],
            'comment' => ['nullable','string','max:2000'],
        ]);

        $appointment->forceFill([
            'satisfaction_score' => $data['score'],
            'satisfaction_comment' => $data['comment'] ?? null,
            'satisfaction_at' => now(),
        ])->save();

        AppointmentEvent::create([
            'appointment_id' => $appointment->id,
            'event_type' => 'patient_satisfaction',
            'title' => 'Pesquisa de satisfação respondida',
            'description' => 'Paciente avaliou o atendimento com nota '.$data['score'].'.',
            'occurred_at' => now(),
        ]);

        return back()->with('success', 'Obrigado pela sua avaliação.');
    }

    public function calendar(string $token): StreamedResponse
    {
        $appointment = $this->appointment($token);

        $start = Carbon::parse($appointment->start_at)->utc()->format('Ymd\THis\Z');
        $end = Carbon::parse($appointment->end_at)->utc()->format('Ymd\THis\Z');
        $uid = $appointment->code.'@agenda.enfas.com.br';

        $escape = static fn (?string $value) => str_replace(
            ["\\", ",", ";", "\r", "\n"],
            ["\\\\", "\,", "\;", "", "\\n"],
            (string) $value
        );

        $summary = $escape($appointment->service?->name ?: 'Atendimento ENFAS');
        $description = $escape(
            'Atendimento com '.($appointment->professional?->name ?: 'profissional ENFAS')
        );

        $ics = implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//ENFAS//Agenda//PT-BR',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:'.$uid,
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:'.$start,
            'DTEND:'.$end,
            'SUMMARY:'.$summary,
            'DESCRIPTION:'.$description,
            'END:VEVENT',
            'END:VCALENDAR',
            '',
        ]);

        return response()->streamDownload(
            static function () use ($ics) {
                echo $ics;
            },
            'agendamento-'.$appointment->code.'.ics',
            ['Content-Type' => 'text/calendar; charset=utf-8']
        );
    }
}
