@php
    $navigation = [
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'D'],
        ['label' => 'Generate List', 'href' => route('admin.generate-list.index'), 'icon' => 'file-text'],
        ['label' => 'Supervisors', 'href' => route('admin.supervisors.index'), 'active' => true, 'icon' => 'V'],
        ['label' => 'Students', 'href' => route('admin.students.index'), 'icon' => 'S'],
    ];
@endphp

<x-layouts.app-shell title="Supervisor Profile" role="Admin" :navigation="$navigation">
    <div class="grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
        <x-ui.card title="{{ $supervisor->user->name }}" description="{{ $supervisor->staff_no }}">
            <dl class="space-y-3 text-sm">
                <div><dt class="text-[var(--text-soft)]">Email</dt><dd class="font-medium">{{ $supervisor->user->email }}</dd></div>
                <div><dt class="text-[var(--text-soft)]">Active Assignments</dt><dd class="font-medium">{{ $supervisor->activeAssignments()->count() }}</dd></div>
                <div><dt class="text-[var(--text-soft)]">Status</dt><dd class="font-medium">{{ ucfirst($supervisor->status) }}</dd></div>
            </dl>
            <div class="mt-5 flex gap-2">
                <form method="POST" action="{{ route('admin.supervisors.suspend', $supervisor) }}">
                    @csrf
                    <x-ui.button type="submit" variant="danger">Suspend</x-ui.button>
                </form>
                <form method="POST" action="{{ route('admin.supervisors.reactivate', $supervisor) }}">
                    @csrf
                    <x-ui.button type="submit" variant="secondary">Reactivate</x-ui.button>
                </form>
            </div>
        </x-ui.card>

        <x-ui.card title="Assignment History" description="Historical records are retained after revocation.">
            <x-ui.data-table
                :headers="['Student', 'Matric', 'Department', 'Assigned', 'Status']"
                :rows="$supervisor->assignments->map(fn ($assignment) => [
                    e($assignment->student->user->name),
                    e($assignment->student->matric_no),
                    e($assignment->student->department?->name ?? 'N/A'),
                    e($assignment->assigned_at->toDateString()),
                    $assignment->revoked_at ? 'Revoked' : 'Active',
                ])->all()"
            />
        </x-ui.card>
    </div>
</x-layouts.app-shell>
