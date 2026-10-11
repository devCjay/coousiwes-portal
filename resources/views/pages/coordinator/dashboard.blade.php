@php
    $navigation = [
        ['label' => 'Dashboard', 'href' => route('coordinator.dashboard'), 'active' => true, 'icon' => 'D'],
        ['label' => 'Students', 'href' => route('coordinator.students.index'), 'icon' => 'S'],
        ['label' => 'Download Logbook Gradesheet', 'href' => route('coordinator.logbook-grade-sheet.index'), 'icon' => 'clipboard-check'],
        ['label' => 'Change Password', 'href' => route('account.password.edit'), 'icon' => 'key'],
    ];
@endphp

<x-layouts.app-shell title="Coordinator Dashboard" role="SIWES Coordinator" :navigation="$navigation">
    <div class="rounded-3xl bg-brand-900 p-6 text-white shadow-[0_22px_54px_rgb(8_15_12_/_0.18)]">
        <p class="text-sm font-bold uppercase text-brand-100">Department Workspace</p>
        <h1 class="mt-3 text-2xl font-black tracking-normal sm:text-3xl">{{ $coordinator->department?->name ?? 'Assigned Department' }}</h1>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-white/80">{{ $coordinator->faculty?->name ?? 'Faculty not assigned' }} / {{ $coordinator->rank }}</p>
    </div>

    <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card label="Department Students" :value="number_format($summary['students'])" meta="Assigned department only" />
        <x-ui.stat-card label="Students With Placement" :value="number_format($summary['placed_students'])" meta="Placement records submitted" tone="cyan" />
        <x-ui.stat-card label="Submitted Assessments" :value="number_format($summary['submitted_assessments'])" meta="Logbook scores available" tone="amber" />
        <x-ui.stat-card label="Notifications" :value="number_format($summary['notifications'])" meta="Unread coordinator alerts" tone="rose" />
    </div>

    <x-ui.card class="mt-6" title="SIWES Coordinator Notifications" description="Department-specific updates and system messages.">
        <div class="space-y-3">
            @forelse ($notifications as $notification)
                <div class="rounded-xl border border-[var(--line)] bg-[var(--surface-muted)] p-4">
                    <p class="text-sm font-bold text-[var(--text-strong)]">{{ $notification->data['title'] ?? 'Coordinator notification' }}</p>
                    <p class="mt-1 text-sm leading-6 text-[var(--text-soft)]">{{ $notification->data['message'] ?? 'Open your coordinator workspace for details.' }}</p>
                </div>
            @empty
                <p class="rounded-xl border border-[var(--line)] bg-[var(--surface-muted)] p-4 text-sm text-[var(--text-soft)]">No unread notifications.</p>
            @endforelse
        </div>
    </x-ui.card>
</x-layouts.app-shell>
