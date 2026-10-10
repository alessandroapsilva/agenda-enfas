<?php

namespace App\Http\Controllers\Enfas;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Services\Enfas\ConfirmationPolicyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function branding()
    {
        $logo = AppSetting::getValue(
            'branding',
            'logo'
        );

        $favicon = AppSetting::getValue(
            'branding',
            'favicon'
        );

        return view('enfas.admin.branding', [
            'systemName' => AppSetting::getValue(
                'branding',
                'system_name',
                'ENFAS Agenda'
            ),
            'organizationName' =>
                AppSetting::getValue(
                    'branding',
                    'organization_name',
                    ''
                ),
            'primaryColor' => AppSetting::getValue(
                'branding',
                'primary_color',
                '#2563eb'
            ),
            'logoPath' => $logo,
            'faviconPath' => $favicon,
            'logoUrl' => $logo
                ? asset('storage/' . ltrim($logo, '/'))
                : asset(
                    'assets/brand/enfas-agenda.svg'
                ),
            'faviconUrl' => $favicon
                ? asset(
                    'storage/'
                    . ltrim($favicon, '/')
                )
                : asset(
                    'assets/brand/enfas-agenda.svg'
                ),
        ]);
    }

    public function saveBranding(Request $request)
    {
        $data = $request->validate([
            'system_name' => [
                'required',
                'string',
                'max:120',
            ],
            'organization_name' => [
                'nullable',
                'string',
                'max:160',
            ],
            'primary_color' => [
                'required',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],
            'logo' => [
                'nullable',
                'file',
                'mimes:png,jpg,jpeg,webp',
                'max:4096',
            ],
            'favicon' => [
                'nullable',
                'file',
                'mimes:png,jpg,jpeg,webp,ico',
                'max:2048',
            ],
            'remove_logo' => [
                'nullable',
                'boolean',
            ],
            'remove_favicon' => [
                'nullable',
                'boolean',
            ],
        ]);

        AppSetting::setValue(
            'branding',
            'system_name',
            $data['system_name']
        );

        AppSetting::setValue(
            'branding',
            'organization_name',
            $data['organization_name'] ?? ''
        );

        AppSetting::setValue(
            'branding',
            'primary_color',
            $data['primary_color']
        );

        if ($request->boolean('remove_logo')) {
            $this->deleteBrandFile(
                AppSetting::getValue(
                    'branding',
                    'logo'
                )
            );

            AppSetting::setValue(
                'branding',
                'logo',
                null
            );
        }

        if ($request->hasFile('logo')) {
            $old = AppSetting::getValue(
                'branding',
                'logo'
            );

            $path = $request->file('logo')->store(
                'branding',
                'public'
            );

            AppSetting::setValue(
                'branding',
                'logo',
                $path
            );

            if ($old && $old !== $path) {
                $this->deleteBrandFile($old);
            }
        }

        if ($request->boolean('remove_favicon')) {
            $this->deleteBrandFile(
                AppSetting::getValue(
                    'branding',
                    'favicon'
                )
            );

            AppSetting::setValue(
                'branding',
                'favicon',
                null
            );
        }

        if ($request->hasFile('favicon')) {
            $old = AppSetting::getValue(
                'branding',
                'favicon'
            );

            $path = $request
                ->file('favicon')
                ->store(
                    'branding',
                    'public'
                );

            AppSetting::setValue(
                'branding',
                'favicon',
                $path
            );

            if ($old && $old !== $path) {
                $this->deleteBrandFile($old);
            }
        }

        return back()->with(
            'success',
            'Identidade visual atualizada.'
        );
    }

    private function deleteBrandFile(
        ?string $path
    ): void {
        if (! $path) {
            return;
        }

        if (str_starts_with(
            $path,
            'branding/'
        )) {
            Storage::disk('public')->delete(
                $path
            );
        }
    }

    public function agenda(
        ConfirmationPolicyService $policy
    ) {
        $professionals = DB::table(
            'professionals'
        )
            ->orderBy('name')
            ->get([
                'id',
                'name',
            ]);

        $services = DB::table(
            'services'
        )
            ->orderBy('name')
            ->get([
                'id',
                'name',
            ]);

        $voiceSystem = [
            'enabled' => (bool) config(
                'services.voice.enabled',
                false
            ),
            'provider' => (string) config(
                'services.voice.provider',
                'twilio'
            ),
            'configured' =>
                filled(
                    config(
                        'services.voice.twilio.account_sid'
                    )
                )
                && filled(
                    config(
                        'services.voice.twilio.auth_token'
                    )
                )
                && filled(
                    config(
                        'services.voice.twilio.from'
                    )
                ),
            'webhook_validation' => (bool) config(
                'services.voice.twilio.validate_webhooks',
                true
            ),
        ];

        return view(
            'enfas.admin.agenda-settings',
            [
                'slot' => AppSetting::getValue(
                    'agenda',
                    'default_slot',
                    30
                ),
                'start' => AppSetting::getValue(
                    'agenda',
                    'day_start',
                    '07:00'
                ),
                'end' => AppSetting::getValue(
                    'agenda',
                    'day_end',
                    '20:00'
                ),
                'confirmation' =>
                    AppSetting::getValue(
                        'agenda',
                        'confirmation_enabled',
                        true
                    ),
                'reminders' =>
                    AppSetting::getValue(
                        'agenda',
                        'reminders_enabled',
                        true
                    ),
                'confirmationPolicy' =>
                    $policy->summary(),
                'professionals' =>
                    $professionals,
                'services' =>
                    $services,
                'voiceSystem' =>
                    $voiceSystem,
            ]
        );
    }

    public function saveAgenda(
        Request $request
    ) {
        $data = $request->validate([
            'default_slot' => [
                'required',
                'integer',
                'min:5',
                'max:240',
            ],
            'day_start' => [
                'required',
                'date_format:H:i',
            ],
            'day_end' => [
                'required',
                'date_format:H:i',
                'after:day_start',
            ],
            'voice_escalation_minutes' => [
                'required',
                'integer',
                'min:15',
                'max:1440',
            ],
            'voice_no_whatsapp_minutes' => [
                'required',
                'integer',
                'min:30',
                'max:2880',
            ],
            'voice_retry_minutes' => [
                'required',
                'integer',
                'min:5',
                'max:1440',
            ],
            'voice_max_attempts' => [
                'required',
                'integer',
                'min:1',
                'max:10',
            ],
            'voice_allowed_start' => [
                'required',
                'date_format:H:i',
            ],
            'voice_allowed_end' => [
                'required',
                'date_format:H:i',
                'after:voice_allowed_start',
            ],
            'voice_professional_ids' => [
                'nullable',
                'array',
            ],
            'voice_professional_ids.*' => [
                'integer',
                'exists:professionals,id',
            ],
            'voice_service_ids' => [
                'nullable',
                'array',
            ],
            'voice_service_ids.*' => [
                'integer',
                'exists:services,id',
            ],
        ]);

        AppSetting::setValue(
            'agenda',
            'default_slot',
            $data['default_slot'],
            'integer'
        );

        AppSetting::setValue(
            'agenda',
            'day_start',
            $data['day_start']
        );

        AppSetting::setValue(
            'agenda',
            'day_end',
            $data['day_end']
        );

        AppSetting::setValue(
            'agenda',
            'confirmation_enabled',
            $request->boolean(
                'confirmation_enabled'
            ),
            'boolean'
        );

        AppSetting::setValue(
            'agenda',
            'reminders_enabled',
            $request->boolean(
                'reminders_enabled'
            ),
            'boolean'
        );

        AppSetting::setValue(
            'confirmation',
            'voice_fallback_enabled',
            $request->boolean(
                'voice_fallback_enabled'
            ),
            'boolean'
        );

        AppSetting::setValue(
            'confirmation',
            'human_fallback_enabled',
            $request->boolean(
                'human_fallback_enabled'
            ),
            'boolean'
        );

        AppSetting::setValue(
            'confirmation',
            'respect_contact_consent',
            $request->boolean(
                'respect_contact_consent'
            ),
            'boolean'
        );

        foreach ([
            'voice_escalation_minutes',
            'voice_no_whatsapp_minutes',
            'voice_retry_minutes',
            'voice_max_attempts',
        ] as $key) {
            AppSetting::setValue(
                'confirmation',
                $key,
                (int) $data[$key],
                'integer'
            );
        }

        AppSetting::setValue(
            'confirmation',
            'voice_allowed_start',
            $data['voice_allowed_start']
        );

        AppSetting::setValue(
            'confirmation',
            'voice_allowed_end',
            $data['voice_allowed_end']
        );

        AppSetting::setValue(
            'confirmation',
            'voice_professional_ids',
            implode(
                ',',
                collect(
                    $data['voice_professional_ids']
                    ?? []
                )
                    ->map(
                        fn ($id) => (int) $id
                    )
                    ->filter()
                    ->unique()
                    ->values()
                    ->all()
            )
        );

        AppSetting::setValue(
            'confirmation',
            'voice_service_ids',
            implode(
                ',',
                collect(
                    $data['voice_service_ids']
                    ?? []
                )
                    ->map(
                        fn ($id) => (int) $id
                    )
                    ->filter()
                    ->unique()
                    ->values()
                    ->all()
            )
        );

        return back()->with(
            'success',
            'Preferências da agenda e régua de confirmação atualizadas.'
        );
    }
}
