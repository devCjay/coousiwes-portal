@php
    $assignment = $student->activeSupervisorAssignment;
    $latestTicket = $student->tickets->sortByDesc('created_at')->first();
    $latestPayment = $student->payments->sortByDesc('created_at')->first();
    $profileCompletion = $student->profileCompletionPercent();
    $placement = $student->placement;
    $placementCompletion = $placement ? 100 : 0;
    $placementCompany = $placement?->company_name ?? 'N/A';
    $placementLocation = $placement ? "{$placement->company_lga}, {$placement->company_state}" : 'N/A';
    $placementLevel = $placement?->academicLevel?->name ?? 'N/A';
    $placementSession = $placement?->academicSession?->name ?? 'N/A';
    $placementYear = $placement?->siwes_year ?? 'N/A';
    $placementStatus = $placement ? 'Submitted' : 'Not started';
    $placementRows = [[
        e($placementCompany),
        e($placementLocation),
        e($placementLevel),
        e($placementSession),
        e((string) $placementYear),
        e($placementStatus),
    ]];
    $fullName = $student->user->name;
    $firstName = explode(' ', trim($fullName))[0] ?: 'Student';
    $heroName = \Illuminate\Support\Str::limit($fullName, 28);
    $ticketCount = $student->tickets->count();
    $successfulPayments = $student->payments->where('status', \App\Models\Payment::STATUS_SUCCESSFUL)->count();
    $navigation = [
        ['label' => 'Dashboard', 'href' => route('student.dashboard'), 'active' => true, 'icon' => 'D'],
        ['label' => 'Profile', 'href' => route('student.profile.show'), 'icon' => 'user-circle'],
        ['label' => 'My Ticket', 'href' => route('student.tickets.index'), 'icon' => 'ticket'],
        ['label' => 'Placement', 'href' => route('student.placements.ticket'), 'icon' => 'briefcase'],
        ['label' => 'Payment', 'href' => route('student.payments.index'), 'icon' => 'K'],
        ['label' => 'Feedback', 'href' => route('student.feedback.index'), 'icon' => 'F'],
    ];
@endphp

