<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class EnfasV8ServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $menu=config('adminlte.menu',[]);
        if(!collect($menu)->contains(fn($i)=>is_array($i)&&($i['text']??null)==='Central Premium')){
            $menu[]=['header'=>'ENFAS PREMIUM'];
            $menu[]=['text'=>'Central Premium','url'=>'premium','icon'=>'bi bi-stars'];
            $menu[]=['text'=>'Profissionais Premium','url'=>'premium/profissionais','icon'=>'bi bi-person-badge'];
            $menu[]=['text'=>'Pacientes Premium','url'=>'premium/pacientes','icon'=>'bi bi-people'];
            $menu[]=['text'=>'Serviços Premium','url'=>'premium/servicos','icon'=>'bi bi-grid'];
            $menu[]=['text'=>'Unidades e locais','url'=>'premium/unidades','icon'=>'bi bi-geo-alt'];
            $menu[]=['text'=>'Disponibilidade','url'=>'premium/disponibilidade','icon'=>'bi bi-calendar2-check'];
            $menu[]=['text'=>'Modelos WhatsApp','url'=>'premium/whatsapp/modelos','icon'=>'bi bi-whatsapp'];
            $menu[]=['text'=>'Biblioteca de mídia','url'=>'premium/whatsapp/midia','icon'=>'bi bi-images'];
            $menu[]=['text'=>'Automações WhatsApp','url'=>'whatsapp/automacoes','icon'=>'bi bi-lightning-charge'];
            $menu[]=['text'=>'Relatórios Premium','url'=>'premium/relatorios','icon'=>'bi bi-bar-chart'];
            $menu[]=['text'=>'Auditoria','url'=>'premium/auditoria','icon'=>'bi bi-shield-check'];
            $menu[]=['text'=>'Configurações','url'=>'premium/configuracoes','icon'=>'bi bi-sliders'];
            config(['adminlte.menu'=>$menu]);
        }
    }
}
