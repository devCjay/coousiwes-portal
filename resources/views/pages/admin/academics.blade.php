@php
    $navigation = [
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'D'],
        ['label' => 'Generate List', 'href' => route('admin.generate-list.index'), 'icon' => 'file-text'],
        ['label' => 'Academics', 'href' => route('admin.academics.index'), 'active' => true, 'icon' => 'A'],
        ['label' => 'Settings', 'href' => route('admin.settings.index'), 'icon' => 'G'],
    ];
@endphp

<x-layouts.app-shell title="Academic Configuration" role="Admin" :navigation="$navigation">
    @if (session('status'))
        <x-ui.alert title="Saved" tone="success">{{ session('status') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert title="Action blocked" tone="danger">{{ $errors->first() }}</x-ui.alert>
    @endif

    <div class="grid gap-4 md:grid-cols-3">
        <x-ui.stat-card label="Faculties" :value="$faculties->count()" meta="Academic parent records" />
        <x-ui.stat-card label="Departments" :value="$departments->count()" meta="Mapped to faculties" tone="cyan" />
        <x-ui.stat-card label="Active Session" :value="$activeSession?->name ?? 'Not set'" meta="Used as system default" tone="amber" />
    </div>

    <x-ui.card class="mt-6" title="Academic Records" description="Manage academic structures from focused tabs.">
        <div class="overflow-x-auto">
            <div class="inline-flex min-w-full gap-2 rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-1" role="tablist" aria-label="Academic records">
                <button type="button" class="academic-tab is-active rounded-md px-3 py-2 text-sm font-semibold theme-transition" data-academic-tab-target="#faculty-panel" aria-selected="true">Faculties</button>
                <button type="button" class="academic-tab rounded-md px-3 py-2 text-sm font-semibold theme-transition" data-academic-tab-target="#department-panel" aria-selected="false">Departments</button>
                <button type="button" class="academic-tab rounded-md px-3 py-2 text-sm font-semibold theme-transition" data-academic-tab-target="#level-panel" aria-selected="false">Levels</button>
                <button type="button" class="academic-tab rounded-md px-3 py-2 text-sm font-semibold theme-transition" data-academic-tab-target="#session-panel" aria-selected="false">Academic Sessions</button>
            </div>
        </div>

        <section id="faculty-panel" class="academic-panel mt-5" data-academic-panel>
            <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-[var(--text-strong)]">Faculties</h2>
                    <p class="mt-1 text-sm text-[var(--text-soft)]">Protected records cannot be deleted while departments exist.</p>
                </div>
                <x-ui.button type="button" data-modal-target="#create-faculty-modal">Create Faculty</x-ui.button>
            </div>
            <x-ui.input class="mb-4" label="Live Search" name="faculty_search" placeholder="Search faculties..." data-live-search="#faculties-table tbody tr" />
            <x-ui.data-table
                id="faculties-table"
                :headers="['Name', 'Code', 'Departments', 'Status']"
                :rows="$faculties->map(fn ($faculty) => [
                    e($faculty->name),
                    e($faculty->code),
                    (string) $faculty->departments_count,
                    $faculty->is_active ? 'Active' : 'Inactive',
                ])->all()"
            />
        </section>

        <section id="department-panel" class="academic-panel mt-5 hidden" data-academic-panel>
            <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-[var(--text-strong)]">Departments</h2>
                    <p class="mt-1 text-sm text-[var(--text-soft)]">Live searchable faculty mappings.</p>
                </div>
                <x-ui.button type="button" data-modal-target="#create-department-modal">Create Department</x-ui.button>
            </div>
            <x-ui.input class="mb-4" label="Live Search" name="department_search" placeholder="Search departments..." data-live-search="#departments-table tbody tr" />
            <x-ui.data-table
                id="departments-table"
                :headers="['Department', 'Code', 'Faculty', 'Students', 'Status']"
                :rows="$departments->map(fn ($department) => [
                    e($department->name),
                    e($department->code),
                    e($department->faculty->name),
                    (string) $department->students_count,
                    $department->is_active ? 'Active' : 'Inactive',
                ])->all()"
            />
        </section>

        <section id="level-panel" class="academic-panel mt-5 hidden" data-academic-panel>
            <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-[var(--text-strong)]">Levels</h2>
                    <p class="mt-1 text-sm text-[var(--text-soft)]">Reusable level definitions for student profiles.</p>
                </div>
                <x-ui.button type="button" data-modal-target="#create-level-modal">Add Level</x-ui.button>
            </div>
            <x-ui.input class="mb-4" label="Live Search" name="level_search" placeholder="Search levels..." data-live-search="#levels-table tbody tr" />
            <x-ui.data-table id="levels-table" :headers="['Name', 'Level', 'Status']" :rows="$levels->map(fn ($level) => [e($level->name), (string) $level->level, $level->is_active ? 'Active' : 'Inactive'])->all()" />
        </section>

        <section id="session-panel" class="academic-panel mt-5 hidden" data-academic-panel>
            <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-[var(--text-strong)]">Academic Sessions</h2>
                    <p class="mt-1 text-sm text-[var(--text-soft)]">Only one session can be active at a time.</p>
                </div>
                <x-ui.button type="button" data-modal-target="#create-session-modal">Create Session</x-ui.button>
            </div>
            <x-ui.input class="mb-4" label="Live Search" name="session_search" placeholder="Search sessions..." data-live-search="#sessions-table tbody tr" />
            <x-ui.data-table id="sessions-table" :headers="['Name', 'Starts', 'Ends', 'Status']" :rows="$sessions->map(fn ($session) => [e($session->name), $session->starts_on->toDateString(), $session->ends_on->toDateString(), $session->is_active ? 'Active' : 'Inactive'])->all()" />
        </section>
    </x-ui.card>

    <x-ui.modal id="create-faculty-modal" title="Create Faculty" class="w-[min(40rem,calc(100vw-2rem))]">
        <form method="POST" action="{{ route('admin.academics.faculties.store') }}" class="grid gap-4">
            @csrf
            <x-ui.input label="Faculty Name" name="name" placeholder="Faculty of Physical Sciences" />
            <x-ui.input label="Code" name="code" placeholder="FPS" />
            <x-ui.input label="Description" name="description" placeholder="Optional note" />
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_active" value="1" checked class="rounded border-[var(--line)]">
                Active
            </label>
            <div class="flex justify-end gap-2">
                <x-ui.button type="button" variant="ghost" data-modal-close>Cancel</x-ui.button>
                <x-ui.button type="submit">Create Faculty</x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    <x-ui.modal id="create-department-modal" title="Create Department" class="w-[min(40rem,calc(100vw-2rem))]">
        <form method="POST" action="{{ route('admin.academics.departments.store') }}" class="grid gap-4">
            @csrf
            <label class="block">
                <span class="text-sm font-medium text-[var(--text-strong)]">Faculty</span>
                <select name="faculty_id" class="siwes-form-control mt-2">
                    @foreach ($faculties as $faculty)
                        <option value="{{ $faculty->id }}">{{ $faculty->name }}</option>
                    @endforeach
                </select>
            </label>
            <x-ui.input label="Department Name" name="name" placeholder="Computer Science" />
            <x-ui.input label="Code" name="code" placeholder="CSC" />
            <input type="hidden" name="is_active" value="1">
            <div class="flex justify-end gap-2">
                <x-ui.button type="button" variant="ghost" data-modal-close>Cancel</x-ui.button>
                <x-ui.button type="submit">Create Department</x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    <x-ui.modal id="create-level-modal" title="Add Level" class="w-[min(34rem,calc(100vw-2rem))]">
        <form method="POST" action="{{ route('admin.academics.levels.store') }}" class="grid gap-4">
            @csrf
            <x-ui.input label="Name" name="name" placeholder="300 Level" />
            <x-ui.input label="Level" name="level" type="number" placeholder="300" />
            <div class="flex justify-end gap-2">
                <x-ui.button type="button" variant="ghost" data-modal-close>Cancel</x-ui.button>
                <x-ui.button type="submit">Add Level</x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    <x-ui.modal id="create-session-modal" title="Create Academic Session" class="w-[min(40rem,calc(100vw-2rem))]">
        <form method="POST" action="{{ route('admin.academics.sessions.store') }}" class="grid gap-4">
            @csrf
            <x-ui.input label="Session Name" name="name" placeholder="2026/2027" />
            <div class="grid gap-3 md:grid-cols-2">
                <x-ui.input label="Starts On" name="starts_on" type="date" />
                <x-ui.input label="Ends On" name="ends_on" type="date" />
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_active" value="1" class="rounded border-[var(--line)]">
                Make active session
            </label>
            <div class="flex justify-end gap-2">
                <x-ui.button type="button" variant="ghost" data-modal-close>Cancel</x-ui.button>
                <x-ui.button type="submit">Create Session</x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</x-layouts.app-shell>
