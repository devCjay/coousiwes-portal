<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\MaintenanceModeNotification;
use Illuminate\Support\Facades\Log;

class MaintenanceNotificationService
{
    /**
     * Notify current supervisors and SIWES coordinators about a maintenance transition.
     *
     * @return array{sent: int, failed: int}
     */
    public function notifyStaff(bool $maintenanceEnabled): array
    {
        $sent = 0;
        $failed = 0;

        User::query()
            ->whereNotNull('email')
            ->where(function ($query): void {
                $query->whereHas('supervisor')->orWhereHas('siwesCoordinator');
            })
            ->get()
            ->each(function (User $user) use ($maintenanceEnabled, &$sent, &$failed): void {
                $portalUrl = $maintenanceEnabled
                    ? ($user->supervisor ? route('login.supervisor') : route('login.coordinator'))
                    : route('home');

                try {
                    $user->notify(new MaintenanceModeNotification($maintenanceEnabled, $portalUrl));
                    $sent++;
                } catch (\Throwable $exception) {
                    $failed++;
                    Log::error('Maintenance mode notification failed.', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'maintenance_enabled' => $maintenanceEnabled,
                        'error' => $exception->getMessage(),
                    ]);
                }
            });

        return compact('sent', 'failed');
    }
}
