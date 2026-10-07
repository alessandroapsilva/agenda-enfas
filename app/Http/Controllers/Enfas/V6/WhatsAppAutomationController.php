<?php

namespace App\Http\Controllers\Enfas\V6;

use App\Http\Controllers\Controller;
use App\Models\WaAutomation;
use App\Models\WaTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
        $automation->update([
            'is_active' => ! $automation->is_active,
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
