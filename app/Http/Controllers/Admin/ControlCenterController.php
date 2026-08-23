<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Support\PortalPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ControlCenterController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless(PortalPermission::isRootAdmin($request->user()), 403);

        return view('pages.admin.control-center', [
            'admins' => Admin::query()
                ->with(['roles', 'permissions'])
                ->orderBy('name')
                ->get(),
            'roles' => Role::query()->with('permissions')->where('guard_name', 'web')->orderBy('name')->get(),
            'permissions' => Permission::query()->where('guard_name', 'web')->orderBy('name')->get()->groupBy(fn (Permission $permission): string => str($permission->name)->before('.')->toString()),
            'settings' => AppSetting::query()->orderBy('group')->orderBy('key')->get()->groupBy('group'),
            'auditLogs' => AuditLog::query()->with('user')->latest()->limit(20)->get(),
            'health' => [
                'queued_jobs' => DB::table('jobs')->count(),
                'failed_jobs' => DB::table('failed_jobs')->count(),
                'recent_webhooks' => Payment::query()->whereNotNull('webhook_event')->latest('updated_at')->limit(5)->get(),
                'last_payment' => Payment::query()->latest()->first(),
            ],
        ]);
    }
}
