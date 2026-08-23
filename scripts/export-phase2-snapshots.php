<?php

declare(strict_types=1);

use App\Models\AcademicLevel;
use App\Models\AcademicSession;
use App\Models\AppSetting;
use App\Models\Assessment;
use App\Models\AssessmentRubricItem;
use App\Models\AssessmentScore;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentImport;
use App\Models\Supervisor;
use App\Models\SupervisorStudentAssignment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\ViewErrorBag;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

require __DIR__.'/../vendor/autoload.php';

$routes = [
    '/' => ['route', 'home.html'],
    '/login/admin' => ['route', 'login-admin.html'],
    '/login/supervisor' => ['route', 'login-supervisor.html'],
    '/login/student' => ['route', 'login-student.html'],
    'pages.admin.dashboard' => ['view', 'admin-dashboard.html'],
    'pages.admin.students' => ['students-view', 'admin-students.html'],
    'pages.admin.supervisors' => ['supervisors-view', 'admin-supervisors.html'],
    'pages.admin.tickets' => ['tickets-view', 'admin-tickets.html'],
    'pages.admin.assessment-rubric' => ['assessment-rubric-view', 'admin-assessment-rubric.html'],
    'pages.admin.reports' => ['reports-view', 'admin-reports.html'],
    'pages.admin.control-center' => ['control-center-view', 'admin-control-center.html'],
    'pages.admin.audit-logs' => ['audit-logs-view', 'admin-audit-logs.html'],
    'pages.notifications.index' => ['notifications-view', 'notifications.html'],
    'pages.supervisor.dashboard' => ['supervisor-dashboard-view', 'supervisor-dashboard.html'],
    'pages.supervisor.students' => ['supervisor-students-view', 'supervisor-students.html'],
    'pages.supervisor.assessments' => ['supervisor-assessments-view', 'supervisor-assessments.html'],
    'pages.student.dashboard' => ['student-dashboard-view', 'student-dashboard.html'],
    'pages.student.payments' => ['student-payments-view', 'student-payments.html'],
    'pages.student.feedback' => ['student-feedback-view', 'student-feedback.html'],
];

$snapshotDirectory = __DIR__.'/../tests/e2e/snapshots';

if (! is_dir($snapshotDirectory)) {
    mkdir($snapshotDirectory, 0777, true);
}

foreach ($routes as $source => [$type, $filename]) {
    /** @var Application $app */
    $app = require __DIR__.'/../bootstrap/app.php';

    if ($type === 'view') {
        $app->make(Kernel::class)->bootstrap();
        $content = view($source)->render();
    } elseif ($type === 'students-view') {
        $app->make(Kernel::class)->bootstrap();
        $content = view($source, studentSnapshotData())->render();
    } elseif ($type === 'supervisors-view') {
        $app->make(Kernel::class)->bootstrap();
        $content = view($source, supervisorSnapshotData())->render();
    } elseif ($type === 'tickets-view') {
        $app->make(Kernel::class)->bootstrap();
        $content = view($source, ticketSnapshotData())->render();
    } elseif ($type === 'assessment-rubric-view') {
        $app->make(Kernel::class)->bootstrap();
        $content = view($source, assessmentRubricSnapshotData())->render();
    } elseif ($type === 'reports-view') {
        $app->make(Kernel::class)->bootstrap();
        $content = view($source, reportSnapshotData())->render();
    } elseif ($type === 'control-center-view') {
        $app->make(Kernel::class)->bootstrap();
        $content = view($source, controlCenterSnapshotData())->render();
    } elseif ($type === 'audit-logs-view') {
        $app->make(Kernel::class)->bootstrap();
        $content = view($source, auditLogSnapshotData())->render();
    } elseif ($type === 'notifications-view') {
        $app->make(Kernel::class)->bootstrap();
        $content = view($source, notificationSnapshotData())->render();
    } elseif ($type === 'supervisor-students-view') {
        $app->make(Kernel::class)->bootstrap();
        $content = view($source, supervisorStudentsSnapshotData())->render();
    } elseif ($type === 'supervisor-assessments-view') {
        $app->make(Kernel::class)->bootstrap();
        $content = view($source, supervisorAssessmentsSnapshotData())->render();
    } elseif ($type === 'student-dashboard-view') {
        $app->make(Kernel::class)->bootstrap();
        $content = view($source, studentDashboardSnapshotData())->render();
    } elseif ($type === 'supervisor-dashboard-view') {
        $app->make(Kernel::class)->bootstrap();
        $content = view($source, supervisorDashboardSnapshotData())->render();
    } elseif ($type === 'student-payments-view') {
        $app->make(Kernel::class)->bootstrap();
        $content = view($source, studentPaymentSnapshotData())->render();
    } elseif ($type === 'student-feedback-view') {
        $app->make(Kernel::class)->bootstrap();
        $content = view($source, studentFeedbackSnapshotData())->render();
    } else {
        ob_start();
        $app->handleRequest(Request::create($source, 'GET'));
        $content = ob_get_clean();
    }

    file_put_contents($snapshotDirectory.'/'.$filename, $content);
}

