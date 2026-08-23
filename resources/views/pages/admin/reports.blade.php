@php
    $navigation = [
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'D'],
        ['label' => 'Generate List', 'href' => route('admin.generate-list.index'), 'icon' => 'file-text'],
        ['label' => 'Students', 'href' => route('admin.students.index'), 'icon' => 'S'],
        ['label' => 'Tickets', 'href' => route('admin.tickets.index'), 'icon' => 'T'],
        ['label' => 'Supervisors', 'href' => route('admin.supervisors.index'), 'icon' => 'V'],
        ['label' => 'Reports', 'href' => route('admin.reports.index'), 'active' => true, 'icon' => 'R'],
        ['label' => 'Rubric', 'href' => route('admin.assessments.rubric.index'), 'icon' => 'A'],
        ['label' => 'Settings', 'href' => route('admin.settings.index'), 'icon' => 'G'],
    ];
@endphp

<x-layouts.app-shell title="Reports" role="Admin" :navigation="$navigation">
    <div class="grid gap-4 md:grid-cols-4">
        <x-ui.stat-card label="Assessments" :value="$assessmentCount" meta="Submitted feedback" />
        <x-ui.stat-card label="Average Score" :value="$averageScore.'%'" meta="Weighted rubric average" tone="cyan" />
        <x-ui.stat-card label="Students" :value="$studentCount" meta="Registered records" tone="amber" />
        <x-ui.stat-card label="Payments" :value="$paymentCount" meta="Korapay attempts" tone="rose" />
    </div>

    <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <x-ui.input label="Live Search" name="report_search" placeholder="Search report tables..." data-live-search="#recent-assessments-table tbody tr, #supervisor-performance-table tbody tr" />
        <x-ui.button :href="route('admin.reports.export')" variant="secondary">Export CSV</x-ui.button>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <x-ui.card title="Supervisor Performance" description="Submission count and weighted average score.">
            <x-ui.data-table
                id="supervisor-performance-table"
                :headers="['Supervisor', 'Assessments', 'Average']"
                :rows="$supervisorPerformance->map(fn ($row) => [
                    e($row['name']),
                    e((string) $row['assessments']),
                    e((string) $row['average']).'%',
                ])->all()"
            />
        </x-ui.card>

        <x-ui.card title="Assessment Completion" description="Faculty-level coverage across registered students.">
            <x-ui.data-table
                :headers="['Faculty', 'Students', 'Assessed']"
                :rows="$completionByFaculty->map(fn ($row) => [
                    e($row['faculty']),
                    e((string) $row['students']),
                    e((string) $row['assessed']),
                ])->all()"
            />
        </x-ui.card>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <x-ui.card title="Score Distribution" description="Weighted score bands.">
            <x-ui.data-table
                :headers="['Range', 'Count']"
                :rows="$scoreDistribution->map(fn ($row) => [e($row['range']), e((string) $row['count'])])->all()"
            />
        </x-ui.card>

        <x-ui.card title="Payment Status" description="Korapay payment lifecycle counts.">
            <x-ui.data-table
                :headers="['Status', 'Count']"
                :rows="$paymentTrends->map(fn ($count, $status) => [e(ucfirst((string) $status)), e((string) $count)])->values()->all()"
            />
        </x-ui.card>

        <x-ui.card title="Activation Status" description="Student activation posture.">
            <x-ui.data-table
                :headers="['Status', 'Count']"
                :rows="$activationTrends->map(fn ($count, $status) => [e(ucfirst((string) $status)), e((string) $count)])->values()->all()"
            />
        </x-ui.card>
    </div>

    <x-ui.card class="mt-6" title="Recent Assessments" description="Latest supervisor submissions.">
        <x-ui.data-table
            id="recent-assessments-table"
            :headers="['Student', 'Supervisor', 'Score', 'Submitted', 'Feedback']"
            :rows="$recentAssessments->map(fn ($assessment) => [
                e($assessment->student->user->name).' <span class=&quot;text-xs text-[var(--text-soft)]&quot;>'.e($assessment->student->matric_no).'</span>',
                e($assessment->supervisor->user->name),
                e((string) $assessment->total_score).' / '.e((string) $assessment->max_score),
                e($assessment->submitted_at?->toDateTimeString() ?? 'Pending'),
                e(str($assessment->feedback)->limit(80)->toString()),
            ])->all()"
        />
    </x-ui.card>
</x-layouts.app-shell>
