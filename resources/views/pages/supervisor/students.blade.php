@php
    $navigation = [
        ['label' => 'Dashboard', 'href' => route('supervisor.dashboard'), 'icon' => 'D'],
        ['label' => 'Assigned Students', 'href' => route('supervisor.students.index'), 'active' => true, 'icon' => 'S'],
        ['label' => 'Assessments', 'href' => route('supervisor.assessments.index'), 'icon' => 'A'],
    ];
@endphp

<x-layouts.app-shell title="Assigned Students" role="Supervisor" :navigation="$navigation">
    <div class="grid gap-4 md:grid-cols-2">
        <x-ui.stat-card label="Assigned Students" :value="$assignments->count()" meta="Active assignments" />
        <x-ui.stat-card label="Assessment Queue" :value="$assignments->count()" meta="Students available for review" tone="cyan" />
    </div>

    <x-ui.card class="mt-6" title="Student Work Queue" description="Only actively assigned students appear here.">
        <x-ui.input class="mb-4" label="Live Search" name="assigned_search" placeholder="Search by name, reg no, email, department, company, state..." data-live-search="#assigned-students-list [data-assigned-student]" />

        <div id="assigned-students-list" class="grid gap-4">
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
                <article data-assigned-student class="rounded-2xl border border-[var(--line)] bg-[var(--surface-raised)] p-4 shadow-[0_16px_42px_rgb(8_15_12_/_0.06)]">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="flex min-w-0 gap-3">
                            <span class="grid size-16 shrink-0 place-items-center overflow-hidden rounded-2xl bg-brand-600 text-lg font-black text-white">
                                @if ($photoUrl)
                                    <img src="{{ $photoUrl }}" alt="{{ $user->name }} profile photo" class="h-full w-full object-cover">
                                @else
                                    {{ $initials }}
                                @endif
                            </span>
                            <div class="min-w-0">
                                <h3 class="truncate text-base font-extrabold text-[var(--text-strong)]">{{ $user->name }}</h3>
                                <p class="mt-1 text-sm font-semibold text-brand-700 dark:text-brand-200">{{ $student->matric_no }}</p>
                                <p class="mt-1 text-xs text-[var(--text-soft)]">Assigned {{ $assignment->assigned_at->toDayDateTimeString() }}</p>
                            </div>
                        </div>
                        <span class="inline-flex w-fit rounded-full bg-brand-600/10 px-3 py-1 text-xs font-bold text-brand-700 dark:text-brand-200">Active assignment</span>
                    </div>

                    <div class="mt-4 grid gap-4 lg:grid-cols-3">
                        <div class="rounded-xl border border-[var(--line)] bg-[var(--surface-muted)] p-4">
                            <p class="text-xs font-extrabold uppercase text-[var(--text-soft)]">Contact</p>
                            <dl class="mt-3 space-y-2 text-sm">
                                <div><dt class="text-[var(--text-soft)]">Email</dt><dd class="font-semibold text-[var(--text-strong)]">{{ $user->email ?: 'N/A' }}</dd></div>
                                <div><dt class="text-[var(--text-soft)]">Phone</dt><dd class="font-semibold text-[var(--text-strong)]">{{ $user->phone ?: 'N/A' }}</dd></div>
                                <div><dt class="text-[var(--text-soft)]">Address</dt><dd class="font-semibold text-[var(--text-strong)]">{{ $student->address ?: 'N/A' }}</dd></div>
                            </dl>
                        </div>

                        <div class="rounded-xl border border-[var(--line)] bg-[var(--surface-muted)] p-4">
                            <p class="text-xs font-extrabold uppercase text-[var(--text-soft)]">Academic</p>
                            <dl class="mt-3 space-y-2 text-sm">
                                <div><dt class="text-[var(--text-soft)]">Faculty</dt><dd class="font-semibold text-[var(--text-strong)]">{{ $student->faculty?->name ?? 'N/A' }}</dd></div>
                                <div><dt class="text-[var(--text-soft)]">Department</dt><dd class="font-semibold text-[var(--text-strong)]">{{ $student->department?->name ?? 'N/A' }}</dd></div>
                                <div><dt class="text-[var(--text-soft)]">Level / Session</dt><dd class="font-semibold text-[var(--text-strong)]">{{ $placement?->academicLevel?->name ?? $student->academicLevel?->name ?? 'N/A' }} / {{ $placement?->academicSession?->name ?? $student->academicSession?->name ?? 'N/A' }}</dd></div>
                            </dl>
                        </div>

                        <div class="rounded-xl border border-[var(--line)] bg-[var(--surface-muted)] p-4">
                            <p class="text-xs font-extrabold uppercase text-[var(--text-soft)]">Placement</p>
                            <dl class="mt-3 space-y-2 text-sm">
                                <div><dt class="text-[var(--text-soft)]">Company</dt><dd class="font-semibold text-[var(--text-strong)]">{{ $placement?->company_name ?? 'N/A' }}</dd></div>
                                <div><dt class="text-[var(--text-soft)]">Location</dt><dd class="font-semibold text-[var(--text-strong)]">{{ $placement ? trim(($placement->company_state ?: 'N/A').' / '.($placement->company_lga ?: 'N/A')) : 'N/A' }}</dd></div>
                                <div><dt class="text-[var(--text-soft)]">Supervisor Phone</dt><dd class="font-semibold text-[var(--text-strong)]">{{ $placement?->company_supervisor_phone ?: 'N/A' }}</dd></div>
                            </dl>
                        </div>
                    </div>
                </article>
            @empty
                <p class="rounded-xl border border-[var(--line)] bg-[var(--surface-muted)] p-5 text-sm text-[var(--text-soft)]">No assigned students yet.</p>
            @endforelse
        </div>
    </x-ui.card>
</x-layouts.app-shell>
