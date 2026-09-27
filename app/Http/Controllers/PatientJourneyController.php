<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentEvent;
use App\Services\Enfas\AvailabilityService;
use App\Services\Enfas\WaitlistService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PatientJourneyController extends Controller
{
    private function appointment(string $token): Appointment
    {
        return Appointment::query()
            ->with(['patient','professional','service','location'])
            ->where('public_token', $token)
            ->firstOrFail();
    }

    public function show(string $token, AvailabilityService $availability)
    {
        $appointment = $this->appointment($token);

        $rescheduleSlots = [];

        if (
            $appointment->service?->allow_online_reschedule
            && ! in_array($appointment->status, ['completed','cancelled','no_show'], true)
        ) {
            $rescheduleSlots = $availability->nextSlots(
                $appointment->professional_id,
                $appointment->service_id,
                now(),
                null,
                8,
                $appointment->id,
                30
            );
        }

        return response()
            ->view(
                'patient-journey.show',
                compact('appointment', 'rescheduleSlots')
            )
            ->header('Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive');
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

    public function reschedule(
        Request $request,
        string $token,
        AvailabilityService $availability
    ) {
        $appointment = $this->appointment($token);

        abort_if(
            ! $appointment->service?->allow_online_reschedule,
            422,
            'Este serviço não permite reagendamento online.'
        );

        abort_if(
            in_array($appointment->status, ['completed','cancelled','no_show'], true),
            422,
            'Este agendamento não pode mais ser reagendado.'
        );

        $data = $request->validate([
            'start_at' => ['required','date','after:now'],
        ]);

        $start = Carbon::parse($data['start_at']);
        $end = $start->copy()->addMinutes($appointment->duration_minutes);

        if (! $availability->isBookable(
            $appointment->professional_id,
            $start,
            $end,
            $appointment->id
        )) {
            throw ValidationException::withMessages([
                'start_at' => 'Esse horário acabou de ficar indisponível. Escolha outra opção.',
            ]);
        }

        $hold = $availability->hold(
            $appointment->professional_id,
            $start,
            $end,
            $appointment->id,
            5
        );

        try {
            DB::transaction(function () use ($appointment, $availability, $start, $end, $hold) {
                $locked = Appointment::query()
                    ->whereKey($appointment->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $availability->isBookable(
                    $locked->professional_id,
                    $start,
                    $end,
                    $locked->id
                )) {
                    throw ValidationException::withMessages([
                        'start_at' => 'Esse horário acabou de ficar indisponível. Escolha outra opção.',
                    ]);
                }

                $oldStart = $locked->start_at->copy();
                $oldEnd = $locked->end_at->copy();

                $locked->forceFill([
                    'start_at' => $start,
                    'end_at' => $end,
                    'status' => 'awaiting_confirmation',
                    'confirmation_status' => 'pending',
                    'confirmation_channel' => 'patient_journey',
                    'confirmed_at' => null,
                    'cancelled_at' => null,
                    'rescheduled_at' => now(),
                ])->save();

                DB::table('slot_reservations')
                    ->where('id', $hold->id)
                    ->update([
                        'status' => 'consumed',
                        'updated_at' => now(),
                    ]);

                AppointmentEvent::create([
                    'appointment_id' => $locked->id,
                    'event_type' => 'patient_rescheduled',
                    'title' => 'Reagendamento realizado pelo paciente',
                    'description' =>
                        $oldStart->format('d/m/Y H:i').'–'.$oldEnd->format('H:i')
                        .' → '.$start->format('d/m/Y H:i').'–'.$end->format('H:i'),
                    'occurred_at' => now(),
                ]);
            });
        } catch (\Throwable $e) {
            DB::table('slot_reservations')
                ->where('id', $hold->id)
                ->where('status', 'held')
                ->update([
                    'status' => 'released',
                    'updated_at' => now(),
                ]);

            throw $e;
        }

        return back()->with(
            'success',
            'Novo horário reservado. Confirme sua presença para concluir a jornada.'
        );
    }


    public function checkIn(Request $request, string $token)
    {
        $appointment = $this->appointment($token);

        abort_if(
            ! in_array($appointment->status, ['confirmed','awaiting_confirmation'], true),
            422,
            'Este agendamento não permite check-in.'
        );

        $windowStart = $appointment->start_at->copy()->subMinutes(120);
        $windowEnd = $appointment->end_at->copy()->addHours(4);

        abort_if(
            now()->lt($windowStart) || now()->gt($windowEnd),
            422,
            'O check-in fica disponível a partir de 2 horas antes do atendimento.'
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

        abort_unless(
            $appointment->status === 'completed',
            422,
            'A avaliação fica disponível após a conclusão do atendimento.'
        );

        $data = $request->validate([
            'score' => ['required','integer','between:0,10'],
            'stars' => ['required','integer','between:1,5'],
            'comment' => ['nullable','string','max:2000'],
        ]);

        $appointment->forceFill([
            'satisfaction_score' => $data['score'],
            'satisfaction_stars' => $data['stars'],
            'satisfaction_comment' => $data['comment'] ?? null,
            'satisfaction_at' => now(),
        ])->save();

        AppointmentEvent::create([
            'appointment_id' => $appointment->id,
            'event_type' => 'patient_satisfaction',
            'title' => 'Pesquisa de satisfação respondida',
            'description' => 'Paciente avaliou com '.$data['stars'].' estrela(s) e NPS '.$data['score'].'.',
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

        $location = $escape(
            $appointment->location
                ? trim($appointment->location->name.' · '.$appointment->location->fullAddress(), ' ·')
                : ''
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
            ...($location !== '' ? ['LOCATION:'.$location] : []),
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
