<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Supervisor;
use App\Models\SupervisorStudentAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;

class SupervisorAssignmentService
{
    public function assign(Supervisor $supervisor, Student $student, ?Authenticatable $assignedBy = null): SupervisorStudentAssignment
    {
        return DB::transaction(function () use ($supervisor, $student, $assignedBy): SupervisorStudentAssignment {
            if ($supervisor->status !== Supervisor::STATUS_ACTIVE) {
                throw new \RuntimeException('Supervisor is not active.');
            }

            if ($student->supervisorAssignments()->whereNull('revoked_at')->exists()) {
                throw new \RuntimeException('Student already has an active supervisor assignment.');
            }

            return SupervisorStudentAssignment::query()->create([
                'supervisor_id' => $supervisor->id,
                'student_id' => $student->id,
                'assigned_by' => $assignedBy instanceof User ? $assignedBy->id : null,
                'assigned_at' => now(),
            ]);
        });
    }

    public function revoke(SupervisorStudentAssignment $assignment, ?Authenticatable $revokedBy = null, ?string $reason = null): SupervisorStudentAssignment
    {
        $assignment->update([
            'revoked_by' => $revokedBy instanceof User ? $revokedBy->id : null,
            'revoked_at' => now(),
            'revocation_reason' => $reason,
        ]);

        return $assignment->refresh();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{assigned: int, skipped: int}
     */
    public function bulkAssign(Supervisor $supervisor, array $filters, ?Authenticatable $assignedBy = null): array
    {
        $assigned = 0;
        $skipped = 0;

        $this->filteredStudents($filters)->each(function (Student $student) use ($supervisor, $assignedBy, &$assigned, &$skipped): void {
            try {
                $this->assign($supervisor, $student, $assignedBy);
                $assigned++;
            } catch (\RuntimeException) {
                $skipped++;
            }
        });

        return ['assigned' => $assigned, 'skipped' => $skipped];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Student>
     */
    private function filteredStudents(array $filters): Builder
    {
        return Student::query()
            ->when($filters['faculty_id'] ?? null, fn (Builder $query, mixed $value) => $query->where('faculty_id', $value))
            ->when($filters['department_id'] ?? null, fn (Builder $query, mixed $value) => $query->where('department_id', $value))
            ->when($filters['academic_level_id'] ?? null, fn (Builder $query, mixed $value) => $query->where('academic_level_id', $value))
            ->when($filters['academic_session_id'] ?? null, fn (Builder $query, mixed $value) => $query->where('academic_session_id', $value));
    }
}
