<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;
use Throwable;

class EnfasBackup extends Command
{
    protected $signature='enfas:backup {--retention=14}';
    protected $description='Backup diário do banco e arquivos públicos do ENFAS Agenda';

    public function handle(): int
    {
        $dir='/home/agendaenfas/backups';

        if (! is_dir($dir)) {
            mkdir($dir,0750,true);
        }

        $stamp=now()->format('Ymd-His');
        $dbFile=$dir.'/agenda-db-'.$stamp.'.sql.gz';
        $mediaFile=$dir.'/agenda-media-'.$stamp.'.tar.gz';

        $connection=config('database.default');
        $db=config('database.connections.'.$connection);

        if (($db['driver']??null)!=='mysql') {
            $this->error('Backup automatizado configurado apenas para MySQL/MariaDB.');
            return self::FAILURE;
        }

        $host=$db['host']??'127.0.0.1';
        $port=(string)($db['port']??3306);
        $user=$db['username']??'';
        $password=$db['password']??'';
        $database=$db['database']??'';

        $cmd=sprintf(
            'mysqldump --host=%s --port=%s --user=%s --no-tablespaces --single-transaction --routines --triggers %s | gzip -9 > %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($user),
            escapeshellarg($database),
            escapeshellarg($dbFile)
        );

        try {
            $process=Process::fromShellCommandline(
                $cmd,
                base_path(),
                ['MYSQL_PWD'=>$password]
            );
            $process->setTimeout(900);
            $process->run();

            if (! $process->isSuccessful() || ! is_file($dbFile) || filesize($dbFile)===0) {
                @unlink($dbFile);
                $this->error('Falha no backup do banco.');
                return self::FAILURE;
            }

            $public=storage_path('app/public');

            if (is_dir($public)) {
                $tar=Process::fromShellCommandline(
                    'tar -czf '.escapeshellarg($mediaFile).' -C '
                    .escapeshellarg(dirname($public)).' '
                    .escapeshellarg(basename($public))
                );
                $tar->setTimeout(900);
                $tar->run();

                if (! $tar->isSuccessful()) {
                    @unlink($mediaFile);
                    $this->warn('Banco salvo, mas o backup de mídia falhou.');
                }
            }

            $retention=max(3,(int)$this->option('retention'));
            $limit=now()->subDays($retention)->timestamp;

            foreach(glob($dir.'/agenda-*') ?: [] as $file) {
                if (is_file($file) && filemtime($file)<$limit) {
                    @unlink($file);
                }
            }

            Cache::put(
                'enfas.backup.last_success',
                now()->toIso8601String(),
                now()->addDays(30)
            );

            $this->info('Backup concluído: '.basename($dbFile));

            return self::SUCCESS;
        } catch (Throwable $e) {
            report($e);
            $this->error('Backup falhou: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
