@php
    $navigation = [
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'D'],
        ['label' => 'Generate List', 'href' => route('admin.generate-list.index'), 'icon' => 'file-text'],
        ['label' => 'Control', 'href' => route('admin.control.index'), 'icon' => 'C'],
        ['label' => 'Audit', 'href' => route('admin.control.audit.index'), 'active' => true, 'icon' => 'L'],
        ['label' => 'Reports', 'href' => route('admin.reports.index'), 'icon' => 'R'],
    ];
@endphp

<x-layouts.app-shell title="Audit Logs" role="Super Admin" :navigation="$navigation">
    <x-ui.card title="Audit Explorer" description="Filter and export system events for compliance review.">
        <form method="GET" action="{{ route('admin.control.audit.index') }}" data-ajax="false" class="mb-4 grid gap-3 md:grid-cols-[1fr_1fr_auto]">
            <x-ui.input label="Event" name="event" value="{{ request('event') }}" placeholder="admins.created" />
            <x-ui.input label="Actor" name="actor" value="{{ request('actor') }}" placeholder="admin@example.com" />
            <div class="flex items-end gap-2">
                <x-ui.button type="submit">Filter</x-ui.button>
                <x-ui.button :href="route('admin.control.audit.export', request()->query())" variant="secondary">Export</x-ui.button>
            </div>
        </form>
        <x-ui.input class="mb-4" label="Live Search" name="audit_search" placeholder="Search visible audit entries..." data-live-search="#audit-log-table tbody tr" />
        <x-ui.data-table
            id="audit-log-table"
            :headers="['Date', 'Actor', 'Event', 'Auditable', 'IP']"
            :rows="$auditLogs->getCollection()->map(fn ($log) => [
                e($log->created_at?->toDateTimeString() ?? ''),
                e($log->user?->email ?? 'system'),
                e($log->event),
                e(trim((string) $log->auditable_type.' #'.(string) $log->auditable_id)),
                e((string) $log->ip_address),
            ])->all()"
        />
        <div class="mt-4">
            {{ $auditLogs->links() }}
        </div>
    </x-ui.card>
</x-layouts.app-shell>
