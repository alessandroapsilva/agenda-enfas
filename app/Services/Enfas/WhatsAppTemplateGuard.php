<?php

namespace App\Services\Enfas;

use Illuminate\Validation\ValidationException;

class WhatsAppTemplateGuard
{
    public const ALLOWED = [
        'paciente_nome','servico','data','hora',
        'profissional','codigo_agendamento','local',
    ];

    public function presets(): array
    {
        return [
            'confirmation' => [
                'label' => 'Confirmação',
                'name' => 'confirmacao_agendamento',
                'purpose' => 'confirmation',
                'category' => 'UTILITY',
                'language' => 'pt_BR',
                'header' => 'Confirmação de agendamento',
                'body' => "Olá {{1}}!\n\nSeu agendamento de {{2}} foi registrado com sucesso. O atendimento está marcado para o dia {{3}}, às {{4}}, com {{5}}.\n\nPara organizarmos seu atendimento da melhor forma, confirme sua presença utilizando uma das opções abaixo. Se precisar alterar o horário, escolha a opção de reagendamento.\n\nEnfermagem Alessandro Silva",
                'footer' => 'ENFAS Agenda',
                'keys' => ['paciente_nome','servico','data','hora','profissional'],
                'samples' => ['João da Silva','Consulta','31/08/2026','14:30','Dra. Maria'],
                'buttons' => [['Confirmar','confirm'],['Reagendar','reschedule'],['Cancelar','cancel']],
            ],
            'reminder' => [
                'label' => 'Lembrete',
                'name' => 'lembrete_agendamento',
                'purpose' => 'reminder',
                'category' => 'UTILITY',
                'language' => 'pt_BR',
                'header' => 'Lembrete de atendimento',
                'body' => "Olá {{1}}!\n\nEste é um lembrete do seu atendimento de {{2}}, agendado para o dia {{3}}, às {{4}}, com {{5}}.\n\nPedimos que confirme sua presença para que possamos organizar o atendimento da melhor forma possível. Caso precise alterar o horário, utilize uma das opções abaixo.\n\nEnfermagem Alessandro Silva",
                'footer' => 'ENFAS Agenda',
                'keys' => ['paciente_nome','servico','data','hora','profissional'],
                'samples' => ['João da Silva','Consulta','31/08/2026','14:30','Dra. Maria'],
                'buttons' => [['Confirmar','confirm'],['Reagendar','reschedule'],['Cancelar','cancel']],
            ],
            'reschedule' => [
                'label' => 'Reagendamento',
                'name' => 'aviso_reagendamento',
                'purpose' => 'reschedule',
                'category' => 'UTILITY',
                'language' => 'pt_BR',
                'header' => 'Agendamento alterado',
                'body' => "Olá {{1}}!\n\nSeu atendimento de {{2}} foi reagendado com sucesso. A nova data é {{3}}, às {{4}}, com {{5}}.\n\nConfira as informações acima e confirme se poderá comparecer no novo horário. Caso precise de outra alteração, utilize uma das opções disponíveis.\n\nEnfermagem Alessandro Silva",
                'footer' => 'ENFAS Agenda',
                'keys' => ['paciente_nome','servico','data','hora','profissional'],
                'samples' => ['João da Silva','Consulta','01/09/2026','15:00','Dra. Maria'],
                'buttons' => [['Confirmar','confirm'],['Reagendar','reschedule'],['Cancelar','cancel']],
            ],
            'cancellation' => [
                'label' => 'Cancelamento',
                'name' => 'aviso_cancelamento',
                'purpose' => 'cancellation',
                'category' => 'UTILITY',
                'language' => 'pt_BR',
                'header' => 'Agendamento cancelado',
                'body' => "Olá {{1}}!\n\nInformamos que o seu agendamento de {{2}}, previsto para o dia {{3}}, às {{4}}, foi cancelado.\n\nSe desejar realizar um novo agendamento, entre em contato com nossa equipe para verificarmos os próximos horários disponíveis.\n\nEnfermagem Alessandro Silva",
                'footer' => 'ENFAS Agenda',
                'keys' => ['paciente_nome','servico','data','hora'],
                'samples' => ['João da Silva','Consulta','31/08/2026','14:30'],
                'buttons' => [],
            ],
            'post_service' => [
                'label' => 'Pós-atendimento',
                'name' => 'pos_atendimento',
                'purpose' => 'post_service',
                'category' => 'UTILITY',
                'language' => 'pt_BR',
                'header' => 'Obrigado pela confiança',
                'body' => "Olá {{1}}!\n\nEsperamos que seu atendimento de {{2}} tenha ocorrido bem. Agradecemos por escolher a Enfermagem Alessandro Silva.\n\nSe precisar de novas orientações ou desejar outro agendamento, nossa equipe permanece à disposição. Seu código de atendimento é {{3}}.\n\nEnfermagem Alessandro Silva",
                'footer' => 'ENFAS Agenda',
                'keys' => ['paciente_nome','servico','codigo_agendamento'],
                'samples' => ['João da Silva','Consulta','AG-ABC123'],
                'buttons' => [],
            ],
        ];
    }

