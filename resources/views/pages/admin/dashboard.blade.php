@php
    $navigation = [
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'active' => true, 'icon' => 'D'],
        ['label' => 'Generate List', 'href' => route('admin.generate-list.index'), 'icon' => 'file-text'],
        ...(\App\Support\PortalPermission::isRootAdmin(auth()->user()) ? [['label' => 'Control', 'href' => route('admin.control.index'), 'icon' => 'C']] : []),
        ['label' => 'Students', 'href' => route('admin.students.index'), 'icon' => 'S'],
        ['label' => 'Bulk Upload', 'href' => route('admin.students.index').'#bulk-upload', 'icon' => 'U'],
        ['label' => 'Tickets', 'href' => route('admin.tickets.index'), 'icon' => 'T'],
        ['label' => 'Supervisors', 'href' => route('admin.supervisors.index'), 'icon' => 'V'],
        ['label' => 'Payments', 'href' => route('admin.payments.index'), 'icon' => 'P'],
        ['label' => 'Reports', 'href' => route('admin.reports.index'), 'icon' => 'R'],
        ['label' => 'Rubric', 'href' => route('admin.assessments.rubric.index'), 'icon' => 'A'],
        ['label' => 'Academics', 'href' => route('admin.academics.index'), 'icon' => 'A'],
        ['label' => 'Settings', 'href' => route('admin.settings.index'), 'icon' => 'G'],
    ];
    $can = fn (string $permission): bool => \App\Support\PortalPermission::userHas(auth()->user(), $permission);
@endphp

