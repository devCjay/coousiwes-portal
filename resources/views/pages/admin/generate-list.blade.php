@php
    $navigation = [
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'D'],
        ['label' => 'Generate List', 'href' => route('admin.generate-list.index'), 'active' => true, 'icon' => 'file-text'],
        ...(auth()->user()?->hasRole('super-admin') ? [['label' => 'Control', 'href' => route('admin.control.index'), 'icon' => 'C']] : []),
        ['label' => 'Students', 'href' => route('admin.students.index'), 'icon' => 'S'],
        ['label' => 'Academics', 'href' => route('admin.academics.index'), 'icon' => 'A'],
        ['label' => 'Settings', 'href' => route('admin.settings.index'), 'icon' => 'G'],
    ];
@endphp

<x-layouts.app-shell title="Generate List" role="Admin" :navigation="$navigation">
    <div class="grid gap-4 md:grid-cols-3">
        <x-ui.stat-card label="Students" :value="number_format($studentCount)" meta="Total database records" />
        <x-ui.stat-card label="Faculties" :value="number_format($facultyCount)" meta="Academic parent records" tone="cyan" />
        <x-ui.stat-card label="Departments" :value="number_format($departmentCount)" meta="Mapped academic units" tone="amber" />
    </div>

    <x-ui.card class="mt-6" title="List Generation" description="Prepare master and placement list workflows from this workspace.">
        <div class="grid gap-4 md:grid-cols-2">
            <button
                type="button"
                data-modal-target="#masters-list-modal"
                class="group flex min-h-36 items-center justify-between gap-4 rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-5 text-left theme-transition hover:-translate-y-1 hover:border-brand-400 hover:bg-[var(--surface-raised)] hover:shadow-glow"
            >
                <span>
                    <span class="block text-base font-semibold text-[var(--text-strong)]">Generate Masters List</span>
                    <span class="mt-2 block text-sm leading-6 text-[var(--text-soft)]">Compile the complete student master list from current academic records.</span>
                </span>
                <span class="grid size-12 shrink-0 place-items-center rounded-lg bg-brand-600 text-white shadow-glow">
                    <x-ui.icon name="file-text" class="size-5" />
                </span>
            </button>

            <button
                type="button"
                data-modal-target="#placement-list-modal"
                class="group flex min-h-36 items-center justify-between gap-4 rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-5 text-left theme-transition hover:-translate-y-1 hover:border-brand-400 hover:bg-[var(--surface-raised)] hover:shadow-glow"
            >
                <span>
                    <span class="block text-base font-semibold text-[var(--text-strong)]">Generate Placement List</span>
                    <span class="mt-2 block text-sm leading-6 text-[var(--text-soft)]">Prepare the SIWES placement list workflow for assigned students.</span>
                </span>
                <span class="grid size-12 shrink-0 place-items-center rounded-lg bg-cyan-500 text-white shadow-glow">
                    <x-ui.icon name="clipboard-check" class="size-5" />
                </span>
            </button>
        </div>
    </x-ui.card>

    <x-ui.modal id="masters-list-modal" title="Generate Masters List" class="w-[min(44rem,calc(100vw-2rem))]">
        <p class="text-sm leading-6 text-[var(--text-soft)]">Download an Excel-compatible master list grouped by faculty and department.</p>
        <form method="GET" action="{{ route('admin.generate-list.master') }}" data-ajax="false" class="mt-5 grid gap-4">
            <div class="grid gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="siwes-form-label">Faculty</span>
                    <select name="faculty_id" class="siwes-form-control mt-2" data-filter-parent="#master-department">
                        <option value="">All</option>
                        @foreach ($faculties as $faculty)
                            <option value="{{ $faculty->id }}">{{ $faculty->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="siwes-form-label">Department</span>
                    <select id="master-department" name="department_id" class="siwes-form-control mt-2">
                        <option value="">All</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" data-parent-value="{{ $department->faculty_id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="siwes-form-label">Session</span>
                    <select name="academic_session_id" class="siwes-form-control mt-2">
                        <option value="">All</option>
                        @foreach ($sessions as $session)
                            <option value="{{ $session->id }}">{{ $session->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="siwes-form-label">Level</span>
                    <select name="academic_level_id" class="siwes-form-control mt-2">
                        <option value="">All</option>
                        @foreach ($levels as $level)
                            <option value="{{ $level->id }}">{{ $level->name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
            <div class="flex justify-end gap-2 border-t border-[var(--line)] pt-4">
                <x-ui.button type="button" variant="ghost" data-modal-close>Close</x-ui.button>
                <x-ui.button type="submit">Download XLS</x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    <x-ui.modal id="placement-list-modal" title="Generate Placement List" class="w-[min(44rem,calc(100vw-2rem))]">
        <p class="text-sm leading-6 text-[var(--text-soft)]">Download an Excel-compatible placement list using submitted SIWES placement records.</p>
        <form method="GET" action="{{ route('admin.generate-list.placement') }}" data-ajax="false" class="mt-5 grid gap-4">
            <div class="grid gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="siwes-form-label">Faculty</span>
                    <select name="faculty_id" class="siwes-form-control mt-2" data-filter-parent="#placement-department">
                        <option value="">All</option>
                        @foreach ($faculties as $faculty)
                            <option value="{{ $faculty->id }}">{{ $faculty->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="siwes-form-label">Department</span>
                    <select id="placement-department" name="department_id" class="siwes-form-control mt-2">
                        <option value="">All</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" data-parent-value="{{ $department->faculty_id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="siwes-form-label">Session</span>
                    <select name="academic_session_id" class="siwes-form-control mt-2">
                        <option value="">All</option>
                        @foreach ($sessions as $session)
                            <option value="{{ $session->id }}">{{ $session->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="siwes-form-label">Level</span>
                    <select name="academic_level_id" class="siwes-form-control mt-2">
                        <option value="">All</option>
                        @foreach ($levels as $level)
                            <option value="{{ $level->id }}">{{ $level->name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
            <div class="flex justify-end gap-2 border-t border-[var(--line)] pt-4">
                <x-ui.button type="button" variant="ghost" data-modal-close>Close</x-ui.button>
                <x-ui.button type="submit">Download XLS</x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</x-layouts.app-shell>
