@php
    $navigation = [
        ['label' => 'Dashboard', 'href' => route('supervisor.dashboard'), 'active' => true, 'icon' => 'D'],
        ['label' => 'Assigned Students', 'href' => route('supervisor.students.index'), 'icon' => 'S'],
        ['label' => 'Assessments', 'href' => route('supervisor.assessments.index'), 'icon' => 'A'],
    ];
@endphp

<x-layouts.app-shell title="Supervisor Dashboard" role="Supervisor" :navigation="$navigation">
    <div class="grid gap-4 md:grid-cols-3">
        <x-ui.stat-card label="Assigned Students" :value="$assignments->count()" meta="Active work queue" />
        <x-ui.stat-card label="Notifications" :value="$unreadNotifications->count()" meta="Unread alerts" tone="amber" />
        <x-ui.stat-card label="Feedback" value="Live" meta="Assessment workflow" tone="rose" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
        <x-ui.card id="students" title="Assigned Students" description="Only students currently assigned to you are shown.">
            <x-ui.input class="mb-4" label="Live Search" name="supervisor_dashboard_search" placeholder="Search assigned students..." data-live-search="#supervisor-dashboard-students tbody tr" />
            <x-ui.data-table
                id="supervisor-dashboard-students"
                :headers="['Student', 'Matric Number', 'Department', 'Level', 'Assigned']"
                :rows="$assignments->map(fn ($assignment) => [
                    e($assignment->student->user->name),
                    e($assignment->student->matric_no),
                    e($assignment->student->department?->name ?? 'N/A'),
                    e($assignment->student->academicLevel->name),
                    e($assignment->assigned_at->toDateString()),
                ])->all()"
            />
        </x-ui.card>

        <x-ui.card id="feedback" title="Notifications" description="Role-specific supervisor alerts.">
            <div class="space-y-3">
                @forelse ($unreadNotifications as $notification)
                    <div class="rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-4">
                        <p class="text-sm font-semibold">{{ $notification->data['title'] ?? 'Supervisor notification' }}</p>
                        <p class="mt-1 text-sm text-[var(--text-soft)]">{{ $notification->data['message'] ?? 'Open your queue for details.' }}</p>
                    </div>
                @empty
                    <p class="text-sm text-[var(--text-soft)]">No unread notifications.</p>
                @endforelse
            </div>
        </x-ui.card>
    </div>
</x-layouts.app-shell>
