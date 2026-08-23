<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table): void {
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

        $adminUsers = DB::table('users')
            ->join('model_has_roles', function ($join): void {
                $join->on('model_has_roles.model_id', '=', 'users.id')
                    ->where('model_has_roles.model_type', '=', 'App\\Models\\User');
            })
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->whereIn('roles.name', ['super-admin', 'admin'])
            ->select('users.*')
            ->distinct()
            ->orderBy('users.id')
            ->get();

        $adminUsers->each(function (object $user): void {
                DB::table('admins')->updateOrInsert(
                    ['email' => $user->email],
                    [
                        'admin_code' => 'ADM-'.str_pad((string) $user->id, 5, '0', STR_PAD_LEFT),
                        'name' => $user->name,
                        'phone' => $user->phone,
                        'password' => $user->password,
                        'status' => $user->status ?: 'active',
                        'email_verified_at' => $user->email_verified_at,
                        'last_login_at' => $user->last_login_at,
                        'otp_enabled' => false,
                        'remember_token' => $user->remember_token,
                        'metadata' => null,
                        'created_at' => $user->created_at ?? now(),
                        'updated_at' => now(),
                    ],
                );
            });

        $adminUsers->each(function (object $user): void {
            $adminId = DB::table('admins')->where('email', $user->email)->value('id');

            DB::table('model_has_roles')
                ->where('model_type', 'App\\Models\\User')
                ->where('model_id', $user->id)
                ->get()
                ->each(fn (object $role): bool => DB::table('model_has_roles')->insertOrIgnore([
                    'role_id' => $role->role_id,
                    'model_type' => 'App\\Models\\Admin',
                    'model_id' => $adminId,
                ]));

            DB::table('model_has_permissions')
                ->where('model_type', 'App\\Models\\User')
                ->where('model_id', $user->id)
                ->get()
                ->each(fn (object $permission): bool => DB::table('model_has_permissions')->insertOrIgnore([
                    'permission_id' => $permission->permission_id,
                    'model_type' => 'App\\Models\\Admin',
                    'model_id' => $adminId,
                ]));

            DB::table('model_has_roles')->where('model_type', 'App\\Models\\User')->where('model_id', $user->id)->delete();
            DB::table('model_has_permissions')->where('model_type', 'App\\Models\\User')->where('model_id', $user->id)->delete();
            DB::table('users')->where('id', $user->id)->delete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