/**
 * @return Collection<int, AssessmentRubricItem>
 */
function rubricSnapshotItems(): Collection
{
    return new Collection([
        tap(new AssessmentRubricItem(['name' => 'Punctuality', 'description' => 'Attendance and reliability.', 'max_score' => 10, 'weight' => 1, 'sort_order' => 1, 'is_active' => true]), function (AssessmentRubricItem $item): void {
            $item->id = 1;
            $item->exists = true;
        }),
        tap(new AssessmentRubricItem(['name' => 'Technical Skill', 'description' => 'Applied technical delivery.', 'max_score' => 10, 'weight' => 2, 'sort_order' => 2, 'is_active' => true]), function (AssessmentRubricItem $item): void {
            $item->id = 2;
            $item->exists = true;
        }),
        tap(new AssessmentRubricItem(['name' => 'Communication', 'description' => 'Reporting and collaboration.', 'max_score' => 10, 'weight' => 1, 'sort_order' => 3, 'is_active' => true]), function (AssessmentRubricItem $item): void {
            $item->id = 3;
            $item->exists = true;
        }),
    ]);
}

echo 'Phase 2 snapshots exported.'.PHP_EOL;

/**
 * @return array<string, mixed>
 */
function studentSnapshotData(): array
{
    $faculty = new Faculty(['name' => 'Faculty of Agriculture', 'code' => 'AGRIC', 'is_active' => true]);
    $faculty->id = 1;
    $department = new Department(['name' => 'Agricultural Economics', 'code' => 'AGE', 'faculty_id' => 1, 'is_active' => true]);
    $department->id = 1;
    $department->setRelation('faculty', $faculty);
    $course = new Course(['name' => 'Agricultural Economics', 'code' => 'BSC-AGE', 'department_id' => 1, 'duration_years' => 4, 'is_active' => true]);
    $course->id = 1;
    $course->setRelation('department', $department);
    $level = new AcademicLevel(['name' => '300 Level', 'level' => 300, 'is_active' => true]);
    $level->id = 1;
    $session = new AcademicSession(['name' => '2026/2027', 'starts_on' => '2026-09-01', 'ends_on' => '2027-08-31', 'is_active' => true]);
    $session->id = 1;

    $students = collect([
        studentSnapshotModel(1, 'Ada Okoye', 'ada.okoye@example.test', '2026/CSC/001', 'active', $faculty, $department, $course, $level, $session),
        studentSnapshotModel(2, 'Ngozi Eze', 'ngozi.eze@example.test', '2026/CSC/002', 'inactive', $faculty, $department, $course, $level, $session),
    ]);

    return [
        'students' => new LengthAwarePaginator($students, $students->count(), 25, 1, ['path' => '/admin/students']),
        'faculties' => new Collection([$faculty]),
        'departments' => new Collection([$department]),
        'courses' => new Collection([$course]),
        'levels' => new Collection([$level]),
        'sessions' => new Collection([$session]),
        'imports' => new Collection([
            new StudentImport([
                'original_filename' => 'students.csv',
                'status' => StudentImport::STATUS_COMPLETED,
                'total_rows' => 2,
                'successful_rows' => 2,
                'failed_rows' => 0,
            ]),
        ]),
        'errors' => new ViewErrorBag,
    ];
}

