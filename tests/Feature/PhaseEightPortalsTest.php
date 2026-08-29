<?php

namespace Tests\Feature;

use App\Models\AcademicLevel;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\User;
use App\Services\StudentManager;
use App\Services\SupervisorManager;
use App\Services\TicketService;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhaseEightPortalsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(AcademicStructureSeeder::class);
    }

    public function test_student_dashboard_reflects_real_student_data(): void
    {
        $student = $this->student('portal-student@example.test', '2026/PORTAL/001');

        $this->actingAs($student->user)
            ->withSession(['otp.verified' => true])
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('2026/2027')
            ->assertSee('Student Placement')
            ->assertSee('Not started')
            ->assertSee('Activation status');
    }

    public function test_incomplete_student_profile_redirects_to_setup_page(): void
    {
        $student = $this->student('setup-student@example.test', '2026/PORTAL/007', completeProfile: false);

        $this->actingAs($student->user)
            ->withSession(['otp.verified' => true])
            ->get(route('student.dashboard'))
            ->assertRedirect(route('student.profile.edit'));

        $this->actingAs($student->user)
            ->withSession(['otp.verified' => true])
            ->get(route('student.profile.edit'))
            ->assertOk()
            ->assertSee('Complete your student profile')
            ->assertSee('Bank Information');
    }

    public function test_completed_student_profile_uses_original_profile_page_not_onboarding(): void
    {
        $student = $this->student('complete-profile@example.test', '2026/PORTAL/009');

        $this->actingAs($student->user)
            ->withSession(['otp.verified' => true])
            ->get(route('student.profile.edit'))
            ->assertRedirect(route('student.profile.show'));

        $this->actingAs($student->user)
            ->withSession(['otp.verified' => true])
            ->get(route('student.profile.show'))
            ->assertOk()
            ->assertSee('Student Profile')
            ->assertSee('2026/PORTAL/009')
            ->assertSee('Admin-managed information')
            ->assertSee('Save Contact')
            ->assertSee('Save Academic');
    }

    public function test_student_can_save_profile_setup_steps_over_ajax(): void
    {
        $student = $this->student('wizard-student@example.test', '2026/PORTAL/008', completeProfile: false);
        $session = AcademicSession::where('name', '2026/2027')->firstOrFail();

        $this->actingAs($student->user)
            ->withSession(['otp.verified' => true])
            ->postJson(route('student.profile.step'), [
                'step' => 'basic',
                'email' => 'wizard-student@example.test',
                'phone' => '08039990000',
                'gender' => 'Female',
                'date_of_birth' => '2002-01-01',
                'nationality' => 'Nigerian',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Step saved successfully.');

        $this->actingAs($student->user)
            ->withSession(['otp.verified' => true])
            ->postJson(route('student.profile.step'), [
                'step' => 'contact',
                'address' => 'Awka campus',
                'state' => 'Anambra',
                'lga' => 'Awka South',
            ])
            ->assertOk();

        $this->actingAs($student->user)
            ->withSession(['otp.verified' => true])
            ->postJson(route('student.profile.step'), [
                'step' => 'academic',
                'faculty_id' => Faculty::where('code', 'AGRIC')->firstOrFail()->id,
                'department_id' => Department::where('code', 'AGE')->firstOrFail()->id,
                'academic_session_id' => $session->id,
            ])
            ->assertOk();

        $this->actingAs($student->user)
            ->withSession(['otp.verified' => true])
            ->postJson(route('student.profile.step'), [
                'step' => 'bank',
                'bank_name' => 'Access Bank',
                'account_number' => '0123456789',
                'sort_code' => '044',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Profile completed. Welcome to your dashboard.')
            ->assertJsonPath('completion', 100)
            ->assertJsonPath('redirect', route('student.profile.complete', absolute: false));

        $this->assertTrue($student->fresh()->hasCompleteProfile());
        $this->assertSame($session->id, $student->fresh()->academic_session_id);
    }

    public function test_student_can_upload_profile_picture_on_basic_profile_step(): void
    {
        Storage::fake('public');

        $student = $this->student('photo-student@example.test', '2026/PORTAL/013', completeProfile: false);

        $this->actingAs($student->user)
            ->withSession(['otp.verified' => true])
            ->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('student.profile.step'), [
                'step' => 'basic',
                'profile_photo' => UploadedFile::fake()->createWithContent(
                    'student-photo.png',
                    base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=')
                ),
                'email' => 'photo-student@example.test',
                'phone' => '08039990013',
                'gender' => 'Female',
                'date_of_birth' => '2002-01-01',
                'nationality' => 'Nigerian',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Step saved successfully.');

        $photoPath = $student->user->fresh()->metadata['profile_photo_path'] ?? null;

        $this->assertNotNull($photoPath);
        Storage::disk('public')->assertExists($photoPath);

        $this->actingAs($student->user)
            ->withSession(['otp.verified' => true])
            ->get($student->user->fresh()->profilePhotoUrl())
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    public function test_student_can_update_only_their_own_profile(): void
    {
        $student = $this->student('profile-student@example.test', '2026/PORTAL/002');

        $this->actingAs($student->user)
            ->withSession(['otp.verified' => true])
            ->putJson(route('student.profile.update'), [
                'name' => 'Updated Portal Student',
                'email' => 'profile-student@example.test',
                'phone' => '08039999999',
                'gender' => 'Female',
                'date_of_birth' => '2002-01-01',
                'address' => 'Awka campus',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Profile updated.');

        $this->assertSame('Student 2026/PORTAL/002', $student->user->fresh()->name);
        $this->assertSame('Awka campus', $student->fresh()->address);
    }

    public function test_supervisor_dashboard_only_shows_assigned_students(): void
    {
        $supervisor = $this->supervisor('SUP-PORTAL-001');
        $otherSupervisor = $this->supervisor('SUP-PORTAL-002');
        $visible = $this->student('visible-portal@example.test', '2026/PORTAL/003');
        $hidden = $this->student('hidden-portal@example.test', '2026/PORTAL/004');

        $supervisor->assignments()->create(['student_id' => $visible->id, 'assigned_at' => now()]);
        $otherSupervisor->assignments()->create(['student_id' => $hidden->id, 'assigned_at' => now()]);

        $this->actingAs($supervisor->user)
            ->withSession(['otp.verified' => true])
            ->get(route('supervisor.dashboard'))
            ->assertOk()
            ->assertSee('2026/PORTAL/003')
            ->assertDontSee('2026/PORTAL/004');
    }

    public function test_portal_notifications_are_role_specific(): void
    {
        $student = $this->student('notify-student@example.test', '2026/PORTAL/005');
        $supervisor = $this->supervisor('SUP-PORTAL-003');

        DatabaseNotification::query()->create([
            'id' => (string) str()->uuid(),
            'type' => 'portal',
            'notifiable_type' => User::class,
            'notifiable_id' => $student->user->id,
            'data' => ['title' => 'Student activation', 'message' => 'Your payment was verified.'],
        ]);
        DatabaseNotification::query()->create([
            'id' => (string) str()->uuid(),
            'type' => 'portal',
            'notifiable_type' => User::class,
            'notifiable_id' => $supervisor->user->id,
            'data' => ['title' => 'Supervisor assignment', 'message' => 'New student assigned.'],
        ]);

        $this->actingAs($student->user)
            ->withSession(['otp.verified' => true])
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Student activation')
            ->assertDontSee('Supervisor assignment');

        $this->actingAs($supervisor->user)
            ->withSession(['otp.verified' => true])
            ->get(route('supervisor.dashboard'))
            ->assertOk()
            ->assertSee('Supervisor assignment')
            ->assertDontSee('Student activation');
    }

    public function test_role_routes_remain_isolated(): void
    {
        $student = $this->student('route-student@example.test', '2026/PORTAL/006');
        $supervisor = $this->supervisor('SUP-PORTAL-004');

        $this->actingAs($student->user)
            ->withSession(['otp.verified' => true])
            ->get(route('supervisor.dashboard'))
            ->assertForbidden();

        $this->actingAs($supervisor->user)
            ->withSession(['otp.verified' => true])
            ->get(route('student.dashboard'))
            ->assertForbidden();
    }

    public function test_student_confirms_ticket_before_saving_placement_steps(): void
    {
        $student = $this->student('placement-student@example.test', '2026/PORTAL/010');
        $student->update(['activation_status' => Student::STATUS_INACTIVE]);
        $ticket = app(TicketService::class)->generate();

        $this->actingAs($student->user)
            ->withSession(['otp.verified' => true])
            ->get(route('student.placements.create'))
            ->assertRedirect(route('student.placements.ticket'));

        $this->actingAs($student->user)
            ->withSession(['otp.verified' => true])
            ->postJson(route('student.placements.ticket.confirm'), [
                'serial_number' => $ticket->serial_number,
                'pin' => $ticket->pin,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Ticket confirmed. Continue your placement setup.')
            ->assertSessionHas('placement.ticket_id', $ticket->id);

        $level = AcademicLevel::where('level', 400)->firstOrFail();
        $session = AcademicSession::where('name', '2026/2027')->firstOrFail();

        $this->actingAs($student->user)
            ->withSession(['otp.verified' => true, 'placement.ticket_id' => $ticket->id])
            ->postJson(route('student.placements.store-step'), [
                'step' => 'siwes',
                'academic_level_id' => $level->id,
                'siwes_year' => now()->year,
                'academic_session_id' => $session->id,
                'attachment_period' => 'April to October',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Placement step saved successfully.');

        $this->actingAs($student->user)
            ->withSession(['otp.verified' => true, 'placement.ticket_id' => $ticket->id])
            ->postJson(route('student.placements.store-step'), [
                'step' => 'company',
                'company_name' => 'Future Works Ltd',
                'company_address' => '12 Industry Avenue',
                'company_state' => 'Anambra',
                'company_lga' => 'Awka South',
                'company_supervisor_phone' => '08039990000',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Placement completed. Your SIWES details have been saved.')
            ->assertJsonPath('redirect', route('student.placements.complete', absolute: false));

        $this->assertDatabaseHas('student_placements', [
            'student_id' => $student->id,
            'ticket_id' => $ticket->id,
            'company_name' => 'Future Works Ltd',
        ]);
        $this->assertSame($level->id, $student->fresh()->academic_level_id);
        $ticket->refresh();
        $this->assertSame(\App\Models\Ticket::STATUS_USED, $ticket->status);
        $this->assertSame($student->id, $ticket->student_id);

        $otherStudent = $this->student('placement-reuse@example.test', '2026/PORTAL/011');
        $this->actingAs($otherStudent->user)
            ->withSession(['otp.verified' => true])
            ->postJson(route('student.placements.ticket.confirm'), [
                'serial_number' => $ticket->serial_number,
                'pin' => $ticket->pin,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'This ticket has already been used for a placement.');
    }

    public function test_active_student_still_confirms_ticket_before_placement(): void
    {
        $student = $this->student('manual-cash-placement@example.test', '2026/PORTAL/013');
        $student->update(['activation_status' => Student::STATUS_ACTIVE]);

        $this->actingAs($student->user)
            ->withSession(['otp.verified' => true])
            ->get(route('student.placements.create'))
            ->assertRedirect(route('student.placements.ticket'));

        $this->actingAs($student->user)
            ->withSession(['otp.verified' => true])
            ->postJson(route('student.placements.store-step'), [
                'step' => 'siwes',
                'academic_level_id' => AcademicLevel::where('level', 400)->firstOrFail()->id,
                'siwes_year' => now()->year,
                'academic_session_id' => AcademicSession::where('name', '2026/2027')->firstOrFail()->id,
                'attachment_period' => 'April to October',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Confirm your ticket before adding placement details.');
    }

    public function test_student_can_view_assigned_tickets_on_my_ticket_page(): void
    {
        $student = $this->student('my-ticket@example.test', '2026/PORTAL/014');
        $ticket = app(TicketService::class)->generateFor($student);

        $this->actingAs($student->user)
            ->withSession(['otp.verified' => true])
            ->get(route('student.tickets.index'))
            ->assertOk()
            ->assertSee('My Ticket')
            ->assertSee($ticket->serial_number)
            ->assertSee('******');
    }

    public function test_student_can_confirm_ticket_with_displayed_pin_when_hash_is_stale(): void
    {
        $student = $this->student('legacy-ticket@example.test', '2026/PORTAL/012');
        $ticket = app(TicketService::class)->generate();
        $ticket->forceFill(['code_hash' => \Illuminate\Support\Facades\Hash::make('000000')])->save();

        $this->actingAs($student->user)
            ->withSession(['otp.verified' => true])
            ->postJson(route('student.placements.ticket.confirm'), [
                'serial_number' => strtolower($ticket->serial_number),
                'pin' => $ticket->pin,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Ticket confirmed. Continue your placement setup.');
    }

    private function student(string $email, string $matricNo, bool $completeProfile = true): Student
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

        if ($completeProfile) {
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
        }

        return $student;
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
}
