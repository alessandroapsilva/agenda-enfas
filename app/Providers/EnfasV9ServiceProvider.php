<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class EnfasV9ServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $menu=collect(config('adminlte.menu',[]))
            ->reject(function($item){
                if (! is_array($item)) {
                    return false;
                }

                $url=$item['url']??'';

                return is_string($url)
                    && str_starts_with($url,'premium');
            })
            ->values()
            ->all();

        $urls=collect($menu)
            ->filter(fn($i)=>is_array($i))
            ->pluck('url')
            ->filter()
            ->all();

        $add=function(array $item) use (&$menu,&$urls){
            if (! in_array($item['url'],$urls,true)) {
                $menu[]=$item;
                $urls[]=$item['url'];
            }
        };

        $menu[]=['header'=>'GESTÃO'];

        $add([
            'text'=>'Profissionais',
            'url'=>'profissionais',
            'icon'=>'bi bi-person-badge',
        ]);

        $add([
            'text'=>'Pacientes',
            'url'=>'pacientes',
            'icon'=>'bi bi-people',
        ]);

        $add([
            'text'=>'Serviços',
            'url'=>'servicos',
            'icon'=>'bi bi-grid',
        ]);

        $add([
            'text'=>'Unidades e locais',
            'url'=>'locais',
            'icon'=>'bi bi-geo-alt',
        ]);

        $menu[]=['header'=>'WHATSAPP'];

        $add([
            'text'=>'Modelos',
            'url'=>'whatsapp/templates',
            'icon'=>'bi bi-chat-square-text',
        ]);

        $add([
            'text'=>'Biblioteca de mídia',
            'url'=>'whatsapp/midia',
            'icon'=>'bi bi-images',
        ]);

        $add([
            'text'=>'Automações',
            'url'=>'whatsapp/automacoes',
            'icon'=>'bi bi-lightning-charge',
        ]);

        config(['adminlte.menu'=>$menu]);
    }
}
