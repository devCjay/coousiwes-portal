<?php

namespace App\Support;

use Illuminate\Contracts\Auth\Authenticatable;

class RoleRedirector
{
    public static function dashboardFor(Authenticatable $user): string
    {
        return match (true) {
            $user->hasAnyRole(['super-admin', 'admin']) => route('admin.dashboard', absolute: false),
            $user->hasRole('supervisor') => route('supervisor.dashboard', absolute: false),
            default => route('student.dashboard', absolute: false),
        };
    }

    public static function roleSlugFor(Authenticatable $user): string
    {
        return match (true) {
            $user->hasAnyRole(['super-admin', 'admin']) => 'admin',
            $user->hasRole('supervisor') => 'supervisor',
            default => 'student',
        };
    }
}
