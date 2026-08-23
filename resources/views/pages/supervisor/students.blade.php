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
        <x-ui.input class="mb-4" label="Live Search" name="assigned_search" placeholder="Search assigned students..." data-live-search="#assigned-students-table tbody tr" />
        <x-ui.data-table
            id="assigned-students-table"
            :headers="['Student', 'Matric Number', 'Department', 'Level', 'Assigned']"
            :rows="$assignments->map(fn ($assignment) => [
                e($assignment->student->user->name),
                e($assignment->student->matric_no),
                e($assignment->student->department->name),
                e($assignment->student->academicLevel->name),
                e($assignment->assigned_at->toDateString()),
            ])->all()"
        />
    </x-ui.card>
</x-layouts.app-shell>
