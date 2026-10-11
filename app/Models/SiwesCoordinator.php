<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property User $user
 * @property Department $department
 * @property Faculty|null $faculty
 */
class SiwesCoordinator extends Model
{
    use SoftDeletes;

    public const string STATUS_ACTIVE = 'active';

    public const string STATUS_SUSPENDED = 'suspended';

    public const array RANKS = Supervisor::RANKS;

    protected $fillable = [
        'user_id',
        'faculty_id',
        'department_id',
        'staff_no',
        'rank',
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

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
