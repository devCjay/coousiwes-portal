<?php

namespace App\Support;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Schema;

class MaintenanceMode
{
    public static function enabled(): bool
    {
        if (! Schema::hasTable('app_settings')) {
            return false;
        }

        return filter_var(AppSetting::value('system.maintenance_mode', false), FILTER_VALIDATE_BOOLEAN);
    }
}
