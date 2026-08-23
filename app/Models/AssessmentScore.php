<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentScore extends Model
{
    protected $fillable = [
        'assessment_id',
        'assessment_rubric_item_id',
        'score',
        'max_score',
        'comment',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'max_score' => 'integer',
        ];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function rubricItem(): BelongsTo
    {
        return $this->belongsTo(AssessmentRubricItem::class, 'assessment_rubric_item_id');
    }
}
