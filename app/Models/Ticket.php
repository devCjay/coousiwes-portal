<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property Student|null $student
 * @property Carbon|null $expires_at
 */
class Ticket extends Model
{
    use SoftDeletes;

    public const string STATUS_UNUSED = 'unused';

    public const string STATUS_GENERATED = 'unused';

    public const string STATUS_ASSIGNED = 'unused';

    public const string STATUS_PAID = 'used';

    public const string STATUS_USED = 'used';

    public const string STATUS_EXPIRED = 'unused';

    public const string STATUS_REVOKED = 'unused';

    protected $fillable = [
        'student_id',
        'generated_by',
        'serial_number',
        'pin',
        'code_hash',
        'amount',
        'currency',
        'status',
        'assigned_at',
        'paid_at',
        'used_at',
        'expires_at',
        'metadata',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'assigned_at' => 'datetime',
            'paid_at' => 'datetime',
            'used_at' => 'datetime',
            'expires_at' => 'datetime',
            'metadata' => 'array',
            'pin' => 'encrypted',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function placement(): HasOne
    {
        return $this->hasOne(StudentPlacement::class);
    }

    public function isPayable(): bool
    {
        return in_array($this->status, self::payableStatuses(), true)
            && (! $this->expires_at || $this->expires_at->isFuture());
    }

    /**
     * @return list<string>
     */
    public static function unusedStatuses(): array
    {
        return [self::STATUS_UNUSED, 'generated', 'assigned', 'expired', 'revoked'];
    }

    /**
     * @return list<string>
     */
    public static function usedStatuses(): array
    {
        return [self::STATUS_USED, 'paid'];
    }

    /**
     * @return list<string>
     */
    public static function payableStatuses(): array
    {
        return [self::STATUS_UNUSED, 'generated', 'assigned'];
    }

    public function displayStatus(): string
    {
        return in_array($this->status, self::usedStatuses(), true) ? 'Used' : 'Unused';
    }
}
