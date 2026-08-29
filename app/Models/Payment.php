<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Student $student
 * @property Ticket|null $ticket
 */
class Payment extends Model
{
    public const string STATUS_PENDING = 'pending';

    public const string STATUS_SUCCESSFUL = 'successful';

    public const string STATUS_FAILED = 'failed';

    public const string STATUS_ABANDONED = 'abandoned';

    public const string PURPOSE_ACTIVATION_TICKET = 'activation_ticket';

    public const string PURPOSE_WORKSHOP_FEE = 'workshop_fee';

    protected $fillable = [
        'student_id',
        'ticket_id',
        'purpose',
        'provider',
        'reference',
        'amount',
        'currency',
        'status',
        'checkout_url',
        'provider_status',
        'webhook_event',
        'webhook_event_id',
        'payload',
        'verified_at',
        'paid_at',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'payload' => 'array',
            'verified_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function isWorkshopFee(): bool
    {
        return $this->purpose === self::PURPOSE_WORKSHOP_FEE;
    }
}