    public function validateContent(string $body, array $keys, array $samples): array
    {
        preg_match_all('/\{\{(\d+)\}\}/u', $body, $m);
        $idx = array_values(array_unique(array_map('intval', $m[1] ?? [])));
        sort($idx);

        if ($idx !== [] && $idx !== range(1, count($idx))) {
            throw ValidationException::withMessages([
                'body' => 'Use variáveis sequenciais: {{1}}, {{2}}, {{3}}...',
            ]);
        }

        if (count($idx) !== count($keys)) {
            throw ValidationException::withMessages([
                'variable_keys_text' => 'Informe uma chave para cada variável da mensagem.',
            ]);
        }

        if (count($idx) !== count($samples)) {
            throw ValidationException::withMessages([
                'sample_values_text' => 'Informe um exemplo para cada variável da mensagem.',
            ]);
        }

        foreach ($keys as $key) {
            if (! in_array($key, self::ALLOWED, true)) {
                throw ValidationException::withMessages([
                    'variable_keys_text' => 'Variável não reconhecida: '.$key,
                ]);
            }
        }

        $trim = trim($body);
        if (preg_match('/^\{\{\d+\}\}/u', $trim) || preg_match('/\{\{\d+\}\}$/u', $trim)) {
            throw ValidationException::withMessages([
                'body' => 'A mensagem não pode começar nem terminar diretamente com uma variável.',
            ]);
        }

        $fixed = preg_replace('/\{\{\d+\}\}/u', ' ', $body);
        preg_match_all('/[\p{L}\p{N}]+/u', $fixed, $words);
        $wordCount = count($words[0] ?? []);
        $paramCount = count($idx);
        $minimum = $paramCount > 0 ? max(12, $paramCount * 7) : 0;

        /*
         * Regra preventiva: apenas avisa, nunca bloqueia.
         * A Meta faz a validacao real da proporcao no POST.
         */
        $ratioWarning = $paramCount > 0 && $wordCount < $minimum;


        return [
            'parameters' => $paramCount,
            'fixed_words' => $wordCount,
            'minimum' => $minimum,
            'ratio_warning' => $ratioWarning ?? false,
            'safe' => true,
        ];
    }

    public function friendlyMetaError(\Throwable $e): string
    {
        $m = $e->getMessage();

        if (str_contains($m, '2388293')) {
            return 'Mensagem muito curta para a quantidade de variáveis. '
                .'Aumente o texto fixo ou remova alguns campos. O rascunho foi mantido.';
        }

        if (str_contains($m, '2388299')) {
            return 'A Meta não aceita variável solta no começo ou no fim da mensagem. '
                .'Adicione texto antes e depois da variável. O rascunho foi mantido.';
        }

        if (str_contains($m, '2388339')) {
            return 'A Meta recusou a conta usada para criar modelos. Confira a WABA configurada.';
        }

        if (str_contains($m, 'code 190') || str_contains($m, 'código 190')) {
            return 'O token da Meta expirou ou foi invalidado. Atualize o token da integração.';
        }

        return $m;
    }
}
