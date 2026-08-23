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
        ->and(Permission::where('name', 'admins.manage')->exists())->toBeTrue()
        ->and(Permission::where('name', 'payments.view')->exists())->toBeTrue();

    expect(Role::findByName('super-admin')->hasPermissionTo('roles.manage'))->toBeTrue()
        ->and(Role::findByName('admin')->hasPermissionTo('students.import'))->toBeTrue()
        ->and(Role::findByName('supervisor')->hasPermissionTo('feedback.manage'))->toBeTrue()
        ->and(Role::findByName('student')->hasPermissionTo('payments.view'))->toBeTrue();
});
