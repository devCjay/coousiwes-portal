@php
    $navigation = [
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'D'],
        ['label' => 'Generate List', 'href' => route('admin.generate-list.index'), 'icon' => 'file-text'],
        ['label' => 'Students', 'href' => route('admin.students.index'), 'active' => true, 'icon' => 'S'],
        ['label' => 'Reports', 'href' => route('admin.reports.index'), 'icon' => 'R'],
        ['label' => 'Rubric', 'href' => route('admin.assessments.rubric.index'), 'icon' => 'A'],
        ['label' => 'Academics', 'href' => route('admin.academics.index'), 'icon' => 'A'],
        ['label' => 'Settings', 'href' => route('admin.settings.index'), 'icon' => 'G'],
    ];

    $studentStatus = fn ($student): string => $student->activation_status === 'suspended' ? 'suspended' : ($student->placement ? 'active' : 'inactive');
    $academicYear = function ($session): string {
        if (! $session?->name) {
            return 'N/A';
        }

        return preg_replace('/^(\d{4})\/(\d{2})?(\d{2})$/', '$1/$3', $session->name) ?: $session->name;
    };
    $studentYear = function ($student): string {
        $level = $student->placement?->academicLevel?->level ?? $student->academicLevel?->level;

        return $level ? (string) max(1, (int) floor($level / 100)) : 'N/A';
    };
    $can = fn (string $permission): bool => \App\Support\PortalPermission::userHas(auth('admin')->user(), $permission);
@endphp

