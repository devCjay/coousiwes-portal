<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class PhaseTwelveSecurityPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('4|notifications');
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(AcademicStructureSeeder::class);
    }

    public function test_security_headers_are_applied_to_web_responses(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin')
            ->assertHeader('Cross-Origin-Resource-Policy', 'same-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()')
            ->assertHeader('Content-Security-Policy');
    }

    public function test_notification_summary_is_rate_limited(): void
    {
        $student = User::where('email', 'student@coousiwes.test')->firstOrFail();

        for ($attempt = 1; $attempt <= 60; $attempt++) {
            $this->actingAs($student)
                ->withSession(['otp.verified' => true])
                ->getJson(route('notifications.summary'))
                ->assertOk();
        }

        $this->actingAs($student)
            ->withSession(['otp.verified' => true])
            ->getJson(route('notifications.summary'))
            ->assertTooManyRequests();
    }

    public function test_health_check_command_passes_for_ready_application(): void
    {
        $this->artisan('siwes:health-check --json')
            ->expectsOutputToContain('"status": "ok"')
            ->assertExitCode(0);
    }

    public function test_sqlite_backup_command_creates_timestamped_backup(): void
    {
        $source = storage_path('framework/testing-phase12.sqlite');
        $targetDirectory = storage_path('framework/testing-backups');

        File::put($source, 'sqlite-backup-test');
        File::deleteDirectory($targetDirectory);

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => $source,
        ]);

        $this->artisan('siwes:backup-sqlite', ['--path' => $targetDirectory])
            ->expectsOutputToContain('SQLite backup created:')
            ->assertExitCode(0);

        $this->assertCount(1, File::files($targetDirectory));

        File::delete($source);
        File::deleteDirectory($targetDirectory);
    }
}
