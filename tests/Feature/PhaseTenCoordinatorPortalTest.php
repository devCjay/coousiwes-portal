<?php

namespace Tests\Feature;

use App\Models\AcademicLevel;
use App\Models\AcademicSession;
use App\Models\Admin;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\Department;
use App\Models\SiwesCoordinator;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\User;
use App\Notifications\CoordinatorLoginDetailsNotification;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class PhaseTenCoordinatorPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(AcademicStructureSeeder::class);
    }

    public function test_admin_can_create_siwes_coordinator_and_login_details_are_sent(): void
    {
        Notification::fake();
        $admin = $this->admin(['coordinators.view', 'coordinators.create']);
        $department = Department::query()->where('is_active', true)->firstOrFail();
        $temporaryPassword = null;

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->postJson(route('admin.coordinators.store'), [
                'name' => 'Dr Coordinator One',
                'email' => 'coordinator.one@example.test',
                'phone' => '08031110000',
                'faculty_id' => $department->faculty_id,
                'department_id' => $department->id,
                'rank' => 'Lecturer 1',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'SIWES coordinator created.');

        $coordinator = SiwesCoordinator::query()
            ->whereHas('user', fn ($query) => $query->where('email', 'coordinator.one@example.test'))
            ->firstOrFail();

        $this->assertSame($department->id, $coordinator->department_id);
        $this->assertSame($department->faculty_id, $coordinator->faculty_id);
        $this->assertTrue($coordinator->user->hasRole('coordinator'));

        Notification::assertSentTo(
            $coordinator->user,
            CoordinatorLoginDetailsNotification::class,
            function (CoordinatorLoginDetailsNotification $notification) use ($coordinator, &$temporaryPassword): bool {
                $mail = $notification->toMail($coordinator->user);
                $content = implode("\n", $mail->introLines);
                preg_match('/Temporary password:\s*(.+)/', $content, $matches);
                $temporaryPassword = trim($matches[1] ?? '');

                return $mail->subject === 'COOU SIWES Coordinator Portal Login Details'
                    && str_contains($content, "Email: {$coordinator->user->email}")
                    && filled($temporaryPassword);
            }
        );

        auth('admin')->logout();
        $this->flushSession();

        $this->post(route('login.store', 'coordinator'), [
            'email' => 'coordinator.one@example.test',
            'password' => $temporaryPassword,
        ])->assertRedirect(route('otp.show'));
    }

    public function test_coordinator_students_page_and_export_are_limited_to_assigned_department(): void
    {
        $department = Department::query()->where('code', 'AGE')->firstOrFail();
        $otherDepartment = Department::query()->where('id', '!=', $department->id)->firstOrFail();
        $coordinator = $this->coordinator($department);
        $visibleStudent = $this->student('visible-coordinator@example.test', '2026/COR/001', $department);
        $hiddenStudent = $this->student('hidden-coordinator@example.test', '2026/COR/002', $otherDepartment);

        $this->actingAs($coordinator->user)
            ->withSession(['otp.verified' => true])
            ->get(route('coordinator.students.index'))
            ->assertOk()
            ->assertSee('No student list is loaded by default')
            ->assertDontSee($visibleStudent->matric_no);

        $this->actingAs($coordinator->user)
            ->withSession(['otp.verified' => true])
            ->get(route('coordinator.students.index', ['fetch' => 1]))
            ->assertOk()
            ->assertSee($visibleStudent->matric_no)
            ->assertDontSee($hiddenStudent->matric_no);

        $response = $this->actingAs($coordinator->user)
            ->withSession(['otp.verified' => true])
            ->get(route('coordinator.students.export'))
            ->assertOk()
            ->assertDownload('coordinator-department-students.xlsx');

        $sheet = IOFactory::load($response->baseResponse->getFile()->getPathname())->getActiveSheet();
        $rows = collect($sheet->rangeToArray('A1:K'.$sheet->getHighestDataRow()));

        $this->assertTrue($rows->flatten()->contains($visibleStudent->matric_no));
        $this->assertFalse($rows->flatten()->contains($hiddenStudent->matric_no));
    }

    public function test_coordinator_logbook_grade_sheet_is_limited_to_assigned_department(): void
    {
        $department = Department::query()->where('code', 'AGE')->firstOrFail();
        $otherDepartment = Department::query()->where('id', '!=', $department->id)->firstOrFail();
        $coordinator = $this->coordinator($department);
        $visibleStudent = $this->student('visible-score@example.test', '2026/COR/010', $department);
        $hiddenStudent = $this->student('hidden-score@example.test', '2026/COR/011', $otherDepartment);
        $supervisor = $this->supervisor();

        $this->assessment($supervisor, $visibleStudent, 33);
        $this->assessment($supervisor, $hiddenStudent, 12);

        $this->actingAs($coordinator->user)
            ->withSession(['otp.verified' => true])
            ->get(route('coordinator.logbook-grade-sheet.export'))
            ->assertOk()
            ->assertSee($visibleStudent->matric_no)
            ->assertDontSee($hiddenStudent->matric_no);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function admin(array $permissions): Admin
    {
        $admin = Admin::query()->where('email', 'admin@coousiwes.test')->firstOrFail();
        $admin->givePermissionTo($permissions);

        return $admin;
    }

    private function coordinator(Department $department): SiwesCoordinator
    {
        $user = User::query()->create([
            'name' => 'Coordinator '.$department->code,
            'email' => strtolower($department->code).'@coordinator.test',
            'phone' => '08030000001',
            'password' => Hash::make('password'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $user->assignRole('coordinator');

        return SiwesCoordinator::query()->create([
            'user_id' => $user->id,
            'faculty_id' => $department->faculty_id,
            'department_id' => $department->id,
            'staff_no' => 'COR-'.$department->code,
            'rank' => 'Lecturer 1',
            'status' => SiwesCoordinator::STATUS_ACTIVE,
        ]);
    }

    private function student(string $email, string $matricNo, Department $department): Student
    {
        $level = AcademicLevel::query()->where('level', 300)->firstOrFail();
        $session = AcademicSession::query()->where('name', '2026/2027')->firstOrFail();
        $course = Course::query()->where('department_id', $department->id)->first()
            ?? Course::query()->create([
                'department_id' => $department->id,
                'code' => 'TEST-'.$department->code,
                'name' => $department->name,
                'duration_years' => 4,
                'is_active' => true,
            ]);
        $user = User::query()->create([
            'name' => 'Student '.$matricNo,
            'email' => $email,
            'phone' => '08030000000',
            'password' => Hash::make($matricNo),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $user->assignRole('student');

        $student = Student::query()->create([
            'user_id' => $user->id,
            'matric_no' => $matricNo,
            'faculty_id' => $department->faculty_id,
            'department_id' => $department->id,
            'course_id' => $course->id,
            'academic_level_id' => $level->id,
            'academic_session_id' => $session->id,
            'activation_status' => Student::STATUS_ACTIVE,
        ]);
        $student->placement()->create([
            'academic_level_id' => $level->id,
            'academic_session_id' => $session->id,
            'siwes_year' => 2026,
            'attachment_period' => 'April to October',
            'company_name' => 'Coordinator Works Ltd',
            'company_address' => '12 Test Road',
            'company_state' => 'Anambra',
            'company_lga' => 'Awka South',
        ]);

        return $student;
    }

    private function supervisor(): Supervisor
    {
        $user = User::query()->create([
            'name' => 'Score Supervisor',
            'email' => 'score.supervisor@example.test',
            'phone' => '08030000003',
            'password' => Hash::make('password'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $user->assignRole('supervisor');

        return Supervisor::query()->create([
            'user_id' => $user->id,
            'staff_no' => 'SUP-COR-001',
            'organization' => 'COOU',
            'department' => 'SIWES',
            'status' => Supervisor::STATUS_ACTIVE,
        ]);
    }

    private function assessment(Supervisor $supervisor, Student $student, int $score): Assessment
    {
        $assignment = $supervisor->assignments()->create([
            'student_id' => $student->id,
            'assigned_at' => now(),
        ]);

        return Assessment::query()->create([
            'supervisor_id' => $supervisor->id,
            'student_id' => $student->id,
            'supervisor_student_assignment_id' => $assignment->id,
            'total_score' => $score,
            'max_score' => 40,
            'status' => Assessment::STATUS_SUBMITTED,
            'feedback' => 'Reviewed.',
            'submitted_at' => now(),
        ]);
    }
}
