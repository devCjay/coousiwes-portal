<?php

namespace Database\Seeders;

use App\Models\AcademicLevel;
use App\Models\AcademicSession;
use App\Models\Admin;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    private const string GUARD = 'web';

    /**
     * Seed the baseline SIWES roles and permissions.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',
            'students.view',
            'students.create',
            'students.update',
            'students.suspend',
            'students.import',
            'students.export',
            'tickets.view',
            'tickets.generate',
            'tickets.revoke',
            'supervisors.view',
            'supervisors.create',
            'supervisors.update',
            'supervisors.suspend',
            'supervisors.assign',
            'feedback.view',
            'feedback.manage',
            'payments.view',
            'payments.export',
            'academics.manage',
            'settings.view',
            'settings.update',
            'admins.manage',
            'roles.manage',
            'audit.view',
            'notifications.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, self::GUARD);
        }

        $permissionModels = fn (array $names) => Permission::query()
            ->where('guard_name', self::GUARD)
            ->whereIn('name', $names)
            ->get();

        $superAdmin = Role::findOrCreate('super-admin', self::GUARD);
        $admin = Role::findOrCreate('admin', self::GUARD);
        $supervisor = Role::findOrCreate('supervisor', self::GUARD);
        $student = Role::findOrCreate('student', self::GUARD);

        $superAdmin->syncPermissions($permissionModels($permissions));

        $admin->syncPermissions($permissionModels([
            'dashboard.view',
            'students.view',
            'students.create',
            'students.update',
            'students.suspend',
            'students.import',
            'students.export',
            'tickets.view',
            'tickets.generate',
            'supervisors.view',
            'supervisors.create',
            'supervisors.update',
            'supervisors.assign',
            'feedback.view',
            'payments.view',
            'payments.export',
            'academics.manage',
            'settings.view',
        ]));

        $supervisor->syncPermissions($permissionModels([
            'dashboard.view',
            'students.view',
            'feedback.view',
            'feedback.manage',
        ]));

        $student->syncPermissions($permissionModels([
            'dashboard.view',
            'feedback.view',
            'payments.view',
        ]));

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->seedDemoUsers();
    }

    private function seedDemoUsers(): void
    {
        $users = [
            ['name' => 'Super Admin', 'email' => 'superadmin@coousiwes.test', 'role' => 'super-admin'],
            ['name' => 'SIWES Admin', 'email' => 'admin@coousiwes.test', 'role' => 'admin'],
            ['name' => 'Demo Supervisor', 'email' => 'supervisor@coousiwes.test', 'role' => 'supervisor'],
            ['name' => 'Demo Student', 'email' => 'student@coousiwes.test', 'role' => 'student', 'password' => '2026/DEMO/001'],
        ];

        foreach ($users as $userData) {
            if (in_array($userData['role'], ['super-admin', 'admin'], true)) {
                $admin = Admin::updateOrCreate(
                    ['email' => $userData['email']],
                    [
                        'admin_code' => $userData['role'] === 'super-admin' ? 'ADM-00001' : 'ADM-00002',
                        'name' => $userData['name'],
                        'password' => Hash::make($userData['password'] ?? 'password'),
                        'status' => 'active',
                        'otp_enabled' => false,
                        'email_verified_at' => now(),
                    ],
                );

                $admin->assignRole($userData['role']);

                continue;
            }

            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make($userData['password'] ?? 'password'),
                    'status' => 'active',
                    'otp_enabled' => false,
                    'email_verified_at' => now(),
                ],
            );

            $user->assignRole($userData['role']);

            if ($userData['role'] === 'supervisor') {
                Supervisor::query()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'staff_no' => 'SUP-0001',
                        'organization' => 'COOU SIWES Unit',
                        'department' => 'Industrial Training',
                        'status' => 'active',
                    ],
                );
            }

            if ($userData['role'] === 'student') {
                $faculty = Faculty::query()->updateOrCreate(
                    ['code' => 'AGRIC'],
                    ['name' => 'Faculty of Agricultural Science', 'is_active' => true],
                );
                $department = Department::query()->updateOrCreate(
                    ['faculty_id' => $faculty->id, 'code' => 'AGE'],
                    ['name' => 'Agric Economics & Extension', 'is_active' => true],
                );
                $course = Course::query()->updateOrCreate(
                    ['department_id' => $department->id, 'code' => 'BSC-AGE'],
                    ['name' => 'Agric Economics & Extension', 'duration_years' => 4, 'is_active' => true],
                );
                $level = AcademicLevel::query()->updateOrCreate(
                    ['level' => 300],
                    ['name' => '300L', 'is_active' => true],
                );
                $session = AcademicSession::query()->updateOrCreate(
                    ['name' => '2026/2027'],
                    ['starts_on' => '2026-09-01', 'ends_on' => '2027-08-31', 'is_active' => true],
                );

                Student::query()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'matric_no' => '2026/DEMO/001',
                        'faculty_id' => $faculty->id,
                        'department_id' => $department->id,
                        'course_id' => $course->id,
                        'academic_level_id' => $level->id,
                        'academic_session_id' => $session->id,
                        'activation_status' => 'active',
                    ],
                );
            }
        }
    }
}
