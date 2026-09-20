<?php

use App\Models\AcademicLevel;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('seeds the baseline portal roles and permissions', function () {
    Artisan::call('db:seed', ['--class' => RoleAndPermissionSeeder::class]);

    expect(Role::where('name', 'super-admin')->exists())->toBeTrue()
        ->and(Role::where('name', 'admin')->exists())->toBeTrue()
        ->and(Role::where('name', 'supervisor')->exists())->toBeTrue()
        ->and(Role::where('name', 'student')->exists())->toBeTrue()
        ->and(Role::where('name', 'student-manager')->exists())->toBeTrue()
        ->and(Role::where('name', 'generate-list-manager')->exists())->toBeTrue()
        ->and(Role::where('name', 'ticket-manager')->exists())->toBeTrue()
        ->and(Role::where('name', 'supervisor-manager')->exists())->toBeTrue()
        ->and(Role::where('name', 'payment-manager')->exists())->toBeTrue()
        ->and(Role::where('name', 'academic-manager')->exists())->toBeTrue()
        ->and(Role::where('name', 'report-manager')->exists())->toBeTrue()
        ->and(Role::where('name', 'settings-manager')->exists())->toBeTrue()
        ->and(Permission::where('name', 'admins.manage')->exists())->toBeTrue()
        ->and(Permission::where('name', 'payments.view')->exists())->toBeTrue();

    expect(Role::findByName('super-admin')->hasPermissionTo('roles.manage'))->toBeTrue()
        ->and(Role::findByName('admin')->hasPermissionTo('dashboard.view'))->toBeTrue()
        ->and(Role::findByName('admin')->hasPermissionTo('students.import'))->toBeFalse()
        ->and(Role::findByName('student-manager')->hasPermissionTo('students.view'))->toBeTrue()
        ->and(Role::findByName('student-manager')->hasPermissionTo('students.update'))->toBeFalse()
        ->and(Role::findByName('generate-list-manager')->hasPermissionTo('generate-list.view'))->toBeTrue()
        ->and(Role::findByName('generate-list-manager')->hasPermissionTo('generate-list.export'))->toBeFalse()
        ->and(Role::findByName('ticket-manager')->hasPermissionTo('tickets.view'))->toBeTrue()
        ->and(Role::findByName('ticket-manager')->hasPermissionTo('tickets.revoke'))->toBeFalse()
        ->and(Role::findByName('supervisor-manager')->hasPermissionTo('supervisors.view'))->toBeTrue()
        ->and(Role::findByName('supervisor-manager')->hasPermissionTo('supervisors.assign'))->toBeFalse()
        ->and(Role::findByName('payment-manager')->hasPermissionTo('payments.view'))->toBeTrue()
        ->and(Role::findByName('payment-manager')->hasPermissionTo('payments.export'))->toBeFalse()
        ->and(Role::findByName('academic-manager')->hasPermissionTo('academics.manage'))->toBeTrue()
        ->and(Role::findByName('report-manager')->hasPermissionTo('feedback.view'))->toBeTrue()
        ->and(Role::findByName('settings-manager')->hasPermissionTo('settings.view'))->toBeTrue()
        ->and(Role::findByName('settings-manager')->hasPermissionTo('settings.update'))->toBeFalse()
        ->and(Role::findByName('supervisor')->hasPermissionTo('feedback.manage'))->toBeTrue()
        ->and(Role::findByName('student')->hasPermissionTo('payments.view'))->toBeTrue()
        ->and(Role::findByName('student')->hasPermissionTo('feedback.view'))->toBeFalse();
});

it('reuses the demo student matric number when rerunning seeders', function () {
    $faculty = Faculty::query()->create(['name' => 'Legacy Faculty', 'code' => 'LEG', 'is_active' => true]);
    $department = Department::query()->create(['faculty_id' => $faculty->id, 'name' => 'Legacy Department', 'code' => 'LED', 'is_active' => true]);
    $course = Course::query()->create(['department_id' => $department->id, 'name' => 'Legacy Course', 'code' => 'LEG-COURSE', 'duration_years' => 4, 'is_active' => true]);
    $level = AcademicLevel::query()->create(['name' => '300L', 'level' => 300, 'is_active' => true]);
    $session = AcademicSession::query()->create(['name' => '2026/2027', 'starts_on' => '2026-09-01', 'ends_on' => '2027-08-31', 'is_active' => true]);
    $legacyUser = User::query()->create([
        'name' => 'Legacy Demo Student',
        'email' => 'legacy-demo@example.test',
        'password' => Hash::make('password'),
        'status' => 'active',
        'otp_enabled' => false,
        'email_verified_at' => now(),
    ]);

    Student::query()->create([
        'user_id' => $legacyUser->id,
        'matric_no' => '2026/DEMO/001',
        'faculty_id' => $faculty->id,
        'department_id' => $department->id,
        'course_id' => $course->id,
        'academic_level_id' => $level->id,
        'academic_session_id' => $session->id,
        'activation_status' => 'active',
    ]);

    Artisan::call('db:seed', ['--class' => RoleAndPermissionSeeder::class]);

    expect(Student::query()->where('matric_no', '2026/DEMO/001')->count())->toBe(1)
        ->and(Student::query()->where('matric_no', '2026/DEMO/001')->first()->user->email)->toBe('student@coousiwes.test');
});
