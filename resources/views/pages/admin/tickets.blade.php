@php
    $navigation = [
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'D'],
        ['label' => 'Generate List', 'href' => route('admin.generate-list.index'), 'icon' => 'file-text'],
        ['label' => 'Students', 'href' => route('admin.students.index'), 'icon' => 'S'],
        ['label' => 'Tickets', 'href' => route('admin.tickets.index'), 'active' => true, 'icon' => 'T'],
        ['label' => 'Payments', 'href' => route('admin.payments.index'), 'icon' => 'P'],
        ['label' => 'Academics', 'href' => route('admin.academics.index'), 'icon' => 'A'],
    ];
    $statuses = [
        '' => 'All',
        \App\Models\Ticket::STATUS_UNUSED => 'Unused',
        \App\Models\Ticket::STATUS_USED => 'Used',
    ];
    $can = fn (string $permission): bool => \App\Support\PortalPermission::userHas(auth('admin')->user(), $permission);
@endphp

<x-layouts.app-shell title="Manage Tickets" role="Admin" :navigation="$navigation">
    <div class="grid gap-4 md:grid-cols-3">
        <x-ui.stat-card label="Tickets" :value="number_format($ticketTotal)" meta="Total generated records" />
        <x-ui.stat-card label="Available Tickets" :value="number_format($availableTickets)" meta="Unassigned activation stock" tone="amber" />
        <x-ui.stat-card label="Ticket Fee" :value="\App\Support\PaymentSettings::currency().' '.number_format(\App\Support\PaymentSettings::ticketAmount())" meta="Paid through Korapay" tone="cyan" />
    </div>

    @if ($can('tickets.generate'))
        <section class="mt-6 overflow-hidden rounded-lg border border-brand-400/25 bg-graphite-900 p-5 text-white shadow-[0_22px_60px_rgb(8_15_12_/_0.28)]">
            <header class="mb-5">
                <div class="flex items-center gap-3">
                    <span class="grid size-10 place-items-center rounded-lg bg-brand-400/12 text-brand-200 shadow-glow">
                        <x-ui.icon name="ticket" class="size-5" />
                    </span>
                    <div>
                        <h2 class="text-base font-semibold text-white">Generate Tickets</h2>
                        <p class="mt-1 text-sm text-white/62">Create activation tickets. Tickets remain unused until a student's Korapay activation payment is verified.</p>
                    </div>
                </div>
            </header>
            <form method="POST" action="{{ route('admin.tickets.store') }}" class="grid gap-3 md:grid-cols-[1fr_auto] md:items-end">
                @csrf
                <label class="block">
                    <span class="text-sm font-medium text-white/86">Number of Tickets to Generate</span>
                    <input name="quantity" type="number" min="1" max="500" placeholder="Enter number of tickets" required class="mt-2 w-full rounded-lg border border-white/12 bg-white/8 px-3 py-2.5 text-sm text-white shadow-sm theme-transition placeholder:text-white/45 focus:border-brand-300 focus:ring-4 focus:ring-brand-400/20">
                </label>
                <x-ui.button type="submit">Generate</x-ui.button>
            </form>
        </section>
    @endif

    <x-ui.card class="mt-6" title="All Tickets (Total: {{ number_format($ticketTotal) }})" description="Search, filter, print, and export activation ticket records.">
        <form method="GET" action="{{ route('admin.tickets.index') }}" data-ajax="false" class="mb-4 grid gap-3">
            <div class="grid gap-3 md:grid-cols-[1fr_auto]">
                <x-ui.input label="Search" name="search" value="{{ request('search') }}" placeholder="Search by serial, student name, email, or reg no" data-live-search="#tickets-table tbody tr" />
                <div class="flex items-end">
                    <x-ui.button type="submit">Search</x-ui.button>
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-[1fr_auto_1fr_auto] md:items-end">
                <label class="block">
                    <span class="text-sm font-medium text-[var(--text-strong)]">Status</span>
                    <select name="status" class="siwes-form-control mt-2">
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="flex items-center gap-2 rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] px-3 py-2.5 text-sm text-[var(--text-strong)]">
                    <input type="checkbox" name="not_printed" value="1" @checked(request()->boolean('not_printed'))>
                    Not Printed
                </label>
                <x-ui.input label="Activated By" name="activated_by" value="{{ request('activated_by') }}" placeholder="Student name" />
                <x-ui.button type="submit">Apply Filters</x-ui.button>
            </div>
        </form>

        <div class="overflow-hidden rounded-lg border border-[var(--line)]">
            <div class="overflow-x-auto">
                <table id="tickets-table" class="min-w-full divide-y divide-[var(--line)] text-left text-sm">
                    <thead class="bg-[var(--surface-muted)] text-xs font-semibold uppercase text-[var(--text-soft)]">
                        <tr>
                            <th class="w-10 px-3 py-3"><input type="checkbox" data-ticket-select-all aria-label="Select all tickets"></th>
                            <th class="whitespace-nowrap px-3 py-3">Serial Number</th>
                            <th class="whitespace-nowrap px-3 py-3">PIN</th>
                            <th class="whitespace-nowrap px-3 py-3">Status</th>
                            <th class="whitespace-nowrap px-3 py-3">Printed</th>
                            <th class="whitespace-nowrap px-3 py-3">Activated By</th>
                            <th class="whitespace-nowrap px-3 py-3">Activated At</th>
                            <th class="whitespace-nowrap px-3 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--line)] bg-[var(--surface-raised)]">
                        @forelse ($tickets as $ticket)
                            @php
                                $payment = $ticket->student?->payments?->where('ticket_id', $ticket->id)->where('status', \App\Models\Payment::STATUS_SUCCESSFUL)->sortByDesc('paid_at')->first();
                                $printed = data_get($ticket->metadata, 'printed_at') !== null;
                                $isUsed = in_array($ticket->status, \App\Models\Ticket::usedStatuses(), true);
                                $placement = $ticket->placement;
                            @endphp
                            <tr class="theme-transition hover:bg-[var(--surface-muted)]">
                                <td class="px-3 py-3"><input type="checkbox" data-ticket-checkbox value="{{ $ticket->id }}" aria-label="Select ticket {{ $ticket->id }}"></td>
                                <td class="whitespace-nowrap px-3 py-3 font-semibold text-[var(--text-strong)]">{{ $ticket->serial_number ?? 'SIWES-'.str_pad((string) $ticket->id, 12, '0', STR_PAD_LEFT) }}</td>
                                <td class="whitespace-nowrap px-3 py-3 text-[var(--text-soft)]">
                                    <button type="button" data-pin-reveal data-pin="{{ $ticket->pin }}" class="inline-flex items-center gap-2 rounded-md border border-[var(--line)] bg-[var(--surface-muted)] px-2 py-1 font-mono text-xs text-[var(--text-strong)] theme-transition hover:border-brand-400">
                                        <span data-pin-value>******</span>
                                        <x-ui.icon name="eye" class="size-4 text-[var(--text-soft)]" />
                                    </button>
                                </td>
                                <td class="whitespace-nowrap px-3 py-3">{{ $ticket->displayStatus() }}</td>
                                <td class="whitespace-nowrap px-3 py-3">{{ $printed ? 'Yes' : 'No' }}</td>
                                <td class="whitespace-nowrap px-3 py-3">{{ $payment ? $ticket->student?->user?->name : '-' }}</td>
                                <td class="whitespace-nowrap px-3 py-3">{{ ($ticket->used_at ?? $ticket->paid_at)?->toDateTimeString() ?? '-' }}</td>
                                <td class="whitespace-nowrap px-3 py-3">
                                    @if ($isUsed)
                                        <x-ui.button type="button" variant="secondary" class="px-3 py-2 text-xs" data-modal-target="#ticket-details-{{ $ticket->id }}">
                                            <x-ui.icon name="eye" class="size-4" />
                                            View Details
                                        </x-ui.button>
                                    @else
                                        <span class="text-xs text-[var(--text-soft)]">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-3 py-8 text-center text-sm text-[var(--text-soft)]">No tickets match the current filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-2">
                <x-ui.button type="button" data-ticket-print>Print Selected Tickets</x-ui.button>
                <x-ui.button type="button" variant="secondary" data-ticket-export-pdf>Export to PDF</x-ui.button>
            </div>
            {{ $tickets->links() }}
        </div>
    </x-ui.card>

    @foreach ($tickets as $ticket)
        @php
            $payment = $ticket->student?->payments?->where('ticket_id', $ticket->id)->where('status', \App\Models\Payment::STATUS_SUCCESSFUL)->sortByDesc('paid_at')->first();
            $placement = $ticket->placement;
            $isUsed = in_array($ticket->status, \App\Models\Ticket::usedStatuses(), true);
        @endphp

        @if ($isUsed)
            <x-ui.modal id="ticket-details-{{ $ticket->id }}" title="Ticket Details" class="w-[min(62rem,calc(100vw-2rem))]">
                <div class="grid gap-5">
                    <div class="rounded-2xl border border-emerald-500/25 bg-emerald-500/10 p-5">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-extrabold uppercase text-emerald-700 dark:text-emerald-200">Used Ticket</p>
                                <h3 class="mt-1 text-xl font-black text-[var(--text-strong)]">{{ $ticket->serial_number ?? 'SIWES-'.str_pad((string) $ticket->id, 12, '0', STR_PAD_LEFT) }}</h3>
                                <p class="mt-1 text-sm text-[var(--text-soft)]">{{ $ticket->currency }} {{ number_format($ticket->amount) }} / used {{ ($ticket->used_at ?? $ticket->paid_at)?->toDayDateTimeString() ?? 'recently' }}</p>
                            </div>
                            <span class="inline-flex rounded-full bg-emerald-600 px-3 py-1 text-xs font-extrabold uppercase text-white">Used</span>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <x-ui.card title="Student Information" description="Student who used this activation ticket.">
                            <dl class="grid gap-4 sm:grid-cols-2">
                                <x-profile.detail label="Name" :value="$ticket->student?->user?->name ?? 'N/A'" />
                                <x-profile.detail label="Reg No" :value="$ticket->student?->matric_no ?? 'N/A'" />
                                <x-profile.detail label="Email" :value="$ticket->student?->user?->email ?? 'N/A'" />
                                <x-profile.detail label="Phone" :value="$ticket->student?->user?->phone ?: 'N/A'" />
                                <x-profile.detail label="Faculty" :value="$ticket->student?->faculty?->name ?? 'N/A'" />
                                <x-profile.detail label="Department" :value="$ticket->student?->department?->name ?? 'N/A'" />
                            </dl>
                        </x-ui.card>

                        <x-ui.card title="Payment Information" description="Korapay verification linked to this ticket.">
                            <dl class="grid gap-4 sm:grid-cols-2">
                                <x-profile.detail label="Reference" :value="$payment?->reference ?? 'N/A'" />
                                <x-profile.detail label="Provider" :value="$payment?->provider ? ucfirst($payment->provider) : 'N/A'" />
                                <x-profile.detail label="Amount" :value="$payment ? $payment->currency.' '.number_format($payment->amount) : 'N/A'" />
                                <x-profile.detail label="Status" :value="$payment ? ucfirst($payment->status) : 'N/A'" />
                                <x-profile.detail label="Verified At" :value="$payment?->verified_at?->toDateTimeString() ?? 'N/A'" />
                                <x-profile.detail label="Paid At" :value="$payment?->paid_at?->toDateTimeString() ?? 'N/A'" />
                            </dl>
                        </x-ui.card>
                    </div>

                    <x-ui.card title="Placement Information" description="Placement record unlocked with this ticket.">
                        @if ($placement)
                            <dl class="grid gap-4 md:grid-cols-3">
                                <x-profile.detail label="Company" :value="$placement->company_name ?: 'N/A'" />
                                <x-profile.detail label="SIWES Year" :value="$placement->siwes_year ?: 'N/A'" />
                                <x-profile.detail label="Session" :value="$placement->academicSession?->name ?? 'N/A'" />
                                <x-profile.detail label="Level" :value="$placement->academicLevel?->name ?? 'N/A'" />
                                <x-profile.detail label="State" :value="$placement->company_state ?: 'N/A'" />
                                <x-profile.detail label="LGA" :value="$placement->company_lga ?: 'N/A'" />
                                <x-profile.detail label="Supervisor Phone" :value="$placement->company_supervisor_phone ?: 'N/A'" />
                                <x-profile.detail label="Period" :value="$placement->attachment_period ?: 'N/A'" />
                                <div class="md:col-span-3">
                                    <x-profile.detail label="Company Address" :value="$placement->company_address ?: 'N/A'" />
                                </div>
                            </dl>
                        @else
                            <p class="text-sm text-[var(--text-soft)]">No placement record is linked to this used ticket yet.</p>
                        @endif
                    </x-ui.card>

                    <div class="flex justify-end border-t border-[var(--line)] pt-4">
                        <x-ui.button type="button" variant="ghost" data-modal-close>Close</x-ui.button>
                    </div>
                </div>
            </x-ui.modal>
        @endif
    @endforeach
</x-layouts.app-shell>
