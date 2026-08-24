<?php

use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Artisan;
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
        ->and(Role::findByName('student')->hasPermissionTo('payments.view'))->toBeTrue();
});
