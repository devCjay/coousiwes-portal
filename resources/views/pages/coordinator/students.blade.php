@php
    $navigation = [
        ['label' => 'Dashboard', 'href' => route('coordinator.dashboard'), 'icon' => 'D'],
        ['label' => 'Students', 'href' => route('coordinator.students.index'), 'active' => true, 'icon' => 'S'],
        ['label' => 'Download Logbook Gradesheet', 'href' => route('coordinator.logbook-grade-sheet.index'), 'icon' => 'clipboard-check'],
        ['label' => 'Change Password', 'href' => route('account.password.edit'), 'icon' => 'key'],
    ];
@endphp

<x-layouts.app-shell title="Department Students" role="SIWES Coordinator" :navigation="$navigation">
    <x-ui.card title="Fetch Department Students" description="Students are loaded only after you select filters. Results are limited to your assigned department.">
        <form method="GET" action="{{ route('coordinator.students.index') }}" data-ajax="false" class="grid gap-4 lg:grid-cols-[1fr_1fr_auto]">
            <input type="hidden" name="fetch" value="1">
            <label class="block">
                <span class="text-sm font-medium text-[var(--text-strong)]">Level</span>
                <select name="academic_level_id" class="siwes-form-control mt-2">
                    <option value="">All levels</option>
                    @foreach ($levels as $level)
                        <option value="{{ $level->id }}" @selected((int) ($filters['academic_level_id'] ?? 0) === $level->id)>{{ $level->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="text-sm font-medium text-[var(--text-strong)]">Session</span>
                <select name="academic_session_id" class="siwes-form-control mt-2">
                    <option value="">All sessions</option>
                    @foreach ($sessions as $session)
                        <option value="{{ $session->id }}" @selected((int) ($filters['academic_session_id'] ?? 0) === $session->id)>{{ $session->name }}</option>
                    @endforeach
                </select>
            </label>
            <div class="flex items-end">
                <x-ui.button type="submit">Fetch Students</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <x-ui.card class="mt-6" title="Student List" description="{{ $coordinator->department?->name ?? 'Assigned department' }} only.">
        @if ($students)
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <x-ui.input class="sm:max-w-xl sm:flex-1" label="Live Search" name="coordinator_student_search" placeholder="Search current page by name, reg no, email, phone..." data-live-search="#coordinator-students-table tbody tr" />
                <x-ui.button :href="route('coordinator.students.export', array_filter(['academic_level_id' => $filters['academic_level_id'], 'academic_session_id' => $filters['academic_session_id']]))" variant="secondary">Download Student List</x-ui.button>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-[var(--line)]">
                <table id="coordinator-students-table" class="min-w-full divide-y divide-[var(--line)] text-left text-sm">
                    <thead class="bg-brand-600/10 text-xs font-bold uppercase text-brand-700 dark:bg-white/5 dark:text-brand-200">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3">Student</th>
                            <th class="whitespace-nowrap px-4 py-3">Reg No</th>
                            <th class="whitespace-nowrap px-4 py-3">Level</th>
                            <th class="whitespace-nowrap px-4 py-3">Session</th>
                            <th class="whitespace-nowrap px-4 py-3">Phone</th>
                            <th class="whitespace-nowrap px-4 py-3">Email</th>
                            <th class="whitespace-nowrap px-4 py-3">Placement</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--line)]">
                        @forelse ($students as $student)
                            <tr class="theme-transition hover:bg-brand-600/5">
                                <td class="whitespace-nowrap px-4 py-3 font-bold text-[var(--text-strong)]">{{ $student->user?->name ?? 'N/A' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-[var(--text-strong)]">{{ $student->matric_no }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-[var(--text-strong)]">{{ $student->academicLevel?->name ?? 'N/A' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-[var(--text-strong)]">{{ $student->academicSession?->name ?? 'N/A' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-[var(--text-strong)]">{{ $student->user?->phone ?: 'N/A' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-[var(--text-strong)]">{{ $student->user?->email ?: 'N/A' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-[var(--text-strong)]">{{ $student->placement?->company_name ?: 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-sm text-[var(--text-soft)]">No students matched the selected filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $students->links() }}
            </div>
        @else
            <div class="rounded-2xl border border-dashed border-[var(--line)] bg-[var(--surface-muted)] p-8 text-center">
                <p class="font-bold text-[var(--text-strong)]">Select level/session filters and click Fetch Students.</p>
                <p class="mt-2 text-sm text-[var(--text-soft)]">No student list is loaded by default to keep this page fast for large departments.</p>
            </div>
        @endif
    </x-ui.card>
</x-layouts.app-shell>
