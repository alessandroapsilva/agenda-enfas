<?php

namespace App\Console\Commands;

use App\Services\Enfas\V92\ProductionHealthService;
use Illuminate\Console\Command;

class EnfasProductionCheck extends Command
{
    protected $signature='enfas:production-check {--no-deep}';
    protected $description='Go/No-Go de produção do ENFAS Agenda';

    public function handle(ProductionHealthService $health): int
    {
        $summary=$health->summary(! $this->option('no-deep'));

        $this->newLine();
        $this->line('ENFAS Agenda - Production Readiness');
        $this->line(str_repeat('=',48));

        foreach($summary['checks'] as $check) {
            $mark=$check['ok']?'OK':'FALHA';
            $text=str_pad($mark,7).' '.$check['label'];

            if ($check['detail']) {
                $text.=' — '.$check['detail'];
            }

            $check['ok']
                ?$this->info($text)
                :$this->error($text);
        }

        $this->newLine();

        if ($summary['ok']) {
            $this->info('GO_PRODUCAO=true');
            return self::SUCCESS;
        }

        $this->error('GO_PRODUCAO=false');
        $this->error('Falhas críticas: '.$summary['failed']);

        return self::FAILURE;
    }
}
