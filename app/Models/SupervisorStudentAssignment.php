<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property Supervisor $supervisor
 * @property Student $student
 * @property Carbon $assigned_at
 * @property Carbon|null $revoked_at
 */
class SupervisorStudentAssignment extends Model
{
    protected $fillable = [
        'supervisor_id',
        'student_id',
        'assigned_by',
        'revoked_by',
        'assigned_at',
        'revoked_at',
        'revocation_reason',
        'metadata',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'revoked_at' => 'datetime',
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

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }

    public function assessment(): HasOne
    {
        return $this->hasOne(Assessment::class);
    }
}
