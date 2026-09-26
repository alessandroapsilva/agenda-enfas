<?php

namespace App\Http\Controllers;

use App\Models\Professional;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProfessionalController extends Controller
{
    public function index()
    {
        $professionals = Professional::query()
            ->with(['services', 'availabilities'])
            ->orderBy('name')
            ->get();

        $services = Service::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $blocks = DB::table('professional_blocks')
            ->where('ends_at', '>=', now())
            ->orderBy('starts_at')
            ->get()
            ->groupBy('professional_id');

        return view('professionals.index', compact('professionals', 'services', 'blocks'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $professional = Professional::create([
            'name' => trim($data['name']),
            'specialty' => $data['specialty'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'work_start' => $data['work_start'],
            'work_end' => $data['work_end'],
            'slot_interval' => $data['slot_interval'],
            'color' => $data['color'],
            'active_days' => $data['active_days'] ?? [1,2,3,4,5],
            'is_active' => true,
            'whatsapp_notifications_enabled' => (bool) ($data['whatsapp_notifications_enabled'] ?? false),
        ]);

        $professional->services()->sync($data['services'] ?? []);

        return back()->with('success', 'Profissional cadastrado com sucesso.');
    }

    public function update(Request $request, Professional $professional)
    {
        $data = $this->validated($request, $professional);

        $professional->update([
            'name' => trim($data['name']),
            'specialty' => $data['specialty'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'work_start' => $data['work_start'],
            'work_end' => $data['work_end'],
            'slot_interval' => $data['slot_interval'],
            'color' => $data['color'],
            'active_days' => $data['active_days'] ?? [],
            'whatsapp_notifications_enabled' => (bool) ($data['whatsapp_notifications_enabled'] ?? false),
        ]);

        $professional->services()->sync($data['services'] ?? []);

        return back()->with('success', 'Profissional atualizado.');
    }

    public function saveAvailability(Request $request, Professional $professional)
    {
        $rows = $request->validate([
            'availability' => ['nullable', 'array'],
            'availability.*.enabled' => ['nullable', 'boolean'],
            'availability.*.start_time' => ['nullable', 'date_format:H:i'],
            'availability.*.end_time' => ['nullable', 'date_format:H:i'],
            'availability.*.break_start' => ['nullable', 'date_format:H:i'],
            'availability.*.break_end' => ['nullable', 'date_format:H:i'],
        ])['availability'] ?? [];

        DB::transaction(function () use ($professional, $rows) {
            DB::table('professional_availabilities')
                ->where('professional_id', $professional->id)
                ->delete();

            foreach ($rows as $day => $row) {
                if (empty($row['enabled'])) {
                    continue;
                }

                DB::table('professional_availabilities')->insert([
                    'professional_id' => $professional->id,
                    'day_of_week' => (int) $day,
                    'start_time' => $row['start_time'] ?: $professional->work_start,
                    'end_time' => $row['end_time'] ?: $professional->work_end,
                    'break_start' => $row['break_start'] ?: null,
                    'break_end' => $row['break_end'] ?: null,
                    'location_id' => null,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        return back()->with('success', 'Disponibilidade semanal atualizada.');
    }

    public function addBlock(Request $request, Professional $professional)
    {
        $data = $request->validate([
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'type' => ['required', Rule::in(['block','absence','vacation','meeting'])],
            'title' => ['nullable', 'string', 'max:160'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'is_all_day' => ['nullable', 'boolean'],
        ]);

        DB::table('professional_blocks')->insert([
            'professional_id' => $professional->id,
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'type' => $data['type'],
            'title' => $data['title'] ?? null,
            'reason' => $data['reason'] ?? null,
            'is_all_day' => (bool) ($data['is_all_day'] ?? false),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Bloqueio de agenda criado.');
    }

    public function deleteBlock(Professional $professional, int $block)
    {
        DB::table('professional_blocks')
            ->where('id', $block)
            ->where('professional_id', $professional->id)
            ->delete();

        return back()->with('success', 'Bloqueio removido.');
    }

    private function validated(Request $request, ?Professional $professional = null): array
    {
        return $request->validate([
            'name' => ['required','string','max:160'],
            'specialty' => ['nullable','string','max:160'],
            'phone' => ['nullable','string','max:30'],
            'email' => ['nullable','email','max:190'],
            'work_start' => ['required','date_format:H:i'],
            'work_end' => ['required','date_format:H:i','after:work_start'],
            'slot_interval' => ['required','integer','min:5','max:240'],
            'color' => ['required','regex:/^#[0-9A-Fa-f]{6}$/'],
            'active_days' => ['nullable','array'],
            'active_days.*' => ['integer','between:0,6'],
            'services' => ['nullable','array'],
            'services.*' => ['integer','exists:services,id'],
            'whatsapp_notifications_enabled' => ['nullable','boolean'],
        ]);
    }
}
