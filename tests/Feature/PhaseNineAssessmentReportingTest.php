<?php

namespace Tests\Feature;

use App\Models\AcademicLevel;
use App\Models\AcademicSession;
use App\Models\Assessment;
use App\Models\AssessmentRubricItem;
use App\Models\AuditLog;
use App\Models\Admin;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\SupervisorStudentAssignment;
use App\Models\User;
use App\Notifications\PortalNotification;
use App\Services\StudentManager;
use App\Services\SupervisorManager;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Tests\TestCase;

class PhaseNineAssessmentReportingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(AcademicStructureSeeder::class);
    }

    public function test_admin_can_create_assessment_rubric_items(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->postJson(route('admin.assessments.rubric.store'), [
                'name' => 'Industry Innovation',
                'description' => 'Applied creativity and workplace problem solving.',
                'max_score' => 10,
                'weight' => 2,
                'sort_order' => 20,
                'is_active' => true,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Rubric item created.');

        $rubric = AssessmentRubricItem::where('name', 'Industry Innovation')->firstOrFail();

        $this->assertSame(2, $rubric->weight);
        $this->assertTrue(AuditLog::where('event', 'assessments.rubric_created')->where('auditable_id', $rubric->id)->exists());
    }

    public function test_supervisor_can_submit_assessment_for_assigned_student(): void
    {
        $supervisor = $this->supervisor('SUP-9001');
        $student = $this->student('phase-nine@example.test', '2026/ASM/001');
        $supervisor->assignments()->create(['student_id' => $student->id, 'assigned_at' => now()]);
        $scores = AssessmentRubricItem::query()->where('is_active', true)->pluck('max_score', 'id')->map(fn (int $score): int => $score - 1)->all();

        $this->actingAs($supervisor->user)
            ->withSession(['otp.verified' => true])
            ->postJson(route('supervisor.assessments.store'), [
                'student_id' => $student->id,
                'scores' => $scores,
                'feedback' => 'Strong industrial performance with clear improvement in technical delivery.',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Assessment submitted.');

        $assessment = Assessment::where('student_id', $student->id)->firstOrFail();

        $this->assertSame(count($scores), $assessment->scores()->count());
        $this->assertTrue(DatabaseNotification::where('notifiable_id', $student->user_id)
            ->where('type', PortalNotification::class)
            ->where('data->meta->assessment_id', $assessment->id)
            ->exists());
        $this->assertTrue(AuditLog::where('event', 'assessments.submitted')->where('auditable_id', $assessment->id)->exists());
    }

    public function test_supervisor_cannot_assess_unassigned_student(): void
    {
        $supervisor = $this->supervisor('SUP-9002');
        $student = $this->student('unassigned@example.test', '2026/ASM/002');
        $scores = AssessmentRubricItem::query()->where('is_active', true)->pluck('max_score', 'id')->all();

        $this->actingAs($supervisor->user)
            ->withSession(['otp.verified' => true])
            ->postJson(route('supervisor.assessments.store'), [
                'student_id' => $student->id,
                'scores' => $scores,
                'feedback' => 'This should not be accepted.',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Student is not actively assigned to this supervisor.');
    }

    public function test_student_feedback_page_is_scoped_to_authenticated_student(): void
    {
        $supervisor = $this->supervisor('SUP-9003');
        $visibleStudent = $this->student('visible-feedback@example.test', '2026/ASM/003');
        $hiddenStudent = $this->student('hidden-feedback@example.test', '2026/ASM/004');

        $this->createAssessment($supervisor, $visibleStudent, 'Visible private feedback.');
        $this->createAssessment($supervisor, $hiddenStudent, 'Hidden private feedback.');

        $this->actingAs($visibleStudent->user)
            ->withSession(['otp.verified' => true])
            ->get(route('student.feedback.index'))
            ->assertOk()
            ->assertSee('Visible private feedback.')
            ->assertDontSee('Hidden private feedback.');
    }

    public function test_admin_reports_and_export_include_assessment_data(): void
    {
        $admin = $this->admin();
        $supervisor = $this->supervisor('SUP-9004');
        $student = $this->student('report-feedback@example.test', '2026/ASM/005');
        $this->createAssessment($supervisor, $student, 'Reportable feedback sample.');

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('Reports')
            ->assertSee('Reportable feedback sample.', false);

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->get(route('admin.reports.export'))
            ->assertOk()
            ->assertSee('2026/ASM/005')
            ->assertSee('Supervisor SUP-9004');
    }

    private function createAssessment(Supervisor $supervisor, Student $student, string $feedback): Assessment
    {
        $assignment = SupervisorStudentAssignment::query()->create([
            'supervisor_id' => $supervisor->id,
            'student_id' => $student->id,
            'assigned_at' => now(),
        ]);
        $assessment = Assessment::query()->create([
            'supervisor_id' => $supervisor->id,
            'student_id' => $student->id,
            'supervisor_student_assignment_id' => $assignment->id,
            'total_score' => 54,
            'max_score' => 60,
            'status' => Assessment::STATUS_SUBMITTED,
            'feedback' => $feedback,
            'submitted_at' => now(),
        ]);

        AssessmentRubricItem::query()->where('is_active', true)->each(function (AssessmentRubricItem $item) use ($assessment): void {
            $assessment->scores()->create([
                'assessment_rubric_item_id' => $item->id,
                'score' => $item->max_score - 1,
                'max_score' => $item->max_score,
            ]);
        });

        return $assessment;
    }

    private function admin(): Admin
    {
        return Admin::where('email', 'admin@coousiwes.test')->firstOrFail();
    }

    private function superAdmin(): Admin
    {
        return Admin::where('email', 'superadmin@coousiwes.test')->firstOrFail();
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
        $student = app(StudentManager::class)->create([
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

        $student->update([
            'gender' => 'Male',
            'date_of_birth' => '2001-01-01',
            'address' => 'Awka campus',
            'metadata' => [
                'nationality' => 'Nigerian',
                'state' => 'Anambra',
                'lga' => 'Awka South',
                'bank_name' => 'Access Bank',
                'account_number' => '0123456789',
                'sort_code' => '044',
            ],
        ]);

        return $student;
    }
}


