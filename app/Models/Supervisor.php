<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property User $user
 * @property int $active_assignments_count
 * @property int $assignments_count
 */
class Supervisor extends Model
{
    use SoftDeletes;

    public const string STATUS_ACTIVE = 'active';

    public const string STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'user_id',
        'staff_no',
        'organization',
        'department',
        'status',
        'metadata',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(SupervisorStudentAssignment::class);
    }

    public function activeAssignments(): HasMany
    {
        return $this->assignments()->whereNull('revoked_at');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }
}
