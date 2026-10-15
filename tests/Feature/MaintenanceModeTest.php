<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AppSetting;
use App\Models\User;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceModeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(AcademicStructureSeeder::class);
    }

    public function test_maintenance_mode_renders_a_blank_homepage(): void
    {
        $this->enableMaintenanceMode();

        $response = $this->get(route('home'));

        $response->assertOk();
        $this->assertSame('', trim($response->getContent()));
    }

    public function test_maintenance_mode_blocks_student_login(): void
    {
        $this->enableMaintenanceMode();

        $this->get(route('login.student'))->assertRedirect(route('home'));

        $this->post(route('login.store', 'student'), [
            'matric_no' => '2026/DEMO/001',
            'password' => '2026/DEMO/001',
        ])->assertRedirect(route('home'));

        $this->assertGuest('web');
    }

    public function test_maintenance_mode_logs_out_an_authenticated_student(): void
    {
        $this->enableMaintenanceMode();
        $student = User::where('email', 'student@coousiwes.test')->firstOrFail();

        $this->actingAs($student, 'web')
            ->get(route('student.dashboard'))
            ->assertRedirect(route('home'));

        $this->assertGuest('web');
    }

    public function test_staff_portals_remain_available_during_maintenance(): void
    {
        $this->enableMaintenanceMode();
        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    private function enableMaintenanceMode(): void
    {
        AppSetting::query()->updateOrCreate(
            ['key' => 'system.maintenance_mode'],
            [
                'group' => 'system',
                'value' => true,
                'type' => 'boolean',
                'is_public' => false,
            ],
        );
    }
}
