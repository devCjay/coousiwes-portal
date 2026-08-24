@php
    $navigation = [
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'D'],
        ['label' => 'Generate List', 'href' => route('admin.generate-list.index'), 'icon' => 'file-text'],
        ['label' => 'Students', 'href' => route('admin.students.index'), 'icon' => 'S'],
        ['label' => 'Supervisors', 'href' => route('admin.supervisors.index'), 'active' => true, 'icon' => 'V'],
        ['label' => 'Tickets', 'href' => route('admin.tickets.index'), 'icon' => 'T'],
    ];

    $chartPayload = $chartMetrics->map(fn (array $supervisor): array => [
        'name' => $supervisor['name'],
        'performance' => $supervisor['performance_score'],
        'students' => $supervisor['students_assigned'],
    ])->values();

    $summaryCards = [
        ['label' => 'Total Supervisors', 'value' => $summary['total_supervisors'], 'icon' => 'presentation', 'class' => 'from-sky-500 to-cyan-500'],
        ['label' => 'Total Students Assigned', 'value' => $summary['students_assigned'], 'icon' => 'users', 'class' => 'from-emerald-600 to-teal-500'],
        ['label' => 'Total Assessments', 'value' => $summary['assessments'], 'icon' => 'clipboard-check', 'class' => 'from-amber-400 to-orange-500'],
        ['label' => 'Avg Performance Score', 'value' => number_format($summary['average_performance'], 1), 'icon' => 'star', 'class' => 'from-cyan-600 to-indigo-600'],
    ];
    $can = fn (string $permission): bool => \App\Support\PortalPermission::userHas(auth('admin')->user(), $permission);
@endphp

