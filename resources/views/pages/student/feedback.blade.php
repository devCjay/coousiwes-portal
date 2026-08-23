@php
    $latest = $assessments->first();
    $navigation = [
        ['label' => 'Dashboard', 'href' => route('student.dashboard'), 'icon' => 'D'],
        ['label' => 'Placement', 'href' => route('student.placements.ticket'), 'icon' => 'briefcase'],
        ['label' => 'Payments', 'href' => route('student.payments.index'), 'icon' => 'P'],
        ['label' => 'Feedback', 'href' => route('student.feedback.index'), 'active' => true, 'icon' => 'F'],
    ];
@endphp

<x-layouts.app-shell title="Supervisor Feedback" role="Student" :navigation="$navigation">
    <div class="grid gap-4 md:grid-cols-3">
        <x-ui.stat-card label="Assessments" :value="$assessments->count()" meta="Supervisor submissions" />
        <x-ui.stat-card label="Latest Score" :value="$latest ? $latest->total_score.' / '.$latest->max_score : 'Pending'" meta="Weighted rubric score" tone="cyan" />
        <x-ui.stat-card label="Academic Level" :value="$student->placement?->academicLevel?->name ?? 'N/A'" meta="{{ $student->department->name }}" tone="amber" />
    </div>

    <x-ui.card class="mt-6" title="Feedback Timeline" description="Only feedback submitted for your student profile is visible here.">
        <x-ui.input class="mb-4" label="Live Search" name="feedback_search" placeholder="Search feedback..." data-live-search="[data-feedback-entry]" />

        <div class="space-y-4">
            @forelse ($assessments as $assessment)
                <article data-feedback-entry class="rounded-lg border border-[var(--line)] bg-[var(--surface-raised)] p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold">{{ $assessment->supervisor->user->name }}</p>
                            <p class="mt-1 text-xs text-[var(--text-soft)]">{{ $assessment->submitted_at?->toDateTimeString() ?? 'Pending submission date' }}</p>
                        </div>
                        <span class="rounded-md bg-brand-500/10 px-2 py-1 text-sm font-semibold text-brand-700 dark:text-brand-200">{{ $assessment->total_score }} / {{ $assessment->max_score }}</span>
                    </div>
                    <p class="mt-4 text-sm leading-6 text-[var(--text-strong)]">{{ $assessment->feedback }}</p>
                    <div class="mt-4">
                        <x-ui.data-table
                            :headers="['Rubric', 'Score', 'Comment']"
                            :rows="$assessment->scores->map(fn ($score) => [
                                e($score->rubricItem->name),
                                e((string) $score->score).' / '.e((string) $score->max_score),
                                e($score->comment ?? 'No item comment'),
                            ])->all()"
                        />
                    </div>
                </article>
            @empty
                <p class="text-sm text-[var(--text-soft)]">No supervisor feedback has been submitted yet.</p>
            @endforelse
        </div>
    </x-ui.card>
</x-layouts.app-shell>
