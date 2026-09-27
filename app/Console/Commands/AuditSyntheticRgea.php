<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditSyntheticRgea extends Command
{
    protected $signature = 'enfas:rgea-audit {--apply : Limpa apenas RGEAs que coincidem exatamente com o padrão sintético legado}';

    protected $description = 'Identifica RGEAs sintéticos antigos gerados pelo Agenda ENFAS.';

    public function handle(): int
    {
        $rows = DB::table('patients')
            ->whereNotNull('rgea_number')
            ->orderBy('id')
            ->get(['id','name','rgea_number'])
            ->filter(function ($patient) {
                $synthetic = 'RGEA-'.str_pad((string) $patient->id, 6, '0', STR_PAD_LEFT);

                return strtoupper(trim((string) $patient->rgea_number)) === $synthetic;
            })
            ->values();

        if ($rows->isEmpty()) {
            $this->info('Nenhum RGEA sintético legado identificado.');
            return self::SUCCESS;
        }

        $this->table(
            ['ID','Paciente','RGEA identificado'],
            $rows->map(fn ($row) => [$row->id,$row->name,$row->rgea_number])->all()
        );

        $this->warn($rows->count().' cadastro(s) coincidem exatamente com o padrão automático legado.');

        if (! $this->option('apply')) {
            $this->line('Nenhum dado foi alterado. Revise a lista acima.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($rows) {
            DB::table('patients')
                ->whereIn('id', $rows->pluck('id'))
                ->update([
                    'rgea_number' => null,
                    'updated_at' => now(),
                ]);
        });

        $this->info('RGEAs sintéticos limpos. Esses pacientes agora precisam receber o RGEA do sistema de origem.');

        return self::SUCCESS;
    }
}
