<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\StudentPlacement;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Admin;
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
        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();

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
}


