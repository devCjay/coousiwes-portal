<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\AcademicLevel;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Admin;
use Tests\TestCase;

class PhaseFourAcademicConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_permitted_admins_can_open_academic_and_settings_configuration_pages(): void
    {
        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->get(route('admin.generate-list.index'))
            ->assertOk()
            ->assertSee('Generate Masters List')
            ->assertSee('Generate Placement List')
            ->assertSee('Faculty')
            ->assertSee('Department')
            ->assertSee('Session')
            ->assertSee('Level')
            ->assertSee('All');

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->get(route('admin.academics.index'))
            ->assertOk();

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->get(route('admin.settings.index'))
            ->assertOk();
    }

    public function test_non_admin_roles_cannot_manage_academic_configuration(): void
    {
        $supervisor = User::where('email', 'supervisor@coousiwes.test')->firstOrFail();

        $this->actingAs($supervisor)
            ->withSession(['otp.verified' => true])
            ->get(route('admin.academics.index'))
            ->assertForbidden();
    }

    public function test_it_creates_faculties_and_audits_the_change(): void
    {
        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->from(route('admin.academics.index'))
            ->post(route('admin.academics.faculties.store'), [
                'name' => 'Faculty of Management Sciences',
                'code' => 'FMS',
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.academics.index'))
            ->assertSessionHasNoErrors();

        $faculty = Faculty::where('code', 'FMS')->firstOrFail();

        $this->assertTrue(AuditLog::where('event', 'academics.faculty_created')->where('auditable_id', $faculty->id)->exists());
    }

    public function test_ajax_academic_forms_return_json_without_redirecting_the_page(): void
    {
        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->postJson(route('admin.academics.faculties.store'), [
                'name' => 'Faculty of Education',
                'code' => 'FED',
                'is_active' => true,
            ])
            ->assertOk()
            ->assertJson([
                'message' => 'Faculty created.',
                'redirect' => null,
                'reload' => true,
            ]);
    }

    public function test_it_blocks_duplicate_department_codes_inside_the_same_faculty(): void
    {
        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();
        $faculty = Faculty::query()->create(['name' => 'Faculty of Environmental Sciences', 'code' => 'FES']);
        Department::query()->create(['faculty_id' => $faculty->id, 'name' => 'Architecture', 'code' => 'ARC']);

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->from(route('admin.academics.index'))
            ->post(route('admin.academics.departments.store'), [
                'faculty_id' => $faculty->id,
                'name' => 'Architectural Studies',
                'code' => 'ARC',
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.academics.index'))
            ->assertSessionHasErrors('code');
    }

    public function test_it_blocks_deleting_referenced_faculties(): void
    {
        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();
        $faculty = Faculty::query()->create(['name' => 'Faculty of Social Sciences', 'code' => 'FSS']);
        Department::query()->create(['faculty_id' => $faculty->id, 'name' => 'Economics', 'code' => 'ECO']);

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->from(route('admin.academics.index'))
            ->delete(route('admin.academics.faculties.destroy', $faculty))
            ->assertRedirect(route('admin.academics.index'))
            ->assertSessionHasErrors('faculty');

        $this->assertNull($faculty->fresh()->deleted_at);
    }

    public function test_ajax_guarded_delete_returns_validation_json(): void
    {
        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();
        $faculty = Faculty::query()->create(['name' => 'Faculty of Law', 'code' => 'LAW']);
        Department::query()->create(['faculty_id' => $faculty->id, 'name' => 'Private Law', 'code' => 'PLW']);

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->deleteJson(route('admin.academics.faculties.destroy', $faculty))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Faculty cannot be deleted while departments are attached.');
    }

    public function test_active_session_switches_off_previous_sessions(): void
    {
        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();
        $current = AcademicSession::query()->create([
            'name' => '2025/2026',
            'starts_on' => '2025-09-01',
            'ends_on' => '2026-08-31',
            'is_active' => true,
        ]);
        $next = AcademicSession::query()->create([
            'name' => '2027/2028',
            'starts_on' => '2027-09-01',
            'ends_on' => '2028-08-31',
        ]);

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->from(route('admin.academics.index'))
            ->post(route('admin.academics.sessions.activate', $next))
            ->assertRedirect(route('admin.academics.index'));

        $this->assertFalse($current->fresh()->is_active);
        $this->assertTrue($next->fresh()->is_active);
        $this->assertSame('2027/2028', AcademicSession::active()?->name);
    }

    public function test_super_admin_can_create_typed_settings_and_changes_are_audited(): void
    {
        $superAdmin = Admin::where('email', 'superadmin@coousiwes.test')->firstOrFail();

        $this->actingAs($superAdmin, 'admin')
            ->withSession(['otp.verified' => true])
            ->from(route('admin.settings.index'))
            ->post(route('admin.settings.store'), [
                'group' => 'payment',
                'key' => 'payment.provider',
                'value' => 'korapay',
                'type' => 'string',
            ])
            ->assertRedirect(route('admin.settings.index'))
            ->assertSessionHasNoErrors();

        $setting = AppSetting::where('key', 'payment.provider')->firstOrFail();

        $this->assertSame('korapay', $setting->value);
        $this->assertTrue(AuditLog::where('event', 'settings.created')->where('auditable_id', $setting->id)->exists());
    }

    public function test_ajax_settings_forms_return_toast_ready_json(): void
    {
        $superAdmin = Admin::where('email', 'superadmin@coousiwes.test')->firstOrFail();

        $this->actingAs($superAdmin, 'admin')
            ->withSession(['otp.verified' => true])
            ->postJson(route('admin.settings.store'), [
                'group' => 'otp',
                'key' => 'otp.resend_window_seconds',
                'value' => '60',
                'type' => 'integer',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Setting created.');

        $this->assertSame(60, AppSetting::where('key', 'otp.resend_window_seconds')->firstOrFail()->value);
    }

    public function test_generate_list_exports_master_and_placement_xls_files(): void
    {
        $this->seed(\Database\Seeders\AcademicStructureSeeder::class);

        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();
        $faculty = Faculty::where('code', 'AGRIC')->firstOrFail();
        $department = Department::where('code', 'AGE')->firstOrFail();
        $level = AcademicLevel::where('level', 400)->firstOrFail();
        $session = AcademicSession::where('name', '2026/2027')->firstOrFail();

        $user = User::factory()->create([
            'name' => 'Mary Dennis Abang',
            'email' => 'mary.abang@example.test',
            'phone' => '08030000000',
            'metadata' => [
                'bank_name' => 'Access Bank',
                'account_number' => '0123456789',
                'sort_code' => '044',
            ],
        ]);
        $user->assignRole('student');

        $student = Student::query()->create([
            'user_id' => $user->id,
            'matric_no' => '2021015017',
            'faculty_id' => $faculty->id,
            'department_id' => $department->id,
            'academic_level_id' => $level->id,
            'academic_session_id' => $session->id,
            'activation_status' => Student::STATUS_ACTIVE,
            'metadata' => [
                'bank_name' => 'Access Bank',
                'account_number' => '0123456789',
                'sort_code' => '044',
            ],
        ]);

        $student->placement()->create([
            'academic_level_id' => $level->id,
            'academic_session_id' => $session->id,
            'siwes_year' => 2026,
            'attachment_period' => 'April to October',
            'company_name' => 'Future Works Ltd',
            'company_address' => '12 Industry Avenue',
            'company_state' => 'Anambra',
            'company_lga' => 'Awka South',
            'company_supervisor_phone' => '08039990000',
        ]);

        $otherUser = User::factory()->create([
            'name' => 'Filtered Out Student',
            'email' => 'filtered.out@example.test',
        ]);
        $otherUser->assignRole('student');
        $otherDepartment = Department::where('code', 'CSC')->firstOrFail();
        $otherFaculty = $otherDepartment->faculty;
        $otherLevel = AcademicLevel::where('level', 300)->firstOrFail();
        $otherStudent = Student::query()->create([
            'user_id' => $otherUser->id,
            'matric_no' => '2021999999',
            'faculty_id' => $otherFaculty->id,
            'department_id' => $otherDepartment->id,
            'academic_level_id' => $otherLevel->id,
            'academic_session_id' => $session->id,
            'activation_status' => Student::STATUS_ACTIVE,
        ]);
        $otherStudent->placement()->create([
            'academic_level_id' => $otherLevel->id,
            'academic_session_id' => $session->id,
            'siwes_year' => 2026,
            'attachment_period' => 'April to October',
            'company_name' => 'Filtered Works Ltd',
            'company_address' => 'Hidden Address',
            'company_state' => 'Anambra',
            'company_lga' => 'Awka South',
            'company_supervisor_phone' => '08039990001',
        ]);

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->get(route('admin.generate-list.master', [
                'faculty_id' => $faculty->id,
                'department_id' => $department->id,
                'academic_session_id' => $session->id,
                'academic_level_id' => $level->id,
            ]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->assertSee('MASTER LIST FOR 2026/2027 SIWES PROGRAMME', false)
            ->assertSee('NAME_OF_STUDENT', false)
            ->assertSee('MATRIC_NUMBER', false)
            ->assertSee('Mary Dennis Abang', false)
            ->assertSee('APRIL - OCTOBER 2026', false)
            ->assertDontSee('Filtered Out Student', false);

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->get(route('admin.generate-list.placement', [
                'faculty_id' => $faculty->id,
                'department_id' => $department->id,
                'academic_session_id' => $session->id,
                'academic_level_id' => $level->id,
            ]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->assertSee('PLACEMENT LIST FOR 2026/2027 SIWES PROGRAMME', false)
            ->assertSee('PERIOD_OF_ATTACHEMNT_FROM', false)
            ->assertSee('PLACEMENT_OF_ADDRESS', false)
            ->assertSee('Future Works Ltd - 12 Industry Avenue', false)
            ->assertSee('Access Bank', false)
            ->assertDontSee('Filtered Works Ltd', false);
    }

    public function test_bulk_settings_update_saves_grouped_payment_configuration(): void
    {
        $superAdmin = Admin::where('email', 'superadmin@coousiwes.test')->firstOrFail();

        $this->actingAs($superAdmin, 'admin')
            ->withSession(['otp.verified' => true])
            ->postJson(route('admin.settings.bulk'), [
                'settings' => [
                    [
                        'group' => 'payment',
                        'key' => 'payment.provider',
                        'value' => 'korapay',
                        'type' => 'string',
                    ],
                    [
                        'group' => 'payment',
                        'key' => 'payment.ticket_amount',
                        'value' => '7500',
                        'type' => 'integer',
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('message', '2 setting(s) saved.');

        $this->assertSame('korapay', AppSetting::where('key', 'payment.provider')->firstOrFail()->value);
        $this->assertSame(7500, AppSetting::where('key', 'payment.ticket_amount')->firstOrFail()->value);
    }

    public function test_email_connection_test_requires_smtp_host_and_port(): void
    {
        $superAdmin = Admin::where('email', 'superadmin@coousiwes.test')->firstOrFail();
        config(['mail.mailers.smtp.host' => '', 'mail.mailers.smtp.port' => null]);

        $this->actingAs($superAdmin, 'admin')
            ->withSession(['otp.verified' => true])
            ->postJson(route('admin.settings.email.test'))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'SMTP host and port are required before testing email connection.');
    }
}


