<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'is_public',
        'description',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'value' => 'array',
            'is_public' => 'boolean',
        ];
    }

    public static function value(string $key, mixed $default = null): mixed
    {
        return self::query()->where('key', $key)->value('value') ?? $default;
    }
}
