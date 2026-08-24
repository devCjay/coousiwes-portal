<?php

namespace App\Support;

class AdminNavigation
{
    /**
     * @param  array{label?: string, permission?: string}  $item
     */
    public static function canSee(array $item): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if (PortalPermission::isRootAdmin($user)) {
            return true;
        }

        $permission = $item['permission'] ?? self::permissionForLabel((string) ($item['label'] ?? ''));

        if ($permission === null) {
            return true;
        }

        return PortalPermission::userHas($user, $permission);
    }

    private static function permissionForLabel(string $label): ?string
    {
        return match ($label) {
            'Dashboard' => 'dashboard.view',
            'Generate List' => 'students.view',
            'Students' => 'students.view',
            'Bulk Upload' => 'students.import',
            'Tickets' => 'tickets.view',
            'Supervisors' => 'supervisors.view',
            'Payments' => 'payments.view',
            'Reports' => 'feedback.view',
            'Rubric' => 'settings.view',
            'Academics' => 'academics.manage',
            'Notices' => 'settings.view',
            'Settings' => 'settings.view',
            'Audit' => 'audit.view',
            'Control' => 'admins.manage',
            default => null,
        };
    }
}
