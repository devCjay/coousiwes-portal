<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class AcademicSession extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'starts_on',
        'ends_on',
        'is_active',
        'description',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function activate(): void
    {
        DB::transaction(function (): void {
            self::query()->whereKeyNot($this->getKey())->update(['is_active' => false]);

            $this->forceFill(['is_active' => true])->save();
        });
    }

    public static function active(): ?self
    {
        return self::query()->where('is_active', true)->first();
    }
}
