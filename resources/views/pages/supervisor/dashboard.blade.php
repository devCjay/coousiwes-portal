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
            <x-ui.input class="mb-4" label="Live Search" name="supervisor_dashboard_search" placeholder="Search by name, reg no, email, company..." data-live-search="#supervisor-dashboard-students [data-dashboard-student]" />
            <div id="supervisor-dashboard-students" class="space-y-3">
                @forelse ($assignments as $assignment)
                    @php
                        $student = $assignment->student;
                        $user = $student->user;
                        $placement = $student->placement;
                        $photoUrl = $user->profilePhotoUrl();
                        $initials = collect(explode(' ', trim($user->name)))
                            ->filter()
                            ->take(2)
                            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
                            ->join('') ?: 'ST';
                    @endphp
                    <article data-dashboard-student class="rounded-2xl border border-[var(--line)] bg-[var(--surface-muted)] p-4">
                        <div class="flex gap-3">
                            <span class="grid size-14 shrink-0 place-items-center overflow-hidden rounded-xl bg-brand-600 text-sm font-black text-white">
                                @if ($photoUrl)
                                    <img src="{{ $photoUrl }}" alt="{{ $user->name }} profile photo" class="h-full w-full object-cover">
                                @else
                                    {{ $initials }}
                                @endif
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="min-w-0">
                                        <p class="truncate font-extrabold text-[var(--text-strong)]">{{ $user->name }}</p>
                                        <p class="mt-1 text-xs font-semibold text-brand-700 dark:text-brand-200">{{ $student->matric_no }} / {{ $user->email ?: 'N/A' }} / {{ $user->phone ?: 'N/A' }}</p>
                                    </div>
                                    <span class="text-xs font-semibold text-[var(--text-soft)]">{{ $assignment->assigned_at->toDateString() }}</span>
                                </div>
                                <div class="mt-3 grid gap-2 text-xs sm:grid-cols-3">
                                    <p><span class="font-bold text-[var(--text-strong)]">Academic:</span> {{ $student->department?->name ?? 'N/A' }} / {{ $placement?->academicLevel?->name ?? $student->academicLevel?->name ?? 'N/A' }}</p>
                                    <p><span class="font-bold text-[var(--text-strong)]">Company:</span> {{ $placement?->company_name ?? 'N/A' }}</p>
                                    <p><span class="font-bold text-[var(--text-strong)]">Location:</span> {{ $placement ? (($placement->company_state ?: 'N/A').' / '.($placement->company_lga ?: 'N/A')) : 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    </article>
                @empty
                    <p class="text-sm text-[var(--text-soft)]">No assigned students yet.</p>
                @endforelse
            </div>
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