<x-layouts.app-shell title="Admin Dashboard" role="Admin" :navigation="$navigation">
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @if ($can('students.view'))
            <x-ui.stat-card label="Total Students" :value="number_format($stats['totalStudents'])" meta="Database records" />
        @endif
        @if ($can('students.view'))
            <x-ui.stat-card label="Activated Students" :value="number_format($stats['activatedStudents'])" :meta="$can('payments.view') ? number_format($stats['verifiedPayments']).' Korapay verified' : 'Students with placement'" tone="cyan" />
        @elseif ($can('payments.view'))
            <x-ui.stat-card label="Verified Payments" :value="number_format($stats['verifiedPayments'])" meta="Korapay verified" tone="cyan" />
        @endif
        @if ($can('supervisors.view'))
            <x-ui.stat-card label="Supervisors" :value="number_format($stats['totalSupervisors'])" :meta="number_format($stats['activeSupervisors']).' active'" tone="amber" />
        @endif
        @if ($can('tickets.view'))
            <x-ui.stat-card label="Open Tickets" :value="number_format($stats['openTickets'])" :meta="number_format($stats['urgentTickets']).' expiring soon'" tone="rose" />
        @endif
    </div>

    @php
        $conicGradient = function ($items, int $total): string {
            if ($items->sum('count') === 0) {
                return '#e5e7eb 0deg 360deg';
            }

            $cursor = 0;

            return $items
                ->map(function ($item) use (&$cursor, $total) {
                    $start = $cursor;
                    $degrees = $total > 0 ? (($item['count'] / $total) * 360) : 0;
                    $cursor += $degrees;

                    return "{$item['color']} {$start}deg {$cursor}deg";
                })
                ->implode(', ');
        };
    @endphp

    @if ($can('students.view') || $can('tickets.view'))
    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        @if ($can('students.view'))
        <x-ui.card title="Student Distribution by Faculty" description="Live count grouped by faculty records.">
            @if ($facultyDistribution->isNotEmpty())
                <div class="overflow-x-auto pb-2">
                    <div class="flex h-72 min-w-[42rem] items-end gap-3 border-b border-l border-[var(--line)] px-2 pt-4">
                        @foreach ($facultyDistribution as $faculty)
                            @php($height = $faculty->students_count > 0 ? max(8, (int) round(($faculty->students_count / $maxFacultyStudents) * 100)) : 2)
                            <div class="flex min-w-14 flex-1 flex-col items-center gap-2" title="{{ $faculty->name }}: {{ number_format($faculty->students_count) }}">
                                <span class="w-full rounded-t-md bg-brand-500/75 shadow-[0_0_14px_rgb(0_81_54_/_0.24)]" style="height: {{ $height }}%"></span>
                                <span class="max-w-full truncate text-[10px] font-semibold text-[var(--text-soft)]">{{ $faculty->code }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="grid h-72 place-items-center rounded-lg border border-dashed border-[var(--line)] text-sm text-[var(--text-soft)]">
                    No student distribution data yet.
                </div>
            @endif
        </x-ui.card>
        @endif

        @if ($can('tickets.view'))
        <x-ui.card title="Ticket Usage Statistics" description="Live status breakdown from ticket records.">
            <div class="grid place-items-center py-4">
                <div class="grid size-52 place-items-center rounded-full p-8 shadow-glow" style="background: conic-gradient({{ $conicGradient($ticketDistribution, $ticketChartTotal) }});">
                    <div class="grid size-full place-items-center rounded-full bg-[var(--surface-raised)]">
                        <div class="text-center">
                            <p class="text-3xl font-semibold">{{ number_format($ticketDistribution->sum('count')) }}</p>
                            <p class="text-xs text-[var(--text-soft)]">tickets</p>
                        </div>
                    </div>
                </div>
                <div class="mt-5 grid w-full grid-cols-2 gap-3 text-xs">
                    @foreach ($ticketDistribution as $item)
                        <div class="flex items-center justify-between gap-2 rounded-md border border-[var(--line)] px-3 py-2">
                            <span class="inline-flex items-center gap-2 font-semibold text-[var(--text-soft)]">
                                <span class="size-2.5 rounded-full {{ $item['class'] }}"></span>
                                {{ $item['label'] }}
                            </span>
                            <span class="font-bold text-[var(--text-strong)]">{{ number_format($item['count']) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </x-ui.card>
        @endif

        @if ($can('students.view'))
        <x-ui.card title="Gender Distribution" description="Live student gender profile from database records.">
            <div class="grid place-items-center py-4">
                <div class="grid size-52 place-items-center rounded-full p-8 shadow-glow" style="background: conic-gradient({{ $conicGradient($genderDistribution, $genderChartTotal) }});">
                    <div class="grid size-full place-items-center rounded-full bg-[var(--surface-raised)]">
                        <div class="text-center">
                            <p class="text-3xl font-semibold">{{ number_format($genderDistribution->sum('count')) }}</p>
                            <p class="text-xs text-[var(--text-soft)]">students</p>
                        </div>
                    </div>
                </div>
                <div class="mt-5 grid w-full gap-3 text-xs">
                    @foreach ($genderDistribution as $item)
                        @php($width = max(3, (int) round(($item['count'] / $genderChartTotal) * 100)))
                        <div>
                            <div class="mb-1 flex items-center justify-between gap-3">
                                <span class="inline-flex items-center gap-2 font-semibold text-[var(--text-soft)]">
                                    <span class="size-2.5 rounded-full {{ $item['class'] }}"></span>
                                    {{ $item['label'] }}
                                </span>
                                <span class="font-bold text-[var(--text-strong)]">{{ number_format($item['count']) }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-[var(--surface-muted)]">
                                <span class="block h-full rounded-full {{ $item['class'] }}" style="width: {{ $width }}%"></span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </x-ui.card>
        @endif
    </div>
    @endif

    @if ($can('students.view') || $can('feedback.view'))
    <x-ui.card class="mt-6" title="Quick Reports" description="Operational snapshots generated from current portal records.">
        <div class="grid gap-4 md:grid-cols-3">
            @if ($can('students.view'))
            <div class="rounded-lg border border-[var(--line)] bg-[var(--surface-muted)]/55 p-5 text-center shadow-[0_14px_34px_rgb(8_15_12_/_0.06)] theme-transition hover:-translate-y-1 hover:border-amber-400">
                <span class="mx-auto grid size-10 place-items-center rounded-lg bg-amber-400/15 text-amber-600 dark:text-amber-200">
                    <x-ui.icon name="user-check" class="size-5" />
                </span>
                <p class="mt-3 text-sm font-semibold text-[var(--text-strong)]">Pending Activation</p>
                <p data-countup class="mt-2 text-3xl font-semibold text-cyber-amber">{{ number_format($stats['pendingActivation']) }}</p>
                <p class="mt-2 text-xs text-[var(--text-soft)]">Students yet to add placement</p>
            </div>
            @endif

            @if ($can('students.view'))
            <div class="rounded-lg border border-[var(--line)] bg-[var(--surface-muted)]/55 p-5 text-center shadow-[0_14px_34px_rgb(8_15_12_/_0.06)] theme-transition hover:-translate-y-1 hover:border-cyan-400">
                <span class="mx-auto grid size-10 place-items-center rounded-lg bg-cyan-400/15 text-cyan-600 dark:text-cyan-200">
                    <x-ui.icon name="clipboard-check" class="size-5" />
                </span>
                <p class="mt-3 text-sm font-semibold text-[var(--text-strong)]">Submitted Forms</p>
                <p data-countup class="mt-2 text-3xl font-semibold text-cyan-500">{{ number_format($stats['submittedForms']) }}</p>
                <p class="mt-2 text-xs text-[var(--text-soft)]">Placement forms submitted</p>
            </div>
            @endif

            @if ($can('feedback.view'))
            <div class="rounded-lg border border-[var(--line)] bg-[var(--surface-muted)]/55 p-5 text-center shadow-[0_14px_34px_rgb(8_15_12_/_0.06)] theme-transition hover:-translate-y-1 hover:border-brand-400">
                <span class="mx-auto grid size-10 place-items-center rounded-lg bg-brand-500/15 text-brand-700 dark:text-brand-200">
                    <x-ui.icon name="message-square" class="size-5" />
                </span>
                <p class="mt-3 text-sm font-semibold text-[var(--text-strong)]">Feedback Received</p>
                <p data-countup class="mt-2 text-3xl font-semibold text-brand-700 dark:text-brand-300">{{ number_format($stats['feedbackReceived']) }}</p>
                <p class="mt-2 text-xs text-[var(--text-soft)]">Total feedback entries</p>
            </div>
            @endif
        </div>
    </x-ui.card>
    @endif

    @if ($can('students.view'))
    <x-ui.card id="students" class="mt-6" title="Student List" description="Reusable table pattern with filters, statuses, and action controls.">
        <div class="mb-4 grid gap-3 md:grid-cols-[1fr_auto_auto]">
            <x-ui.input label="Search" name="search" placeholder="Search by name, reg no, department, phone..." data-live-search="#students-table tbody tr" />
            <label class="block">
                <span class="text-sm font-medium text-[var(--text-strong)]">Status</span>
                <select class="siwes-form-control mt-2">
                    <option>All Statuses</option>
                    <option>Activated</option>
                    <option>Inactive</option>
                </select>
            </label>
            <div class="flex items-end gap-2">
                <x-ui.button>Search</x-ui.button>
                @if ($can('students.create'))
                    <x-ui.button type="button" variant="secondary" data-modal-target="#student-modal">Add</x-ui.button>
                @endif
            </div>
        </div>

        <x-ui.data-table
            id="students-table"
            :headers="['Name', 'Reg No', 'Faculty', 'Department', 'Year', 'Status']"
            :rows="$recentStudents->map(function ($student) {
                $statusClasses = match ($student->activation_status) {
                    \App\Models\Student::STATUS_ACTIVE => 'bg-brand-500/10 text-brand-700 dark:text-brand-200',
                    \App\Models\Student::STATUS_SUSPENDED => 'bg-rose-500/10 text-rose-700 dark:text-rose-200',
                    default => 'bg-amber-500/10 text-amber-700 dark:text-amber-200',
                };

                return [
                    e($student->user->name),
                    e($student->matric_no),
                    e($student->faculty?->name ?? 'Not assigned'),
                    e($student->department?->name ?? 'Not assigned'),
                    e($student->academicSession?->name ?? 'Not assigned'),
                    '<span class=&quot;inline-flex rounded-md px-2 py-1 text-xs font-semibold '.$statusClasses.'&quot;>'.e(ucfirst($student->activation_status)).'</span>',
                ];
            })->all()"
        />
    </x-ui.card>
    @endif

    @unless ($can('students.view') || $can('tickets.view') || $can('supervisors.view') || $can('payments.view') || $can('feedback.view'))
        <x-ui.card class="mt-6" title="No Assigned Modules" description="Your dashboard will show module data after the super admin assigns roles or direct permissions to your admin account.">
            <p class="text-sm text-[var(--text-soft)]">Contact the super admin to request access.</p>
        </x-ui.card>
    @endunless

    @if ($can('students.create'))
        <x-ui.modal id="student-modal" title="Add Student" class="w-[min(64rem,calc(100vw-2rem))]">
            <form method="POST" action="{{ route('admin.students.store') }}" class="grid gap-4">
                @csrf
                <div class="grid gap-3 md:grid-cols-4">
                    <x-ui.input label="SURNAME" name="last_name" placeholder="Surname" />
                    <x-ui.input label="FIRST NAME" name="first_name" placeholder="First name" required />
                    <x-ui.input label="OTHER NAME" name="middle_name" placeholder="Other name" />
                    <x-ui.input label="REG NO" name="matric_no" placeholder="2026/CSC/001" required />
                </div>
                <div class="flex justify-end gap-2 border-t border-[var(--line)] pt-4">
                    <x-ui.button type="button" variant="ghost" data-modal-close>Cancel</x-ui.button>
                    <x-ui.button type="submit">Create Student</x-ui.button>
                </div>
            </form>
        </x-ui.modal>
    @endif
</x-layouts.app-shell>

