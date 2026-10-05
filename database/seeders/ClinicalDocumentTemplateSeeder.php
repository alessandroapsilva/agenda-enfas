<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClinicalDocumentTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Receita simples',
                'document_type' => 'prescription',
                'title' => 'Receita',
                'body_template' => "Paciente: {{patient.name}}\nRGEA: {{patient.rgea}}\n\nPrescrição:\n\n1. \n\nOrientações:\n\n\n{{professional.name}}\n{{professional.registry}}",
            ],
            [
                'name' => 'Atestado',
                'document_type' => 'certificate',
                'title' => 'Atestado',
                'body_template' => "Atesto, para os devidos fins, que {{patient.name}} esteve em atendimento nesta data.\n\nObservações:\n\n\n{{professional.name}}\n{{professional.registry}}",
            ],
            [
                'name' => 'Declaração de comparecimento',
                'document_type' => 'declaration',
                'title' => 'Declaração de comparecimento',
                'body_template' => "Declaro, para os devidos fins, que {{patient.name}}, RGEA {{patient.rgea}}, compareceu para atendimento em {{appointment.date}}.\n\n{{professional.name}}\n{{professional.registry}}",
            ],
            [
                'name' => 'Encaminhamento',
                'document_type' => 'referral',
                'title' => 'Encaminhamento',
                'body_template' => "Paciente: {{patient.name}}\nRGEA: {{patient.rgea}}\n\nEncaminho para:\n\nMotivo / resumo:\n\n\n{{professional.name}}\n{{professional.registry}}",
            ],
            [
                'name' => 'Solicitação de exame',
                'document_type' => 'exam_request',
                'title' => 'Solicitação de exame',
                'body_template' => "Paciente: {{patient.name}}\nRGEA: {{patient.rgea}}\n\nExame(s) solicitado(s):\n\n\nIndicação / observações:\n\n\n{{professional.name}}\n{{professional.registry}}",
            ],
            [
                'name' => 'Relatório de atendimento',
                'document_type' => 'report',
                'title' => 'Relatório de atendimento',
                'body_template' => "Paciente: {{patient.name}}\nRGEA: {{patient.rgea}}\nData: {{appointment.date}}\n\nRelatório:\n\n\n{{professional.name}}\n{{professional.registry}}",
            ],
        ];

        foreach ($templates as $index => $template) {
            DB::table('clinical_document_templates')->updateOrInsert(
                [
                    'name' => $template['name'],
                    'document_type' => $template['document_type'],
                ],
                [
                    'title' => $template['title'],
                    'body_template' => $template['body_template'],
                    'requires_signature' => true,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
