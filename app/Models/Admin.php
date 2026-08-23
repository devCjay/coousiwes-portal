<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property string $name
 * @property string $email
 * @property string|null $phone
 */
#[Fillable(['admin_code', 'name', 'email', 'phone', 'password', 'status', 'otp_enabled', 'metadata', 'email_verified_at', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class Admin extends Authenticatable
{
    use HasRoles, Notifiable, SoftDeletes;

    protected string $guard_name = 'web';

    public const string STATUS_ACTIVE = 'active';

    public const string STATUS_INACTIVE = 'inactive';

    public const string STATUS_SUSPENDED = 'suspended';

    #[\Override]
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'otp_enabled' => 'boolean',
            'metadata' => 'array',
            'password' => 'hashed',
        ];
    }
}