function studentSnapshotModel(
    int $id,
    string $name,
    string $email,
    string $matricNo,
    string $status,
    Faculty $faculty,
    Department $department,
    Course $course,
    AcademicLevel $level,
    AcademicSession $session,
): Student {
    $user = new User(['name' => $name, 'email' => $email, 'phone' => '08030000000']);
    $user->id = $id;
    $student = new Student([
        'user_id' => $id,
        'matric_no' => $matricNo,
        'activation_status' => $status,
    ]);
    $student->id = $id;
    $student->exists = true;
    $student->setRelation('user', $user);
    $student->setRelation('faculty', $faculty);
    $student->setRelation('department', $department);
    $student->setRelation('course', $course);
    $student->setRelation('academicLevel', $level);
    $student->setRelation('academicSession', $session);

    return $student;
}

/**
 * @return array<string, mixed>
 */
function ticketSnapshotData(): array
{
    $data = studentSnapshotData();
    $student = $data['students']->getCollection()->first();
    $ticket = new Ticket([
        'student_id' => $student->id,
        'amount' => 5000,
        'currency' => 'NGN',
        'status' => Ticket::STATUS_ASSIGNED,
        'expires_at' => now()->addDays(30),
    ]);
    $ticket->id = 1;
    $ticket->exists = true;
    $ticket->setRelation('student', $student);
    $payment = new Payment([
        'student_id' => $student->id,
        'ticket_id' => 1,
        'provider' => 'korapay',
        'reference' => 'SIWES-SNAPSHOT',
        'amount' => 5000,
        'currency' => 'NGN',
        'status' => Payment::STATUS_PENDING,
    ]);
    $payment->id = 1;
    $payment->exists = true;
    $payment->setRelation('student', $student);
    $payment->setRelation('ticket', $ticket);

    return [
        'tickets' => new LengthAwarePaginator(new Collection([$ticket]), 1, 25, 1, ['path' => '/admin/tickets']),
        'students' => $data['students']->getCollection(),
        'payments' => new LengthAwarePaginator(new Collection([$payment]), 1, 25, 1, ['path' => '/admin/tickets']),
        'errors' => new ViewErrorBag,
    ];
}

/**
 * @return array<string, mixed>
 */
function studentPaymentSnapshotData(): array
{
    $data = ticketSnapshotData();
    $student = $data['students']->first();
    $ticket = $data['tickets']->getCollection()->first();
    $payment = $data['payments']->getCollection()->first();
    $student->setRelation('tickets', new Collection([$ticket]));
    $student->setRelation('payments', new Collection([$payment]));

    return [
        'student' => $student,
        'tickets' => new Collection([$ticket]),
        'payments' => new Collection([$payment]),
        'errors' => new ViewErrorBag,
    ];
}

/**
 * @return array<string, mixed>
 */
function studentDashboardSnapshotData(): array
{
    $data = ticketSnapshotData();
    $student = $data['students']->first();
    $ticket = $data['tickets']->getCollection()->first();
    $payment = $data['payments']->getCollection()->first();
    $supervisor = supervisorSnapshotModel();
    $assignment = assignmentSnapshotModel($supervisor, $student);
    $student->setRelation('tickets', new Collection([$ticket]));
    $student->setRelation('payments', new Collection([$payment]));
    $student->setRelation('activeSupervisorAssignment', $assignment);
    $student->setRelation('assessments', new Collection([assessmentSnapshotModel($supervisor, $student, $assignment)]));

    return [
        'student' => $student,
        'unreadNotifications' => new Collection([
            new DatabaseNotification([
                'data' => ['title' => 'Student activation', 'message' => 'Your payment was verified.'],
            ]),
        ]),
        'errors' => new ViewErrorBag,
    ];
}

/**
 * @return array<string, mixed>
 */
function supervisorDashboardSnapshotData(): array
{
    $data = supervisorStudentsSnapshotData();

    return [
        'supervisor' => $data['supervisor'],
        'assignments' => $data['assignments'],
        'unreadNotifications' => new Collection([
            new DatabaseNotification([
                'data' => ['title' => 'Supervisor assignment', 'message' => 'New student assigned.'],
            ]),
        ]),
        'errors' => new ViewErrorBag,
    ];
}

/**
 * @return array<string, mixed>
 */
