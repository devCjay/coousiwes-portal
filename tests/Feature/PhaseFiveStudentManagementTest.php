<?php

namespace Tests\Feature;

use App\Models\AcademicLevel;
use App\Models\AcademicSession;
use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\Admin;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentImport;
use App\Models\Ticket;
use App\Models\User;
use App\Services\StudentImportService;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PhaseFiveStudentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(AcademicStructureSeeder::class);

        Admin::where('email', 'admin@coousiwes.test')
            ->firstOrFail()
            ->assignRole('student-manager')
            ->givePermissionTo([
                'students.create',
                'students.update',
                'students.suspend',
                'students.import',
                'students.export',
            ]);
    }

    public function test_admin_can_open_student_management_page(): void
    {
        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->get(route('admin.students.index'))
            ->assertOk()
            ->assertSee('Student Management')
            ->assertSee('Download Student Posting')
            ->assertSee('Download Student Posting List')
            ->assertSee('All States')
            ->assertSee('Zamfara')
            ->assertSee('200L')
            ->assertSee('300L')
            ->assertSee('400L')
            ->assertSee('500L')
            ->assertSee('600L');
    }

    public function test_student_list_uses_requested_columns_and_activation_status(): void
    {
        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();
        $inactive = $this->createStudent('inactive-list@example.test', '2026/CSC/020');
        $inactive->update(['activation_status' => Student::STATUS_INACTIVE]);
        $active = $this->createStudent('active-list@example.test', '2026/CSC/021');

        $active->placement()->create([
            'academic_level_id' => AcademicLevel::where('level', 600)->firstOrFail()->id,
            'academic_session_id' => AcademicSession::where('name', '2026/2027')->firstOrFail()->id,
            'siwes_year' => 2026,
            'attachment_period' => 'April to October',
            'company_name' => 'Future Works Ltd',
            'company_address' => '12 Industry Avenue',
            'company_state' => 'Anambra',
            'company_lga' => 'Awka South',
            'company_supervisor_phone' => '08039990000',
        ]);

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->get(route('admin.students.index', ['search' => '2026/CSC/02']))
            ->assertOk()
            ->assertDontSee('Student ID')
            ->assertSee('Reg No')
            ->assertSee('Course')
            ->assertSee('Academic Year')
            ->assertSee($inactive->user->name)
            ->assertSee($active->department->name)
            ->assertSee('inactive')
            ->assertSee('active');
    }

    public function test_imported_active_students_show_active_without_placement(): void
    {
        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();
        $file = UploadedFile::fake()->createWithContent('students.csv', $this->csv([
            ['Import', 'Active', '', '2026/CSC/009'],
        ]));

        $import = app(StudentImportService::class)->createImport($file, $admin->id, true);
        app(StudentImportService::class)->process($import);

        $student = Student::where('matric_no', '2026/CSC/009')->firstOrFail();
        $this->assertSame(Student::STATUS_ACTIVE, $student->activation_status);
        $this->assertFalse($student->placement()->exists());

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->get(route('admin.students.index', ['search' => '2026/CSC/009']))
            ->assertOk()
            ->assertSee('Import Active')
            ->assertSee('>active</span>', false)
            ->assertSee('Activated accounts');
    }

    public function test_ajax_manual_student_creation_creates_user_profile_and_audit_log(): void
    {
        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->postJson(route('admin.students.store'), $this->studentPayload([
                'name' => 'Ada Okoye',
                'email' => 'ada.okoye@example.test',
                'matric_no' => '2026/CSC/001',
            ]))
            ->assertOk()
            ->assertJsonPath('message', 'Student created.');

        $student = Student::where('matric_no', '2026/CSC/001')->firstOrFail();

        $this->assertSame('Ada Okoye', $student->user->name);
        $this->assertSame(Student::STATUS_INACTIVE, $student->activation_status);
        $this->assertTrue($student->user->hasRole('student'));
        $this->assertTrue(AuditLog::where('event', 'students.created')->where('auditable_id', $student->id)->exists());
    }

    public function test_admin_can_create_inactive_student_by_default_from_minimal_name_and_matric_fields(): void
    {
        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->postJson(route('admin.students.store'), [
                'first_name' => 'Ada',
                'middle_name' => 'Nneka',
                'last_name' => 'Okoye',
                'matric_no' => '2026/CSC/010',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Student created.');

        $student = Student::where('matric_no', '2026/CSC/010')->firstOrFail();

        $this->assertSame('Okoye Ada Nneka', $student->user->name);
        $this->assertSame(Student::STATUS_INACTIVE, $student->activation_status);
        $this->assertNull($student->user->email);
        $this->assertTrue(Hash::check('2026/CSC/010', $student->user->password));
        $this->assertNull($student->faculty_id);
        $this->assertNull($student->department_id);
        $this->assertNull($student->course_id);
        $this->assertNull($student->academic_level_id);
        $this->assertNull($student->academic_session_id);
        $this->assertSame(0, $student->tickets()->count());
    }

    public function test_admin_can_explicitly_create_active_student_with_auto_ticket(): void
    {
        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->postJson(route('admin.students.store'), [
                'first_name' => 'Active',
                'last_name' => 'Student',
                'matric_no' => '2026/CSC/012',
                'activation_status' => Student::STATUS_ACTIVE,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Student created.');

        $student = Student::where('matric_no', '2026/CSC/012')->firstOrFail();

        $this->assertSame(Student::STATUS_ACTIVE, $student->activation_status);
        $this->assertSame(1, $student->tickets()->count());
        $this->assertSame(Ticket::STATUS_UNUSED, $student->tickets()->firstOrFail()->status);
    }

    public function test_admin_can_mark_workshop_fee_paid_without_activating_student_or_assigning_ticket(): void
    {
        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->postJson(route('admin.students.store'), [
                'first_name' => 'Workshop',
                'last_name' => 'Student',
                'matric_no' => '2026/CSC/013',
                'workshop_fee_paid' => '1',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Student created.');

        $student = Student::where('matric_no', '2026/CSC/013')->firstOrFail();

        $this->assertSame(Student::STATUS_INACTIVE, $student->activation_status);
        $this->assertSame(0, $student->tickets()->count());
        $this->assertDatabaseHas('payments', [
            'student_id' => $student->id,
            'purpose' => Payment::PURPOSE_WORKSHOP_FEE,
            'provider' => 'manual',
            'status' => Payment::STATUS_SUCCESSFUL,
        ]);
    }

    public function test_admin_can_create_inactive_student_without_auto_ticket(): void
    {
        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->postJson(route('admin.students.store'), [
                'first_name' => 'Inactive',
                'last_name' => 'Student',
                'matric_no' => '2026/CSC/011',
                'activation_status' => Student::STATUS_INACTIVE,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Student created.');

        $student = Student::where('matric_no', '2026/CSC/011')->firstOrFail();

        $this->assertSame(Student::STATUS_INACTIVE, $student->activation_status);
        $this->assertSame(0, $student->tickets()->count());
    }

    public function test_duplicate_matric_numbers_are_rejected(): void
    {
        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->postJson(route('admin.students.store'), $this->studentPayload([
                'email' => 'first@example.test',
                'matric_no' => '2026/CSC/002',
            ]))
            ->assertOk();

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('admin.students.store'), $this->studentPayload([
                'email' => 'second@example.test',
                'matric_no' => '2026/CSC/002',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('matric_no');
    }

    public function test_students_can_be_suspended_and_reactivated(): void
    {
        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();
        $student = $this->createStudent('status@example.test', '2026/CSC/003');

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->postJson(route('admin.students.suspend', $student))
            ->assertOk()
            ->assertJsonPath('message', 'Student suspended.');

        $this->assertSame(Student::STATUS_SUSPENDED, $student->fresh()->activation_status);

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->postJson(route('admin.students.reactivate', $student))
            ->assertOk()
            ->assertJsonPath('message', 'Student reactivated.');

        $this->assertSame(Student::STATUS_ACTIVE, $student->fresh()->activation_status);
    }

    public function test_admin_student_profile_has_tabs_actions_reset_password_and_delete(): void
    {
        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();
        $student = $this->createStudent('profile-actions@example.test', '2026/CSC/030');
        $student->user->forceFill(['password' => Hash::make('old-password')])->save();
        $ticket = $student->tickets()->create([
            'serial_number' => 'SIWES-303030303030',
            'pin' => '303030',
            'code_hash' => 'profile-action-ticket',
            'amount' => 2000,
            'currency' => 'NGN',
            'status' => Ticket::STATUS_UNUSED,
            'assigned_at' => now(),
            'expires_at' => now()->addDays(10),
        ]);

        $student->placement()->create([
            'academic_level_id' => AcademicLevel::where('level', 400)->firstOrFail()->id,
            'academic_session_id' => AcademicSession::where('name', '2026/2027')->firstOrFail()->id,
            'siwes_year' => 2026,
            'attachment_period' => 'August to October',
            'company_name' => 'Profile Works Ltd',
            'company_address' => '18 Industrial Road',
            'company_state' => 'Anambra',
            'company_lga' => 'Awka South',
            'company_supervisor_phone' => '08030000008',
        ]);

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->get(route('admin.students.show', $student))
            ->assertOk()
            ->assertSee('Personal Data')
            ->assertSee('Placement Data')
            ->assertSee('Profile Works Ltd')
            ->assertSee('Reset Password')
            ->assertSee('Activate')
            ->assertSee('Delete');

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->postJson(route('admin.students.reset-password', $student))
            ->assertOk()
            ->assertJsonPath('message', 'Student password reset to reg no.');

        $this->assertTrue(Hash::check('2026/CSC/030', $student->user->fresh()->password));

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->deleteJson(route('admin.students.destroy', $student))
            ->assertOk()
            ->assertJsonPath('message', 'Student permanently deleted.');

        $this->assertDatabaseMissing('students', ['id' => $student->id]);
        $this->assertDatabaseMissing('users', ['id' => $student->user_id]);
        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
    }

    public function test_admin_can_bulk_delete_selected_students_permanently(): void
    {
        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();
        $first = $this->createStudent('bulk-delete-one@example.test', '2026/CSC/032');
        $second = $this->createStudent('bulk-delete-two@example.test', '2026/CSC/033');
        $untouched = $this->createStudent('bulk-delete-three@example.test', '2026/CSC/034');

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->deleteJson(route('admin.students.destroy-many'), [
                'student_ids' => [$first->id, $second->id],
            ])
            ->assertOk()
            ->assertJsonPath('message', '2 student(s) permanently deleted.');

        $this->assertDatabaseMissing('students', ['id' => $first->id]);
        $this->assertDatabaseMissing('students', ['id' => $second->id]);
        $this->assertDatabaseHas('students', ['id' => $untouched->id]);
        $this->assertDatabaseMissing('users', ['id' => $first->user_id]);
        $this->assertDatabaseMissing('users', ['id' => $second->user_id]);
        $this->assertTrue(AuditLog::where('event', 'students.bulk_deleted')->exists());
    }

    public function test_view_only_student_admin_cannot_see_or_call_student_actions(): void
    {
        $student = $this->createStudent('view-only-student@example.test', '2026/CSC/031');
        $viewOnlyAdmin = Admin::query()->create([
            'admin_code' => 'ADM-STUDENT-VIEW',
            'name' => 'Student Viewer',
            'email' => 'student.viewer@example.test',
            'password' => 'password',
            'status' => Admin::STATUS_ACTIVE,
            'otp_enabled' => false,
            'email_verified_at' => now(),
        ]);
        $viewOnlyAdmin->assignRole('student-manager');

        $this->actingAs($viewOnlyAdmin, 'admin')
            ->withSession(['otp.verified' => true])
            ->get(route('admin.students.index'))
            ->assertOk()
            ->assertSee('Student List')
            ->assertDontSee('Add Student')
            ->assertDontSee('Bulk Upload')
            ->assertDontSee('Download Student Posting')
            ->assertDontSee('>Export<', false)
            ->assertDontSee('add-student-modal')
            ->assertDontSee('bulk-upload-modal');

        $this->actingAs($viewOnlyAdmin, 'admin')
            ->withSession(['otp.verified' => true])
            ->get(route('admin.students.show', $student))
            ->assertOk()
            ->assertSee($student->matric_no)
            ->assertDontSee('Edit')
            ->assertDontSee('Reset Password')
            ->assertDontSee('Delete')
            ->assertDontSee('edit-student-modal');

        $this->actingAs($viewOnlyAdmin, 'admin')
            ->withSession(['otp.verified' => true])
            ->postJson(route('admin.students.store'), [
                'first_name' => 'Blocked',
                'last_name' => 'Student',
                'matric_no' => '2026/CSC/099',
            ])
            ->assertForbidden();

        $this->actingAs($viewOnlyAdmin, 'admin')
            ->withSession(['otp.verified' => true])
            ->putJson(route('admin.students.update', $student), $this->studentPayload([
                'email' => $student->user->email,
                'matric_no' => $student->matric_no,
                'name' => 'Blocked Update',
            ]))
            ->assertForbidden();

        $this->actingAs($viewOnlyAdmin, 'admin')
            ->withSession(['otp.verified' => true])
            ->postJson(route('admin.students.reset-password', $student))
            ->assertForbidden();

        $this->actingAs($viewOnlyAdmin, 'admin')
            ->withSession(['otp.verified' => true])
            ->deleteJson(route('admin.students.destroy', $student))
            ->assertForbidden();

        $this->actingAs($viewOnlyAdmin, 'admin')
            ->withSession(['otp.verified' => true])
            ->deleteJson(route('admin.students.destroy-many'), ['student_ids' => [$student->id]])
            ->assertForbidden();
    }

    public function test_export_and_templates_are_available_to_permitted_admins(): void
    {
        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();
        $this->createStudent('export@example.test', '2026/CSC/004');

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->get(route('admin.students.export'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertSee('2026/CSC/004');

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->get(route('admin.students.template', 'xlsx'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->get(route('admin.students.template', 'csv'))
            ->assertOk()
            ->assertSee('surname,first_name,other_name,reg_no', false)
            ->assertDontSee('2026/AGRIC/001')
            ->assertDontSee('2026/ENG/002')
            ->assertDontSee('faculty')
            ->assertDontSee('department')
            ->assertDontSee('student_id');
    }

    public function test_student_posting_list_download_matches_reference_xls_structure_and_filters(): void
    {
        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();
        $included = $this->createStudent('posting@example.test', '2026/CSC/011');
        $excluded = $this->createStudent('posting-other@example.test', '2026/CSC/012');
        $includedLevel = AcademicLevel::where('level', 300)->firstOrFail();
        $excludedLevel = AcademicLevel::where('level', 400)->firstOrFail();

        $included->placement()->create([
            'academic_level_id' => $includedLevel->id,
            'academic_session_id' => $included->academic_session_id,
            'siwes_year' => 2026,
            'attachment_period' => 'April to October',
            'company_name' => 'Future Works Ltd',
            'company_address' => '12 Industry Avenue',
            'company_state' => 'Anambra',
            'company_lga' => 'Awka South',
            'company_supervisor_phone' => '08039990000',
        ]);
        $excluded->placement()->create([
            'academic_level_id' => $excludedLevel->id,
            'academic_session_id' => $excluded->academic_session_id,
            'siwes_year' => 2026,
            'attachment_period' => 'April to October',
            'company_name' => 'Other Works Ltd',
            'company_address' => '9 Factory Road',
            'company_state' => 'Anambra',
            'company_lga' => 'Ikeja',
            'company_supervisor_phone' => '08030000001',
        ]);

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->get(route('admin.students.posting-list', ['academic_level_id' => $includedLevel->id, 'state' => 'Anambra']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->assertSee('CHUKWUEMEKA ODIMEGWU OJUKWU UNIVERSITY, IGBARIAM', false)
            ->assertSee('SUPERVISORY LIST BY STATE FOR 2026/2027 SIWES PROGRAMME (APRIL-NOVEMBER 2026 )', false)
            ->assertSee('Name of Student', false)
            ->assertSee('Reg No', false)
            ->assertSee('Company Supervisor Contact', false)
            ->assertSee('2026/CSC/011', false)
            ->assertSee('Future Works Ltd', false)
            ->assertDontSee('2026/CSC/012', false);
    }

    public function test_import_preview_detects_duplicate_rows_and_persists_history(): void
    {
        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();
        $file = UploadedFile::fake()->createWithContent('students.csv', $this->csv([
            ['Okoye', 'Ada', 'Nkechi', '2026/CSC/005'],
            ['Okoye', 'Ada', 'Duplicate', '2026/CSC/005'],
        ]));

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->postJson(route('admin.students.imports.preview'), ['students_file' => $file, 'auto_activate' => '0', 'workshop_fee_paid' => '1'])
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonPath('auto_activate', false)
            ->assertJsonPath('workshop_fee_paid', true)
            ->assertJsonStructure(['import_id', 'process_url', 'errors']);

        $this->assertSame(1, StudentImport::count());
        $this->assertGreaterThan(0, StudentImport::firstOrFail()->failed_rows);
        $this->assertTrue(StudentImport::firstOrFail()->mark_workshop_fee_paid);
    }

    public function test_import_processing_auto_queues_large_files(): void
    {
        Queue::fake();
        AppSetting::query()->updateOrCreate(
            ['key' => 'imports.immediate_threshold'],
            ['group' => 'imports', 'value' => 1, 'type' => 'integer']
        );

        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();
        $file = UploadedFile::fake()->createWithContent('students.csv', $this->csv([
            ['Student One', 'Large', '', '2026/CSC/101'],
            ['Student Two', 'Large', '', '2026/CSC/102'],
        ]));
        $import = app(StudentImportService::class)->createImport($file, $admin->id, true);

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->postJson(route('admin.students.imports.process', $import))
            ->assertOk()
            ->assertJsonPath('message', 'Large import detected (2 rows). It has been queued for cron processing.');

        Queue::assertNothingPushed();
        $this->assertSame(StudentImport::STATUS_QUEUED, $import->fresh()->status);
        $this->assertDatabaseMissing('students', ['matric_no' => '2026/CSC/101']);
    }

    public function test_import_processing_runs_immediately_by_default(): void
    {
        Queue::fake();

        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();
        $file = UploadedFile::fake()->createWithContent('students.csv', $this->csv([
            ['Student', 'Immediate', '', '2026/CSC/008'],
        ]));
        $import = app(StudentImportService::class)->createImport($file, $admin->id, true);

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->postJson(route('admin.students.imports.process', $import))
            ->assertOk()
            ->assertJsonPath('message', 'Student import completed. 1 students created, 0 failed.');

        Queue::assertNothingPushed();
        $this->assertSame(StudentImport::STATUS_COMPLETED, $import->fresh()->status);
        $this->assertDatabaseHas('students', ['matric_no' => '2026/CSC/008']);
    }

    public function test_cron_endpoint_processes_queued_student_imports_in_batches(): void
    {
        AppSetting::query()->updateOrCreate(
            ['key' => 'imports.cron_token'],
            ['group' => 'imports', 'value' => 'test-cron-token', 'type' => 'string']
        );

        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();
        $file = UploadedFile::fake()->createWithContent('students.csv', $this->csv([
            ['Student One', 'Cron', '', '2026/CSC/201'],
            ['Student Two', 'Cron', '', '2026/CSC/202'],
            ['Student Three', 'Cron', '', '2026/CSC/203'],
        ]));
        $import = app(StudentImportService::class)->createImport($file, $admin->id, true);
        $import->update(['status' => StudentImport::STATUS_QUEUED]);

        $this->getJson(route('cron.student-imports.process', ['token' => 'test-cron-token', 'limit' => 500]))
            ->assertOk()
            ->assertJsonPath('processed_imports', 1)
            ->assertJsonPath('processed_rows', 3)
            ->assertJsonPath('completed_imports', 1);

        $this->assertSame(StudentImport::STATUS_COMPLETED, $import->fresh()->status);
        $this->assertDatabaseHas('students', ['matric_no' => '2026/CSC/201']);
        $this->assertDatabaseHas('students', ['matric_no' => '2026/CSC/202']);
        $this->assertDatabaseHas('students', ['matric_no' => '2026/CSC/203']);
    }

    public function test_admin_can_process_queued_student_imports_without_cron_token(): void
    {
        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();
        $file = UploadedFile::fake()->createWithContent('students.csv', $this->csv([
            ['Student One', 'Panel', '', '2026/CSC/301'],
            ['Student Two', 'Panel', '', '2026/CSC/302'],
        ]));
        $import = app(StudentImportService::class)->createImport($file, $admin->id, true);
        $import->update(['status' => StudentImport::STATUS_QUEUED]);

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->postJson(route('admin.settings.imports.process'))
            ->assertOk()
            ->assertJsonPath('message', 'Queued imports processed. 2 row(s) handled, 1 import(s) completed.')
            ->assertJsonPath('processed_rows', 2)
            ->assertJsonPath('completed_imports', 1);

        $this->assertSame(StudentImport::STATUS_COMPLETED, $import->fresh()->status);
        $this->assertDatabaseHas('students', ['matric_no' => '2026/CSC/301']);
        $this->assertDatabaseHas('students', ['matric_no' => '2026/CSC/302']);
        $this->assertTrue(AuditLog::where('event', 'settings.student_import_cron_ran')->exists());
    }

    public function test_valid_import_rows_are_processed_into_students(): void
    {
        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();
        $file = UploadedFile::fake()->createWithContent('students.csv', $this->csv([
            ['Eze', 'Ngozi', '', '2026/CSC/006'],
        ]));

        $import = app(StudentImportService::class)->createImport($file, $admin->id);
        app(StudentImportService::class)->process($import);

        $student = Student::where('matric_no', '2026/CSC/006')->firstOrFail();

        $this->assertSame(Student::STATUS_INACTIVE, $student->activation_status);
        $this->assertSame(0, $student->tickets()->count());
        $this->assertSame(StudentImport::STATUS_COMPLETED, $import->fresh()->status);
        $this->assertSame(1, $import->fresh()->successful_rows);
    }

    public function test_import_accepts_rows_with_only_first_name_and_reg_no(): void
    {
        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();
        $file = UploadedFile::fake()->createWithContent('students.csv', $this->csv([
            ['', 'Ada', '', '2026/CSC/015'],
        ]));

        $import = app(StudentImportService::class)->createImport($file, $admin->id);
        $this->assertSame([], $import->error_report);

        app(StudentImportService::class)->process($import);

        $student = Student::where('matric_no', '2026/CSC/015')->firstOrFail();

        $this->assertSame('Ada', $student->user->name);
        $this->assertSame(StudentImport::STATUS_COMPLETED, $import->fresh()->status);
        $this->assertSame(1, $import->fresh()->successful_rows);
    }

    public function test_import_can_mark_uploaded_students_as_workshop_fee_paid(): void
    {
        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();
        $file = UploadedFile::fake()->createWithContent('students.csv', $this->csv([
            ['Workshop', 'Paid', '', '2026/CSC/014'],
        ]));

        $import = app(StudentImportService::class)->createImport($file, $admin->id, false, true);
        app(StudentImportService::class)->process($import);

        $student = Student::where('matric_no', '2026/CSC/014')->firstOrFail();

        $this->assertSame(Student::STATUS_INACTIVE, $student->activation_status);
        $this->assertSame(0, $student->tickets()->count());
        $this->assertDatabaseHas('payments', [
            'student_id' => $student->id,
            'purpose' => Payment::PURPOSE_WORKSHOP_FEE,
            'provider' => 'manual',
            'status' => Payment::STATUS_SUCCESSFUL,
        ]);
    }

    public function test_import_toggle_can_keep_minimal_uploads_inactive(): void
    {
        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();
        $file = UploadedFile::fake()->createWithContent('students.csv', $this->csv([
            ['Ilo', 'Chidinma', '', '2026/CSC/007'],
        ]));

        $import = app(StudentImportService::class)->createImport($file, $admin->id, false);
        app(StudentImportService::class)->process($import);

        $student = Student::where('matric_no', '2026/CSC/007')->firstOrFail();

        $this->assertSame('Ilo Chidinma', $student->user->name);
        $this->assertSame(Student::STATUS_INACTIVE, $student->activation_status);
        $this->assertNull($student->user->email);
        $this->assertNull($student->faculty_id);
        $this->assertNull($student->department_id);
        $this->assertSame(0, $student->tickets()->count());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function studentPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Demo Student',
            'email' => 'student-create@example.test',
            'phone' => '08030000000',

            'matric_no' => '2026/CSC/000',
            'faculty_id' => Faculty::where('code', 'AGRIC')->firstOrFail()->id,
            'department_id' => Department::where('code', 'AGE')->firstOrFail()->id,
            'academic_level_id' => AcademicLevel::where('level', 300)->firstOrFail()->id,
            'academic_session_id' => AcademicSession::where('name', '2026/2027')->firstOrFail()->id,
            'activation_status' => Student::STATUS_INACTIVE,
        ], $overrides);
    }

    private function createStudent(string $email, string $matricNo): Student
    {
        $this->actingAs(Admin::where('email', 'admin@coousiwes.test')->firstOrFail(), 'admin')
            ->withSession(['otp.verified' => true])
            ->postJson(route('admin.students.store'), $this->studentPayload([
                'email' => $email,
                'matric_no' => $matricNo,
                'activation_status' => Student::STATUS_ACTIVE,
            ]))
            ->assertOk();

        return Student::where('matric_no', $matricNo)->firstOrFail();
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     */
    private function csv(array $rows): string
    {
        $handle = fopen('php://temp', 'w+');
        fputcsv($handle, StudentImportService::HEADERS);

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);

        return (string) stream_get_contents($handle);
    }
}


