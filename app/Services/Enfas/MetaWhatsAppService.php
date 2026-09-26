<?php

namespace App\Services\Enfas;

use App\Models\MetaIntegration;
use App\Models\WaMessage;
use App\Models\WaTemplate;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class MetaWhatsAppService
{
    public function integration(): MetaIntegration
    {
        $integration = MetaIntegration::first();

        if (! $integration) {
            throw new RuntimeException('A integração Meta ainda não foi configurada.');
        }

        if (blank($integration->access_token)) {
            throw new RuntimeException('O Access Token da Meta não está salvo.');
        }

        if (blank($integration->waba_id)) {
            throw new RuntimeException('O WABA ID não está configurado.');
        }

        if (blank($integration->phone_number_id)) {
            throw new RuntimeException('O Phone Number ID não está configurado.');
        }

        return $integration;
    }

    private function base(MetaIntegration $integration): string
    {
        return 'https://graph.facebook.com/'
            . ($integration->graph_version ?: 'v25.0');
    }

    private function client(MetaIntegration $integration)
    {
        return Http::acceptJson()
            ->asJson()
            ->withToken($integration->access_token)
            ->timeout(25)
            ->retry(2, 500, throw: false);
    }

    private function apiException(Response $response, string $fallback): RuntimeException
    {
        $error = $response->json('error', []);

        $message = collect([
            $error['error_user_title'] ?? null,
            $error['error_user_msg'] ?? null,
            data_get($error, 'error_data.details'),
            $error['message'] ?? null,
        ])
            ->filter()
            ->unique()
            ->implode(' — ');

        if ($message === '') {
            $message = $fallback;
        }

        $code = $error['code'] ?? null;
        $subcode = $error['error_subcode'] ?? null;
        $trace = $error['fbtrace_id'] ?? null;

        $meta = collect([
            $code ? 'code ' . $code : null,
            $subcode ? 'subcode ' . $subcode : null,
            $trace ? 'trace ' . $trace : null,
        ])->filter()->implode(', ');

        if ($meta !== '') {
            $message .= ' [' . $meta . ']';
        }

        return new RuntimeException($message);
    }

    public function testConnection(): array
    {
        $integration = $this->integration();

        $response = $this->client($integration)->get(
            $this->base($integration)
                . '/'
                . $integration->phone_number_id,
            [
                'fields' => 'display_phone_number,verified_name,quality_rating',
            ]
        );

        if (! $response->successful()) {
            throw $this->apiException(
                $response,
                'A Meta recusou o teste de conexão.'
            );
        }

        $data = $response->json();

        $integration->update([
            'display_phone_number' => $data['display_phone_number'] ?? null,
            'verified_name' => $data['verified_name'] ?? null,
            'quality_rating' => $data['quality_rating'] ?? null,
            'last_tested_at' => now(),
        ]);

        return $data;
    }

    public function syncTemplates(): int
    {
        $integration = $this->integration();

        // A API oficial suporta GET /{WABA-ID}/message_templates
        // com os campos padrao. Evitamos solicitar campos opcionais
        // que variam entre versoes da Graph API.
        $url = $this->base($integration)
            . '/'
            . $integration->waba_id
            . '/message_templates';

        $params = ['limit' => 100];
        $count = 0;
        $pages = 0;

        while ($pages < 50) {
            $response = $this->client($integration)->get(
                $url,
                $params
            );

            if (! $response->successful()) {
                throw $this->apiException(
                    $response,
                    'Não foi possível sincronizar os modelos da Meta.'
                );
            }

            foreach ($response->json('data', []) as $remote) {
                if (blank($remote['name'] ?? null)) {
                    continue;
                }

                // Modelos de exemplo criados pela Meta nao pertencem
                // ao fluxo operacional do ENFAS Agenda.
                if (str_starts_with(
                    (string) $remote['name'],
                    'sample_'
                )) {
                    continue;
                }

                $language = str_replace(
                    '-',
                    '_',
                    (string) ($remote['language'] ?? 'pt_BR')
                );

                $parsed = $this->parseComponents(
                    $remote['components'] ?? []
                );

                $template = WaTemplate::query()
                    ->where('name', $remote['name'])
                    ->where('language', $language)
                    ->first();

                $values = [
                    'meta_template_id' => $remote['id']
                        ?? $template?->meta_template_id,
                    'category' => $remote['category']
                        ?? $template?->category
                        ?? 'UTILITY',
                    'status' => $remote['status']
                        ?? $template?->status
                        ?? 'UNKNOWN',
                    'last_synced_at' => now(),
                    'header_type' => $parsed['header_type'] ?? 'NONE',
                ];

                if (! $template) {
                    $values += [
                        'name' => $remote['name'],
                        'purpose' => 'general',
                        'language' => $language,
                        'header_text' => $parsed['header'],
                        'body' => $parsed['body'],
                        'footer' => $parsed['footer'],
                        'buttons' => $parsed['buttons'],
                        'variable_keys' => [],
                        'sample_values' => [],
                        'rejection_reason' => null,
                    ];

                    WaTemplate::create($values);
                } else {
                    if (($remote['rejected_reason'] ?? null) !== null) {
                        $values['rejection_reason'] = $remote['rejected_reason'];
                    }

                    $template->update($values);
                }

                $count++;
            }

            $after = data_get(
                $response->json(),
                'paging.cursors.after'
            );

            $next = data_get(
                $response->json(),
                'paging.next'
            );

            if (! $next || ! $after) {
                break;
            }

            $params = [
                'limit' => 100,
                'after' => $after,
            ];

            $pages++;
        }

        return $count;
    }

    private function parseComponents(array $components): array
    {
        $header = null;
        $headerType = 'NONE';
        $body = '';
        $footer = null;
        $buttons = [];

        foreach ($components as $component) {
            $type = strtoupper(
                (string) ($component['type'] ?? '')
            );

            if ($type === 'HEADER') {
                $headerType = strtoupper(
                    (string) ($component['format'] ?? 'NONE')
                );

                if ($headerType === 'TEXT') {
                    $header = $component['text'] ?? null;
                }
            }

            if ($type === 'BODY') {
                $body = $component['text'] ?? '';
            }

            if ($type === 'FOOTER') {
                $footer = $component['text'] ?? null;
            }

            if ($type === 'BUTTONS') {
                foreach ($component['buttons'] ?? [] as $button) {
                    if (($button['type'] ?? null) === 'QUICK_REPLY') {
                        $buttons[] = [
                            'text' => $button['text'] ?? 'Responder',
                            'action' => 'none',
                        ];
                    }
                }
            }
        }

        return [
            'header' => $header,
            'header_type' => $headerType,
            'body' => $body,
            'footer' => $footer,
            'buttons' => $buttons,
        ];
    }

    public function createTemplate(WaTemplate $template): array
    {
        if (! config('enfas_runtime.meta_template_create_api', false)) {
            throw new RuntimeException(
                'Criação de modelos por API está desabilitada para esta WABA porque a Meta retornou code 100 / subcode 2388339. Use o WhatsApp Manager e depois sincronize.'
            );
        }

        $integration = $this->integration();
        $components = [];

        if (filled($template->header_text)) {
            $components[] = [
                'type' => 'HEADER',
                'format' => 'TEXT',
                'text' => $template->header_text,
            ];
        }

        $body = [
            'type' => 'BODY',
            'text' => $template->body,
        ];

        if (($template->sample_values ?? []) !== []) {
            $body['example'] = [
                'body_text' => [
                    array_values($template->sample_values),
                ],
            ];
        }

        $components[] = $body;

        if (filled($template->footer)) {
            $components[] = [
                'type' => 'FOOTER',
                'text' => $template->footer,
            ];
        }

        $buttons = collect($template->buttons ?? [])
            ->take(3)
            ->map(fn (array $button) => [
                'type' => 'QUICK_REPLY',
                'text' => $button['text'],
            ])
            ->values()
            ->all();

        if ($buttons !== []) {
            $components[] = [
                'type' => 'BUTTONS',
                'buttons' => $buttons,
            ];
        }

        $response = $this->client($integration)->post(
            $this->base($integration)
                . '/'
                . $integration->waba_id
                . '/message_templates',
            [
                'name' => $template->name,
                'language' => $template->language,
                'category' => $template->category,
                'components' => $components,
            ]
        );

        if (! $response->successful()) {
            throw $this->apiException(
                $response,
                'A Meta recusou a criação do modelo.'
            );
        }

        $data = $response->json();

        $template->update([
            'meta_template_id' => $data['id'] ?? null,
            'status' => $data['status'] ?? 'PENDING',
            'category' => $data['category'] ?? $template->category,
            'rejection_reason' => null,
            'last_synced_at' => now(),
        ]);

        return $data;
    }

    public function deleteTemplate(WaTemplate $template): void
    {
        if ($template->status !== 'LOCAL'
            || filled($template->meta_template_id)) {
            $integration = $this->integration();

            $response = $this->client($integration)->delete(
                $this->base($integration)
                    . '/'
                    . $integration->waba_id
                    . '/message_templates',
                [
                    'name' => $template->name,
                ]
            );

            if (! $response->successful()) {
                throw $this->apiException(
                    $response,
                    'A Meta recusou a exclusão do modelo.'
                );
            }
        }

        $template->delete();
    }

    private function appointmentData(int $appointmentId): object
    {
        $query = DB::table('appointments')
            ->join(
                'patients',
                'patients.id',
                '=',
                'appointments.patient_id'
            )
            ->join(
                'professionals',
                'professionals.id',
                '=',
                'appointments.professional_id'
            )
            ->join(
                'services',
                'services.id',
                '=',
                'appointments.service_id'
            );

        $hasLocation = Schema::hasTable('locations')
            && Schema::hasColumn(
                'appointments',
                'location_id'
            );

        if ($hasLocation) {
            $query->leftJoin(
                'locations',
                'locations.id',
                '=',
                'appointments.location_id'
            );
        }

        $columns = [
            'appointments.*',
            'patients.name as patient_name',
            'patients.phone as patient_phone',
            'professionals.name as professional_name',
            'services.name as service_name',
        ];

        if ($hasLocation) {
            $columns[] = 'locations.name as location_name';
        }

        $appointment = $query
            ->where('appointments.id', $appointmentId)
            ->first($columns);

        if (! $appointment) {
            throw new RuntimeException('Agendamento não encontrado.');
        }

        if (! property_exists($appointment, 'location_name')) {
            $appointment->location_name = '';
        }

        return $appointment;
    }

    private function variableValue(string $key, object $appointment): string
    {
        return match ($key) {
            'paciente_nome' => (string) $appointment->patient_name,
            'data' => \Carbon\Carbon::parse($appointment->start_at)->format('d/m/Y'),
            'hora' => \Carbon\Carbon::parse($appointment->start_at)->format('H:i'),
            'profissional' => (string) $appointment->professional_name,
            'servico' => (string) $appointment->service_name,
            'codigo_agendamento' => (string) $appointment->code,
            'local' => (string) $appointment->location_name,
            default => '',
        };
    }

    private function headerImageLink(WaTemplate $template): ?string
    {
        if (strtoupper((string) $template->header_type) !== 'IMAGE') {
            return null;
        }

        if (! $template->header_media_id) {
            throw new RuntimeException(
                'O modelo exige imagem no cabeçalho, mas nenhuma mídia foi configurada.'
            );
        }

        $media = DB::table('media_assets')
            ->where('id', $template->header_media_id)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->first();

        if (! $media) {
            throw new RuntimeException(
                'A imagem de cabeçalho não existe ou está desativada.'
            );
        }

        $disk = (string) ($media->disk ?: 'public');
        $path = ltrim((string) $media->path, '/');

        if ($disk !== 'public') {
            throw new RuntimeException(
                'A imagem do WhatsApp precisa estar no disco público.'
            );
        }

        if (! Storage::disk($disk)->exists($path)) {
            throw new RuntimeException(
                'O arquivo da imagem de cabeçalho não foi encontrado.'
            );
        }

        $url = Storage::disk($disk)->url($path);

        if (str_starts_with($url, 'http://')
            || str_starts_with($url, 'https://')) {
            return $url;
        }

        return rtrim((string) config('app.url'), '/')
            . '/'
            . ltrim($url, '/');
    }

    private function normalizeWhatsAppPhone(?string $raw): string
    {
        $digits = preg_replace(
            '/\D+/',
            '',
            (string) $raw
        );

        if ($digits === '') {
            return '';
        }

        /*
         * Remove prefixo internacional 00.
         * Ex.: 005511959759802
         */
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        /*
         * Cadastro brasileiro com zero de operadora/tronco.
         * Ex.: 011959759802
         */
        if (str_starts_with($digits, '0')
            && in_array(strlen($digits), [11, 12], true)) {
            $withoutZero = substr($digits, 1);

            if (in_array(strlen($withoutZero), [10, 11], true)) {
                $digits = $withoutZero;
            }
        }

        /*
         * Brasil sem DDI:
         * 11959759802  -> 5511959759802
         * 1133334444   -> 551133334444
         */
        if (in_array(strlen($digits), [10, 11], true)) {
            return '55'.$digits;
        }

        /*
         * Se já possui DDI, preservamos.
         */
        return $digits;
    }

    public function sendAppointmentTemplate(
        int $appointmentId,
        WaTemplate $template,
        ?int $automationId = null,
        ?string $dedupeKey = null
    ): WaMessage {
        if ($template->status !== 'APPROVED') {
            throw new RuntimeException(
                'O modelo ainda não está aprovado pela Meta.'
            );
        }

        $integration = $this->integration();
        $appointment = $this->appointmentData($appointmentId);

        $phone = $this->normalizeWhatsAppPhone(
            (string) $appointment->patient_phone
        );

        if (blank($phone)) {
            throw new RuntimeException(
                'O paciente não possui WhatsApp válido.'
            );
        }

        $parameters = [];

        foreach ($template->variable_keys ?? [] as $key) {
            $parameters[] = [
                'type' => 'text',
                'text' => $this->variableValue(
                    $key,
                    $appointment
                ),
            ];
        }

        $components = [];

        $headerImage = $this->headerImageLink($template);

        if ($headerImage !== null) {
            $components[] = [
                'type' => 'header',
                'parameters' => [
                    [
                        'type' => 'image',
                        'image' => [
                            'link' => $headerImage,
                        ],
                    ],
                ],
            ];
        }


        if ($parameters !== []) {
            $components[] = [
                'type' => 'body',
                'parameters' => $parameters,
            ];
        }

        foreach (($template->buttons ?? []) as $index => $button) {
            $payload = match ($button['action'] ?? 'none') {
                'confirm' => 'CONFIRM:' . $appointment->code,
                'cancel' => 'CANCEL:' . $appointment->code,
                'reschedule' => 'RESCHEDULE:' . $appointment->code,
                default => 'ACTION:' . $appointment->code,
            };

            $components[] = [
                'type' => 'button',
                'sub_type' => 'quick_reply',
                'index' => (string) $index,
                'parameters' => [
                    [
                        'type' => 'payload',
                        'payload' => $payload,
                    ],
                ],
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $phone,
            'type' => 'template',
            'template' => [
                'name' => $template->name,
                'language' => [
                    'code' => $template->language,
                ],
                'components' => $components,
            ],
        ];

        $message = WaMessage::create([
            'appointment_id' => $appointmentId,
            'patient_id' => $appointment->patient_id,
            'template_id' => $template->id,
            'automation_id' => $automationId,
            'direction' => 'outbound',
            'message_type' => 'template',
            'status' => 'sending',
            'recipient' => $phone,
            'body' => $template->body,
            'payload' => $payload,
            'dedupe_key' => $dedupeKey,
        ]);

        $response = $this->client($integration)->post(
            $this->base($integration)
                . '/'
                . $integration->phone_number_id
                . '/messages',
            $payload
        );

        if (! $response->successful()) {
            $error = $this->apiException(
                $response,
                'A Meta recusou o envio.'
            )->getMessage();

            $message->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => $error,
            ]);

            throw new RuntimeException($error);
        }

        $message->update([
            'status' => 'sent',
            'meta_message_id' => $response->json('messages.0.id'),
            'sent_at' => now(),
        ]);

        if (Schema::hasColumn(
            'appointments',
            'confirmation_requested_at'
        )) {
            DB::table('appointments')
                ->where('id', $appointmentId)
                ->update([
                    'confirmation_requested_at' => now(),
                    'confirmation_channel' => 'whatsapp',
                    'whatsapp_message_id' => $message->meta_message_id,
                    'updated_at' => now(),
                ]);
        }

        return $message;
    }
}
