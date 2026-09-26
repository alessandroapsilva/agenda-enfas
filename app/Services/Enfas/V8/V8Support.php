<?php
namespace App\Services\Enfas\V8;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class V8Support
{
    public function cols(string $table,array $data): array
    {
        $cols=array_flip(Schema::getColumnListing($table));
        return array_filter($data,fn($v,$k)=>isset($cols[$k]),ARRAY_FILTER_USE_BOTH);
    }

    public function active(string $table,bool $value): array
    {
        $d=[];
        if(Schema::hasColumn($table,'is_active'))$d['is_active']=$value;
        if(Schema::hasColumn($table,'active'))$d['active']=$value;
        return $d;
    }

    public function audit(string $module,string $action,?int $id=null,?string $description=null): void
    {
        if(!Schema::hasTable('audit_logs'))return;
        DB::table('audit_logs')->insert([
            'user_id'=>auth()->id(),'module'=>$module,'action'=>$action,
            'entity_id'=>$id,'description'=>$description,'ip'=>request()?->ip(),
            'user_agent'=>request()?->userAgent(),'created_at'=>now(),'updated_at'=>now()
        ]);
    }
}
