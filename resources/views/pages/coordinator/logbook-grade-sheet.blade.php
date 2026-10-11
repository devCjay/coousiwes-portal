@php
    $navigation = [
        ['label' => 'Dashboard', 'href' => route('coordinator.dashboard'), 'icon' => 'D'],
        ['label' => 'Students', 'href' => route('coordinator.students.index'), 'icon' => 'S'],
        ['label' => 'Download Logbook Gradesheet', 'href' => route('coordinator.logbook-grade-sheet.index'), 'active' => true, 'icon' => 'clipboard-check'],
        ['label' => 'Change Password', 'href' => route('account.password.edit'), 'icon' => 'key'],
    ];
@endphp

<x-layouts.app-shell title="Download Logbook Gradesheet" role="SIWES Coordinator" :navigation="$navigation">
    <div class="grid gap-4 md:grid-cols-3">
        <x-ui.stat-card label="Department" :value="$coordinator->department?->code ?? 'N/A'" :meta="$coordinator->department?->name ?? 'Assigned department'" />
        <x-ui.stat-card label="Available Scores" :value="number_format($assessmentCount)" meta="Submitted supervisor assessments" tone="cyan" />
        <x-ui.stat-card label="Access Scope" value="Department" meta="Other departments are restricted" tone="amber" />
    </div>

    <x-ui.card class="mt-6" title="Log Book Score Sheet" description="Download supervisor assessment scores for your department only.">
        <form method="GET" action="{{ route('coordinator.logbook-grade-sheet.export') }}" data-ajax="false" class="grid gap-4 lg:grid-cols-[1fr_1fr_auto]">
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
                <x-ui.button type="submit" variant="secondary">Download Logbook Grade Sheet</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</x-layouts.app-shell>
