<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = ['group_name','key_name','value','value_type'];

    public static function getValue(string $group, string $key, mixed $default = null): mixed
    {
        $row = static::where('group_name',$group)->where('key_name',$key)->first();
        if (! $row) return $default;

        return match ($row->value_type) {
            'boolean' => $row->value === '1',
            'integer' => (int) $row->value,
            default => $row->value,
        };
    }

    public static function setValue(string $group,string $key,mixed $value,string $type='string'): void
    {
        if ($type === 'boolean') $value = $value ? '1' : '0';

        static::updateOrCreate(
            ['group_name'=>$group,'key_name'=>$key],
            ['value'=>$value === null ? null : (string)$value,'value_type'=>$type]
        );
    }
}
