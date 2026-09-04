<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Supervisor;
use App\Models\SupervisorStudentAssignment;
use App\Models\User;
use App\Notifications\SupervisorAssignmentNotification;
use App\Notifications\SupervisorBulkAssignmentNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;

class SupervisorAssignmentService
{
    public function assign(Supervisor $supervisor, Student $student, ?Authenticatable $assignedBy = null, bool $notify = true): SupervisorStudentAssignment
    {
        [$assignment, $created] = DB::transaction(function () use ($supervisor, $student, $assignedBy): array {
            if ($supervisor->status !== Supervisor::STATUS_ACTIVE) {
                throw new \RuntimeException('Supervisor is not active.');
            }

            $student->supervisorAssignments()
                ->whereNull('revoked_at')
                ->where('supervisor_id', '!=', $supervisor->id)
                ->update([
                    'revoked_by' => $assignedBy instanceof User ? $assignedBy->id : null,
                    'revoked_at' => now(),
                    'revocation_reason' => 'Automatically revoked during supervisor reassignment.',
                ]);

            $existing = $student->supervisorAssignments()
                ->whereNull('revoked_at')
                ->where('supervisor_id', $supervisor->id)
                ->first();

            if ($existing) {
                return [$existing, false];
            }

            return [SupervisorStudentAssignment::query()->create([
                'supervisor_id' => $supervisor->id,
                'student_id' => $student->id,
                'assigned_by' => $assignedBy instanceof User ? $assignedBy->id : null,
                'assigned_at' => now(),
            ]), true];
        });

        if ($created && $notify) {
            $assignment->loadMissing(['supervisor.user', 'student.user', 'student.department', 'student.faculty', 'student.placement']);
            $assignment->supervisor->user?->notify(new SupervisorAssignmentNotification($assignment));
            $this->sendSingleWhatsApp($assignment);
        }

        return $assignment;
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
     * @return array{assigned: int, reassigned: int, skipped: int}
     */
    public function bulkAssign(Supervisor $supervisor, array $filters, ?Authenticatable $assignedBy = null): array
    {
        $assigned = 0;
        $reassigned = 0;
        $skipped = 0;

        $this->filteredStudents($filters)->each(function (Student $student) use ($supervisor, $assignedBy, &$assigned, &$reassigned, &$skipped): void {
            try {
                $hadOtherSupervisor = $student->supervisorAssignments()
                    ->whereNull('revoked_at')
                    ->where('supervisor_id', '!=', $supervisor->id)
                    ->exists();
                $hadSameSupervisor = $student->supervisorAssignments()
                    ->whereNull('revoked_at')
                    ->where('supervisor_id', $supervisor->id)
                    ->exists();
                $this->assign($supervisor, $student, $assignedBy, notify: false);
                match (true) {
                    $hadOtherSupervisor => $reassigned++,
                    $hadSameSupervisor => $skipped++,
                    default => $assigned++,
                };
            } catch (\RuntimeException) {
                $skipped++;
            }
        });

        $result = ['assigned' => $assigned, 'reassigned' => $reassigned, 'skipped' => $skipped];

        if (($assigned + $reassigned) > 0) {
            $supervisor->loadMissing('user');
            $supervisor->user?->notify(new SupervisorBulkAssignmentNotification($result, $filters));
            $this->sendBulkWhatsApp($supervisor, $result, $filters);
        }

        return $result;
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
            ->when($filters['academic_session_id'] ?? null, fn (Builder $query, mixed $value) => $query->where('academic_session_id', $value))
            ->when($filters['company_state'] ?? null, fn (Builder $query, mixed $value) => $query->whereHas('placement', fn (Builder $placementQuery) => $placementQuery->where('company_state', $value)))
            ->when($filters['company_lga'] ?? null, fn (Builder $query, mixed $value) => $query->whereHas('placement', fn (Builder $placementQuery) => $placementQuery->where('company_lga', $value)));
    }

    private function sendSingleWhatsApp(SupervisorStudentAssignment $assignment): void
    {
        $student = $assignment->student;
        $placement = $student->placement;

        app(WhatsAppNotificationService::class)->send(
            $assignment->supervisor->user?->phone,
            'supervisor_assignment',
            "A student has been assigned to you for SIWES supervision.\nStudent: {student_name}\nReg No: {matric_no}\nDepartment: {department}\nFaculty: {faculty}\nPlacement Location: {lga}, {state}",
            [
                'supervisor_name' => $assignment->supervisor->user?->name ?? 'N/A',
                'student_name' => $student->user?->name ?? 'N/A',
                'matric_no' => $student->matric_no,
                'department' => $student->department?->name ?? 'N/A',
                'faculty' => $student->faculty?->name ?? 'N/A',
                'state' => $placement?->company_state ?? 'N/A',
                'lga' => $placement?->company_lga ?? 'N/A',
                'dashboard_url' => route('supervisor.dashboard'),
            ],
        );
    }

    /**
     * @param  array{assigned: int, reassigned: int, skipped: int}  $result
     * @param  array<string, mixed>  $filters
     */
    private function sendBulkWhatsApp(Supervisor $supervisor, array $result, array $filters): void
    {
        app(WhatsAppNotificationService::class)->send(
            $supervisor->user?->phone,
            'supervisor_bulk_assignment',
            "Bulk SIWES supervisor assignment has been completed.\nTotal Students Assigned: {total_assigned}\nFaculty: {faculty}\nDepartment: {department}\nPlacement State: {state}\nPlacement LGA: {lga}",
            [
                'supervisor_name' => $supervisor->user?->name ?? 'N/A',
                'total_assigned' => $result['assigned'] + $result['reassigned'],
                'assigned_count' => $result['assigned'],
                'reassigned_count' => $result['reassigned'],
                'skipped_count' => $result['skipped'],
                'faculty' => $this->filterLabel(\App\Models\Faculty::class, $filters['faculty_id'] ?? null),
                'department' => $this->filterLabel(\App\Models\Department::class, $filters['department_id'] ?? null),
                'state' => $filters['company_state'] ?? 'Any',
                'lga' => $filters['company_lga'] ?? 'Any',
                'dashboard_url' => route('supervisor.dashboard'),
            ],
        );
    }

    /**
     * @param  class-string  $model
     */
    private function filterLabel(string $model, mixed $id): string
    {
        if (! $id) {
            return 'Any';
        }

        return (string) ($model::query()->find($id)?->name ?? 'Any');
    }
}
