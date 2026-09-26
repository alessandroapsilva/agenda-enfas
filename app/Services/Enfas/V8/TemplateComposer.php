<?php

namespace App\Services\Enfas\V8;

use App\Models\WaTemplate;

class TemplateComposer
{
    public function __construct(
        private TemplateCategoryRecovery $categoryRecovery
    ) {
    }

    public function analyze(string $body): array
    {
        preg_match_all('/\{\{(\d+)\}\}/u', $body, $matches);

        $indices = array_values(
            array_unique(array_map('intval', $matches[1] ?? []))
        );
        sort($indices);

        $fixed = preg_replace('/\{\{\d+\}\}/u', ' ', $body);
        preg_match_all('/[\p{L}\p{N}]+/u', $fixed, $words);

        $params = count($indices);
        $fixedWords = count($words[0] ?? []);
        $recommended = $params > 0 ? max(55, $params * 11) : 0;

        return [
            'params' => $params,
            'words' => $fixedWords,
            'recommended' => $recommended,
        ];
    }

    public function normalize(WaTemplate $template): WaTemplate
    {
        $analysis = $this->analyze((string) $template->body);

        if ($analysis['params'] === 0
            || $analysis['words'] >= $analysis['recommended']) {
            return $template;
        }

        if (in_array($template->purpose, [
            'confirmation',
            'reminder',
            'reschedule',
            'cancellation',
            'post_service',
        ], true)) {
            $body = $this->categoryRecovery->strictUtilityBody(
                (string) $template->purpose,
                array_values($template->variable_keys ?? [])
            );

            if (mb_strlen($body) <= 1024) {
                $template->forceFill([
                    'body' => $body,
                    'category' => 'UTILITY',
                    'updated_by' => auth()->id(),
                    'version' => ((int) ($template->version ?? 1)) + 1,
                ])->save();
            }
        }

        return $template->fresh();
    }
}
