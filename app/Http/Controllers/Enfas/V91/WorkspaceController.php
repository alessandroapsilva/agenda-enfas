<?php

namespace App\Http\Controllers\Enfas\V91;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WorkspaceController extends Controller
{
    public function index()
    {
        $dateColumn=$this->appointmentDateColumn();

        $metrics=[
            'appointments_today'=>0,
            'patients'=>$this->countActive('patients'),
            'professionals'=>$this->countActive('professionals'),
            'services'=>$this->countActive('services'),
            'templates_approved'=>0,
            'templates_pending'=>0,
            'messages_today'=>0,
        ];

        if ($dateColumn) {
            $metrics['appointments_today']=DB::table('appointments')
                ->whereDate($dateColumn,today())
                ->count();
        }

        if (Schema::hasTable('wa_templates')) {
            $approved=DB::table('wa_templates')
                ->where('status','APPROVED');

            $pending=DB::table('wa_templates')
                ->where('status','PENDING');

            if (Schema::hasColumn('wa_templates','is_active')) {
                $approved->where('is_active',true);
            }

            $metrics['templates_approved']=$approved->count();
            $metrics['templates_pending']=$pending->count();
        }

        if (Schema::hasTable('wa_messages')
            && Schema::hasColumn('wa_messages','created_at')) {
            $metrics['messages_today']=DB::table('wa_messages')
                ->whereDate('created_at',today())
                ->count();
        }

        $modules=[
            [
                'group'=>'Atendimento',
                'items'=>[
                    ['title'=>'Agenda','subtitle'=>'Visão diária e movimentação','url'=>'/agenda','icon'=>'bi-calendar3'],
                    ['title'=>'Agendamentos','subtitle'=>'Histórico e status','url'=>'/agendamentos','icon'=>'bi-calendar2-check'],
                    ['title'=>'Disponibilidade','subtitle'=>'Horários, bloqueios e férias','url'=>'/disponibilidade','icon'=>'bi-clock-history'],
                ],
            ],
            [
                'group'=>'Cadastros',
                'items'=>[
                    ['title'=>'Profissionais','subtitle'=>'Equipe e registros','url'=>'/profissionais','icon'=>'bi-person-badge'],
                    ['title'=>'Pacientes','subtitle'=>'Cadastro e contato','url'=>'/pacientes','icon'=>'bi-people'],
                    ['title'=>'Serviços','subtitle'=>'Duração, valor e preparo','url'=>'/servicos','icon'=>'bi-grid'],
                    ['title'=>'Unidades','subtitle'=>'Locais de atendimento','url'=>'/locais','icon'=>'bi-geo-alt'],
                    ['title'=>'Campos personalizados','subtitle'=>'Dados sob medida','url'=>'/campos-personalizados','icon'=>'bi-ui-checks-grid'],
                ],
            ],
            [
                'group'=>'WhatsApp',
                'items'=>[
                    ['title'=>'Central WhatsApp','subtitle'=>'Conexão e operação','url'=>'/whatsapp','icon'=>'bi-whatsapp'],
                    ['title'=>'Modelos','subtitle'=>'Criar, editar e revisar','url'=>'/whatsapp/templates','icon'=>'bi-chat-square-text'],
                    ['title'=>'Automações','subtitle'=>'Confirmações e lembretes','url'=>'/whatsapp/automacoes','icon'=>'bi-lightning-charge'],
                    ['title'=>'Mensagens','subtitle'=>'Fila e histórico','url'=>'/whatsapp/mensagens','icon'=>'bi-send'],
                    ['title'=>'Biblioteca de mídia','subtitle'=>'Imagens dos modelos','url'=>'/whatsapp/midia','icon'=>'bi-images'],
                ],
            ],
            [
                'group'=>'Gestão',
                'items'=>[
                    ['title'=>'Usuários','subtitle'=>'Acessos e segurança','url'=>'/usuarios','icon'=>'bi-person-lock'],
                    ['title'=>'Relatórios','subtitle'=>'Indicadores gerenciais','url'=>'/relatorios','icon'=>'bi-bar-chart'],
                    ['title'=>'Auditoria','subtitle'=>'Rastreabilidade','url'=>'/auditoria','icon'=>'bi-shield-check'],
                    ['title'=>'Alertas','subtitle'=>'Pendências e eventos','url'=>'/alertas','icon'=>'bi-bell'],
                    ['title'=>'Configurações','subtitle'=>'Preferências do sistema','url'=>'/configuracoes','icon'=>'bi-sliders'],
                ],
            ],
        ];

        $meta=[
            'configured'=>false,
            'phone'=>null,
            'quality'=>null,
        ];

        if (Schema::hasTable('meta_integrations')) {
            $integration=DB::table('meta_integrations')->first();

            if ($integration) {
                $meta['configured']=filled($integration->waba_id??null)
                    && filled($integration->phone_number_id??null);

                $meta['phone']=$integration->display_phone_number
                    ??$integration->phone
                    ??null;

                $meta['quality']=$integration->quality_rating
                    ??null;
            }
        }

        return view(
            'enfas.v91.workspace',
            compact('metrics','modules','meta')
        );
    }

    private function countActive(string $table): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        $query=DB::table($table);

        if (Schema::hasColumn($table,'is_active')) {
            $query->where('is_active',true);
        } elseif (Schema::hasColumn($table,'active')) {
            $query->where('active',true);
        }

        return $query->count();
    }

    private function appointmentDateColumn(): ?string
    {
        if (! Schema::hasTable('appointments')) {
            return null;
        }

        foreach([
            'starts_at',
            'scheduled_at',
            'start_at',
            'appointment_at',
            'date',
        ] as $column) {
            if (Schema::hasColumn('appointments',$column)) {
                return $column;
            }
        }

        return null;
    }
}