function supervisorSnapshotData(): array
{
    $studentData = studentSnapshotData();
    $students = $studentData['students']->getCollection();
    $supervisor = supervisorSnapshotModel();
    $assignment = assignmentSnapshotModel($supervisor, $students->first());

    return [
        'supervisors' => new LengthAwarePaginator(new Collection([$supervisor]), 1, 25, 1, ['path' => '/admin/supervisors']),
        'allSupervisors' => new Collection([$supervisor]),
        'students' => $students,
        'faculties' => $studentData['faculties'],
        'departments' => $studentData['departments'],
        'courses' => $studentData['courses'],
        'levels' => $studentData['levels'],
        'sessions' => $studentData['sessions'],
        'assignments' => new LengthAwarePaginator(new Collection([$assignment]), 1, 25, 1, ['path' => '/admin/supervisors']),
        'errors' => new ViewErrorBag,
    ];
}

/**
 * @return array<string, mixed>
 */
function supervisorStudentsSnapshotData(): array
{
    $studentData = studentSnapshotData();
    $supervisor = supervisorSnapshotModel();
    $assignment = assignmentSnapshotModel($supervisor, $studentData['students']->getCollection()->first());
    $assignment->setRelation('assessment', null);
    $supervisor->setRelation('assignments', new Collection([$assignment]));

    return [
        'supervisor' => $supervisor,
        'assignments' => new Collection([$assignment]),
        'errors' => new ViewErrorBag,
    ];
}

/**
 * @return array<string, mixed>
 */
function assessmentRubricSnapshotData(): array
{
    $studentData = studentSnapshotData();
    $supervisor = supervisorSnapshotModel();
    $student = $studentData['students']->getCollection()->first();
    $assignment = assignmentSnapshotModel($supervisor, $student);
    $assessment = assessmentSnapshotModel($supervisor, $student, $assignment);

    return [
        'rubricItems' => rubricSnapshotItems(),
        'recentAssessments' => new Collection([$assessment]),
        'errors' => new ViewErrorBag,
    ];
}

/**
 * @return array<string, mixed>
 */
function supervisorAssessmentsSnapshotData(): array
{
    $studentData = studentSnapshotData();
    $students = $studentData['students']->getCollection();
    $supervisor = supervisorSnapshotModel();
    $openAssignment = assignmentSnapshotModel($supervisor, $students->first());
    $openAssignment->setRelation('assessment', null);
    $completedAssignment = assignmentSnapshotModel($supervisor, $students->last());
    $assessment = assessmentSnapshotModel($supervisor, $students->last(), $completedAssignment);
    $completedAssignment->id = 71;
    $completedAssignment->setRelation('assessment', $assessment);

    return [
        'supervisor' => $supervisor,
        'assignments' => new Collection([$openAssignment, $completedAssignment]),
        'rubricItems' => rubricSnapshotItems(),
        'assessments' => new Collection([$assessment]),
        'errors' => new ViewErrorBag,
    ];
}

/**
 * @return array<string, mixed>
 */
function studentFeedbackSnapshotData(): array
{
    $studentData = studentSnapshotData();
    $student = $studentData['students']->getCollection()->first();
    $supervisor = supervisorSnapshotModel();
    $assignment = assignmentSnapshotModel($supervisor, $student);
    $assessment = assessmentSnapshotModel($supervisor, $student, $assignment);
    $student->setRelation('assessments', new Collection([$assessment]));

    return [
        'student' => $student,
        'assessments' => new Collection([$assessment]),
        'errors' => new ViewErrorBag,
    ];
}

/**
 * @return array<string, mixed>
 */
function reportSnapshotData(): array
{
    $studentData = studentSnapshotData();
    $student = $studentData['students']->getCollection()->first();
    $supervisor = supervisorSnapshotModel();
    $assignment = assignmentSnapshotModel($supervisor, $student);
    $assessment = assessmentSnapshotModel($supervisor, $student, $assignment);

    return [
        'assessmentCount' => 1,
        'averageScore' => 90,
        'studentCount' => 2,
        'paymentCount' => 1,
        'supervisorPerformance' => new Collection([
            ['name' => $supervisor->user->name, 'assessments' => 1, 'average' => 90],
        ]),
        'completionByFaculty' => new Collection([
            ['faculty' => 'Faculty of Agriculture', 'students' => 2, 'assessed' => 1],
        ]),
        'scoreDistribution' => new Collection([
            ['range' => '0-39%', 'count' => 0],
            ['range' => '40-59%', 'count' => 0],
            ['range' => '60-79%', 'count' => 0],
            ['range' => '80-100%', 'count' => 1],
        ]),
        'paymentTrends' => collect(['pending' => 1]),
        'activationTrends' => collect(['active' => 1, 'inactive' => 1]),
        'recentAssessments' => new Collection([$assessment]),
        'errors' => new ViewErrorBag,
    ];
}

