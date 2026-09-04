<?php

namespace App\Notifications;

use App\Models\SupervisorStudentAssignment;
use App\Support\EmailTemplate;
use App\Support\MailConfiguration;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupervisorAssignmentNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly SupervisorStudentAssignment $assignment,
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

        $assignment = $this->assignment->loadMissing(['student.user', 'student.department', 'student.faculty', 'student.placement']);
        $student = $assignment->student;
        $placement = $student->placement;
        $replacements = [
            'supervisor_name' => $notifiable->name,
            'student_name' => $student->user?->name ?? 'N/A',
            'matric_no' => $student->matric_no,
            'department' => $student->department?->name ?? 'N/A',
            'faculty' => $student->faculty?->name ?? 'N/A',
            'state' => $placement?->company_state ?? 'N/A',
            'lga' => $placement?->company_lga ?? 'N/A',
            'dashboard_url' => route('supervisor.dashboard'),
        ];

        $body = EmailTemplate::lines(
            'supervisor_assignment',
            "A student has been assigned to you for SIWES supervision.\n\nStudent: {student_name}\nReg No: {matric_no}\nDepartment: {department}\nFaculty: {faculty}\nPlacement Location: {lga}, {state}",
            $replacements,
        );

        return (new MailMessage)
            ->subject(EmailTemplate::subject('supervisor_assignment', 'New COOU SIWES Student Assignment', $replacements))
            ->greeting("Hello {$notifiable->name},")
            ->lines($body)
            ->action('Open Supervisor Dashboard', route('supervisor.dashboard'));
    }
}
