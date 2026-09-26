<?php

namespace App\Services\Enfas\V8;

use App\Models\WaTemplate;
use Illuminate\Support\Str;

class TemplateCategoryRecovery
{
    public function recover(WaTemplate $source): WaTemplate
    {
        $keys = array_values($source->variable_keys ?? []);
        $category = $source->purpose === 'general' ? 'MARKETING' : 'UTILITY';

        $name = Str::limit($source->name.'_r'.$source->id, 155, '');

        $existing = WaTemplate::query()
            ->where('name', $name)
            ->where('language', $source->language)
            ->first();

        if ($existing) {
            return $existing;
        }

        $copy = $source->replicate();

        $copy->forceFill([
            'name' => $name,
            'category' => $category,
            'status' => 'LOCAL',
            'meta_template_id' => null,
            'rejection_reason' => null,
            'last_error' => null,
            'is_active' => true,
            'archived_at' => null,
            'header_type' => $source->header_type ?: 'TEXT',
            'header_media_id' => $source->header_media_id,
            'header_text' => $this->headerFor((string) $source->purpose),
            'body' => $this->bodyFor((string) $source->purpose, $keys),
            'footer' => $source->footer ?: 'ENFAS Agenda',
            'version' => ((int) ($source->version ?? 1)) + 1,
            'updated_by' => auth()->id(),
        ])->save();

        $source->forceFill([
            'is_active' => false,
            'updated_by' => auth()->id(),
        ])->save();

        return $copy->fresh();
    }

    public function strictUtilityBody(string $purpose, array $keys): string
    {
        return $this->bodyFor($purpose, $keys);
    }

    private function headerFor(string $purpose): string
    {
        return match ($purpose) {
            'confirmation' => 'Confirmação de agendamento',
            'reminder' => 'Lembrete de agendamento',
            'reschedule' => 'Agendamento reagendado',
            'cancellation' => 'Agendamento cancelado',
            'post_service' => 'Registro de atendimento',
            default => 'Informação sobre seu atendimento',
        };
    }

    private function bodyFor(string $purpose, array $keys): string
    {
        $patient = $this->token($keys, 'paciente_nome');
        $service = $this->token($keys, 'servico');
        $date = $this->token($keys, 'data');
        $time = $this->token($keys, 'hora');
        $professional = $this->token($keys, 'profissional');
        $code = $this->token($keys, 'codigo_agendamento');
        $location = $this->token($keys, 'local');

        $hello = $patient ? "Olá {$patient}." : "Olá.";

        $details = [];
        if ($service) $details[] = "serviço {$service}";
        if ($date) $details[] = "data {$date}";
        if ($time) $details[] = "horário {$time}";
        if ($professional) $details[] = "profissional {$professional}";
        if ($location) $details[] = "local {$location}";
        if ($code) $details[] = "código {$code}";

        $detailText = $details !== []
            ? implode(', ', $details)
            : 'dados registrados no sistema';

        return trim(match ($purpose) {
            'confirmation' =>
                "{$hello} Confirmamos o agendamento que foi solicitado e registrado em nosso sistema. "
                ."Os dados atuais deste agendamento são: {$detailText}. "
                ."Confira atentamente estas informações e utilize as opções disponíveis somente para confirmar sua presença, solicitar reagendamento ou cancelar este horário. "
                ."Se algum dado estiver incorreto, entre em contato com a equipe responsável pelo atendimento. "
                ."Esta mensagem se refere exclusivamente ao agendamento informado acima.",

            'reminder' =>
                "{$hello} Este é um lembrete referente ao agendamento que já está registrado em nosso sistema. "
                ."Os dados atuais deste agendamento são: {$detailText}. "
                ."Confira atentamente estas informações e utilize as opções disponíveis somente para confirmar sua presença, solicitar reagendamento ou cancelar este horário. "
                ."Se algum dado estiver incorreto, entre em contato com a equipe responsável pelo atendimento. "
                ."Esta mensagem se refere exclusivamente ao agendamento informado acima.",

            'reschedule' =>
                "{$hello} O agendamento registrado em nosso sistema foi alterado e os dados abaixo substituem o horário informado anteriormente. "
                ."Os dados atualizados são: {$detailText}. "
                ."Confira atentamente a nova data, o novo horário e as demais informações do atendimento. "
                ."Utilize as opções disponíveis somente para confirmar sua presença, solicitar outro reagendamento ou cancelar este horário. "
                ."Esta mensagem se refere exclusivamente ao agendamento informado acima.",

            'cancellation' =>
                "{$hello} Informamos que o agendamento registrado em nosso sistema foi cancelado. "
                ."Os dados relacionados ao registro cancelado são: {$detailText}. "
                ."O horário anteriormente reservado não deve mais ser considerado válido. "
                ."Guarde esta mensagem como confirmação administrativa do cancelamento. "
                ."Se você não reconhece esta alteração ou precisa esclarecer alguma informação deste registro, entre em contato com a equipe responsável pelo atendimento.",

            'post_service' =>
                "{$hello} Esta mensagem registra uma atualização administrativa relacionada ao atendimento já realizado. "
                ."Os dados associados ao registro são: {$detailText}. "
                ."Confira as informações acima e guarde esta mensagem para referência administrativa. "
                ."Se algum dado estiver incorreto, entre em contato com a equipe responsável pelo atendimento. "
                ."Esta comunicação se refere exclusivamente ao atendimento já registrado em nosso sistema.",

            default =>
                "{$hello} Temos uma comunicação referente ao seu cadastro de atendimento. "
                ."As informações relacionadas são: {$detailText}. "
                ."Leia os dados acima com atenção e entre em contato com a equipe responsável se precisar de esclarecimento. "
                ."Esta comunicação está vinculada ao relacionamento com nosso serviço.",
        });
    }

    private function token(array $keys, string $wanted): ?string
    {
        $index = array_search($wanted, $keys, true);

        return $index === false ? null : '{{'.($index + 1).'}}';
    }
}