/**
 * @return array<string, mixed>
 */
function controlCenterSnapshotData(): array
{
    $superAdmin = new User(['name' => 'Super Admin', 'email' => 'superadmin@coousiwes.test', 'phone' => '08030000000', 'status' => 'active', 'otp_enabled' => true]);
    $superAdmin->id = 501;
    $superAdmin->exists = true;
    $admin = new User(['name' => 'SIWES Admin', 'email' => 'admin@coousiwes.test', 'phone' => '08030000001', 'status' => 'active', 'otp_enabled' => true]);
    $admin->id = 502;
    $admin->exists = true;
    $roles = snapshotRoles();
    $permissions = snapshotPermissions();
    $superAdmin->setRelation('roles', new Collection([$roles->firstWhere('name', 'super-admin')]));
    $superAdmin->setRelation('permissions', new Collection);
    $admin->setRelation('roles', new Collection([$roles->firstWhere('name', 'admin')]));
    $admin->setRelation('permissions', new Collection([$permissions->firstWhere('name', 'payments.export')]));
    $payment = new Payment(['reference' => 'SIWES-HEALTH', 'status' => Payment::STATUS_SUCCESSFUL, 'webhook_event' => 'charge.success']);
    $payment->id = 801;
    $payment->exists = true;

    return [
        'admins' => new Collection([$superAdmin, $admin]),
        'roles' => $roles,
        'permissions' => $permissions->groupBy(fn (Permission $permission): string => str($permission->name)->before('.')->toString()),
        'settings' => new Collection([
            new AppSetting(['group' => 'payment', 'key' => 'payment.provider', 'value' => 'korapay', 'type' => 'string', 'is_public' => false]),
        ])->groupBy('group'),
        'auditLogs' => new Collection([snapshotAuditLog($superAdmin, 'admins.created')]),
        'health' => [
            'queued_jobs' => 2,
            'failed_jobs' => 0,
            'recent_webhooks' => new Collection([$payment]),
            'last_payment' => $payment,
        ],
        'errors' => new ViewErrorBag,
    ];
}

/**
 * @return array<string, mixed>
 */
function auditLogSnapshotData(): array
{
    $superAdmin = new User(['name' => 'Super Admin', 'email' => 'superadmin@coousiwes.test']);
    $superAdmin->id = 501;
    $superAdmin->exists = true;
    $logs = new Collection([
        snapshotAuditLog($superAdmin, 'admins.created'),
        snapshotAuditLog($superAdmin, 'roles.updated'),
    ]);

    return [
        'auditLogs' => new LengthAwarePaginator($logs, $logs->count(), 30, 1, ['path' => '/admin/control/audit-logs']),
        'errors' => new ViewErrorBag,
    ];
}

/**
 * @return array<string, mixed>
 */
function notificationSnapshotData(): array
{
    $notifications = new Collection([
        new DatabaseNotification([
            'id' => '7ef8594f-3f3a-48d4-9f91-f5d3538f4b01',
            'type' => 'App\\Notifications\\PortalNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => 4,
            'data' => [
                'title' => 'Supervisor feedback submitted',
                'message' => 'Your SIWES supervisor assessment is now available.',
                'tone' => 'success',
                'action_url' => '/student/feedback',
            ],
            'read_at' => null,
        ]),
        new DatabaseNotification([
            'id' => '02c414f1-d769-49cf-93ff-6d28fb57b24a',
            'type' => 'App\\Notifications\\PortalNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => 4,
            'data' => [
                'title' => 'Payment verified',
                'message' => 'Your Korapay activation payment was verified.',
                'tone' => 'info',
                'action_url' => '/student/payments',
            ],
            'read_at' => now()->subDay(),
        ]),
    ]);
    $notifications->each(function (DatabaseNotification $notification): void {
        $notification->exists = true;
        $notification->created_at = now()->subHour();
    });

    return [
        'notifications' => new LengthAwarePaginator($notifications, $notifications->count(), 20, 1, ['path' => '/notifications']),
        'unreadCount' => 1,
        'errors' => new ViewErrorBag,
    ];
}

