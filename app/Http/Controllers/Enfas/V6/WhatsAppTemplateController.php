<?php

namespace App\Http\Controllers\Enfas\V6;

use App\Http\Controllers\Controller;
use App\Models\WaTemplate;
use App\Services\Enfas\MetaWhatsAppService;
use App\Services\Enfas\WhatsAppTemplateGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WhatsAppTemplateController extends Controller
{
    public function index(WhatsAppTemplateGuard $guard)
    {
        $rows = WaTemplate::query()
            ->where(fn ($q) => $q->whereNull('name')->orWhere('name', 'not like', 'sample\_%'))
            ->orderByRaw("CASE status
                WHEN 'APPROVED' THEN 1
                WHEN 'PENDING' THEN 2
                WHEN 'LOCAL' THEN 3
                WHEN 'REJECTED' THEN 4
                ELSE 5 END")
            ->orderBy('name')
            ->get();

        return view('enfas.v6.whatsapp.templates', [
            'rows' => $rows,
            'presets' => $guard->presets(),
            'apiCreationEnabled' => config('enfas_runtime.meta_template_create_api', true),
        ]);
    }

    public function store(
        Request $request,
        MetaWhatsAppService $meta,
        WhatsAppTemplateGuard $guard
    ) {
        $data = $request->validate([
            'name' => ['required','regex:/^[a-z0-9_]+$/','max:160'],
            'purpose' => ['required','in:confirmation,reminder,reschedule,cancellation,post_service,general'],
            'category' => ['required','in:UTILITY,MARKETING,AUTHENTICATION'],
            'language' => ['required','string','max:20'],
            'header_text' => ['nullable','string','max:255'],
            'body' => ['required','string','max:1024'],
            'footer' => ['nullable','string','max:255'],
            'variable_keys_text' => ['nullable','string','max:1000'],
            'sample_values_text' => ['nullable','string','max:1000'],
            'buttons_text' => ['nullable','string','max:1000'],
            'action' => ['nullable','in:draft,send'],
        ]);

        $language = str_replace('-', '_', $data['language']);

        if (WaTemplate::where('name', $data['name'])->where('language', $language)->exists()) {
            return back()->withInput()->withErrors([
                'name' => 'Já existe um modelo com esse nome e idioma.',
            ]);
        }

        $keys = $this->csv($data['variable_keys_text'] ?? '');
        $samples = $this->csv($data['sample_values_text'] ?? '');
        $guard->validateContent($data['body'], $keys, $samples);

        $template = WaTemplate::create([
            'name' => $data['name'],
            'purpose' => $data['purpose'],
            'category' => $data['category'],
            'language' => $language,
            'status' => 'LOCAL',
            'header_text' => $data['header_text'] ?? null,
            'body' => $data['body'],
            'footer' => $data['footer'] ?? null,
            'buttons' => $this->buttons($data['buttons_text'] ?? ''),
            'variable_keys' => $keys,
            'sample_values' => $samples,
            'created_by' => auth()->id(),
        ]);

        if (($data['action'] ?? 'send') === 'draft') {
            return redirect(url('/whatsapp/templates'))->with('success', 'Rascunho salvo.');
        }

        try {
            $response = $meta->createTemplate($template);
            $template->refresh();

            if ($template->status === 'LOCAL') {
                $template->update([
                    'status' => $response['status'] ?? 'PENDING',
                    'meta_template_id' => $response['id'] ?? $template->meta_template_id,
                ]);
            }

            return redirect(url('/whatsapp/templates'))->with(
                'success',
                'Modelo salvo e enviado para aprovação na Meta.'
            );
        } catch (\Throwable $e) {
            report($e);

            return redirect(url('/whatsapp/templates'))
                ->with('meta_error', $guard->friendlyMetaError($e))
                ->with('failed_template_id', $template->id);
        }
    }

    public function submit(
        WaTemplate $template,
        MetaWhatsAppService $meta,
        WhatsAppTemplateGuard $guard
    ) {
        $guard->validateContent(
            $template->body,
            $template->variable_keys ?? [],
            $template->sample_values ?? []
        );

        try {
            $response = $meta->createTemplate($template);
            $template->refresh();

            if ($template->status === 'LOCAL') {
                $template->update([
                    'status' => $response['status'] ?? 'PENDING',
                    'meta_template_id' => $response['id'] ?? $template->meta_template_id,
                ]);
            }

            return back()->with('success', 'Modelo enviado para aprovação.');
        } catch (\Throwable $e) {
            report($e);
            return back()->with('meta_error', $guard->friendlyMetaError($e));
        }
    }

    public function sync(MetaWhatsAppService $meta)
    {
        try {
            $count = $meta->syncTemplates();
            return back()->with('success', $count.' modelo(s) sincronizado(s) com a Meta.');
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['template' => $e->getMessage()]);
        }
    }

    public function delete(WaTemplate $template, MetaWhatsAppService $meta)
    {
        try {
            if ($template->status === 'LOCAL' && blank($template->meta_template_id)) {
                $template->delete();
                return back()->with('success', 'Rascunho excluído.');
            }

            $meta->deleteTemplate($template);
            return back()->with('success', 'Modelo excluído.');
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['template' => $e->getMessage()]);
        }
    }

    private function csv(string $value): array
    {
        return collect(explode(',', $value))
            ->map(fn ($v) => trim($v))
            ->filter()
            ->values()
            ->all();
    }

    private function buttons(string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $value))
            ->map(function ($line) {
                $parts = array_map('trim', explode('|', $line, 2));
                if (blank($parts[0] ?? null)) return null;

                $action = $parts[1] ?? 'none';
                if (! in_array($action, ['confirm','cancel','reschedule','none'], true)) {
                    $action = 'none';
                }

                return [
                    'text' => Str::limit($parts[0], 20, ''),
                    'action' => $action,
                ];
            })
            ->filter()
            ->take(3)
            ->values()
            ->all();
    }
}