<x-layouts.app-shell title="Student Management" role="Admin" :navigation="$navigation">
    @if (session('status'))
        <x-ui.alert title="Saved" tone="success">{{ session('status') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert title="Action required" tone="danger">{{ $errors->first() }}</x-ui.alert>
    @endif

    <div class="grid gap-4 md:grid-cols-4">
        <x-ui.stat-card label="Students" :value="$students->total()" meta="Current filtered list" />
        <x-ui.stat-card label="Active" :value="$students->getCollection()->filter(fn ($student) => $studentStatus($student) === 'active')->count()" meta="Visible with placement" tone="cyan" />
        <x-ui.stat-card label="Inactive" :value="$students->getCollection()->filter(fn ($student) => $studentStatus($student) === 'inactive')->count()" meta="No placement record" tone="amber" />
        <x-ui.stat-card label="Imports" :value="$imports->count()" meta="Recent upload history" tone="rose" />
    </div>

    <x-ui.card class="mt-6" title="Student List" description="Search, filter, export, suspend, and reactivate student records.">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-3">
            <div>
                <p class="text-sm font-semibold text-[var(--text-strong)]">Student actions</p>
                <p class="mt-1 text-xs text-[var(--text-soft)]">Create a single record or preview a bulk import without leaving the list.</p>
            </div>
            @if ($can('students.create') || $can('students.import') || $can('students.export'))
                <div class="flex flex-wrap gap-2">
                    @if ($can('students.create'))
                        <x-ui.button type="button" data-modal-target="#add-student-modal">
                            <x-ui.icon name="user-plus" class="size-4" />
                            Add Student
                        </x-ui.button>
                    @endif
                    @if ($can('students.import'))
                        <x-ui.button type="button" variant="secondary" data-modal-target="#bulk-upload-modal">
                            <x-ui.icon name="upload" class="size-4" />
                            Bulk Upload
                        </x-ui.button>
                    @endif
                    @if ($can('students.export'))
                        <x-ui.button type="button" variant="secondary" data-modal-target="#download-posting-modal">
                            <x-ui.icon name="download" class="size-4" />
                            Download Student Posting
                        </x-ui.button>
                    @endif
                </div>
            @endif
        </div>

        <form method="GET" action="{{ route('admin.students.index') }}" data-ajax="false" class="mb-4 grid gap-3 lg:grid-cols-[1fr_12rem_12rem_auto]">
            <x-ui.input label="Search" name="search" value="{{ request('search') }}" placeholder="Name, email, or matric number..." data-live-search="#students-management-table tbody tr" />
            <label class="block">
                <span class="text-sm font-medium text-[var(--text-strong)]">Status</span>
                <select name="status" class="siwes-form-control mt-2">
                    <option value="">All</option>
                    @foreach (['inactive', 'active', 'suspended'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="text-sm font-medium text-[var(--text-strong)]">Session</span>
                <select name="academic_session_id" class="siwes-form-control mt-2">
                    <option value="">All</option>
                    @foreach ($sessions as $session)
                        <option value="{{ $session->id }}" @selected((string) request('academic_session_id') === (string) $session->id)>{{ $session->name }}</option>
                    @endforeach
                </select>
            </label>
            <div class="flex items-end gap-2">
                <x-ui.button type="submit">Filter</x-ui.button>
                @if ($can('students.export'))
                    <x-ui.button :href="route('admin.students.export')" variant="secondary">Export</x-ui.button>
                @endif
            </div>
        </form>

        <div class="overflow-hidden rounded-2xl border border-[var(--line)] bg-[var(--surface-raised)]">
            <div class="overflow-x-auto">
                <table id="students-management-table" class="min-w-[68rem] w-full divide-y divide-[var(--line)] text-left text-sm">
                    <thead class="bg-[var(--surface-muted)] text-xs font-extrabold text-[var(--text-strong)]">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3">Name</th>
                            <th class="whitespace-nowrap px-4 py-3">Matric Number</th>
                            <th class="whitespace-nowrap px-4 py-3">Faculty</th>
                            <th class="whitespace-nowrap px-4 py-3">Course</th>
                            <th class="whitespace-nowrap px-4 py-3">Year</th>
                            <th class="whitespace-nowrap px-4 py-3">Academic Year</th>
                            <th class="whitespace-nowrap px-4 py-3">Phone</th>
                            <th class="whitespace-nowrap px-4 py-3">Email</th>
                            <th class="whitespace-nowrap px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--line)]">
                        @forelse ($students as $student)
                            @php
                                $status = $studentStatus($student);
                                $statusClasses = match ($status) {
                                    'active' => 'border border-emerald-700/20 bg-emerald-600 text-white dark:border-emerald-200/20 dark:bg-emerald-500 dark:text-white',
                                    'suspended' => 'border border-rose-700/20 bg-rose-100 text-rose-800 dark:border-rose-200/20 dark:bg-rose-400/18 dark:text-rose-100',
                                    default => 'border border-red-700/20 bg-red-600 text-white dark:border-red-200/20 dark:bg-red-500 dark:text-white',
                                };
                            @endphp
                            <tr class="theme-transition hover:bg-brand-600/5">
                                <td class="whitespace-nowrap px-4 py-3">
                                    <a class="font-extrabold text-brand-700 underline-offset-2 hover:text-brand-600 hover:underline dark:text-brand-200 dark:hover:text-brand-100" href="{{ route('admin.students.show', $student) }}">
                                        {{ $student->user->name }}
                                    </a>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-[var(--text-strong)]">{{ $student->matric_no }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-[var(--text-strong)]">{{ $student->faculty?->name ?? 'N/A' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-[var(--text-strong)]">{{ $student->department?->name ?? 'N/A' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-[var(--text-strong)]">{{ $studentYear($student) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-[var(--text-strong)]">{{ $academicYear($student->placement?->academicSession ?? $student->academicSession) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-[var(--text-strong)]">{{ $student->user->phone ?: 'N/A' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-[var(--text-strong)]">{{ $student->user->email ?: 'N/A' }}</td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="inline-flex rounded-md px-2.5 py-1 text-xs font-extrabold uppercase {{ $statusClasses }}">{{ $status }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-sm text-[var(--text-soft)]">No students found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $students->links() }}
        </div>
    </x-ui.card>

    <x-ui.card id="bulk-upload" class="mt-6" title="Import History" description="Recent upload attempts and queued processing results.">
        <x-ui.data-table
            id="student-imports-table"
            :headers="['File', 'Status', 'Rows', 'Success', 'Failed']"
            :rows="$imports->map(fn ($import) => [
                e($import->original_filename),
                e(ucfirst($import->status)),
                (string) $import->total_rows,
                (string) $import->successful_rows,
                (string) $import->failed_rows,
            ])->all()"
        />
    </x-ui.card>

    @if ($can('students.create'))
        <x-ui.modal id="add-student-modal" title="Add Student" class="w-[min(64rem,calc(100vw-2rem))]">
            <form method="POST" action="{{ route('admin.students.store') }}" class="grid gap-4">
                @csrf
                <div class="grid gap-3 md:grid-cols-4">
                    <x-ui.input label="FIRST NAME" name="first_name" placeholder="First name" required />
                    <x-ui.input label="MIDDLE NAME" name="middle_name" placeholder="Middle name" />
                    <x-ui.input label="LAST NAME" name="last_name" placeholder="Last name" required />
                    <x-ui.input label="MATRIC NUMBER" name="matric_no" placeholder="2026/CSC/001" required />
                </div>
                <div class="flex justify-end gap-2 border-t border-[var(--line)] pt-4">
                    <x-ui.button type="button" variant="ghost" data-modal-close>Cancel</x-ui.button>
                    <x-ui.button type="submit">Create Student</x-ui.button>
                </div>
            </form>
        </x-ui.modal>
    @endif

    @if ($can('students.import'))
        <x-ui.modal id="bulk-upload-modal" title="Bulk Upload" class="w-[min(52rem,calc(100vw-2rem))]">
            <div class="max-h-[75vh] overflow-y-auto pr-1">
                <p class="mb-4 text-sm text-[var(--text-soft)]">Upload CSV or XLSX files for preview, duplicate detection, and queued processing.</p>
                <div class="mb-4 flex flex-wrap gap-2">
                    <x-ui.button :href="route('admin.students.template', 'csv')" variant="secondary">CSV Template</x-ui.button>
                    <x-ui.button :href="route('admin.students.template', 'xlsx')" variant="secondary">XLSX Template</x-ui.button>
                </div>
                <form method="POST" action="{{ route('admin.students.imports.preview') }}" enctype="multipart/form-data" class="grid gap-4" data-preview-target="#student-import-preview" data-ajax-reset="false">
                    @csrf
                    <fieldset class="rounded-2xl border border-[var(--line)] bg-[var(--surface-muted)] p-4">
                        <legend class="px-1 text-sm font-semibold text-[var(--text-strong)]">Auto Activate Uploaded Students</legend>
                        <p class="mt-1 text-xs text-[var(--text-soft)]">Choose whether students in this file become active immediately after the import is processed.</p>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-[var(--line)] bg-[var(--surface)] px-4 py-3 text-sm font-semibold text-[var(--text-strong)] transition hover:border-brand-400">
                                <input type="radio" name="auto_activate" value="1" class="h-4 w-4 accent-brand-600" checked>
                                <span>ON - Activate students</span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-[var(--line)] bg-[var(--surface)] px-4 py-3 text-sm font-semibold text-[var(--text-strong)] transition hover:border-brand-400">
                                <input type="radio" name="auto_activate" value="0" class="h-4 w-4 accent-brand-600">
                                <span>OFF - Keep inactive</span>
                            </label>
                        </div>
                    </fieldset>
                    <x-ui.input label="Student File" name="students_file" type="file" accept=".csv,.txt,.xlsx" required />
                    <x-ui.button type="submit">Preview Import</x-ui.button>
                </form>
                <div id="student-import-preview" class="mt-4"></div>
            </div>
        </x-ui.modal>
    @endif

    @if ($can('students.export'))
        <x-ui.modal id="download-posting-modal" title="Download Student Posting List" class="w-[min(30rem,calc(100vw-2rem))]">
            <form method="GET" action="{{ route('admin.students.posting-list') }}" data-ajax="false" class="grid gap-4">
                <label class="block">
                    <span class="text-sm font-medium text-[var(--text-strong)]">Filter by Student Level (Optional)</span>
                    <select name="academic_level_id" class="siwes-form-control mt-2">
                        <option value="">All Levels</option>
                        @foreach ($postingLevels as $level)
                            <option value="{{ $level->id }}">{{ $level->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-[var(--text-strong)]">Filter by State (Optional)</span>
                    <select name="state" class="siwes-form-control mt-2">
                        <option value="">All States</option>
                        @foreach ($postingStates as $state)
                            <option value="{{ $state }}">{{ $state }}</option>
                        @endforeach
                    </select>
                </label>

                <div class="flex justify-end gap-2 border-t border-[var(--line)] pt-4">
                    <x-ui.button type="button" variant="ghost" data-modal-close>Cancel</x-ui.button>
                    <x-ui.button type="submit">Download</x-ui.button>
                </div>
            </form>
        </x-ui.modal>
    @endif
</x-layouts.app-shell>
