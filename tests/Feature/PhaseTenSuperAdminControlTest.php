<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Admin;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PhaseTenSuperAdminControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(AcademicStructureSeeder::class);
    }

    public function test_super_admin_can_open_control_center(): void
    {
        $this->actingAs($this->superAdmin(), 'admin')
            ->withSession(['otp.verified' => true])
            ->get(route('admin.control.index'))
            ->assertOk()
            ->assertSee('Super Admin Control')
            ->assertSee('Create Admin')
            ->assertSee('Role Builder');
    }

    public function test_regular_admin_cannot_open_control_center(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->withSession(['otp.verified' => true])
            ->get(route('admin.control.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_create_admin_with_roles_and_permissions(): void
    {
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin, 'admin')
            ->withSession(['otp.verified' => true])
            ->postJson(route('admin.control.admins.store'), [
                'current_password' => 'password',
                'name' => 'Finance Admin',
                'email' => 'finance.admin@example.test',
                'phone' => '08030000000',
                'password' => 'Password123!',
                'status' => 'active',
                'otp_enabled' => true,
                'roles' => ['admin'],
                'permissions' => ['payments.export'],
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Admin created.');

        $admin = Admin::where('email', 'finance.admin@example.test')->firstOrFail();

        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue($admin->hasPermissionTo('payments.export'));
        $this->assertDatabaseHas('admins', [
            'email' => 'finance.admin@example.test',
            'status' => 'active',
        ]);
        $this->assertTrue(AuditLog::where('event', 'admins.created')->where('auditable_id', $admin->id)->exists());
    }

    public function test_super_admin_can_create_role_and_assign_it_to_admin(): void
    {
        $superAdmin = $this->superAdmin();
        $admin = $this->admin();

        $this->actingAs($superAdmin, 'admin')
            ->withSession(['otp.verified' => true])
            ->postJson(route('admin.control.roles.store'), [
                'current_password' => 'password',
                'name' => 'ticket-auditor',
                'permissions' => ['tickets.view', 'audit.view'],
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Role created.');

        $this->assertTrue(Role::where('name', 'ticket-auditor')->firstOrFail()->hasPermissionTo('audit.view'));

        $this->actingAs($superAdmin, 'admin')
            ->withSession(['otp.verified' => true])
            ->putJson(route('admin.control.admins.update', $admin), [
                'current_password' => 'password',
                'name' => $admin->name,
                'email' => $admin->email,
                'phone' => $admin->phone,
                'status' => 'active',
                'otp_enabled' => true,
                'roles' => ['ticket-auditor'],
                'permissions' => [],
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Admin updated.');

        $this->assertTrue($admin->fresh()->hasRole('ticket-auditor'));
        $this->assertTrue($admin->fresh()->hasPermissionTo('audit.view'));
        $this->assertSame('active', $admin->fresh()->status);
    }

    public function test_admin_status_changes_immediately_block_login(): void
    {
        $superAdmin = $this->superAdmin();
        $admin = $this->admin();

        $this->actingAs($superAdmin, 'admin')
            ->withSession(['otp.verified' => true])
            ->putJson(route('admin.control.admins.update', $admin), [
                'current_password' => 'password',
                'name' => $admin->name,
                'email' => $admin->email,
                'phone' => $admin->phone,
                'status' => 'suspended',
                'otp_enabled' => true,
                'roles' => ['admin'],
                'permissions' => [],
            ])
            ->assertOk();

        $this->assertSame('suspended', $admin->fresh()->status);

        auth()->logout();
        $this->flushSession();

        $this->post(route('login.store', 'admin'), [
            'email' => $admin->email,
            'password' => 'password',
        ])
            ->assertSessionHasErrors('email');
    }

    public function test_audit_export_is_available_to_super_admin(): void
    {
        AuditLog::query()->create(['event' => 'phase.ten.test']);

        $this->actingAs($this->superAdmin(), 'admin')
            ->withSession(['otp.verified' => true])
            ->get(route('admin.control.audit.export'))
            ->assertOk()
            ->assertSee('phase.ten.test')
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    private function superAdmin(): Admin
    {
        return Admin::where('email', 'superadmin@coousiwes.test')->firstOrFail();
    }

    private function admin(): Admin
    {
        return Admin::where('email', 'admin@coousiwes.test')->firstOrFail();
    }
}
