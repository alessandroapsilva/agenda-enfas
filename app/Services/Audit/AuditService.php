<?php

namespace App\Services\Audit;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditService
{
    public function record(
        string $event,
        ?string $entityType=null,
        ?string $entityId=null,
        array $context=[],
        ?Request $request=null,
    ): void {
        DB::table('audit_logs')->insert([
            'id'=>(string)str()->ulid(),
            'actor_id'=>$request?->user()?->getAuthIdentifier(),
            'event'=>$event,
            'entity_type'=>$entityType,
            'entity_id'=>$entityId,
            'ip_address'=>$request?->ip(),
            'user_agent'=>mb_substr((string)$request?->userAgent(),0,500),
            'context'=>json_encode($context,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            'occurred_at'=>now(),
            'created_at'=>now(),
            'updated_at'=>now(),
        ]);
    }
}
