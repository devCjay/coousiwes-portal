<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicLevel extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'level',
        'is_active',
        'description',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
