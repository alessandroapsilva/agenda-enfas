<?php

namespace App\Http\Controllers\Enfas\V6;

use App\Http\Controllers\Controller;
use App\Models\WaAutomation;
use App\Models\WaTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WhatsAppAutomationController extends Controller
{
    public function index(Request $request)
    {
        return view('enfas.v6.whatsapp.automations', [
            'rows' => WaAutomation::with('template')
                ->orderBy('name')
                ->get(),

            'templates' => WaTemplate::where('status', 'APPROVED')
                ->where('is_active', true)
                ->whereNull('archived_at')
                ->orderBy('name')
                ->get(),

            'services' => DB::table('services')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),

            'editing' => $request->integer('edit')
                ? WaAutomation::find(
                    $request->integer('edit')
                )
                : null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        if ($request->boolean('is_active')) {
            $this->ensureNoEquivalentActiveRule(
                $data
            );
        }

        WaAutomation::create([
            'name' => $data['name'],
            'trigger_event' => $data['trigger_event'],
            'offset_minutes' => $this->offset(
                $data['trigger_event'],
                (int) ($data['offset_minutes'] ?? 0)
            ),
            'template_id' => $data['template_id'],
            'wa_template_id' => $data['template_id'],
            'service_id' => $data['service_id'] ?? null,
            'send_once' => true,
            'retry_count' => $data['retry_count'] ?? 3,
            'is_active' => $request->boolean('is_active'),
            'last_run_at' => now(),
        ]);

        return back()->with(
            'success',
            'Automação criada.'
        );
    }

    public function update(
        Request $request,
        WaAutomation $automation
    ) {
        $data = $this->validated($request);

        if ($request->boolean('is_active')) {
            $this->ensureNoEquivalentActiveRule(
                $data,
                $automation->id
            );
        }

        $automation->update([
            'name' => $data['name'],
            'trigger_event' => $data['trigger_event'],
            'offset_minutes' => $this->offset(
                $data['trigger_event'],
                (int) ($data['offset_minutes'] ?? 0)
            ),
            'template_id' => $data['template_id'],
            'wa_template_id' => $data['template_id'],
            'service_id' => $data['service_id'] ?? null,
            'retry_count' => $data['retry_count'] ?? 3,
            'is_active' => $request->boolean('is_active'),
            'last_run_at' => now(),
        ]);

        return redirect('/whatsapp/automacoes')
            ->with(
                'success',
                'Automação atualizada.'
            );
    }

    public function toggle(WaAutomation $automation)
    {
        $activating = ! $automation->is_active;

        if ($activating) {
            $this->ensureNoEquivalentActiveRule([
                'trigger_event' =>
                    $automation->trigger_event,
                'offset_minutes' =>
                    $automation->offset_minutes,
                'template_id' =>
                    $automation->template_id,
                'service_id' =>
                    $automation->service_id,
            ], $automation->id);
        }

        $automation->update([
            'is_active' => $activating,
            'last_run_at' => now(),
        ]);

        return back()->with(
            'success',
            $automation->is_active
                ? 'Automação ativada.'
                : 'Automação pausada.'
        );
    }

    public function delete(WaAutomation $automation)
    {
        $automation->delete();

        return back()->with(
            'success',
            'Automação excluída.'
        );
    }

    private function ensureNoEquivalentActiveRule(
        array $data,
        ?int $ignoreId = null
    ): void {
        $template = WaTemplate::find(
            (int) $data['template_id']
        );

        if (! $template) {
            return;
        }

        $offset = $this->offset(
            (string) $data['trigger_event'],
            (int) ($data['offset_minutes'] ?? 0)
        );

        $query = WaAutomation::query()
            ->join(
                'wa_templates as wt',
                'wt.id',
                '=',
                'wa_automations.template_id'
            )
            ->where(
                'wa_automations.is_active',
                true
            )
            ->where(
                'wa_automations.trigger_event',
                $data['trigger_event']
            )
            ->where(
                'wa_automations.offset_minutes',
                $offset
            )
            ->where(
                'wt.purpose',
                $template->purpose
            );

        $serviceId = $data['service_id']
            ?? null;

        if ($serviceId) {
            $query->where(
                'wa_automations.service_id',
                $serviceId
            );
        } else {
            $query->whereNull(
                'wa_automations.service_id'
            );
        }

        if ($ignoreId) {
            $query->where(
                'wa_automations.id',
                '!=',
                $ignoreId
            );
        }

        $duplicate = $query->first([
            'wa_automations.id',
            'wa_automations.name',
        ]);

        if (! $duplicate) {
            return;
        }

        throw ValidationException::withMessages([
            'trigger_event' =>
                'Já existe uma automação ativa equivalente: '
                .$duplicate->name
                .' (#'
                .$duplicate->id
                .'). Pause ou edite a regra existente.',
        ]);
    }

    private function validated(
        Request $request
    ): array {
        return $request->validate([
            'name' => 'required|string|max:160',
            'trigger_event' =>
                'required|in:appointment_created,appointment_before,appointment_confirmed,appointment_rescheduled,appointment_cancelled,appointment_completed,appointment_return_due',
            'offset_minutes' =>
                'nullable|integer|min:0|max:525600',
            'template_id' => [
                'required',
                Rule::exists('wa_templates', 'id')
                    ->where(fn ($query) => $query
                        ->where('status', 'APPROVED')
                        ->where('is_active', true)
                        ->whereNull('archived_at')),
            ],
            'service_id' =>
                'nullable|exists:services,id',
            'retry_count' =>
                'nullable|integer|min:0|max:5',
        ]);
    }

    private function offset(
        string $trigger,
        int $offset
    ): int {
        return $trigger === 'appointment_before'
            ? max(0, $offset)
            : 0;
    }
}
