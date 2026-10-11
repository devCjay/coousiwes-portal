@php
    $navigation = [
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'D'],
        ['label' => 'Generate List', 'href' => route('admin.generate-list.index'), 'icon' => 'file-text'],
        ['label' => 'Students', 'href' => route('admin.students.index'), 'icon' => 'S'],
        ['label' => 'Supervisors', 'href' => route('admin.supervisors.index'), 'icon' => 'V'],
        ['label' => 'Coordinators', 'href' => route('admin.coordinators.index'), 'active' => true, 'icon' => 'users'],
        ['label' => 'Assessment', 'href' => route('admin.assessments.index'), 'icon' => 'clipboard-check'],
        ['label' => 'Settings', 'href' => route('admin.settings.index'), 'icon' => 'G'],
    ];
    $can = fn (string $permission): bool => \App\Support\PortalPermission::userHas(auth('admin')->user(), $permission);
@endphp

<x-layouts.app-shell title="SIWES Coordinators" role="Admin" :navigation="$navigation">
    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-2xl font-extrabold tracking-normal text-[var(--text-strong)]">SIWES Coordinators</h1>
                <p class="mt-1 text-sm text-[var(--text-soft)]">Course lecturers assigned to manage SIWES students within a specific department.</p>
            </div>
            @if ($can('coordinators.create'))
                <x-ui.button type="button" data-modal-target="#add-coordinator-modal">Add Coordinator</x-ui.button>
            @endif
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-ui.stat-card label="Coordinators" :value="number_format($summary['total'])" meta="All coordinator accounts" />
            <x-ui.stat-card label="Active Coordinators" :value="number_format($summary['active'])" meta="Can access portal" tone="cyan" />
            <x-ui.stat-card label="Covered Departments" :value="number_format($summary['departments'])" meta="Departments with coordinators" tone="amber" />
            <x-ui.stat-card label="Department Students" :value="number_format($summary['students'])" meta="Students under covered departments" tone="rose" />
        </div>

        <x-ui.card title="Coordinator List" description="Create, review, and remove departmental SIWES coordinator accounts.">
            <x-ui.input class="mb-4" label="Live Search" name="coordinator_search" placeholder="Search by name, email, phone, faculty, department, or rank..." data-live-search="#coordinators-table tbody tr" />

            <div class="overflow-x-auto rounded-2xl border border-[var(--line)]">
                <table id="coordinators-table" class="min-w-full divide-y divide-[var(--line)] text-left text-sm">
                    <thead class="bg-brand-600/10 text-xs font-bold uppercase text-brand-700 dark:bg-white/5 dark:text-brand-200">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3">Name</th>
                            <th class="whitespace-nowrap px-4 py-3">Email</th>
                            <th class="whitespace-nowrap px-4 py-3">Phone</th>
                            <th class="whitespace-nowrap px-4 py-3">Faculty</th>
                            <th class="whitespace-nowrap px-4 py-3">Department</th>
                            <th class="whitespace-nowrap px-4 py-3">Rank</th>
                            <th class="whitespace-nowrap px-4 py-3">Status</th>
                            @if ($can('coordinators.update'))
                                <th class="whitespace-nowrap px-4 py-3">Action</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--line)]">
                        @forelse ($coordinators as $coordinator)
                            <tr class="theme-transition hover:bg-brand-600/5">
                                <td class="whitespace-nowrap px-4 py-3">
                                    <p class="font-bold text-[var(--text-strong)]">{{ $coordinator->user?->name ?? 'N/A' }}</p>
                                    <p class="text-xs text-[var(--text-soft)]">{{ $coordinator->staff_no }}</p>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-[var(--text-strong)]">{{ $coordinator->user?->email ?? 'N/A' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-[var(--text-strong)]">{{ $coordinator->user?->phone ?? 'N/A' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-[var(--text-strong)]">{{ $coordinator->faculty?->name ?? 'N/A' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-[var(--text-strong)]">{{ $coordinator->department?->name ?? 'N/A' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-[var(--text-strong)]">{{ $coordinator->rank }}</td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="inline-flex rounded-full bg-brand-400 px-3 py-1 text-xs font-bold text-white">{{ ucfirst($coordinator->status) }}</span>
                                </td>
                                @if ($can('coordinators.update'))
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <form method="POST" action="{{ route('admin.coordinators.destroy', $coordinator) }}" onsubmit="return confirm('Delete this SIWES coordinator account?');">
                                            @csrf
                                            @method('DELETE')
                                            <x-ui.button type="submit" variant="danger" class="px-3 py-2 text-xs" data-loading-text="Deleting...">Delete</x-ui.button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $can('coordinators.update') ? 8 : 7 }}" class="px-4 py-8 text-center text-sm text-[var(--text-soft)]">No SIWES coordinators have been created.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $coordinators->links() }}
            </div>
        </x-ui.card>
    </div>

    @if ($can('coordinators.create'))
        <x-ui.modal id="add-coordinator-modal" title="Add SIWES Coordinator" class="w-[min(48rem,calc(100vw-2rem))]">
            <form method="POST" action="{{ route('admin.coordinators.store') }}" class="grid gap-4">
                @csrf
                <div class="grid gap-4 md:grid-cols-2">
                    <x-ui.input label="Full Name" name="name" required />
                    <x-ui.input label="Email Address" name="email" type="email" required />
                    <x-ui.input label="Phone Number" name="phone" placeholder="08030000000" required />
                    <label class="block">
                        <span class="siwes-form-label">Department</span>
                        <select name="department_id" class="siwes-form-control mt-2" required data-supervisor-department="#add-coordinator-faculty" data-supervisor-faculty-id="#add-coordinator-faculty-id">
                            <option value="">Select department</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}" data-faculty-id="{{ $department->faculty_id }}" data-faculty-name="{{ $department->faculty?->name ?? 'N/A' }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="siwes-form-label">Faculty</span>
                        <input id="add-coordinator-faculty" class="siwes-form-control mt-2" value="" placeholder="Select a department first" readonly>
                        <input id="add-coordinator-faculty-id" type="hidden" name="faculty_id" value="">
                    </label>
                    <label class="block">
                        <span class="siwes-form-label">Rank</span>
                        <select name="rank" class="siwes-form-control mt-2" required>
                            <option value="">Select rank</option>
                            @foreach (\App\Models\SiwesCoordinator::RANKS as $rank)
                                <option value="{{ $rank }}">{{ $rank }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <div class="flex justify-end gap-2">
                    <x-ui.button type="button" variant="ghost" data-modal-close>Cancel</x-ui.button>
                    <x-ui.button type="submit">Create Coordinator</x-ui.button>
                </div>
            </form>
        </x-ui.modal>
    @endif
</x-layouts.app-shell>
