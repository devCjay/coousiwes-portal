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
@endphp

<x-layouts.app-shell title="Manage Tickets" role="Admin" :navigation="$navigation">
    <div class="grid gap-4 md:grid-cols-3">
        <x-ui.stat-card label="Tickets" :value="number_format($ticketTotal)" meta="Total generated records" />
        <x-ui.stat-card label="Available Tickets" :value="number_format($availableTickets)" meta="Unassigned activation stock" tone="amber" />
        <x-ui.stat-card label="Ticket Fee" :value="'NGN '.number_format(config('siwes.payments.ticket_amount'))" meta="Paid through Korapay" tone="cyan" />
    </div>

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

    <x-ui.card class="mt-6" title="All Tickets (Total: {{ number_format($ticketTotal) }})" description="Search, filter, print, and export activation ticket records.">
        <form method="GET" action="{{ route('admin.tickets.index') }}" data-ajax="false" class="mb-4 grid gap-3">
            <div class="grid gap-3 md:grid-cols-[1fr_auto]">
                <x-ui.input label="Search" name="search" value="{{ request('search') }}" placeholder="Search by serial, student name, email, or matric number" data-live-search="#tickets-table tbody tr" />
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
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--line)] bg-[var(--surface-raised)]">
                        @forelse ($tickets as $ticket)
                            @php
                                $payment = $ticket->student?->payments?->where('ticket_id', $ticket->id)->where('status', \App\Models\Payment::STATUS_SUCCESSFUL)->sortByDesc('paid_at')->first();
                                $printed = data_get($ticket->metadata, 'printed_at') !== null;
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
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-3 py-8 text-center text-sm text-[var(--text-soft)]">No tickets match the current filters.</td>
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
</x-layouts.app-shell>
