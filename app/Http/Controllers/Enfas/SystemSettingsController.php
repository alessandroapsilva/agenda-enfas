<?php

namespace App\Http\Controllers\Enfas;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Services\Enfas\BusinessHoursService;
use App\Services\Enfas\EmailChannelService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SystemSettingsController extends Controller
{
    public function index(
        BusinessHoursService $businessHours,
        EmailChannelService $email
    ) {
        return view('enfas.admin.system-settings', [
            'hours' => $businessHours->schedule(),
            'emailSystem' => [
                'configured' => $email->configured(),
                'mailer' => (string) config('mail.default', 'log'),
                'from' => (string) config('mail.from.address', ''),
            ],
            'microsoftSystem' => [
                'enabled' => (bool) config('services.microsoft.enabled', false),
                'configured' => filled(config('services.microsoft.client_id'))
                    && filled(config('services.microsoft.client_secret'))
                    && filled(config('services.microsoft.tenant')),
                'tenant' => (string) config('services.microsoft.tenant', 'organizations'),
                'auto_provision' => (bool) config('services.microsoft.auto_provision', false),
                'redirect_uri' => (string) (
                    config('services.microsoft.redirect_uri')
                    ?: route('microsoft.callback')
                ),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $hours = $request->input('hours', []);
        $normalized = [];

        foreach (range(0, 6) as $day) {
            $row = is_array($hours[$day] ?? null) ? $hours[$day] : [];
            $enabled = filter_var($row['enabled'] ?? false, FILTER_VALIDATE_BOOL);
            $start = trim((string) ($row['start'] ?? ''));
            $end = trim((string) ($row['end'] ?? ''));
            $breakStart = trim((string) ($row['break_start'] ?? ''));
            $breakEnd = trim((string) ($row['break_end'] ?? ''));

            if ($enabled) {
                foreach (['start' => $start, 'end' => $end] as $field => $value) {
                    if (! preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value)) {
                        throw ValidationException::withMessages([
                            "hours.$day.$field" => 'Informe um horário válido.',
                        ]);
                    }
                }

                if ($this->minutes($end) <= $this->minutes($start)) {
                    throw ValidationException::withMessages([
                        "hours.$day.end" => 'O encerramento deve ser depois da abertura.',
                    ]);
                }

                if (($breakStart === '') xor ($breakEnd === '')) {
                    throw ValidationException::withMessages([
                        "hours.$day.break_start" => 'Preencha início e fim do intervalo ou deixe ambos vazios.',
                    ]);
                }

                if ($breakStart !== '') {
                    if (
                        ! preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $breakStart)
                        || ! preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $breakEnd)
                        || $this->minutes($breakEnd) <= $this->minutes($breakStart)
                        || $this->minutes($breakStart) < $this->minutes($start)
                        || $this->minutes($breakEnd) > $this->minutes($end)
                    ) {
                        throw ValidationException::withMessages([
                            "hours.$day.break_start" => 'O intervalo precisa estar dentro do horário de funcionamento.',
                        ]);
                    }
                }
            }

            $normalized[(string) $day] = [
                'enabled' => $enabled,
                'start' => $start !== '' ? $start : '08:00',
                'end' => $end !== '' ? $end : '18:00',
                'break_start' => $breakStart !== '' ? $breakStart : null,
                'break_end' => $breakEnd !== '' ? $breakEnd : null,
            ];
        }

        AppSetting::setValue(
            'system',
            'business_hours',
            json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        return back()->with('success', 'Configurações do sistema atualizadas.');
    }

    private function minutes(string $time): int
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));
        return ($hour * 60) + $minute;
    }
}
