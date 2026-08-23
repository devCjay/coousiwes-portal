<?php

namespace App\Support;

use App\Models\User;

class RoleRedirector
{
    public static function dashboardFor(User $user): string
    {
        return match (true) {
            $user->hasAnyRole(['super-admin', 'admin']) => route('admin.dashboard', absolute: false),
            $user->hasRole('supervisor') => route('supervisor.dashboard', absolute: false),
            default => route('student.dashboard', absolute: false),
        };
    }

    public static function roleSlugFor(User $user): string
    {
        return match (true) {
            $user->hasAnyRole(['super-admin', 'admin']) => 'admin',
            $user->hasRole('supervisor') => 'supervisor',
            default => 'student',
        };
    }
}
