<?php

namespace App\Notifications;

use App\Models\AcademicLevel;
use App\Models\AcademicSession;
use App\Models\Department;
use App\Models\Faculty;
use App\Support\EmailTemplate;
use App\Support\MailConfiguration;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupervisorBulkAssignmentNotification extends Notification
{
    use Queueable;

    /**
     * @param  array{assigned: int, reassigned: int, skipped: int}  $result
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        private readonly array $result,
        private readonly array $filters,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        MailConfiguration::apply();

        $totalAssigned = $this->result['assigned'] + $this->result['reassigned'];
        $replacements = [
            'supervisor_name' => $notifiable->name,
            'total_assigned' => $totalAssigned,
            'assigned_count' => $this->result['assigned'],
            'reassigned_count' => $this->result['reassigned'],
            'skipped_count' => $this->result['skipped'],
            'faculty' => $this->label(Faculty::class, 'faculty_id'),
            'department' => $this->label(Department::class, 'department_id'),
            'level' => $this->label(AcademicLevel::class, 'academic_level_id'),
            'academic_session' => $this->label(AcademicSession::class, 'academic_session_id'),
            'state' => $this->filters['company_state'] ?? 'Any',
            'lga' => $this->filters['company_lga'] ?? 'Any',
            'dashboard_url' => route('supervisor.dashboard'),
        ];

        $body = EmailTemplate::lines(
            'supervisor_bulk_assignment',
            "Bulk SIWES supervisor assignment has been completed.\n\nTotal Students Assigned: {total_assigned}\nFaculty: {faculty}\nDepartment: {department}\nPlacement State: {state}\nPlacement LGA: {lga}\n\nOpen your supervisor dashboard to view the assigned students.",
            $replacements,
        );

        return (new MailMessage)
            ->subject(EmailTemplate::subject('supervisor_bulk_assignment', 'COOU SIWES Bulk Student Assignment Summary', $replacements))
            ->greeting("Hello {$notifiable->name},")
            ->lines($body)
            ->action('Open Supervisor Dashboard', route('supervisor.dashboard'));
    }

    /**
     * @param  class-string  $model
     */
    private function label(string $model, string $filterKey): string
    {
        $id = $this->filters[$filterKey] ?? null;

        if (! $id) {
            return 'Any';
        }

        return (string) ($model::query()->find($id)?->name ?? 'Any');
    }
}
