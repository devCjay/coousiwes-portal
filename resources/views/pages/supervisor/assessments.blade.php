@php
    $openAssignments = $assignments->filter(fn ($assignment) => $assignment->assessment === null);
    $navigation = [
        ['label' => 'Dashboard', 'href' => route('supervisor.dashboard'), 'icon' => 'D'],
        ['label' => 'Assigned Students', 'href' => route('supervisor.students.index'), 'icon' => 'S'],
        ['label' => 'Assessments', 'href' => route('supervisor.assessments.index'), 'active' => true, 'icon' => 'A'],
    ];
@endphp

<x-layouts.app-shell title="Assessments" role="Supervisor" :navigation="$navigation">
    <div class="grid gap-4 md:grid-cols-3">
        <x-ui.stat-card label="Open Assessments" :value="$openAssignments->count()" meta="Assigned and not scored" />
        <x-ui.stat-card label="Completed" :value="$assessments->count()" meta="Submitted feedback" tone="cyan" />
        <x-ui.stat-card label="Rubric Items" :value="$rubricItems->count()" meta="Active scoring criteria" tone="amber" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[0.85fr_1.15fr]">
        <x-ui.card title="Submit Assessment" description="Scores are validated against the active rubric.">
            <form method="POST" action="{{ route('supervisor.assessments.store') }}" class="grid gap-4">
                @csrf
                <label class="block">
                    <span class="text-sm font-medium text-[var(--text-strong)]">Student</span>
                    <select name="student_id" class="siwes-form-control mt-2" required>
                        <option value="">Select assigned student</option>
                        @foreach ($openAssignments as $assignment)
                            <option value="{{ $assignment->student->id }}">{{ $assignment->student->user->name }} - {{ $assignment->student->matric_no }}</option>
                        @endforeach
                    </select>
                </label>

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
                <x-ui.button type="submit">Submit Assessment</x-ui.button>
            </form>
        </x-ui.card>

        <x-ui.card title="Submitted Feedback" description="Search previous submissions in real time.">
            <x-ui.input class="mb-4" label="Live Search" name="assessment_search" placeholder="Search assessed students..." data-live-search="#supervisor-assessments-table tbody tr" />
            <x-ui.data-table
                id="supervisor-assessments-table"
                :headers="['Student', 'Score', 'Submitted', 'Feedback']"
                :rows="$assessments->map(fn ($assessment) => [
                    e($assessment->student->user->name).' <span class=&quot;text-xs text-[var(--text-soft)]&quot;>'.e($assessment->student->matric_no).'</span>',
                    e((string) $assessment->total_score).' / '.e((string) $assessment->max_score),
                    e($assessment->submitted_at?->toDateTimeString() ?? 'Pending'),
                    e(str($assessment->feedback)->limit(80)->toString()),
                ])->all()"
            />
        </x-ui.card>
    </div>
</x-layouts.app-shell>
