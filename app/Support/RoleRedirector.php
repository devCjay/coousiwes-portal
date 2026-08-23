<?php

namespace App\Support;

use App\Models\Admin;
use Illuminate\Contracts\Auth\Authenticatable;

class RoleRedirector
{
    public static function dashboardFor(Authenticatable $user): string
    {
        return match (true) {
            $user instanceof Admin => route('admin.dashboard', absolute: false),
            $user->hasAnyRole(['super-admin', 'admin']) => route('admin.dashboard', absolute: false),
            method_exists($user, 'supervisor') && $user->supervisor()->exists() => route('supervisor.dashboard', absolute: false),
            default => route('student.dashboard', absolute: false),
        };
    }

    public static function roleSlugFor(Authenticatable $user): string
    {
        return match (true) {
            $user instanceof Admin => 'admin',
            $user->hasAnyRole(['super-admin', 'admin']) => 'admin',
            method_exists($user, 'supervisor') && $user->supervisor()->exists() => 'supervisor',
            default => 'student',
        };
    }
}
