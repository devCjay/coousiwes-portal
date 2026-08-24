@php
    $navigation = [
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'D'],
        ['label' => 'Generate List', 'href' => route('admin.generate-list.index'), 'icon' => 'file-text'],
        ['label' => 'Students', 'href' => route('admin.students.index'), 'active' => true, 'icon' => 'S'],
        ['label' => 'Academics', 'href' => route('admin.academics.index'), 'icon' => 'A'],
        ['label' => 'Settings', 'href' => route('admin.settings.index'), 'icon' => 'G'],
    ];

    $metadata = $student->metadata ?? [];
    $placement = $student->placement;
    $displayStatus = $student->activation_status === 'suspended' ? 'suspended' : ($placement ? 'active' : 'inactive');
    $displayStatusClasses = match ($displayStatus) {
        'active' => 'bg-emerald-400/15 text-emerald-100 ring-emerald-300/25',
        'suspended' => 'bg-rose-400/15 text-rose-100 ring-rose-300/25',
        default => 'bg-amber-300/15 text-amber-100 ring-amber-200/25',
    };
    $academicYear = function ($session): string {
        if (! $session?->name) {
            return 'N/A';
        }

        return preg_replace('/^(\d{4})\/(\d{2})?(\d{2})$/', '$1/$3', $session->name) ?: $session->name;
    };
    $studentYear = function () use ($student, $placement): string {
        $level = $placement?->academicLevel?->level ?? $student->academicLevel?->level;

        return $level ? (string) max(1, (int) floor($level / 100)) : 'N/A';
    };
    $tabs = [
        ['id' => 'overview', 'label' => 'Overview', 'icon' => 'layout-dashboard'],
        ['id' => 'personal', 'label' => 'Personal Data', 'icon' => 'user-circle'],
        ['id' => 'academic', 'label' => 'Academic', 'icon' => 'graduation-cap'],
        ['id' => 'placement', 'label' => 'Placement', 'icon' => 'building'],
        ['id' => 'account', 'label' => 'Account', 'icon' => 'shield'],
    ];
    $can = fn (string $permission): bool => \App\Support\PortalPermission::userHas(auth('admin')->user(), $permission);
@endphp

