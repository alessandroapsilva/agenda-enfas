<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClinicalProtocolSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $templateId = DB::table('clinical_protocol_templates')->updateOrInsert(
            ['name' => 'Checklist de segurança do atendimento'],
            [
                'description' => 'Checklist operacional para conferência dos principais registros antes do encerramento do atendimento.',
                'is_active' => true,
                'sort_order' => 10,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        $template = DB::table('clinical_protocol_templates')
            ->where('name', 'Checklist de segurança do atendimento')
            ->first();

        if (! $template) {
            return;
        }

        $items = [
            'Identificação do paciente conferida',
            'Alergias revisadas e registradas quando informadas',
            'Medicamentos em uso revisados e registrados quando informados',
            'Sinais vitais registrados quando aplicável ao atendimento',
            'Orientações e plano assistencial registrados',
        ];

        foreach ($items as $index => $label) {
            DB::table('clinical_protocol_template_items')->updateOrInsert(
                [
                    'template_id' => $template->id,
                    'label' => $label,
                ],
                [
                    'is_required' => true,
                    'sort_order' => $index + 1,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }
}