/**
 * @return Collection<int, Permission>
 */
function snapshotPermissions(): Collection
{
    return collect(['admins.manage', 'roles.manage', 'audit.view', 'students.view', 'tickets.view', 'payments.export', 'settings.update'])
        ->values()
        ->map(function (string $name, int $index): Permission {
            $permission = new Permission(['name' => $name, 'guard_name' => 'web']);
            $permission->id = $index + 1;
            $permission->exists = true;

            return $permission;
        });
}

/**
 * @return Collection<int, Role>
 */
function snapshotRoles(): Collection
{
    $permissions = snapshotPermissions();

    return collect(['super-admin', 'admin', 'finance-admin'])
        ->values()
        ->map(function (string $name, int $index) use ($permissions): Role {
            $role = new Role(['name' => $name, 'guard_name' => 'web']);
            $role->id = $index + 1;
            $role->exists = true;
            $role->setRelation('permissions', $name === 'super-admin' ? $permissions : $permissions->take(3));

            return $role;
        });
}

function snapshotAuditLog(User $user, string $event): AuditLog
{
    $log = new AuditLog(['user_id' => $user->id, 'event' => $event, 'ip_address' => '127.0.0.1', 'metadata' => ['source' => 'snapshot']]);
    $log->id = random_int(900, 999);
    $log->exists = true;
    $log->created_at = now();
    $log->setRelation('user', $user);

    return $log;
}

function supervisorSnapshotModel(): Supervisor
{
    $user = new User(['name' => 'Dr Ada Supervisor', 'email' => 'ada.supervisor@example.test', 'phone' => '08030000000']);
    $user->id = 50;
    $supervisor = new Supervisor([
        'user_id' => 50,
        'staff_no' => 'SUP-1001',
        'organization' => 'COOU',
        'department' => 'SIWES',
        'capacity' => 30,
        'status' => Supervisor::STATUS_ACTIVE,
    ]);
    $supervisor->id = 50;
    $supervisor->exists = true;
    $supervisor->active_assignments_count = 1;
    $supervisor->assignments_count = 1;
    $supervisor->setRelation('user', $user);

    return $supervisor;
}

function assignmentSnapshotModel(Supervisor $supervisor, Student $student): SupervisorStudentAssignment
{
    $assignment = new SupervisorStudentAssignment([
        'supervisor_id' => $supervisor->id,
        'student_id' => $student->id,
        'assigned_at' => now()->subDay(),
    ]);
    $assignment->id = 70;
    $assignment->exists = true;
    $assignment->setRelation('supervisor', $supervisor);
    $assignment->setRelation('student', $student);

    return $assignment;
}

function assessmentSnapshotModel(Supervisor $supervisor, Student $student, SupervisorStudentAssignment $assignment): Assessment
{
    $assessment = new Assessment([
        'supervisor_id' => $supervisor->id,
        'student_id' => $student->id,
        'supervisor_student_assignment_id' => $assignment->id,
        'total_score' => 27,
        'max_score' => 30,
        'status' => Assessment::STATUS_SUBMITTED,
        'feedback' => 'Strong workplace conduct and improving technical confidence.',
        'submitted_at' => now()->subHours(2),
    ]);
    $assessment->id = 90;
    $assessment->exists = true;
    $assessment->setRelation('supervisor', $supervisor);
    $assessment->setRelation('student', $student);
    $assessment->setRelation('assignment', $assignment);
    $assessment->setRelation('scores', rubricSnapshotItems()->map(function (AssessmentRubricItem $item): AssessmentScore {
        $score = new AssessmentScore([
            'assessment_rubric_item_id' => $item->id,
            'score' => $item->max_score - 1,
            'max_score' => $item->max_score,
            'comment' => null,
        ]);
        $score->id = 100 + $item->id;
        $score->exists = true;
        $score->setRelation('rubricItem', $item);

        return $score;
    }));

    return $assessment;
}
