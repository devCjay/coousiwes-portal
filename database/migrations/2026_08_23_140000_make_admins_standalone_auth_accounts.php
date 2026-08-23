<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admins') || ! Schema::hasColumn('admins', 'user_id')) {
            return;
        }

        Schema::create('admins_standalone', function (Blueprint $table): void {
            $table->id();
            $table->string('admin_code', 40)->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 40)->nullable();
            $table->string('password');
            $table->string('status', 30)->default('active');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->boolean('otp_enabled')->default(false);
            $table->rememberToken();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'created_at']);
        });

        $oldAdmins = DB::table('admins')
            ->leftJoin('users', 'users.id', '=', 'admins.user_id')
            ->select(
                'admins.id as old_admin_id',
                'admins.admin_code',
                'admins.status as admin_status',
                'admins.metadata as admin_metadata',
                'admins.created_at as admin_created_at',
                'admins.updated_at as admin_updated_at',
                'admins.deleted_at as admin_deleted_at',
                'users.id as user_id',
                'users.name',
                'users.email',
                'users.phone',
                'users.password',
                'users.email_verified_at',
                'users.last_login_at',
                'users.remember_token',
                'users.created_at as user_created_at'
            )
            ->orderBy('admins.id')
            ->get();

        foreach ($oldAdmins as $oldAdmin) {
            if (! $oldAdmin->email) {
                continue;
            }

            DB::table('admins_standalone')->insert([
                'id' => $oldAdmin->old_admin_id,
                'admin_code' => $oldAdmin->admin_code,
                'name' => $oldAdmin->name,
                'email' => $oldAdmin->email,
                'phone' => $oldAdmin->phone,
                'password' => $oldAdmin->password,
                'status' => $oldAdmin->admin_status ?: 'active',
                'email_verified_at' => $oldAdmin->email_verified_at,
                'last_login_at' => $oldAdmin->last_login_at,
                'otp_enabled' => false,
                'remember_token' => $oldAdmin->remember_token,
                'metadata' => $oldAdmin->admin_metadata,
                'created_at' => $oldAdmin->admin_created_at ?? $oldAdmin->user_created_at ?? now(),
                'updated_at' => $oldAdmin->admin_updated_at ?? now(),
                'deleted_at' => $oldAdmin->admin_deleted_at,
            ]);

            DB::table('model_has_roles')
                ->where('model_type', 'App\\Models\\User')
                ->where('model_id', $oldAdmin->user_id)
                ->get()
                ->each(fn (object $role): bool => DB::table('model_has_roles')->insertOrIgnore([
                    'role_id' => $role->role_id,
                    'model_type' => 'App\\Models\\Admin',
                    'model_id' => $oldAdmin->old_admin_id,
                ]));

            DB::table('model_has_permissions')
                ->where('model_type', 'App\\Models\\User')
                ->where('model_id', $oldAdmin->user_id)
                ->get()
                ->each(fn (object $permission): bool => DB::table('model_has_permissions')->insertOrIgnore([
                    'permission_id' => $permission->permission_id,
                    'model_type' => 'App\\Models\\Admin',
                    'model_id' => $oldAdmin->old_admin_id,
                ]));
        }

        Schema::drop('admins');
        Schema::rename('admins_standalone', 'admins');

        foreach ($oldAdmins as $oldAdmin) {
            if (! $oldAdmin->user_id) {
                continue;
            }

            DB::table('model_has_roles')->where('model_type', 'App\\Models\\User')->where('model_id', $oldAdmin->user_id)->delete();
            DB::table('model_has_permissions')->where('model_type', 'App\\Models\\User')->where('model_id', $oldAdmin->user_id)->delete();
            DB::table('users')->where('id', $oldAdmin->user_id)->delete();
        }
    }

    public function down(): void
    {
        // This migration intentionally does not recreate admin users in the users table.
    }
};
