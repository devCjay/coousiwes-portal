<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentRubricItem extends Model
{
    protected $fillable = [
        'name',
        'description',
        'max_score',
        'weight',
        'sort_order',
        'is_active',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'max_score' => 'integer',
            'weight' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scores(): HasMany
    {
        return $this->hasMany(AssessmentScore::class);
    }
}
