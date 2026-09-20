@php
    $navigation = [
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'D'],
        ['label' => 'Generate List', 'href' => route('admin.generate-list.index'), 'icon' => 'file-text'],
        ['label' => 'Students', 'href' => route('admin.students.index'), 'icon' => 'S'],
        ['label' => 'Tickets', 'href' => route('admin.tickets.index'), 'icon' => 'T'],
        ['label' => 'Supervisors', 'href' => route('admin.supervisors.index'), 'icon' => 'V'],
        ['label' => 'Assessment', 'href' => route('admin.assessments.index'), 'active' => true, 'icon' => 'clipboard-check'],
        ['label' => 'Reports', 'href' => route('admin.reports.index'), 'icon' => 'R'],
        ['label' => 'Rubric', 'href' => route('admin.assessments.rubric.index'), 'icon' => 'A'],
        ['label' => 'Settings', 'href' => route('admin.settings.index'), 'icon' => 'G'],
    ];
    $can = fn (string $permission): bool => \App\Support\PortalPermission::userHas(auth('admin')->user(), $permission);
@endphp

<x-layouts.app-shell title="Assessment" role="Admin" :navigation="$navigation">
    <div class="grid gap-4 md:grid-cols-2">
        <x-ui.stat-card label="Active Assessment Rubric" :value="number_format($activeRubricCount)" meta="Active grading criteria" />
        <x-ui.stat-card label="Submitted Assessments" :value="number_format($submittedAssessmentCount)" meta="Supervisor score submissions" tone="cyan" />
    </div>

    @if ($can('assessments.export'))
        <x-ui.card class="mt-6" title="Log Book Score Sheet" description="Download supervisor assessment scores filtered by department, session, and level.">
            <form method="GET" action="{{ route('admin.assessments.logbook-score-sheet') }}" data-ajax="false" class="grid gap-4 lg:grid-cols-[1fr_1fr_1fr_auto]">
                <label class="block">
                    <span class="text-sm font-medium text-[var(--text-strong)]">Department</span>
                    <select name="department_id" class="siwes-form-control mt-2">
                        <option value="">All departments</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="text-sm font-medium text-[var(--text-strong)]">Session</span>
                    <select name="academic_session_id" class="siwes-form-control mt-2">
                        <option value="">All sessions</option>
                        @foreach ($sessions as $session)
                            <option value="{{ $session->id }}">{{ $session->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="text-sm font-medium text-[var(--text-strong)]">Level</span>
                    <select name="academic_level_id" class="siwes-form-control mt-2">
                        <option value="">All levels</option>
                        @foreach ($levels as $level)
                            <option value="{{ $level->id }}">{{ $level->name }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="flex items-end">
                    <x-ui.button type="submit" variant="secondary">Download Log Book Score Sheet</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    @endif

    <x-ui.card class="mt-6" title="Active Assessment Rubric" description="Current active scoring criteria available to supervisors.">
        <x-ui.input class="mb-4" label="Live Search" name="rubric_search" placeholder="Search active assessment criteria..." data-live-search="#active-rubric-table tbody tr" />
        <x-ui.data-table
            id="active-rubric-table"
            :headers="['Criterion', 'Max Score', 'Weight', 'Sort Order', 'Description']"
            :rows="$rubricItems->map(fn ($item) => [
                e($item->name),
                e((string) $item->max_score),
                e((string) $item->weight),
                e((string) $item->sort_order),
                e($item->description ?: 'N/A'),
            ])->all()"
        />
    </x-ui.card>
</x-layouts.app-shell>