<x-layouts.app-shell title="Manage Supervisors" role="Admin" :navigation="$navigation">
    <div class="space-y-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-2xl font-extrabold tracking-normal text-[var(--text-strong)]">Manage Supervisors</h1>
                <p class="mt-1 text-sm text-[var(--text-soft)]">Track supervisor workload, assessment performance, assignments, and account actions.</p>
            </div>
            @if ($can('supervisors.create') || $can('supervisors.assign'))
                <div class="flex flex-wrap gap-3">
                    @if ($can('supervisors.create'))
                        <x-ui.button type="button" data-modal-target="#add-supervisor-modal">Add Supervisor</x-ui.button>
                    @endif
                    @if ($can('supervisors.assign'))
                        <x-ui.button type="button" variant="secondary" data-modal-target="#assign-student-modal">Assign Student</x-ui.button>
                        <x-ui.button type="button" variant="secondary" data-modal-target="#bulk-assignment-modal">Bulk Assignment</x-ui.button>
                    @endif
                </div>
            @endif
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($summaryCards as $card)
                <article class="rounded-2xl bg-gradient-to-br {{ $card['class'] }} p-5 text-center text-white shadow-[0_18px_38px_rgb(8_15_12_/_0.12)]">
                    <x-ui.icon :name="$card['icon']" class="mx-auto size-9" />
                    <div class="mt-3 text-3xl font-black leading-none">{{ $card['value'] }}</div>
                    <div class="mt-2 text-xs font-semibold text-white/90">{{ $card['label'] }}</div>
                </article>
            @endforeach
        </div>

        <section class="siwes-surface overflow-hidden rounded-2xl border border-[var(--line)] bg-[var(--surface-raised)] p-4 shadow-[0_14px_34px_rgb(8_15_12_/_0.055)] sm:p-5">
            <h2 class="text-base font-extrabold text-[var(--text-strong)]">Supervisor Performance Overview (Top 10)</h2>
            <div class="mt-4 h-[18rem] min-w-0 rounded-xl border border-[var(--line)] bg-[var(--surface)] p-2 sm:h-[22rem]">
                <canvas
                    id="supervisor-performance-chart"
                    class="h-full w-full"
                    data-chart='@json($chartPayload)'
                    aria-label="Top 10 Supervisors - Performance vs Workload"
                ></canvas>
            </div>
        </section>

        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <form method="GET" action="{{ route('admin.supervisors.index') }}" data-ajax="false" class="w-full lg:max-w-sm">
                <select name="year" class="siwes-form-control" onchange="this.form.submit()">
                    <option value="">All Years</option>
                    @foreach ($analyticsYears as $year)
                        <option value="{{ $year }}" @selected((int) $analyticsYear === (int) $year)>{{ $year }}</option>
                    @endforeach
                </select>
                @if (request('search'))
                    <input type="hidden" name="search" value="{{ request('search') }}">
                @endif
            </form>
            <x-ui.button :href="route('admin.supervisors.export', array_filter(['year' => $analyticsYear]))" variant="primary">Export Analytics (Excel)</x-ui.button>
        </div>

        <section class="siwes-surface overflow-hidden rounded-2xl border border-[var(--line)] bg-[var(--surface-raised)] p-4 shadow-[0_14px_34px_rgb(8_15_12_/_0.055)] sm:p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h2 class="inline-flex flex-wrap items-center gap-2 text-base font-extrabold text-[var(--text-strong)]">
                        Supervisors List with Performance Metrics
                        <span class="rounded-md bg-slate-600 px-2 py-1 text-xs font-bold text-white">Sorted by Performance Score</span>
                    </h2>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.supervisors.index') }}" data-ajax="false" class="mt-4 grid gap-3 lg:grid-cols-[1fr_auto_auto]">
                <input type="hidden" name="year" value="{{ $analyticsYear }}">
                <x-ui.input
                    label="Search supervisors"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search supervisors by name, email, account name, bank name, or account number..."
                />
                <div class="flex items-end">
                    <x-ui.button type="submit">Search</x-ui.button>
                </div>
                <div class="flex items-end">
                    <x-ui.button :href="route('admin.supervisors.index', array_filter(['year' => $analyticsYear]))" variant="secondary">Clear</x-ui.button>
                </div>
            </form>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-[70rem] w-full divide-y divide-[var(--line)] text-left text-sm">
                    <thead class="text-xs font-bold text-[var(--text-strong)]">
                        <tr>
                            <th class="whitespace-nowrap px-3 py-3">Select</th>
                            <th class="whitespace-nowrap px-3 py-3">Rank</th>
                            <th class="whitespace-nowrap px-3 py-3">Name</th>
                            <th class="whitespace-nowrap px-3 py-3">Email</th>
                            <th class="whitespace-nowrap px-3 py-3">Students</th>
                            <th class="whitespace-nowrap px-3 py-3">Assessments</th>
                            <th class="whitespace-nowrap px-3 py-3">Feedback</th>
                            <th class="whitespace-nowrap px-3 py-3">Months</th>
                            <th class="whitespace-nowrap px-3 py-3">Performance</th>
                            <th class="whitespace-nowrap px-3 py-3">Rating</th>
                            <th class="whitespace-nowrap px-3 py-3">Payment</th>
                            <th class="whitespace-nowrap px-3 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--line)]">
                        @forelse ($supervisors as $supervisor)
                            <tr class="theme-transition hover:bg-brand-600/5">
                                <td class="whitespace-nowrap px-3 py-3">
                                    <input type="checkbox" class="rounded border-[var(--line)]" aria-label="Select {{ $supervisor['name'] }}">
                                </td>
                                <td class="whitespace-nowrap px-3 py-3 font-semibold text-[var(--text-strong)]">{{ $loop->iteration + $supervisors->firstItem() - 1 }}</td>
                                <td class="whitespace-nowrap px-3 py-3">
                                    <a class="font-bold text-brand-700 dark:text-brand-200" href="{{ $supervisor['show_url'] }}">{{ $supervisor['name'] }}</a>
                                    <div class="text-xs text-[var(--text-soft)]">{{ $supervisor['staff_no'] }}</div>
                                </td>
                                <td class="whitespace-nowrap px-3 py-3 text-[var(--text-strong)]">{{ $supervisor['email'] }}</td>
                                <td class="whitespace-nowrap px-3 py-3 text-[var(--text-strong)]">{{ $supervisor['students_assigned'] }}</td>
                                <td class="whitespace-nowrap px-3 py-3 text-[var(--text-strong)]">{{ $supervisor['assessments'] }}</td>
                                <td class="whitespace-nowrap px-3 py-3 text-[var(--text-strong)]">{{ $supervisor['feedback'] }}</td>
                                <td class="whitespace-nowrap px-3 py-3 text-[var(--text-strong)]">{{ $supervisor['months'] }}</td>
                                <td class="whitespace-nowrap px-3 py-3">
                                    <div class="flex min-w-32 items-center gap-2">
                                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-200 dark:bg-white/10">
                                            <div class="h-full rounded-full bg-brand-600" style="width: {{ min(100, $supervisor['performance_score']) }}%"></div>
                                        </div>
                                        <span class="text-xs font-bold text-[var(--text-strong)]">{{ number_format($supervisor['performance_score'], 1) }}%</span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-3 py-3 text-[var(--text-strong)]">{{ $supervisor['rating'] }}</td>
                                <td class="whitespace-nowrap px-3 py-3 text-[var(--text-strong)]">{{ $supervisor['payment'] }}</td>
                                <td class="whitespace-nowrap px-3 py-3">
                                    <x-ui.button :href="$supervisor['show_url']" variant="secondary" class="px-3 py-2 text-xs">View</x-ui.button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="px-3 py-8 text-center text-sm text-[var(--text-soft)]">No supervisors found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $supervisors->links() }}
            </div>
        </section>

        <x-ui.card title="Current Assignments" description="Search active student-supervisor assignments and revoke assignment access when required.">
            @if ($can('supervisors.assign'))
                <form id="bulk-revoke-assignments-form" method="POST" action="{{ route('admin.supervisor-assignments.bulk-revoke') }}">
                    @csrf
                    <input type="hidden" name="reason" value="Revoked from assignment table">
                </form>
            @endif

            <form method="GET" action="{{ route('admin.supervisors.index') }}" data-ajax="false" class="mb-4 grid gap-3 lg:grid-cols-[1fr_auto_auto]">
                <x-ui.input
                    label="Search assignments"
                    name="assignment_search"
                    value="{{ request('assignment_search') }}"
                    placeholder="Search by student name, supervisor, matric number, or year..."
                />
                <div class="flex items-end gap-2">
                    <x-ui.button type="submit">Search</x-ui.button>
                    <x-ui.button :href="route('admin.supervisors.index')" variant="secondary">Clear</x-ui.button>
                </div>
                <div class="flex items-end justify-start lg:justify-end">
                    <x-ui.button :href="route('admin.supervisors.assignments.export')" variant="secondary">Export Assignments</x-ui.button>
                </div>
            </form>

            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                @if ($can('supervisors.assign'))
                    <x-ui.button type="submit" form="bulk-revoke-assignments-form" variant="danger" data-loading-text="Revoking...">
                        Revoke Selected
                    </x-ui.button>
                @endif
                <span class="text-sm text-[var(--text-soft)]">{{ $assignments->total() }} active assignment(s)</span>
            </div>

            <div class="overflow-hidden rounded-2xl border border-[var(--line)] bg-[var(--surface-raised)] shadow-[0_14px_34px_rgb(8_15_12_/_0.055)]">
                <div class="overflow-x-auto">
                    <table id="assignments-table" class="min-w-full divide-y divide-[var(--line)] text-left text-sm">
                        <thead class="bg-brand-600/10 text-xs font-bold uppercase text-brand-700 dark:bg-white/5 dark:text-brand-200">
                            <tr>
                                @if ($can('supervisors.assign'))
                                    <th class="whitespace-nowrap px-4 py-3.5">
                                        <input type="checkbox" class="rounded border-[var(--line)]" data-check-all="[data-assignment-checkbox]" aria-label="Select all assignments">
                                    </th>
                                @endif
                                <th class="whitespace-nowrap px-4 py-3.5">#</th>
                                <th class="whitespace-nowrap px-4 py-3.5">Student Name</th>
                                <th class="whitespace-nowrap px-4 py-3.5">Matric Number</th>
                                <th class="whitespace-nowrap px-4 py-3.5">Year</th>
                                <th class="whitespace-nowrap px-4 py-3.5">Supervisor</th>
                                @if ($can('supervisors.assign'))
                                    <th class="whitespace-nowrap px-4 py-3.5">Action</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--line)] bg-[var(--surface-raised)]">
                            @forelse ($assignments as $assignment)
                                <tr class="theme-transition hover:bg-brand-600/5">
                                    @if ($can('supervisors.assign'))
                                        <td class="whitespace-nowrap px-4 py-3.5">
                                            <input
                                                type="checkbox"
                                                name="assignment_ids[]"
                                                value="{{ $assignment->id }}"
                                                form="bulk-revoke-assignments-form"
                                                class="rounded border-[var(--line)]"
                                                data-assignment-checkbox
                                                aria-label="Select assignment for {{ $assignment->student->matric_no }}"
                                            >
                                        </td>
                                    @endif
                                    <td class="whitespace-nowrap px-4 py-3.5 text-[var(--text-strong)]">{{ $loop->iteration + $assignments->firstItem() - 1 }}</td>
                                    <td class="whitespace-nowrap px-4 py-3.5 text-[var(--text-strong)]">{{ $assignment->student->user->name }}</td>
                                    <td class="whitespace-nowrap px-4 py-3.5 text-[var(--text-strong)]">{{ $assignment->student->matric_no }}</td>
                                    <td class="whitespace-nowrap px-4 py-3.5 text-[var(--text-strong)]">{{ $assignment->student->placement?->siwes_year ?? 'N/A' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3.5 text-[var(--text-strong)]">{{ $assignment->supervisor->user->name }}</td>
                                    @if ($can('supervisors.assign'))
                                        <td class="whitespace-nowrap px-4 py-3.5">
                                            <form method="POST" action="{{ route('admin.supervisor-assignments.revoke', $assignment) }}" class="inline-block">
                                                @csrf
                                                <input type="hidden" name="reason" value="Revoked from assignment table">
                                                <x-ui.button type="submit" variant="danger" class="px-3 py-2 text-xs" data-loading-text="Revoking...">Revoke</x-ui.button>
                                            </form>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $can('supervisors.assign') ? 7 : 5 }}" class="px-4 py-8 text-center text-sm text-[var(--text-soft)]">No assignments found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4">
                {{ $assignments->links() }}
            </div>
        </x-ui.card>
    </div>

    @if ($can('supervisors.create'))
        <x-ui.modal id="add-supervisor-modal" title="Add Supervisor" class="w-[min(42rem,calc(100vw-2rem))]">
            <form method="POST" action="{{ route('admin.supervisors.store') }}" class="grid gap-4">
                @csrf
                <div class="grid gap-4 md:grid-cols-2">
                    <x-ui.input label="Full Name" name="name" required />
                    <x-ui.input label="Email" name="email" type="email" required />
                </div>
                <div class="flex justify-end gap-2">
                    <x-ui.button type="button" variant="ghost" data-modal-close>Cancel</x-ui.button>
                    <x-ui.button type="submit">Create Supervisor</x-ui.button>
                </div>
            </form>
        </x-ui.modal>
    @endif

    @if ($can('supervisors.assign'))
        <x-ui.modal id="assign-student-modal" title="Assign Student" class="w-[min(40rem,calc(100vw-2rem))]">
            <form method="POST" action="{{ route('admin.supervisor-assignments.store') }}" class="grid gap-4">
                @csrf
                <label class="block">
                    <span class="text-sm font-medium text-[var(--text-strong)]">Supervisor</span>
                    <select name="supervisor_id" class="siwes-form-control mt-2">
                        @foreach ($allSupervisors as $supervisor)
                            <option value="{{ $supervisor->id }}">{{ $supervisor->user->name }} / {{ $supervisor->staff_no }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="text-sm font-medium text-[var(--text-strong)]">Student</span>
                    <select name="student_id" class="siwes-form-control mt-2">
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}">{{ $student->user->name }} / {{ $student->matric_no }} / {{ $student->department?->code ?? 'N/A' }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="flex justify-end gap-2">
                    <x-ui.button type="button" variant="ghost" data-modal-close>Cancel</x-ui.button>
                    <x-ui.button type="submit">Assign Student</x-ui.button>
                </div>
            </form>
        </x-ui.modal>

        <x-ui.modal id="bulk-assignment-modal" title="Bulk Assignment" class="w-[min(52rem,calc(100vw-2rem))]">
            <form method="POST" action="{{ route('admin.supervisor-assignments.bulk') }}" class="grid gap-4">
                @csrf
                <label class="block">
                    <span class="text-sm font-medium text-[var(--text-strong)]">Supervisor</span>
                    <select name="supervisor_id" class="siwes-form-control mt-2">
                        @foreach ($allSupervisors as $supervisor)
                            <option value="{{ $supervisor->id }}">{{ $supervisor->user->name }} / {{ $supervisor->staff_no }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="grid gap-3 md:grid-cols-2">
                    <label class="block">
                        <span class="text-sm font-medium text-[var(--text-strong)]">Faculty</span>
                        <select name="faculty_id" class="siwes-form-control mt-2" data-filter-parent="#bulk-assignment-department">
                            <option value="">Any</option>
                            @foreach ($faculties as $faculty)
                                <option value="{{ $faculty->id }}">{{ $faculty->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium text-[var(--text-strong)]">Department</span>
                        <select id="bulk-assignment-department" name="department_id" class="siwes-form-control mt-2">
                            <option value="">Any</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}" data-parent-value="{{ $department->faculty_id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium text-[var(--text-strong)]">Session</span>
                        <select name="academic_session_id" class="siwes-form-control mt-2">
                            <option value="">Any</option>
                            @foreach ($sessions as $session)
                                <option value="{{ $session->id }}">{{ $session->name }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <div class="flex justify-end gap-2">
                    <x-ui.button type="button" variant="ghost" data-modal-close>Cancel</x-ui.button>
                    <x-ui.button type="submit">Bulk Assign</x-ui.button>
                </div>
            </form>
        </x-ui.modal>
    @endif

    <script>
        (() => {
            const canvas = document.getElementById('supervisor-performance-chart');

            if (!canvas) {
                return;
            }

            const data = JSON.parse(canvas.dataset.chart || '[]');
            const context = canvas.getContext('2d');

            const render = () => {
                const ratio = window.devicePixelRatio || 1;
                const rect = canvas.getBoundingClientRect();

                canvas.width = rect.width * ratio;
                canvas.height = rect.height * ratio;
                context.setTransform(ratio, 0, 0, ratio, 0, 0);
                context.clearRect(0, 0, rect.width, rect.height);

                const width = rect.width;
                const height = rect.height;
                const padding = { top: 54, right: 46, bottom: 46, left: 52 };
                const plotWidth = width - padding.left - padding.right;
                const plotHeight = height - padding.top - padding.bottom;
                const maxStudents = Math.max(1, ...data.map((item) => Number(item.students || 0)));

                context.font = '600 11px Inter, system-ui, sans-serif';
                context.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--text-soft') || '#667085';
                context.textAlign = 'center';
                context.fillText('Top 10 Supervisors - Performance vs Workload', width / 2, 20);

                context.fillStyle = '#60a5fa';
                context.fillRect(width / 2 - 130, 36, 30, 9);
                context.fillStyle = '#fda4af';
                context.fillRect(width / 2 + 35, 36, 30, 9);
                context.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--text-soft') || '#667085';
                context.textAlign = 'left';
                context.fillText('Performance Score (%)', width / 2 - 94, 44);
                context.fillText('Students Assigned', width / 2 + 72, 44);

                context.strokeStyle = 'rgba(100, 116, 139, 0.24)';
                context.lineWidth = 1;
                context.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--text-soft') || '#667085';
                context.textAlign = 'right';

                for (let tick = 0; tick <= 10; tick += 1) {
                    const y = padding.top + plotHeight - (plotHeight * tick / 10);
                    context.beginPath();
                    context.moveTo(padding.left, y);
                    context.lineTo(width - padding.right, y);
                    context.stroke();
                    context.fillText(String(tick * 10), padding.left - 8, y + 4);
                    context.textAlign = 'left';
                    context.fillText((maxStudents * tick / 10).toFixed(1), width - padding.right + 8, y + 4);
                    context.textAlign = 'right';
                }

                context.save();
                context.translate(14, padding.top + plotHeight / 2);
                context.rotate(-Math.PI / 2);
                context.textAlign = 'center';
                context.fillText('Performance Score (%)', 0, 0);
                context.restore();

                context.save();
                context.translate(width - 10, padding.top + plotHeight / 2);
                context.rotate(Math.PI / 2);
                context.textAlign = 'center';
                context.fillText('Number of Students', 0, 0);
                context.restore();

                if (!data.length) {
                    context.textAlign = 'center';
                    context.font = '700 13px Inter, system-ui, sans-serif';
                    context.fillText('No supervisor performance data available.', width / 2, height / 2);
                    return;
                }

                const groupWidth = plotWidth / data.length;
                const barWidth = Math.max(8, Math.min(24, groupWidth * 0.28));

                data.forEach((item, index) => {
                    const center = padding.left + groupWidth * index + groupWidth / 2;
                    const performanceHeight = plotHeight * Math.min(100, Number(item.performance || 0)) / 100;
                    const studentHeight = plotHeight * Number(item.students || 0) / maxStudents;
                    const baseline = padding.top + plotHeight;

                    context.fillStyle = '#60a5fa';
                    context.fillRect(center - barWidth - 2, baseline - performanceHeight, barWidth, performanceHeight);
                    context.fillStyle = '#fda4af';
                    context.fillRect(center + 2, baseline - studentHeight, barWidth, studentHeight);

                    context.save();
                    context.translate(center, height - 12);
                    context.rotate(-Math.PI / 6);
                    context.textAlign = 'right';
                    context.font = '600 10px Inter, system-ui, sans-serif';
                    context.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--text-soft') || '#667085';
                    context.fillText(String(item.name || '').slice(0, 18), 0, 0);
                    context.restore();
                });
            };

            render();
            window.addEventListener('resize', render);
        })();
    </script>
</x-layouts.app-shell>