<x-layouts.app-shell title="Student Dashboard" role="Student" :navigation="$navigation">
    <section class="overflow-hidden rounded-2xl bg-brand-600 text-white shadow-[0_24px_70px_rgba(0,81,54,0.26)] sm:rounded-[1.75rem]">
        <div class="relative isolate px-4 pb-5 pt-4 sm:px-8 sm:pb-8 sm:pt-7 lg:px-10">
            <div class="absolute inset-0 -z-10 bg-[linear-gradient(122deg,transparent_0%,transparent_58%,rgba(255,255,255,0.08)_58%,rgba(255,255,255,0.08)_64%,transparent_64%,transparent_70%,rgba(255,255,255,0.08)_70%,rgba(255,255,255,0.08)_77%,transparent_77%)]"></div>
            <div class="absolute right-0 top-0 -z-10 hidden h-full w-[38%] bg-[radial-gradient(circle_at_55%_35%,rgba(255,255,255,0.18),transparent_42%)] lg:block"></div>

            <div class="grid gap-6 xl:grid-cols-[1fr_22rem]">
                <div class="min-w-0">
                    <div class="inline-flex max-w-full items-center gap-2 rounded-full bg-white/12 px-3 py-1 text-xs font-semibold text-white/85 ring-1 ring-white/15">
                        <x-ui.icon name="graduation-cap" class="size-4" />
                        <span class="truncate">{{ $student->matric_no }}</span>
                    </div>
                    <h2 class="mt-4 max-w-full text-2xl font-bold leading-tight sm:text-3xl">Hello, {{ $heroName }}</h2>
                    <p class="mt-2 max-w-2xl text-sm text-white/82 sm:text-base">Welcome back. Continue your SIWES profile and placement activities from one workspace.</p>

                    <div class="mt-6 grid grid-cols-2 gap-3 sm:gap-4">
                        <div class="flex min-w-0 items-center gap-2 sm:gap-3">
                            <span class="grid size-10 shrink-0 place-items-center rounded-2xl bg-white/18 text-white shadow-[inset_0_1px_0_rgba(255,255,255,0.18)] sm:size-12">
                                <x-ui.icon name="user-check" class="size-4 sm:size-5" />
                            </span>
                            <span class="min-w-0">
                                <span class="block text-base font-bold leading-none sm:text-lg">{{ $profileCompletion }}%</span>
                                <span class="mt-1 block text-xs leading-tight text-white/78 sm:text-sm">Profile Complete</span>
                            </span>
                        </div>
                        <div class="flex min-w-0 items-center gap-2 sm:gap-3">
                            <span class="grid size-10 shrink-0 place-items-center rounded-2xl bg-white/18 text-white shadow-[inset_0_1px_0_rgba(255,255,255,0.18)] sm:size-12">
                                <x-ui.icon name="ticket" class="size-4 sm:size-5" />
                            </span>
                            <span class="min-w-0">
                                <span class="block text-base font-bold leading-none sm:text-lg">{{ $ticketCount }}</span>
                                <span class="mt-1 block text-xs leading-tight text-white/78 sm:text-sm">Activation Tickets</span>
                            </span>
                        </div>
                        <div class="flex min-w-0 items-center gap-2 sm:gap-3">
                            <span class="grid size-10 shrink-0 place-items-center rounded-2xl bg-white/18 text-white shadow-[inset_0_1px_0_rgba(255,255,255,0.18)] sm:size-12">
                                <x-ui.icon name="credit-card" class="size-4 sm:size-5" />
                            </span>
                            <span class="min-w-0">
                                <span class="block text-base font-bold leading-none sm:text-lg">{{ $successfulPayments }}</span>
                                <span class="mt-1 block text-xs leading-tight text-white/78 sm:text-sm">Verified Payments</span>
                            </span>
                        </div>
                        <div class="flex min-w-0 items-center gap-2 sm:gap-3">
                            <span class="grid size-10 shrink-0 place-items-center rounded-2xl bg-white/18 text-white shadow-[inset_0_1px_0_rgba(255,255,255,0.18)] sm:size-12">
                                <x-ui.icon name="briefcase" class="size-4 sm:size-5" />
                            </span>
                            <span class="min-w-0">
                                <span class="block text-base font-bold leading-none sm:text-lg">{{ $placement ? '1' : '0' }}</span>
                                <span class="mt-1 block text-xs leading-tight text-white/78 sm:text-sm">Placement Records</span>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="relative hidden min-h-64 xl:block">
                    <img
                        src="{{ asset('images/student-auth-overlay.png') }}"
                        alt=""
                        class="absolute bottom-[-2rem] right-[-2rem] h-[20rem] max-w-none object-contain drop-shadow-[0_22px_34px_rgba(0,0,0,0.18)]"
                    >
                </div>
            </div>

            <div id="profile-actions" class="mt-8">
                <p class="mb-3 text-base font-bold">Continue</p>
                <div class="grid gap-4 lg:grid-cols-2">
                    <a href="{{ route('student.profile.show') }}" class="group min-w-0 overflow-hidden rounded-2xl bg-white text-left text-[#18304f] shadow-[0_18px_45px_rgba(15,23,42,0.16)] transition duration-200 hover:-translate-y-1 hover:shadow-[0_22px_55px_rgba(15,23,42,0.22)]">
                        <span class="block p-4 sm:p-5">
                            <span class="flex items-start gap-4">
                                <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-slate-100 text-slate-500">
                                    <x-ui.icon name="user-circle" class="size-5" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-bold">Update Profile</span>
                                    <span class="mt-1 block text-xs text-slate-500">Keep your student details current.</span>
                                </span>
                            </span>
                            <span class="mt-5 block h-2 overflow-hidden rounded-full bg-slate-100">
                                <span class="block h-full rounded-full bg-brand-600" style="width: {{ $profileCompletion }}%"></span>
                            </span>
                            <span class="mt-2 block text-xs font-semibold text-[#18304f]">{{ $profileCompletion }}% <span class="font-medium text-slate-400">Completed</span></span>
                        </span>
                        <span class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 px-4 py-3 text-xs font-bold text-slate-500 sm:px-5">
                            <span>{{ $profileCompletion }}% profile ready</span>
                            <span class="inline-flex items-center gap-1 text-brand-600">Continue <x-ui.icon name="open" class="size-3.5" /></span>
                        </span>
                    </a>

                    <a href="{{ $placement ? route('student.placements.create') : route('student.placements.ticket') }}" class="group min-w-0 overflow-hidden rounded-2xl bg-white text-left text-[#18304f] shadow-[0_18px_45px_rgba(15,23,42,0.16)] transition duration-200 hover:-translate-y-1 hover:shadow-[0_22px_55px_rgba(15,23,42,0.22)]">
                        <span class="block p-4 sm:p-5">
                            <span class="flex items-start gap-4">
                                <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-slate-100 text-slate-500">
                                    <x-ui.icon name="briefcase" class="size-5" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-bold">Add Placement</span>
                                    <span class="mt-1 block text-xs text-slate-500">{{ $placement ? $placement->company_name : 'Ticket confirmation required.' }}</span>
                                </span>
                            </span>
                            <span class="mt-5 block h-2 overflow-hidden rounded-full bg-slate-100">
                                <span class="block h-full rounded-full bg-brand-600" style="width: {{ $placementCompletion }}%"></span>
                            </span>
                            <span class="mt-2 block text-xs font-semibold text-[#18304f]">{{ $placementCompletion }}% <span class="font-medium text-slate-400">Completed</span></span>
                        </span>
                        <span class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 px-4 py-3 text-xs font-bold text-slate-500 sm:px-5">
                            <span>{{ $placement ? 'Submitted' : 'Not started' }}</span>
                            <span class="inline-flex items-center gap-1 text-brand-600">Continue <x-ui.icon name="open" class="size-3.5" /></span>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
        <x-ui.card title="Student Placement" description="Your current SIWES placement record and company attachment details.">
            <dl class="grid gap-3 md:hidden">
                <div class="rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-3">
                    <dt class="text-xs font-semibold uppercase text-[var(--text-soft)]">Company</dt>
                    <dd class="mt-1 text-sm font-semibold text-[var(--text-strong)]">{{ $placementCompany }}</dd>
                </div>
                <div class="rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-3">
                    <dt class="text-xs font-semibold uppercase text-[var(--text-soft)]">Location</dt>
                    <dd class="mt-1 text-sm font-semibold text-[var(--text-strong)]">{{ $placementLocation }}</dd>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-3">
                        <dt class="text-xs font-semibold uppercase text-[var(--text-soft)]">Level</dt>
                        <dd class="mt-1 text-sm font-semibold text-[var(--text-strong)]">{{ $placementLevel }}</dd>
                    </div>
                    <div class="rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-3">
                        <dt class="text-xs font-semibold uppercase text-[var(--text-soft)]">Status</dt>
                        <dd class="mt-1 text-sm font-semibold text-[var(--text-strong)]">{{ $placementStatus }}</dd>
                    </div>
                </div>
            </dl>
            <div class="hidden md:block">
                <x-ui.data-table
                    :headers="['Company', 'Location', 'Level', 'SIWES Session', 'Year', 'Status']"
                    :rows="$placementRows"
                />
            </div>
        </x-ui.card>

        <x-ui.card title="Notifications" description="Role-specific alerts and updates.">
            <div class="space-y-3">
                @forelse ($unreadNotifications as $notification)
                    <div class="rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-4">
                        <p class="text-sm font-semibold">{{ $notification->data['title'] ?? 'Portal notification' }}</p>
                        <p class="mt-1 text-sm text-[var(--text-soft)]">{{ $notification->data['message'] ?? 'Open your portal for details.' }}</p>
                    </div>
                @empty
                    <p class="text-sm text-[var(--text-soft)]">No unread notifications.</p>
                @endforelse
            </div>
        </x-ui.card>
    </section>

    <section class="mt-6">
        <x-ui.alert tone="{{ $student->activation_status === 'active' ? 'success' : 'warning' }}" title="Activation status">
            Your account is currently {{ $student->activation_status }} for {{ $student->academicSession?->name ?? 'N/A' }}.
            @if ($latestTicket)
                Latest ticket: {{ $latestTicket->serial_number }} ({{ ucfirst($latestTicket->status) }}).
            @endif
            @if ($latestPayment)
                Latest Korapay payment: {{ ucfirst($latestPayment->status) }}.
            @endif
        </x-ui.alert>
    </section>

</x-layouts.app-shell>
