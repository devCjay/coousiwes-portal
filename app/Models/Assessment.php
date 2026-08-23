<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Student $student
 * @property Supervisor $supervisor
 * @property SupervisorStudentAssignment $assignment
 * @property Carbon|null $submitted_at
 */
class Assessment extends Model
{
    public const string STATUS_SUBMITTED = 'submitted';

    public const string STATUS_REVIEWED = 'reviewed';

    protected $fillable = [
        'supervisor_id',
        'student_id',
        'supervisor_student_assignment_id',
        'total_score',
        'max_score',
        'status',
        'feedback',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'metadata',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'total_score' => 'integer',
            'max_score' => 'integer',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Supervisor::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(SupervisorStudentAssignment::class, 'supervisor_student_assignment_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(AssessmentScore::class);
    }
}