<x-layouts.app-shell title="Student Profile" role="Admin" :navigation="$navigation">
    <section class="overflow-hidden rounded-2xl border border-brand-600/15 bg-[var(--surface-raised)] shadow-[0_24px_70px_rgb(8_15_12_/_0.10)]">
        <div class="relative bg-brand-700 p-5 text-white sm:p-7">
            <div class="absolute inset-y-0 right-0 hidden w-1/2 bg-[linear-gradient(120deg,transparent_0%,rgba(255,255,255,.08)_36%,transparent_37%,transparent_55%,rgba(255,255,255,.10)_56%,transparent_78%)] md:block"></div>
            <div class="relative flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div class="flex min-w-0 gap-4">
                    <span class="grid size-20 shrink-0 place-items-center rounded-2xl bg-white/12 text-white ring-1 ring-white/15 sm:size-24">
                        <x-ui.icon name="graduation-cap" class="size-10" />
                    </span>
                    <div class="min-w-0">
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-extrabold uppercase ring-1 {{ $displayStatusClasses }}">{{ $displayStatus }}</span>
                        <h1 class="mt-3 truncate text-2xl font-black tracking-normal sm:text-3xl">{{ $student->user->name }}</h1>
                        <p class="mt-1 text-sm font-semibold text-white/75">{{ $student->matric_no }}</p>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-white/66">
                            {{ $student->faculty?->name ?? 'N/A' }} / {{ $student->department?->name ?? 'N/A' }}
                        </p>
                    </div>
                </div>

                @if ($can('students.update'))
                    <div class="flex flex-wrap gap-2">
                        <x-ui.button type="button" data-modal-target="#edit-student-modal">Edit</x-ui.button>
                        <form method="POST" action="{{ route('admin.students.reset-password', $student) }}">
                            @csrf
                            <x-ui.button type="submit" variant="secondary" data-loading-text="Resetting...">Reset Password</x-ui.button>
                        </form>
                        <form method="POST" action="{{ route('admin.students.reactivate', $student) }}">
                            @csrf
                            <x-ui.button type="submit" variant="secondary" data-loading-text="Activating...">Activate</x-ui.button>
                        </form>
                        <form method="POST" action="{{ route('admin.students.destroy', $student) }}" onsubmit="return confirm('Delete this student record?');">
                            @csrf
                            @method('DELETE')
                            <x-ui.button type="submit" variant="danger" data-loading-text="Deleting...">Delete</x-ui.button>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        <div class="grid gap-5 p-4 lg:grid-cols-[18rem_1fr] lg:p-5">
            <aside class="grid gap-2 self-start rounded-2xl border border-[var(--line)] bg-[var(--surface-muted)] p-2 lg:sticky lg:top-24">
                @foreach ($tabs as $tab)
                    <button
                        type="button"
                        data-student-admin-tab-target="#student-tab-{{ $tab['id'] }}"
                        @class([
                            'flex items-center gap-3 rounded-xl px-4 py-3 text-left text-sm font-bold theme-transition',
                            'bg-brand-600 text-white shadow-[0_14px_34px_rgb(0_81_54_/_0.18)]' => $loop->first,
                            'text-[var(--text-strong)] hover:bg-brand-600/8' => ! $loop->first,
                        ])
                    >
                        <span @class([
                            'grid size-10 shrink-0 place-items-center rounded-xl',
                            'bg-white/14 text-white' => $loop->first,
                            'bg-brand-600 text-white' => ! $loop->first,
                        ])>
                            <x-ui.icon :name="$tab['icon']" class="size-5" />
                        </span>
                        {{ $tab['label'] }}
                    </button>
                @endforeach
            </aside>

            <div class="min-w-0">
                <div id="student-tab-overview" data-student-admin-panel>
                    <div class="grid gap-4 md:grid-cols-3">
                        <x-ui.stat-card label="Matric Number" :value="$student->matric_no" meta="Login identity" tone="cyan" />
                        <x-ui.stat-card label="Year" :value="$studentYear()" meta="Derived from academic level" tone="amber" />
                        <x-ui.stat-card label="Academic Year" :value="$academicYear($placement?->academicSession ?? $student->academicSession)" meta="Current session" tone="rose" />
                    </div>

                    <div class="mt-5 grid gap-5 xl:grid-cols-2">
                        <x-ui.card title="Personal Summary" description="Core identity and direct contact details.">
                            <dl class="grid gap-4 sm:grid-cols-2">
                                <x-profile.detail label="Full Name" :value="$student->user->name" />
                                <x-profile.detail label="Email" :value="$student->user->email" />
                                <x-profile.detail label="Phone" :value="$student->user->phone ?: 'N/A'" />
                                <x-profile.detail label="Gender" :value="$student->gender ?: 'N/A'" />
                            </dl>
                        </x-ui.card>

                        <x-ui.card title="Placement Summary" description="SIWES placement and supervisor assignment status.">
                            <dl class="grid gap-4 sm:grid-cols-2">
                                <x-profile.detail label="Company" :value="$placement?->company_name ?? 'No placement'" />
                                <x-profile.detail label="SIWES Year" :value="$placement?->siwes_year ?? 'N/A'" />
                                <x-profile.detail label="State / LGA" :value="$placement ? $placement->company_state.' / '.$placement->company_lga : 'N/A'" />
                                <x-profile.detail label="Assigned Supervisor" :value="$student->activeSupervisorAssignment?->supervisor?->user?->name ?? 'Not assigned'" />
                            </dl>
                        </x-ui.card>
                    </div>
                </div>

                <div id="student-tab-personal" data-student-admin-panel class="hidden">
                    <x-ui.card title="Personal Data" description="Student biodata and contact information submitted during profile setup.">
                        <dl class="grid gap-4 md:grid-cols-2">
                            <x-profile.detail label="Full Name" :value="$student->user->name" />
                            <x-profile.detail label="Email" :value="$student->user->email" />
                            <x-profile.detail label="Phone" :value="$student->user->phone ?: 'N/A'" />
                            <x-profile.detail label="Gender" :value="$student->gender ?: 'N/A'" />
                            <x-profile.detail label="Date of Birth" :value="$student->date_of_birth?->format('d M Y') ?? 'N/A'" />
                            <x-profile.detail label="Nationality" :value="$metadata['nationality'] ?? 'N/A'" />
                            <x-profile.detail label="State" :value="$metadata['state'] ?? 'N/A'" />
                            <x-profile.detail label="LGA" :value="$metadata['lga'] ?? 'N/A'" />
                            <div class="md:col-span-2">
                                <x-profile.detail label="Address" :value="$student->address ?: 'N/A'" />
                            </div>
                        </dl>
                    </x-ui.card>
                </div>

                <div id="student-tab-academic" data-student-admin-panel class="hidden">
                    <x-ui.card title="Academic Data" description="Course here means the department attached to the student's SIWES record.">
                        <dl class="grid gap-4 md:grid-cols-2">
                            <x-profile.detail label="Matric Number" :value="$student->matric_no" />
                            <x-profile.detail label="Faculty" :value="$student->faculty?->name ?? 'N/A'" />
                            <x-profile.detail label="Course" :value="$student->department?->name ?? 'N/A'" />
                            <x-profile.detail label="Year" :value="$studentYear()" />
                            <x-profile.detail label="Academic Year" :value="$academicYear($placement?->academicSession ?? $student->academicSession)" />
                        </dl>
                    </x-ui.card>
                </div>

                <div id="student-tab-placement" data-student-admin-panel class="hidden">
                    <x-ui.card title="Placement Data" description="Company attachment details submitted by the student after ticket confirmation.">
                        @if ($placement)
                            <dl class="grid gap-4 md:grid-cols-2">
                                <x-profile.detail label="Company Name" :value="$placement->company_name" />
                                <x-profile.detail label="Company Supervisor Phone" :value="$placement->company_supervisor_phone ?: 'N/A'" />
                                <x-profile.detail label="Company State" :value="$placement->company_state ?: 'N/A'" />
                                <x-profile.detail label="Company LGA" :value="$placement->company_lga ?: 'N/A'" />
                                <x-profile.detail label="SIWES Year" :value="$placement->siwes_year" />
                                <x-profile.detail label="Period of Attachment" :value="$placement->attachment_period ?: 'N/A'" />
                                <x-profile.detail label="Ticket Serial" :value="$placement->ticket?->serial_number ?? 'N/A'" />
                                <x-profile.detail label="Academic Session" :value="$placement->academicSession?->name ?? 'N/A'" />
                                <div class="md:col-span-2">
                                    <x-profile.detail label="Company Address" :value="$placement->company_address ?: 'N/A'" />
                                </div>
                            </dl>
                        @else
                            <div class="rounded-2xl border border-amber-300/30 bg-amber-300/10 p-5">
                                <p class="font-bold text-[var(--text-strong)]">No placement submitted</p>
                                <p class="mt-1 text-sm text-[var(--text-soft)]">This student is displayed as inactive on the student list because no placement record exists.</p>
                            </div>
                        @endif
                    </x-ui.card>
                </div>

                <div id="student-tab-account" data-student-admin-panel class="hidden">
                    <x-ui.card title="Account and Security" description="Login state, payment count, and administrative security actions.">
                        <dl class="grid gap-4 md:grid-cols-2">
                            <x-profile.detail label="Portal Email" :value="$student->user->email" />
                            <x-profile.detail label="User Status" :value="ucfirst($student->user->status)" />
                            <x-profile.detail label="Activation Status" :value="ucfirst($student->activation_status)" />
                            <x-profile.detail label="Displayed Status" :value="ucfirst($displayStatus)" />
                            <x-profile.detail label="Payments" :value="$student->payments->count()" />
                            <x-profile.detail label="Last Login" :value="$student->user->last_login_at?->diffForHumans() ?? 'N/A'" />
                        </dl>
                    </x-ui.card>
                </div>
            </div>
        </div>
    </section>

    @if ($can('students.update'))
        <x-ui.modal id="edit-student-modal" title="Edit Student" class="w-[min(62rem,calc(100vw-2rem))]">
            <form method="POST" action="{{ route('admin.students.update', $student) }}" class="grid gap-4" data-ajax-reset="false">
                @csrf
                @method('PUT')
                <div class="grid gap-4 md:grid-cols-2">
                    <x-ui.input label="Full Name" name="name" value="{{ $student->user->name }}" required />
                    <x-ui.input label="Email" name="email" type="email" value="{{ $student->user->email }}" required />
                    <x-ui.input label="Phone" name="phone" value="{{ $student->user->phone }}" />
                    <x-ui.input label="Matric Number" name="matric_no" value="{{ $student->matric_no }}" required />
                    <label class="block">
                        <span class="siwes-form-label">Faculty</span>
                        <select name="faculty_id" class="siwes-form-control mt-2" data-filter-parent="#admin-student-department" required>
                            @foreach ($faculties as $faculty)
                                <option value="{{ $faculty->id }}" @selected($student->faculty_id === $faculty->id)>{{ $faculty->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="siwes-form-label">Course</span>
                        <select id="admin-student-department" name="department_id" class="siwes-form-control mt-2" required>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}" data-parent-value="{{ $department->faculty_id }}" @selected($student->department_id === $department->id)>{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="siwes-form-label">Academic Level</span>
                        <select name="academic_level_id" class="siwes-form-control mt-2" required>
                            @foreach ($levels as $level)
                                <option value="{{ $level->id }}" @selected($student->academic_level_id === $level->id)>{{ $level->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="siwes-form-label">Academic Session</span>
                        <select name="academic_session_id" class="siwes-form-control mt-2" required>
                            @foreach ($sessions as $session)
                                <option value="{{ $session->id }}" @selected($student->academic_session_id === $session->id)>{{ $session->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="siwes-form-label">Activation Status</span>
                        <select name="activation_status" class="siwes-form-control mt-2" required>
                            @foreach (['inactive', 'active', 'suspended'] as $status)
                                <option value="{{ $status }}" @selected($student->activation_status === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="siwes-form-label">Gender</span>
                        <select name="gender" class="siwes-form-control mt-2">
                            <option value="">Not set</option>
                            <option value="Male" @selected($student->gender === 'Male')>Male</option>
                            <option value="Female" @selected($student->gender === 'Female')>Female</option>
                        </select>
                    </label>
                    <x-ui.input label="Date of Birth" name="date_of_birth" type="date" value="{{ $student->date_of_birth?->format('Y-m-d') }}" />
                    <label class="block md:col-span-2">
                        <span class="siwes-form-label">Address</span>
                        <textarea name="address" rows="4" class="siwes-form-control mt-2">{{ $student->address }}</textarea>
                    </label>
                </div>
                <div class="flex justify-end gap-2 border-t border-[var(--line)] pt-4">
                    <x-ui.button type="button" variant="ghost" data-modal-close>Cancel</x-ui.button>
                    <x-ui.button type="submit" data-loading-text="Saving...">Save Changes</x-ui.button>
                </div>
            </form>
        </x-ui.modal>
    @endif

    <script>
        (() => {
            const buttons = document.querySelectorAll('[data-student-admin-tab-target]');
            const panels = document.querySelectorAll('[data-student-admin-panel]');

            buttons.forEach((button) => {
                button.addEventListener('click', () => {
                    const target = document.querySelector(button.dataset.studentAdminTabTarget);

                    buttons.forEach((item) => {
                        item.classList.remove('bg-brand-600', 'text-white', 'shadow-[0_14px_34px_rgb(0_81_54_/_0.18)]');
                        item.classList.add('text-[var(--text-strong)]');
                        item.querySelector('span')?.classList.remove('bg-white/14');
                        item.querySelector('span')?.classList.add('bg-brand-600');
                    });

                    panels.forEach((panel) => panel.classList.add('hidden'));

                    button.classList.add('bg-brand-600', 'text-white', 'shadow-[0_14px_34px_rgb(0_81_54_/_0.18)]');
                    button.classList.remove('text-[var(--text-strong)]');
                    button.querySelector('span')?.classList.add('bg-white/14');
                    target?.classList.remove('hidden');
                });
            });
        })();
    </script>
</x-layouts.app-shell>
