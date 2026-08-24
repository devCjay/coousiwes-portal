<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Admin;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PhaseThreeAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_it_requires_authentication_before_protected_dashboards_are_available(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('supervisor.dashboard'))->assertRedirect(route('login'));
        $this->get(route('student.dashboard'))->assertRedirect(route('login'));
    }

    public function test_admin_login_redirects_directly_to_dashboard_without_otp(): void
    {
        $this->post(route('login.store', 'admin'), [
            'email' => 'admin@coousiwes.test',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();

        $this->assertTrue(AuditLog::where('event', 'auth.login_success')->where('auditable_type', null)->exists());
        $this->assertFalse(AuditLog::where('user_id', $admin->id)->where('event', 'otp.challenge_created')->exists());
    }

    public function test_ajax_login_returns_json_with_dashboard_redirect(): void
    {
        $this->postJson(route('login.store', 'admin'), [
            'email' => 'admin@coousiwes.test',
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJson([
                'message' => 'Signed in successfully.',
                'redirect' => route('admin.dashboard', absolute: false),
                'reload' => false,
            ]);
    }

    public function test_active_admin_record_can_enter_admin_portal_even_before_role_cache_refresh(): void
    {
        $admin = Admin::query()->create([
            'admin_code' => 'ADM-01000',
            'name' => 'Cache Safe Admin',
            'email' => 'cache.safe.admin@example.test',
            'password' => Hash::make('password'),
            'status' => 'active',
            'otp_enabled' => false,
            'email_verified_at' => now(),
        ]);

        $this->post(route('login.store', 'admin'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));
    }

    public function test_supervisor_and_student_portal_login_use_profile_tables_even_before_role_cache_refresh(): void
    {
        $supervisor = User::where('email', 'supervisor@coousiwes.test')->firstOrFail();
        $student = User::where('email', 'student@coousiwes.test')->firstOrFail();
        $supervisor->syncRoles([]);
        $student->syncRoles([]);

        $this->post(route('login.store', 'supervisor'), [
            'email' => 'supervisor@coousiwes.test',
            'password' => 'password',
        ])->assertRedirect(route('supervisor.dashboard'));

        auth()->logout();
        $this->flushSession();

        $this->post(route('login.store', 'student'), [
            'matric_no' => '2026/DEMO/001',
            'password' => '2026/DEMO/001',
        ])->assertRedirect(route('student.dashboard'));
    }

    public function test_permission_protected_admin_modules_use_database_permission_fallback(): void
    {
        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();
        $admin->givePermissionTo('students.view');

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->get(route('admin.students.index'))
            ->assertOk()
            ->assertSee('Student Management');
    }

    public function test_student_login_uses_matric_number_and_redirects_through_profile_setup_when_incomplete(): void
    {
        $this->post(route('login.store', 'student'), [
            'matric_no' => '2026/DEMO/001',
            'password' => '2026/DEMO/001',
        ])->assertRedirect(route('student.dashboard'));

        $this->get(route('student.dashboard'))->assertRedirect(route('student.profile.edit'));
    }

    public function test_xhr_student_login_with_matric_number_returns_dashboard_redirect_json(): void
    {
        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('login.store', 'student'), [
                'matric_no' => '2026/DEMO/001',
                'password' => '2026/DEMO/001',
            ])
            ->assertOk()
            ->assertJson([
                'message' => 'Signed in successfully.',
                'redirect' => route('student.dashboard', absolute: false),
                'reload' => false,
            ]);
    }

    public function test_it_blocks_users_from_logging_in_through_the_wrong_role_portal(): void
    {
        $this->post(route('login.store', 'admin'), [
            'email' => 'student@coousiwes.test',
            'password' => '2026/DEMO/001',
        ])->assertSessionHasErrors('email');
    }

    public function test_it_blocks_inactive_accounts_before_dashboard_access(): void
    {
        $user = User::where('email', 'student@coousiwes.test')->firstOrFail();
        $user->forceFill(['status' => 'suspended'])->save();

        $this->from(route('login.student'))->post(route('login.store', 'student'), [
            'matric_no' => '2026/DEMO/001',
            'password' => '2026/DEMO/001',
        ])->assertSessionHasErrors('matric_no');

        $this->assertGuest();
    }

    public function test_authenticated_users_are_redirected_from_login_to_their_dashboard(): void
    {
        $student = User::where('email', 'student@coousiwes.test')->firstOrFail();

        $this->actingAs($student)
            ->get(route('login.student'))
            ->assertRedirect(route('student.dashboard'));
    }

    public function test_it_prevents_authenticated_users_from_accessing_another_role_dashboard(): void
    {
        $student = User::where('email', 'student@coousiwes.test')->firstOrFail();

        $this->actingAs($student)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_authenticated_users_can_open_change_password_page(): void
    {
        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->get(route('account.password.edit'))
            ->assertOk()
            ->assertSee('Change Password')
            ->assertSee('Current Password')
            ->assertSee('New Password');
    }

    public function test_new_users_with_otp_enabled_flag_still_pass_directly_to_dashboard(): void
    {
        $admin = Admin::query()->create([
            'admin_code' => 'ADM-00999',
            'name' => 'Direct Admin',
            'email' => 'direct-admin@coousiwes.test',
            'password' => Hash::make('password'),
            'status' => 'active',
            'otp_enabled' => true,
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        $this->post(route('login.store', 'admin'), [
            'email' => 'direct-admin@coousiwes.test',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));
    }

    public function test_authenticated_admin_supervisor_and_student_can_change_password(): void
    {
        $users = [
            ['email' => 'admin@coousiwes.test', 'current' => 'password', 'model' => Admin::class, 'guard' => 'admin'],
            ['email' => 'supervisor@coousiwes.test', 'current' => 'password'],
            ['email' => 'student@coousiwes.test', 'current' => '2026/DEMO/001'],
        ];

        foreach ($users as $index => $credentials) {
            $model = $credentials['model'] ?? User::class;
            $guard = $credentials['guard'] ?? 'web';
            $user = $model::where('email', $credentials['email'])->firstOrFail();
            $newPassword = "NewPassword{$index}123!";

            $this->actingAs($user, $guard)
                ->putJson(route('account.password.update'), [
                    'current_password' => $credentials['current'],
                    'password' => $newPassword,
                    'password_confirmation' => $newPassword,
                ])
                ->assertOk()
                ->assertJsonPath('message', 'Password changed successfully.');

            $this->assertTrue(Hash::check($newPassword, $user->fresh()->password));
            $this->assertTrue(AuditLog::where('event', 'account.password_updated')->exists());
        }
    }

    public function test_change_password_requires_current_password(): void
    {
        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->putJson(route('account.password.update'), [
                'current_password' => 'wrong-password',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ])
            ->assertUnprocessable();

        $this->assertTrue(Hash::check('password', $admin->fresh()->password));
    }
}
