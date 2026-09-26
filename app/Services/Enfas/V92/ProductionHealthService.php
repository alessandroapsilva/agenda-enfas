<?php

namespace App\Services\Enfas\V92;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Throwable;

class ProductionHealthService
{
    public function checks(bool $deep=true): array
    {
        $checks=[];

        $this->add(
            $checks,
            'environment',
            app()->environment('production'),
            'Ambiente production'
        );

        $this->add(
            $checks,
            'debug',
            config('app.debug') === false,
            'APP_DEBUG desativado'
        );

        $this->add(
            $checks,
            'https_url',
            str_starts_with((string)config('app.url'),'https://'),
            'APP_URL utiliza HTTPS'
        );

        $this->add(
            $checks,
            'timezone',
            config('app.timezone') === 'America/Sao_Paulo',
            'Timezone America/Sao_Paulo'
        );

        $this->add(
            $checks,
            'app_key',
            filled(config('app.key')),
            'APP_KEY configurada'
        );

        try {
            DB::select('SELECT 1');
            $this->add($checks,'database',true,'Banco de dados respondeu');
        } catch (Throwable $e) {
            $this->add($checks,'database',false,'Banco de dados indisponível');
        }

        try {
            $key='enfas.health.'.uniqid();
            Cache::put($key,'ok',30);
            $ok=Cache::get($key)==='ok';
            Cache::forget($key);
            $this->add($checks,'cache',$ok,'Cache leitura/escrita');
        } catch (Throwable $e) {
            $this->add($checks,'cache',false,'Cache indisponível');
        }

        try {
            $file='health/'.uniqid().'.txt';
            Storage::disk('local')->put($file,'ok');
            $ok=Storage::disk('local')->exists($file);
            Storage::disk('local')->delete($file);
            $this->add($checks,'storage',$ok,'Storage leitura/escrita');
        } catch (Throwable $e) {
            $this->add($checks,'storage',false,'Storage indisponível');
        }

        $this->add(
            $checks,
            'storage_link',
            is_link(public_path('storage')),
            'public/storage ligado'
        );

        foreach([
            'dashboard',
            'agenda.index',
            'agenda.best-slots',
            'appointments.index',
            'appointments.recurring.store',
            'patients.index',
            'patients.show',
            'professionals.index',
            'professionals.availability',
            'services.index',
            'waitlist.index',
            'users.index',
            'enfas.whatsapp',
            'enfas.whatsapp.thread.send',
            'enfas.v6.templates',
            'enfas.v6.automations',
        ] as $route) {
            $this->add(
                $checks,
                'route_'.$route,
                Route::has($route),
                'Rota '.$route
            );
        }

        foreach([
            'appointments',
            'patients',
            'professionals',
            'services',
            'professional_availabilities',
            'professional_blocks',
            'appointment_series',
            'slot_reservations',
            'wa_messages',
            'wa_conversations',
            'wa_webhook_events',
            'waitlist_entries',
        ] as $table) {
            $this->add(
                $checks,
                'table_'.$table,
                Schema::hasTable($table),
                'Tabela '.$table
            );
        }

        $scheduler=$this->heartbeat('enfas.scheduler.heartbeat');
        $queue=$this->heartbeat('enfas.queue.heartbeat');

        $this->add(
            $checks,
            'scheduler_heartbeat',
            $scheduler['fresh'],
            'Scheduler heartbeat',
            $scheduler['value']
        );

        $this->add(
            $checks,
            'queue_heartbeat',
            $queue['fresh'],
            'Queue heartbeat',
            $queue['value']
        );

        if ($deep) {
            $this->add(
                $checks,
                'queue_service',
                $this->processOk(['systemctl','is-active','enfas-agenda-queue.service']),
                'Serviço da fila ativo'
            );

            $cron=$this->processOutput(['crontab','-l']);

            $this->add(
                $checks,
                'scheduler_cron',
                str_contains($cron,'schedule:run'),
                'Cron schedule:run presente'
            );

            $backup=$this->latestBackup();

            $this->add(
                $checks,
                'backup',
                $backup['ok'],
                'Backup recente',
                $backup['label']
            );
        }

        $free=@disk_free_space(base_path());
        $total=@disk_total_space(base_path());
        $percent=($free && $total) ? ($free/$total)*100 : 0;

        $this->add(
            $checks,
            'disk',
            $percent >= 10,
            'Espaço livre em disco',
            number_format($percent,1,',','.').'% livre'
        );

        $meta=$this->metaConfigured();

        $this->add(
            $checks,
            'meta',
            $meta,
            'Integração Meta configurada'
        );

        return $checks;
    }

    public function summary(bool $deep=true): array
    {
        $checks=$this->checks($deep);
        $failed=collect($checks)->where('ok',false)->count();

        return [
            'ok'=>$failed===0,
            'failed'=>$failed,
            'total'=>count($checks),
            'checks'=>$checks,
        ];
    }

    private function heartbeat(string $key): array
    {
        $value=Cache::get($key);

        if (! $value) {
            return ['fresh'=>false,'value'=>'Sem heartbeat'];
        }

        try {
            $time=Carbon::parse($value);

            return [
                'fresh'=>$time->gt(now()->subMinutes(5)),
                'value'=>$time->diffForHumans(),
            ];
        } catch (Throwable $e) {
            return ['fresh'=>false,'value'=>'Heartbeat inválido'];
        }
    }

    private function processOk(array $command): bool
    {
        try {
            $p=new Process($command);
            $p->setTimeout(8);
            $p->run();

            return $p->isSuccessful();
        } catch (Throwable $e) {
            return false;
        }
    }

    private function processOutput(array $command): string
    {
        try {
            $p=new Process($command);
            $p->setTimeout(8);
            $p->run();

            return $p->getOutput().$p->getErrorOutput();
        } catch (Throwable $e) {
            return '';
        }
    }

    private function latestBackup(): array
    {
        $files=glob('/home/agendaenfas/backups/agenda-db-*.sql.gz') ?: [];

        if ($files===[]) {
            return ['ok'=>false,'label'=>'Nenhum backup diário'];
        }

        usort(
            $files,
            fn($a,$b)=>filemtime($b)<=>filemtime($a)
        );

        $latest=$files[0];
        $age=time()-filemtime($latest);

        return [
            'ok'=>$age <= 36*3600,
            'label'=>basename($latest)
                .' · '.Carbon::createFromTimestamp(filemtime($latest))->diffForHumans(),
        ];
    }

    private function metaConfigured(): bool
    {
        try {
            $model='App\\Models\\MetaIntegration';

            if (! class_exists($model)) {
                return false;
            }

            $integration=$model::query()->first();

            return $integration
                && filled($integration->waba_id)
                && filled($integration->phone_number_id)
                && filled($integration->access_token);
        } catch (Throwable $e) {
            return false;
        }
    }

    private function add(
        array &$checks,
        string $key,
        bool $ok,
        string $label,
        ?string $detail=null
    ): void {
        $checks[]=[
            'key'=>$key,
            'ok'=>$ok,
            'label'=>$label,
            'detail'=>$detail,
        ];
    }
}
