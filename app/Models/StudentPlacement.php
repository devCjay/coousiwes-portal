<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentPlacement extends Model
{
    protected $fillable = [
        'student_id',
        'ticket_id',
        'academic_level_id',
        'academic_session_id',
        'siwes_year',
        'attachment_period',
        'company_name',
        'company_address',
        'company_state',
        'company_lga',
        'company_supervisor_phone',
        'metadata',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'siwes_year' => 'integer',
            'metadata' => 'array',
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

    public function academicLevel(): BelongsTo
    {
        return $this->belongsTo(AcademicLevel::class);
    }

    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class);
    }
}
