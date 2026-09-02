<?php

namespace Tests\Feature;

use App\Models\AcademicLevel;
use App\Models\AcademicSession;
use App\Models\AuditLog;
use App\Models\Admin;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\SupervisorStudentAssignment;
use App\Models\User;
use App\Notifications\SupervisorAssignmentNotification;
use App\Notifications\SupervisorLoginDetailsNotification;
use App\Services\StudentManager;
use App\Services\SupervisorManager;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PhaseSevenSupervisorAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(AcademicStructureSeeder::class);
    }

    public function test_admin_can_create_supervisor_profiles(): void
    {
        $admin = $this->admin();
        Notification::fake();

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->postJson(route('admin.supervisors.store'), [
                'name' => 'Dr Ada Supervisor',
                'email' => 'ada.supervisor@example.test',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Supervisor created.');

        $supervisor = Supervisor::whereHas('user', fn ($query) => $query->where('email', 'ada.supervisor@example.test'))->firstOrFail();

        $this->assertSame('Dr Ada Supervisor', $supervisor->user->name);
        $this->assertStringStartsWith('SUP-', $supervisor->staff_no);
        $this->assertTrue($supervisor->user->hasRole('supervisor'));
        Notification::assertSentTo(
            $supervisor->user,
            SupervisorLoginDetailsNotification::class,
            function (SupervisorLoginDetailsNotification $notification, array $channels) use ($supervisor): bool {
                $mail = $notification->toMail($supervisor->user);
                $content = implode("\n", $mail->introLines);

                return in_array('mail', $channels, true)
                    && $mail->subject === 'COOU SIWES Supervisor Portal Login Details'
                    && str_contains($content, "Email: {$supervisor->user->email}")
                    && str_contains($content, 'Temporary password: ');
            }
        );
        $this->assertTrue(AuditLog::where('event', 'supervisors.created')->where('auditable_id', $supervisor->id)->exists());
    }

    public function test_assignment_can_reassign_students_and_preserves_history_on_revocation(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $student = $this->student('assigned@example.test', '2026/SUP/001');
        $supervisor = $this->supervisor('SUP-2001');
        $newSupervisor = $this->supervisor('SUP-2099');

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->postJson(route('admin.supervisor-assignments.store'), [
                'supervisor_id' => $supervisor->id,
                'student_id' => $student->id,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Student assigned to supervisor.');

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->postJson(route('admin.supervisor-assignments.store'), [
                'supervisor_id' => $newSupervisor->id,
                'student_id' => $student->id,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Student assigned to supervisor.');

        $assignment = SupervisorStudentAssignment::where('student_id', $student->id)->where('supervisor_id', $supervisor->id)->firstOrFail();
        $activeAssignment = SupervisorStudentAssignment::where('student_id', $student->id)->whereNull('revoked_at')->firstOrFail();
        $this->assertNotNull($assignment->fresh()->revoked_at);
        $this->assertSame($newSupervisor->id, $activeAssignment->supervisor_id);
        Notification::assertSentTo($newSupervisor->user, SupervisorAssignmentNotification::class);

        $student->placement()->create([
            'academic_level_id' => $student->academic_level_id,
            'academic_session_id' => $student->academic_session_id,
            'siwes_year' => 2026,
            'attachment_period' => 'April to October',
            'company_name' => 'Future Works Ltd',
        ]);

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->get(route('admin.supervisors.index'))
            ->assertOk()
            ->assertSee('Current Assignments')
            ->assertSee('Student Name')
            ->assertSee('Matric Number')
            ->assertSee('Year')
            ->assertSee('Supervisor')
            ->assertSee('2026')
            ->assertSee('Revoke Selected')
            ->assertSee('Revoke');

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->postJson(route('admin.supervisor-assignments.revoke', $activeAssignment), ['reason' => 'Rebalanced'])
            ->assertOk()
            ->assertJsonPath('message', 'Supervisor assignment revoked.');

        $this->assertNotNull($activeAssignment->fresh()->revoked_at);
        $this->assertSame(2, SupervisorStudentAssignment::where('student_id', $student->id)->count());
    }

    public function test_supervisor_can_receive_multiple_assignments(): void
    {
        $admin = $this->admin();
        $supervisor = $this->supervisor('SUP-2002');
        $first = $this->student('assignment-one@example.test', '2026/SUP/002');
        $second = $this->student('assignment-two@example.test', '2026/SUP/003');

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->postJson(route('admin.supervisor-assignments.store'), [
                'supervisor_id' => $supervisor->id,
                'student_id' => $first->id,
            ])
            ->assertOk();

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->postJson(route('admin.supervisor-assignments.store'), [
                'supervisor_id' => $supervisor->id,
                'student_id' => $second->id,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Student assigned to supervisor.');

        $this->assertSame(2, $supervisor->activeAssignments()->count());
    }

    public function test_bulk_assignment_assigns_all_matching_students(): void
    {
        $admin = $this->admin();
        $supervisor = $this->supervisor('SUP-2003');
        $first = $this->student('bulk-one@example.test', '2026/SUP/004');
        $this->student('bulk-two@example.test', '2026/SUP/005');
        $this->student('bulk-three@example.test', '2026/SUP/006');

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->postJson(route('admin.supervisor-assignments.bulk'), [
                'supervisor_id' => $supervisor->id,
                'faculty_id' => $first->faculty_id,
            ])
            ->assertOk()
            ->assertJsonPath('message', '4 assigned, 0 reassigned, 0 skipped.');

        $this->assertSame(4, $supervisor->activeAssignments()->count());
    }

    public function test_bulk_assignment_can_filter_by_placement_state_and_lga_and_reassign_existing_students(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $targetSupervisor = $this->supervisor('SUP-2010');
        $previousSupervisor = $this->supervisor('SUP-2011');
        $lagosMainland = $this->student('lagos-mainland@example.test', '2026/SUP/040');
        $lagosIkeja = $this->student('lagos-ikeja@example.test', '2026/SUP/041');
        $this->student('anambra@example.test', '2026/SUP/042');

        $lagosMainland->placement()->create([
            'academic_level_id' => $lagosMainland->academic_level_id,
            'academic_session_id' => $lagosMainland->academic_session_id,
            'siwes_year' => 2026,
            'attachment_period' => 'April to October',
            'company_name' => 'Mainland Works',
            'company_state' => 'Lagos',
            'company_lga' => 'Lagos Mainland',
        ]);
        $lagosIkeja->placement()->create([
            'academic_level_id' => $lagosIkeja->academic_level_id,
            'academic_session_id' => $lagosIkeja->academic_session_id,
            'siwes_year' => 2026,
            'attachment_period' => 'April to October',
            'company_name' => 'Ikeja Works',
            'company_state' => 'Lagos',
            'company_lga' => 'Ikeja',
        ]);
        $previousSupervisor->assignments()->create([
            'student_id' => $lagosMainland->id,
            'assigned_at' => now(),
        ]);

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->postJson(route('admin.supervisor-assignments.bulk'), [
                'supervisor_id' => $targetSupervisor->id,
                'company_state' => 'Lagos',
                'company_lga' => 'Lagos Mainland',
            ])
            ->assertOk()
            ->assertJsonPath('message', '0 assigned, 1 reassigned, 0 skipped.');

        $this->assertSame($targetSupervisor->id, $lagosMainland->activeSupervisorAssignment()->firstOrFail()->supervisor_id);
        $this->assertNull($lagosIkeja->activeSupervisorAssignment()->first());
        Notification::assertSentTo($targetSupervisor->user, SupervisorAssignmentNotification::class);
    }

    public function test_supervisor_can_only_see_their_assigned_students(): void
    {
        $assignedSupervisor = $this->supervisor('SUP-2004');
        $otherSupervisor = $this->supervisor('SUP-2005');
        $assignedStudent = $this->student('visible@example.test', '2026/SUP/007');
        $hiddenStudent = $this->student('hidden@example.test', '2026/SUP/008');

        $assignedSupervisor->assignments()->create([
            'student_id' => $assignedStudent->id,
            'assigned_at' => now(),
        ]);
        $otherSupervisor->assignments()->create([
            'student_id' => $hiddenStudent->id,
            'assigned_at' => now(),
        ]);

        $this->actingAs($assignedSupervisor->user)
            ->withSession(['otp.verified' => true])
            ->get(route('supervisor.students.index'))
            ->assertOk()
            ->assertSee('2026/SUP/007')
            ->assertDontSee('2026/SUP/008');
    }

    public function test_supervisor_exports_are_available_to_admins(): void
    {
        $admin = $this->admin();
        $this->supervisor('SUP-2006');

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->get(route('admin.supervisors.export'))
            ->assertOk()
            ->assertSee('SUP-2006');
    }

    private function admin(): Admin
    {
        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();
        $admin->assignRole('supervisor-manager');
        $admin->givePermissionTo([
            'supervisors.create',
            'supervisors.update',
            'supervisors.suspend',
            'supervisors.assign',
        ]);

        return $admin;
    }

    private function supervisor(string $staffNo): Supervisor
    {
        return app(SupervisorManager::class)->create([
            'name' => "Supervisor {$staffNo}",
            'email' => strtolower($staffNo).'@example.test',
            'phone' => '08030000000',
            'staff_no' => $staffNo,
            'organization' => 'COOU',
            'department' => 'SIWES',
            'status' => Supervisor::STATUS_ACTIVE,
        ]);
    }

    private function student(string $email, string $matricNo): Student
    {
        return app(StudentManager::class)->create([
            'name' => "Student {$matricNo}",
            'email' => $email,
            'phone' => '08030000000',

            'matric_no' => $matricNo,
            'faculty_id' => Faculty::where('code', 'AGRIC')->firstOrFail()->id,
            'department_id' => Department::where('code', 'AGE')->firstOrFail()->id,
            'course_id' => Course::where('code', 'BSC-AGE')->firstOrFail()->id,
            'academic_level_id' => AcademicLevel::where('level', 300)->firstOrFail()->id,
            'academic_session_id' => AcademicSession::where('name', '2026/2027')->firstOrFail()->id,
            'activation_status' => Student::STATUS_ACTIVE,
        ]);
    }
}


