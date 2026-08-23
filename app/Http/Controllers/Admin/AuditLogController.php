<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('audit.view'), 403);

        return view('pages.admin.audit-logs', [
            'auditLogs' => $this->query($request)->paginate(30)->withQueryString(),
        ]);
    }

    public function export(Request $request): Response
    {
        abort_unless($request->user()?->can('audit.view'), 403);

        $handle = fopen('php://temp', 'w+');
        fputcsv($handle, ['Date', 'Actor', 'Event', 'Auditable', 'IP Address', 'Metadata']);

        $this->query($request)->each(function (AuditLog $log) use ($handle): void {
            $actor = $log->user instanceof User ? $log->user->email : 'system';

            fputcsv($handle, [
                $log->created_at?->toDateTimeString() ?? '',
                $actor,
                $log->event,
                trim((string) $log->auditable_type.' #'.(string) $log->auditable_id),
                $log->ip_address,
                json_encode($log->metadata),
            ]);
        });

        rewind($handle);

        return response((string) stream_get_contents($handle), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=audit-logs.csv',
        ]);
    }

    private function query(Request $request)
    {
        return AuditLog::query()
            ->with('user')
            ->when($request->filled('event'), fn ($query) => $query->where('event', 'like', '%'.$request->string('event')->toString().'%'))
            ->when($request->filled('actor'), fn ($query) => $query->whereHas('user', fn ($userQuery) => $userQuery->where('email', 'like', '%'.$request->string('actor')->toString().'%')))
            ->latest();
    }
}
