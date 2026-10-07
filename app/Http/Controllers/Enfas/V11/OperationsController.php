<?php

namespace App\Http\Controllers\Enfas\V11;

use App\Http\Controllers\Controller;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OperationsController extends Controller
{
    private function appointments(): Builder
    {
        $query = DB::table('appointments as a')
            ->leftJoin(
                'patients as p',
                'p.id',
                '=',
                'a.patient_id'
            )
            ->select([
                'a.*',
                'p.name as patient_name',
                'p.phone as patient_phone',
                'p.email as patient_email',
            ]);

        if (Schema::hasTable('professionals')) {
            $query
                ->leftJoin(
                    'professionals as pro',
                    'pro.id',
                    '=',
                    'a.professional_id'
                )
                ->addSelect(
                    'pro.name as professional_name'
                );
        } else {
            $query->addSelect(
                DB::raw(
                    'NULL as professional_name'
                )
            );
        }

        if (Schema::hasTable('services')) {
            $query
                ->leftJoin(
                    'services as srv',
                    'srv.id',
                    '=',
                    'a.service_id'
                )
                ->addSelect(
                    'srv.name as service_name'
                );
        } else {
            $query->addSelect(
                DB::raw(
                    'NULL as service_name'
                )
            );
        }

        if (
            Schema::hasTable('locations')
            && Schema::hasColumn(
                'appointments',
                'location_id'
            )
        ) {
            $query
                ->leftJoin(
                    'locations as loc',
                    'loc.id',
                    '=',
                    'a.location_id'
                )
                ->addSelect(
                    'loc.name as location_name'
                );
        } else {
            $query->addSelect(
                DB::raw(
                    'NULL as location_name'
                )
            );
        }

        if (Schema::hasTable('wa_messages')) {
            $query->selectSub(
                function ($sub) {
                    $sub
                        ->from('wa_messages as wm')
                        ->select('wm.status')
                        ->whereColumn(
                            'wm.appointment_id',
                            'a.id'
                        )
                        ->orderByDesc('wm.id')
                        ->limit(1);
                },
                'wa_status'
            );

            $query->selectSub(
                function ($sub) {
                    $sub
                        ->from('wa_messages as wm')
                        ->select('wm.updated_at')
                        ->whereColumn(
                            'wm.appointment_id',
                            'a.id'
                        )
                        ->orderByDesc('wm.id')
                        ->limit(1);
                },
                'wa_updated_at'
            );

            $query->selectSub(
                function ($sub) {
                    $sub
                        ->from('wa_messages as wm')
                        ->select('wm.error_message')
                        ->whereColumn(
                            'wm.appointment_id',
                            'a.id'
                        )
                        ->orderByDesc('wm.id')
                        ->limit(1);
                },
                'wa_error'
            );

            $query->selectSub(
                function ($sub) {
                    $sub
                        ->from('wa_messages as wm')
                        ->select('wm.meta_message_id')
                        ->whereColumn(
                            'wm.appointment_id',
                            'a.id'
                        )
                        ->orderByDesc('wm.id')
                        ->limit(1);
                },
                'wa_message_id_latest'
            );

            $query->selectSub(
                function ($sub) {
                    $sub
                        ->from('wa_messages as wm')
                        ->selectRaw('COUNT(*)')
                        ->whereColumn(
                            'wm.appointment_id',
                            'a.id'
                        )
                        ->where(
                            'wm.direction',
                            'outbound'
                        );
                },
                'wa_attempts'
            );

            $query->selectSub(
                function ($sub) {
                    $sub
                        ->from('wa_messages as wm')
                        ->selectRaw('MAX(wm.created_at)')
                        ->whereColumn(
                            'wm.appointment_id',
                            'a.id'
                        )
                        ->where(
                            'wm.direction',
                            'outbound'
                        );
                },
                'wa_last_outbound_at'
            );

            $query->selectSub(
                function ($sub) {
                    $sub
                        ->from('wa_messages as wm')
                        ->selectRaw('MAX(wm.created_at)')
                        ->whereColumn(
                            'wm.appointment_id',
                            'a.id'
                        )
                        ->where(
                            'wm.direction',
                            'inbound'
                        );
                },
                'wa_last_inbound_at'
            );
        } else {
            $query->addSelect([
                DB::raw(
                    'NULL as wa_status'
                ),
                DB::raw(
                    'NULL as wa_updated_at'
                ),
                DB::raw(
                    'NULL as wa_error'
                ),
                DB::raw(
                    'NULL as wa_message_id_latest'
                ),
                DB::raw(
                    '0 as wa_attempts'
                ),
                DB::raw(
                    'NULL as wa_last_outbound_at'
                ),
                DB::raw(
                    'NULL as wa_last_inbound_at'
                ),
            ]);
        }

        if (Schema::hasTable('confirmation_attempts')) {
            $query->selectSub(
                function ($sub) {
                    $sub
                        ->from('confirmation_attempts as ca')
                        ->select('ca.status')
                        ->whereColumn(
                            'ca.appointment_id',
                            'a.id'
                        )
                        ->where(
                            'ca.channel',
                            'voice'
                        )
                        ->orderByDesc('ca.id')
                        ->limit(1);
                },
                'voice_status'
            );

            $query->selectSub(
                function ($sub) {
                    $sub
                        ->from('confirmation_attempts as ca')
                        ->select('ca.outcome')
                        ->whereColumn(
                            'ca.appointment_id',
                            'a.id'
                        )
                        ->where(
                            'ca.channel',
                            'voice'
                        )
                        ->orderByDesc('ca.id')
                        ->limit(1);
                },
                'voice_outcome'
            );

            $query->selectSub(
                function ($sub) {
                    $sub
                        ->from('confirmation_attempts as ca')
                        ->select('ca.scheduled_at')
                        ->whereColumn(
                            'ca.appointment_id',
                            'a.id'
                        )
                        ->where(
                            'ca.channel',
                            'voice'
                        )
                        ->orderByDesc('ca.id')
                        ->limit(1);
                },
                'voice_scheduled_at'
            );

            $query->selectSub(
                function ($sub) {
                    $sub
                        ->from('confirmation_attempts as ca')
                        ->selectRaw('COUNT(*)')
                        ->whereColumn(
                            'ca.appointment_id',
                            'a.id'
                        )
                        ->where(
                            'ca.channel',
                            'voice'
                        );
                },
                'voice_attempts'
            );
        } else {
            $query->addSelect([
                DB::raw(
                    'NULL as voice_status'
                ),
                DB::raw(
                    'NULL as voice_outcome'
                ),
                DB::raw(
                    'NULL as voice_scheduled_at'
                ),
                DB::raw(
                    '0 as voice_attempts'
                ),
            ]);
        }

        return $query;
    }

    private function dayRange(
        ?string $value = null
    ): array {
        try {
            $day = $value
                ? Carbon::createFromFormat(
                    'Y-m-d',
                    $value,
                    config(
                        'app.timezone',
                        'America/Sao_Paulo'
                    )
                )
                : now();
        } catch (\Throwable) {
            $day = now();
        }

        return [
            $day->copy()->startOfDay(),
            $day->copy()->endOfDay(),
            $day,
        ];
    }

    private function dayStats(
        Carbon $start,
        Carbon $end
    ): array {
        $base = DB::table('appointments')
            ->whereBetween(
                'start_at',
                [$start, $end]
            );

        return [
            'total' =>
                (clone $base)->count(),

            'waiting' =>
                (clone $base)
                    ->where(
                        function ($query) {
                            $query
                                ->where(
                                    'confirmation_status',
                                    'pending'
                                )
                                ->orWhere(
                                    'status',
                                    'awaiting_confirmation'
                                );
                        }
                    )
                    ->count(),

            'confirmed' =>
                (clone $base)
                    ->where(
                        function ($query) {
                            $query
                                ->where(
                                    'confirmation_status',
                                    'confirmed'
                                )
                                ->orWhere(
                                    'status',
                                    'confirmed'
                                );
                        }
                    )
                    ->count(),

            'cancelled' =>
                (clone $base)
                    ->where(
                        'status',
                        'cancelled'
                    )
                    ->count(),
        ];
    }

    public function today()
    {
        [$start, $end, $day] =
            $this->dayRange();

        $rows = $this->appointments()
            ->whereBetween(
                'a.start_at',
                [$start, $end]
            )
            ->orderBy('a.start_at')
            ->limit(100)
            ->get();

        $stats = $this->dayStats(
            $start,
            $end
        );

        $since = now()->subDay();

        $messages = [
            'sent' => 0,
            'delivered' => 0,
            'read' => 0,
            'failed' => 0,
            'received' => 0,
        ];

        if (Schema::hasTable('wa_messages')) {
            foreach (
                array_keys($messages)
                as $status
            ) {
                $messages[$status] =
                    DB::table('wa_messages')
                        ->where(
                            'created_at',
                            '>=',
                            $since
                        )
                        ->where(
                            'status',
                            $status
                        )
                        ->count();
            }
        }

        $failures = collect();

        if (Schema::hasTable('wa_messages')) {
            $failures =
                DB::table('wa_messages as wm')
                    ->leftJoin(
                        'patients as p',
                        'p.id',
                        '=',
                        'wm.patient_id'
                    )
                    ->where(
                        'wm.status',
                        'failed'
                    )
                    ->where(
                        'wm.created_at',
                        '>=',
                        now()->subDay()
                    )
                    ->orderByDesc(
                        'wm.id'
                    )
                    ->limit(5)
                    ->get([
                        'wm.id',
                        'wm.appointment_id',
                        'wm.recipient',
                        'wm.error_message',
                        'wm.failed_at',
                        'p.name as patient_name',
                    ]);
        }

        $lastWebhook = null;

        if (
            Schema::hasTable(
                'wa_webhook_events'
            )
        ) {
            $lastWebhook =
                DB::table(
                    'wa_webhook_events'
                )
                    ->orderByDesc('id')
                    ->first();
        }

        $automation = [
            'active' => 0,
            'total' => 0,
        ];

        if (
            Schema::hasTable(
                'wa_automations'
            )
        ) {
            $automation['active'] =
                DB::table(
                    'wa_automations'
                )
                    ->where(
                        'is_active',
                        true
                    )
                    ->count();

            $automation['total'] =
                DB::table(
                    'wa_automations'
                )
                    ->count();
        }

        return view(
            'enfas.v11.today',
            compact(
                'rows',
                'stats',
                'messages',
                'failures',
                'lastWebhook',
                'automation',
                'day'
            )
        );
    }

    public function confirmations(
        Request $request
    ) {
        [$start, $end, $day] =
            $this->dayRange(
                $request->string(
                    'date'
                )->toString()
            );

        $state = $request
            ->string(
                'state',
                'waiting'
            )
            ->toString();

        if (
            ! in_array(
                $state,
                [
                    'waiting',
                    'confirmed',
                    'cancelled',
                    'attention',
                    'calls',
                    'all',
                ],
                true
            )
        ) {
            $state = 'waiting';
        }

        $search = trim(
            $request
                ->string('q')
                ->toString()
        );

        $query = $this
            ->appointments()
            ->whereBetween(
                'a.start_at',
                [$start, $end]
            );

        if ($state === 'waiting') {
            $query->where(
                function ($q) {
                    $q
                        ->where(
                            'a.confirmation_status',
                            'pending'
                        )
                        ->orWhere(
                            'a.status',
                            'awaiting_confirmation'
                        );
                }
            );
        }

        if ($state === 'confirmed') {
            $query->where(
                function ($q) {
                    $q
                        ->where(
                            'a.confirmation_status',
                            'confirmed'
                        )
                        ->orWhere(
                            'a.status',
                            'confirmed'
                        );
                }
            );
        }

        if ($state === 'cancelled') {
            $query->where(
                'a.status',
                'cancelled'
            );
        }

        if ($state === 'attention') {
            $query
                ->where(
                    function ($q) {
                        $q
                            ->where(
                                'a.confirmation_status',
                                'pending'
                            )
                            ->orWhere(
                                'a.status',
                                'awaiting_confirmation'
                            );
                    }
                )
                ->where(
                    function ($q) {
                        $q
                            ->where(
                                'a.start_at',
                                '<=',
                                now()->addDay()
                            )
                            ->orWhereRaw(
                                "(SELECT wm.status FROM wa_messages wm WHERE wm.appointment_id = a.id ORDER BY wm.id DESC LIMIT 1) = 'failed'"
                            );
                    }
                );
        }

        if ($state === 'calls') {
            if (Schema::hasTable('confirmation_attempts')) {
                $query->whereExists(
                    function ($sub) {
                        $sub
                            ->selectRaw('1')
                            ->from('confirmation_attempts as ca')
                            ->whereColumn(
                                'ca.appointment_id',
                                'a.id'
                            )
                            ->where(
                                'ca.channel',
                                'voice'
                            )
                            ->whereIn(
                                'ca.status',
                                ['queued', 'in_progress']
                            );
                    }
                );
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if ($search !== '') {
            $query->where(
                function ($q) use (
                    $search
                ) {
                    $q
                        ->where(
                            'p.name',
                            'like',
                            '%'.$search.'%'
                        )
                        ->orWhere(
                            'p.phone',
                            'like',
                            '%'.$search.'%'
                        )
                        ->orWhere(
                            'a.code',
                            'like',
                            '%'.$search.'%'
                        );
                }
            );
        }

        $rows = $query
            ->orderBy(
                'a.start_at'
            )
            ->paginate(25)
            ->withQueryString();

        $stats = $this->dayStats(
            $start,
            $end
        );

        $attention = DB::table('appointments as a')
            ->whereBetween(
                'a.start_at',
                [$start, $end]
            )
            ->where(
                function ($q) {
                    $q
                        ->where(
                            'a.confirmation_status',
                            'pending'
                        )
                        ->orWhere(
                            'a.status',
                            'awaiting_confirmation'
                        );
                }
            )
            ->where(
                function ($q) {
                    $q
                        ->where(
                            'a.start_at',
                            '<=',
                            now()->addDay()
                        )
                        ->orWhereRaw(
                            "(SELECT wm.status FROM wa_messages wm WHERE wm.appointment_id = a.id ORDER BY wm.id DESC LIMIT 1) = 'failed'"
                        );
                }
            )
            ->count();

        $stats['attention'] = $attention;

        $stats['calls'] = Schema::hasTable(
            'confirmation_attempts'
        )
            ? DB::table('confirmation_attempts as ca')
                ->join(
                    'appointments as a',
                    'a.id',
                    '=',
                    'ca.appointment_id'
                )
                ->whereBetween(
                    'a.start_at',
                    [$start, $end]
                )
                ->where(
                    'ca.channel',
                    'voice'
                )
                ->whereIn(
                    'ca.status',
                    ['queued', 'in_progress']
                )
                ->count()
            : 0;

        return view(
            'enfas.v11.confirmations',
            compact(
                'rows',
                'stats',
                'state',
                'search',
                'day'
            )
        );
    }

    public function recordCall(
        Request $request,
        int $appointment
    ) {
        abort_unless(
            Schema::hasTable('confirmation_attempts'),
            404
        );

        $data = $request->validate([
            'outcome' => [
                'required',
                Rule::in([
                    'confirmed',
                    'cancelled',
                    'no_answer',
                    'busy',
                    'invalid_number',
                    'callback',
                ]),
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $current = DB::table('appointments')
            ->where('id', $appointment)
            ->first();

        abort_unless(
            $current,
            404
        );

        DB::transaction(
            function () use (
                $appointment,
                $current,
                $data
            ) {
                $attempt = DB::table(
                    'confirmation_attempts'
                )
                    ->where(
                        'appointment_id',
                        $appointment
                    )
                    ->where(
                        'channel',
                        'voice'
                    )
                    ->whereIn(
                        'status',
                        ['queued', 'in_progress']
                    )
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();

                if (! $attempt) {
                    $attemptNo = DB::table(
                        'confirmation_attempts'
                    )
                        ->where(
                            'appointment_id',
                            $appointment
                        )
                        ->where(
                            'channel',
                            'voice'
                        )
                        ->count() + 1;

                    $attemptId = DB::table(
                        'confirmation_attempts'
                    )->insertGetId([
                        'appointment_id' =>
                            $appointment,
                        'channel' =>
                            'voice',
                        'status' =>
                            'in_progress',
                        'reason' =>
                            'manual',
                        'source' =>
                            'operator',
                        'provider' =>
                            null,
                        'recipient' =>
                            preg_replace(
                                '/\D+/',
                                '',
                                (string) (
                                    DB::table('patients')
                                        ->where(
                                            'id',
                                            $current->patient_id
                                        )
                                        ->value('phone')
                                    ?? ''
                                )
                            ) ?: null,
                        'attempt_no' =>
                            $attemptNo,
                        'dedupe_key' =>
                            'manual:voice:appointment:'
                            .$appointment
                            .':'
                            .Str::uuid(),
                        'scheduled_at' =>
                            now(),
                        'started_at' =>
                            now(),
                        'created_by' =>
                            auth()->id(),
                        'created_at' =>
                            now(),
                        'updated_at' =>
                            now(),
                    ]);

                    $attempt = DB::table(
                        'confirmation_attempts'
                    )
                        ->where(
                            'id',
                            $attemptId
                        )
                        ->first();
                }

                DB::table('confirmation_attempts')
                    ->where(
                        'id',
                        $attempt->id
                    )
                    ->update([
                        'status' =>
                            'completed',
                        'outcome' =>
                            $data['outcome'],
                        'started_at' =>
                            $attempt->started_at
                                ?: now(),
                        'completed_at' =>
                            now(),
                        'notes' =>
                            $data['notes']
                                ?? null,
                        'created_by' =>
                            auth()->id(),
                        'updated_at' =>
                            now(),
                    ]);

                if (
                    $data['outcome']
                    === 'confirmed'
                ) {
                    DB::table('appointments')
                        ->where(
                            'id',
                            $appointment
                        )
                        ->update([
                            'status' =>
                                'confirmed',
                            'confirmation_status' =>
                                'confirmed',
                            'confirmation_channel' =>
                                'phone',
                            'confirmed_at' =>
                                now(),
                            'cancelled_at' =>
                                null,
                            'cancellation_reason' =>
                                null,
                            'updated_by' =>
                                auth()->id(),
                            'updated_at' =>
                                now(),
                        ]);
                }

                if (
                    $data['outcome']
                    === 'cancelled'
                ) {
                    DB::table('appointments')
                        ->where(
                            'id',
                            $appointment
                        )
                        ->update([
                            'status' =>
                                'cancelled',
                            'confirmation_status' =>
                                'cancelled',
                            'confirmation_channel' =>
                                'phone',
                            'confirmed_at' =>
                                null,
                            'cancelled_at' =>
                                now(),
                            'updated_by' =>
                                auth()->id(),
                            'updated_at' =>
                                now(),
                        ]);
                }

                if (Schema::hasTable(
                    'appointment_events'
                )) {
                    DB::table(
                        'appointment_events'
                    )->insert([
                        'appointment_id' =>
                            $appointment,
                        'user_id' =>
                            auth()->id(),
                        'event_type' =>
                            'phone_confirmation_'
                            .$data['outcome'],
                        'title' =>
                            'Contato por ligação',
                        'description' =>
                            'Resultado registrado na Central de Confirmações.',
                        'metadata' =>
                            json_encode(
                                [
                                    'source' =>
                                        'v11-confirmations',
                                    'channel' =>
                                        'voice',
                                    'outcome' =>
                                        $data['outcome'],
                                    'attempt_id' =>
                                        $attempt->id,
                                ],
                                JSON_UNESCAPED_UNICODE
                            ),
                        'occurred_at' =>
                            now(),
                        'created_at' =>
                            now(),
                        'updated_at' =>
                            now(),
                    ]);
                }
            }
        );

        $labels = [
            'confirmed' =>
                'Ligação registrada: presença confirmada.',
            'cancelled' =>
                'Ligação registrada: horário cancelado.',
            'no_answer' =>
                'Ligação registrada: não atendeu.',
            'busy' =>
                'Ligação registrada: ocupado.',
            'invalid_number' =>
                'Ligação registrada: número inválido.',
            'callback' =>
                'Ligação registrada: retorno solicitado.',
        ];

        return back()->with(
            'success',
            $labels[$data['outcome']]
        );
    }

    public function mark(
        Request $request,
        int $appointment
    ) {
        $data = $request->validate([
            'action' => [
                'required',
                Rule::in([
                    'confirm',
                    'pending',
                    'cancel',
                ]),
            ],
        ]);

        $current =
            DB::table('appointments')
                ->where(
                    'id',
                    $appointment
                )
                ->first();

        abort_unless(
            $current,
            404
        );

        $messages = [
            'confirm' =>
                'Presença confirmada.',

            'pending' =>
                'Horário voltou para aguardando resposta.',

            'cancel' =>
                'Horário cancelado.',
        ];

        DB::transaction(
            function () use (
                $data,
                $current
            ) {
                $update = [
                    'updated_at' => now(),
                    'updated_by' =>
                        auth()->id(),
                ];

                $eventType = null;
                $eventTitle = null;

                if (
                    $data['action']
                    === 'confirm'
                ) {
                    $update = array_merge(
                        $update,
                        [
                            'status' =>
                                'confirmed',

                            'confirmation_status' =>
                                'confirmed',

                            'confirmation_channel' =>
                                'manual',

                            'confirmed_at' =>
                                now(),

                            'cancelled_at' =>
                                null,

                            'cancellation_reason' =>
                                null,
                        ]
                    );

                    $eventType =
                        'manual_confirmed';

                    $eventTitle =
                        'Presença confirmada';
                }

                if (
                    $data['action']
                    === 'pending'
                ) {
                    $update = array_merge(
                        $update,
                        [
                            'status' =>
                                'awaiting_confirmation',

                            'confirmation_status' =>
                                'pending',

                            'confirmation_channel' =>
                                null,

                            'confirmed_at' =>
                                null,

                            'cancelled_at' =>
                                null,

                            'cancellation_reason' =>
                                null,
                        ]
                    );

                    $eventType =
                        'manual_pending';

                    $eventTitle =
                        'Aguardando resposta';
                }

                if (
                    $data['action']
                    === 'cancel'
                ) {
                    $update = array_merge(
                        $update,
                        [
                            'status' =>
                                'cancelled',

                            'confirmation_status' =>
                                'cancelled',

                            'confirmed_at' =>
                                null,

                            'cancelled_at' =>
                                now(),
                        ]
                    );

                    $eventType =
                        'manual_cancelled';

                    $eventTitle =
                        'Horário cancelado';
                }

                DB::table(
                    'appointments'
                )
                    ->where(
                        'id',
                        $current->id
                    )
                    ->update($update);

                if (
                    Schema::hasTable(
                        'appointment_events'
                    )
                ) {
                    DB::table(
                        'appointment_events'
                    )->insert([
                        'appointment_id' =>
                            $current->id,

                        'user_id' =>
                            auth()->id(),

                        'event_type' =>
                            $eventType,

                        'title' =>
                            $eventTitle,

                        'description' =>
                            'Alteração realizada pela tela de confirmações.',

                        'metadata' =>
                            json_encode(
                                [
                                    'source' =>
                                        'v11-confirmations',

                                    'action' =>
                                        $data['action'],
                                ],
                                JSON_UNESCAPED_UNICODE
                            ),

                        'occurred_at' =>
                            now(),

                        'created_at' =>
                            now(),

                        'updated_at' =>
                            now(),
                    ]);
                }
            }
        );

        return back()->with(
            'success',
            $messages[
                $data['action']
            ]
        );
    }
}
