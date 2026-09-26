<?php

namespace App\Jobs\Enfas;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class QueueHeartbeatJob implements ShouldQueue
{
    use Queueable;

    public int $tries=1;
    public int $timeout=20;

    public function handle(): void
    {
        Cache::put(
            'enfas.queue.heartbeat',
            now()->toIso8601String(),
            now()->addMinutes(15)
        );
    }
}
