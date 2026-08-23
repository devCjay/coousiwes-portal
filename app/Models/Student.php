<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property User $user
 * @property Faculty $faculty
 * @property Department $department
 * @property Course $course
 * @property AcademicLevel $academicLevel
 * @property AcademicSession $academicSession
 * @property SupervisorStudentAssignment|null $activeSupervisorAssignment
 */
class Student extends Model
{
    use SoftDeletes;

    public const string STATUS_INACTIVE = 'inactive';

    public const string STATUS_ACTIVE = 'active';

    public const string STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'user_id',
        'matric_no',
        'faculty_id',
        'department_id',
        'course_id',
        'academic_level_id',
        'academic_session_id',
        'activation_status',
        'gender',
        'date_of_birth',
        'address',
        'metadata',
    ];

    public const array PROFILE_METADATA_FIELDS = [
        'nationality',
        'state',
        'lga',
        'bank_name',
        'account_number',
        'sort_code',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
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

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function academicLevel(): BelongsTo
    {
        return $this->belongsTo(AcademicLevel::class);
    }

    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function supervisorAssignments(): HasMany
    {
        return $this->hasMany(SupervisorStudentAssignment::class);
    }

    public function activeSupervisorAssignment(): HasOne
    {
        return $this->hasOne(SupervisorStudentAssignment::class)->whereNull('revoked_at')->latestOfMany();
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    public function placement(): HasOne
    {
        return $this->hasOne(StudentPlacement::class);
    }

    public function profileCompletionPercent(): int
    {
        $metadata = $this->metadata ?? [];
        $fields = collect([
            $this->user?->email,
            $this->user?->phone,
            $this->gender,
            $this->date_of_birth,
            $metadata['nationality'] ?? null,
            $this->address,
            $metadata['state'] ?? null,
            $metadata['lga'] ?? null,
            $this->faculty_id,
            $this->department_id,
            $metadata['bank_name'] ?? null,
            $metadata['account_number'] ?? null,
            $metadata['sort_code'] ?? null,
        ]);

        return (int) round(($fields->filter(fn (mixed $value): bool => filled($value))->count() / $fields->count()) * 100);
    }

    public function hasCompleteProfile(): bool
    {
        return $this->profileCompletionPercent() >= 100;
    }
}
