<?php

namespace App\Support;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class OtpRequirement
{
    public static function requiredFor(?Authenticatable $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user instanceof Admin) {
            return true;
        }

        if ($user instanceof User && $user->supervisor()->exists()) {
            return true;
        }

        return (bool) ($user->otp_enabled ?? false);
    }
}
