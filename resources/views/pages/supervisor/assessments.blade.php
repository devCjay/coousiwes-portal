@php
    $openAssignments = $assignments->filter(fn ($assignment) => $assignment->assessment === null);
    $navigation = [
        ['label' => 'Dashboard', 'href' => route('supervisor.dashboard'), 'icon' => 'D'],
        ['label' => 'Assigned Students', 'href' => route('supervisor.students.index'), 'icon' => 'S'],
        ['label' => 'Grade Log Book', 'href' => route('supervisor.assessments.index'), 'active' => true, 'icon' => 'A'],
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
                    <input type="search" name="assessment_student_search" class="siwes-form-control mt-2" placeholder="Live search by student name or reg no..." data-option-search="#assessment-student-select">
                    <select id="assessment-student-select" name="student_id" class="siwes-form-control mt-2" required>
                        <option value="">Select assigned student</option>
                        @foreach ($openAssignments as $assignment)
                            <option value="{{ $assignment->student->id }}">
                                {{ $assignment->student->user->name }} - {{ $assignment->student->matric_no }} - {{ $assignment->student->department?->name ?? 'N/A' }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <div class="grid gap-3">
                    @forelse ($rubricItems as $item)
                        <div class="rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold">{{ $item->name }}</p>
                                    <p class="mt-1 text-xs text-[var(--text-soft)]">Max {{ $item->max_score }} marks</p>
                                </div>
                                <input name="scores[{{ $item->id }}]" type="number" min="0" max="{{ $item->max_score }}" data-assessment-score data-assessment-group="assessment-form" class="w-24 rounded-lg border border-[var(--line)] bg-[var(--surface-raised)] px-3 py-2 text-sm" required>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-[var(--text-soft)]">No active rubric items have been configured.</p>
                    @endforelse
                </div>

                <div class="rounded-xl border border-brand-600/20 bg-brand-600/5 p-4 text-sm font-bold text-[var(--text-strong)]">
                    Total Grade:
                    <span data-assessment-total data-assessment-group="assessment-form">0</span>
                    / {{ $rubricItems->sum('max_score') }}
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

    <script>
        document.addEventListener('input', (event) => {
            const input = event.target.closest('[data-assessment-score]');
            if (! input) return;

            const group = input.dataset.assessmentGroup;
            const total = document.querySelector(`[data-assessment-total][data-assessment-group="${group}"]`);
            if (! total) return;

            const sum = Array.from(document.querySelectorAll(`[data-assessment-score][data-assessment-group="${group}"]`))
                .reduce((carry, field) => carry + (Number.parseInt(field.value || '0', 10) || 0), 0);

            total.textContent = sum;
        });
    </script>
</x-layouts.app-shell>
