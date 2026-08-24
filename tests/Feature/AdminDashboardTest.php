<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\StudentPlacement;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Admin;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_admin_dashboard_uses_database_synced_student_totals(): void
    {
        $admin = Admin::where('email', 'superadmin@coousiwes.test')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(number_format(Student::query()->count()))
            ->assertDontSee('4,500');
    }

    public function test_admin_dashboard_quick_reports_use_placement_records(): void
    {
        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();
        $admin->assignRole('student-manager');
        $student = Student::query()->firstOrFail();

        $student->placement()->create([
            'academic_level_id' => $student->academic_level_id,
            'academic_session_id' => $student->academic_session_id,
            'siwes_year' => 2026,
            'attachment_period' => 'April to October',
            'company_name' => 'Future Works Ltd',
        ]);

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('stats', fn (array $stats): bool => $stats['pendingActivation'] === Student::query()->doesntHave('placement')->count()
                && $stats['submittedForms'] === StudentPlacement::query()->count())
            ->assertSee('Students yet to add placement')
            ->assertSee('Placement forms submitted');
    }

    public function test_dashboard_and_navigation_follow_assigned_admin_module_permissions(): void
    {
        $role = Role::query()->create(['name' => 'payment-admin', 'guard_name' => 'web']);
        $role->givePermissionTo(['dashboard.view', 'payments.view']);

        $admin = Admin::query()->create([
            'admin_code' => 'ADM-PAY',
            'name' => 'Payment Admin',
            'email' => 'payment-admin@example.test',
            'password' => 'password',
            'status' => Admin::STATUS_ACTIVE,
            'otp_enabled' => false,
            'email_verified_at' => now(),
        ]);
        $admin->assignRole($role);

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Payments')
            ->assertSee('Verified Payments')
            ->assertDontSee('Total Students')
            ->assertDontSee('Students yet to add placement')
            ->assertDontSee(route('admin.students.index'))
            ->assertDontSee(route('admin.tickets.index'))
            ->assertDontSee(route('admin.supervisors.index'));

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->get(route('admin.students.index'))
            ->assertForbidden();
    }

    public function test_base_admin_role_only_shows_dashboard_without_module_features(): void
    {
        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('No Assigned Modules')
            ->assertDontSee('Total Students')
            ->assertDontSee('Open Tickets')
            ->assertDontSee(route('admin.students.index'))
            ->assertDontSee(route('admin.tickets.index'))
            ->assertDontSee(route('admin.supervisors.index'));

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->get(route('admin.students.index'))
            ->assertForbidden();
    }

    public function test_generate_list_module_permission_is_independent_from_student_management(): void
    {
        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();
        $admin->assignRole('generate-list-manager');

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.generate-list.index'))
            ->assertDontSee(route('admin.students.index'));

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->get(route('admin.generate-list.index'))
            ->assertOk();

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->get(route('admin.students.index'))
            ->assertForbidden();
    }
}


