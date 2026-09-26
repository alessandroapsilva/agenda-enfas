<?php

namespace App\Http\Controllers\Enfas;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;
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

    public function agenda()
    {
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

        return back()->with(
            'success',
            'Preferências da agenda atualizadas.'
        );
    }
}
