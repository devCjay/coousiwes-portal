@php
    $navigation = [
        ['label' => 'Dashboard', 'href' => route('supervisor.dashboard'), 'icon' => 'D'],
        ['label' => 'Assigned Students', 'href' => route('supervisor.students.index'), 'active' => true, 'icon' => 'S'],
        ['label' => 'Grade Log Book', 'href' => route('supervisor.assessments.index'), 'icon' => 'A'],
    ];
@endphp

<x-layouts.app-shell title="Assigned Students" role="Supervisor" :navigation="$navigation">
    <style>
        .student-details-toggle:checked ~ .student-details-control .student-details-show { display: none; }
        .student-details-toggle:checked ~ .student-details-control .student-details-hide { display: inline; }
        .student-details-toggle:checked ~ .student-details-control .student-details-chevron { transform: rotate(180deg); }
    </style>

    <div class="grid gap-4 md:grid-cols-2">
        <x-ui.stat-card label="Assigned Students" :value="$assignments->total()" meta="Active assignments" />
        <x-ui.stat-card label="Assessment Queue" :value="$assignments->total()" meta="Students available for review" tone="cyan" />
    </div>

    <x-ui.card class="mt-6" title="Student Work Queue" description="Only actively assigned students appear here. Showing 20 records per page.">
        <x-ui.input class="mb-4" label="Live Search" name="assigned_search" placeholder="Search current page by name, reg no, email, department, company, state..." data-live-search="#assigned-students-list [data-assigned-student]" />

        <div id="assigned-students-list" class="grid gap-4">
            @forelse ($assignments as $assignment)
                @php
                    $student = $assignment->student;
                    $user = $student->user;
                    $placement = $student->placement;
                    $assessment = $assignment->assessment;
                    $photoUrl = $user->profilePhotoUrl();
                    $initials = collect(explode(' ', trim($user->name)))
                        ->filter()
                        ->take(2)
                        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
                        ->join('') ?: 'ST';
                    $detailsId = 'assigned-student-details-'.$assignment->id;
                    $gradeModalId = 'grade-student-'.$assignment->id;
                @endphp
                <article data-assigned-student class="rounded-2xl border border-[var(--line)] bg-[var(--surface-raised)] p-4 shadow-[0_16px_42px_rgb(8_15_12_/_0.06)]">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="flex min-w-0 gap-3">
                            <span class="relative grid size-16 shrink-0 place-items-center overflow-hidden rounded-2xl bg-brand-600 text-lg font-black text-white">
                                @if ($photoUrl)
                                    <img src="{{ $photoUrl }}" alt="{{ $user->name }} profile photo" class="h-full w-full object-cover">
                                    <button type="button" data-modal-target="#assigned-student-photo-{{ $assignment->id }}" class="absolute inset-x-1 bottom-1 inline-flex items-center justify-center gap-1 rounded-md bg-graphite-950/75 px-1.5 py-0.5 text-[0.6rem] font-bold text-white shadow-lg backdrop-blur-sm">
                                        <x-ui.icon name="eye" class="size-3" />
                                        View
                                    </button>
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

                        <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                            <span class="inline-flex w-fit rounded-full bg-brand-600/10 px-3 py-1 text-xs font-bold text-brand-700 dark:text-brand-200">Active assignment</span>
                            @if ($assessment)
                                <x-ui.button type="button" variant="secondary" class="px-3 py-2 text-xs opacity-50" disabled>Graded</x-ui.button>
                            @else
                                <x-ui.button type="button" class="px-3 py-2 text-xs" data-modal-target="#{{ $gradeModalId }}">Grade Student</x-ui.button>
                            @endif
                        </div>
                    </div>

                    <input id="{{ $detailsId }}" type="checkbox" class="student-details-toggle peer sr-only">
                    <div class="student-details-control mt-4 flex justify-end">
                        <label for="{{ $detailsId }}" class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-[var(--line)] bg-[var(--surface-muted)] px-3 py-2 text-xs font-bold text-[var(--text-strong)] theme-transition hover:border-brand-400 hover:text-brand-700">
                            <x-ui.icon name="chevron-down" class="student-details-chevron size-4 transition-transform duration-300" />
                            <span class="student-details-show">Expand details</span>
                            <span class="student-details-hide hidden">Retract details</span>
                        </label>
                    </div>

                    <div class="grid grid-rows-[0fr] transition-[grid-template-rows] duration-500 ease-out peer-checked:grid-rows-[1fr]">
                        <div class="min-h-0 overflow-hidden">
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
                        </div>
                    </div>
                </article>

                @unless ($assessment)
                    <x-ui.modal id="{{ $gradeModalId }}" title="Grade Student - {{ $user->name }}" class="w-[min(44rem,calc(100vw-2rem))]">
                        <form method="POST" action="{{ route('supervisor.assessments.store') }}" class="grid gap-4">
                            @csrf
                            <input type="hidden" name="student_id" value="{{ $student->id }}">

                            <div class="rounded-xl border border-[var(--line)] bg-[var(--surface-muted)] p-4">
                                <p class="font-bold text-[var(--text-strong)]">{{ $user->name }}</p>
                                <p class="mt-1 text-sm font-semibold text-brand-700 dark:text-brand-200">{{ $student->matric_no }} / {{ $student->department?->name ?? 'N/A' }}</p>
                            </div>

                            <div class="grid gap-3">
                                @forelse ($rubricItems as $item)
                                    <div class="rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-3">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-semibold">{{ $item->name }}</p>
                                                <p class="mt-1 text-xs text-[var(--text-soft)]">Max {{ $item->max_score }}, weight {{ $item->weight }}</p>
                                            </div>
                                            <input name="scores[{{ $item->id }}]" type="number" min="0" max="{{ $item->max_score }}" class="w-24 rounded-lg border border-[var(--line)] bg-[var(--surface-raised)] px-3 py-2 text-sm" required>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-sm text-[var(--text-soft)]">No active rubric items have been configured.</p>
                                @endforelse
                            </div>

                            <label class="block">
                                <span class="text-sm font-medium text-[var(--text-strong)]">Feedback</span>
                                <textarea name="feedback" rows="5" class="siwes-form-control mt-2" required placeholder="Structured supervisor feedback for the student"></textarea>
                            </label>

                            <div class="flex justify-end gap-2">
                                <x-ui.button type="button" variant="ghost" data-modal-close>Cancel</x-ui.button>
                                <x-ui.button type="submit" :disabled="$rubricItems->isEmpty()" data-loading-text="Saving...">Submit Grade</x-ui.button>
                            </div>
                        </form>
                    </x-ui.modal>
                @endunless

                @if ($photoUrl)
                    <x-ui.modal id="assigned-student-photo-{{ $assignment->id }}" title="{{ $user->name }} - Profile Picture" class="w-[min(42rem,calc(100vw-2rem))]">
                        <div class="overflow-hidden rounded-2xl border border-[var(--line)] bg-[var(--surface-muted)]">
                            <img src="{{ $photoUrl }}" alt="{{ $user->name }} full profile picture" class="max-h-[70vh] w-full object-contain">
                        </div>
                    </x-ui.modal>
                @endif
            @empty
                <p class="rounded-xl border border-[var(--line)] bg-[var(--surface-muted)] p-5 text-sm text-[var(--text-soft)]">No assigned students yet.</p>
            @endforelse
        </div>

        <div class="mt-5">
            {{ $assignments->links() }}
        </div>
    </x-ui.card>
</x-layouts.app-shell>
