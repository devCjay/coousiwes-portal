@php
    $navigation = [
        ['label' => 'Dashboard', 'href' => route('student.dashboard'), 'icon' => 'D'],
        ['label' => 'Profile', 'href' => route('student.profile.show'), 'icon' => 'user-circle'],
        ['label' => 'My Ticket', 'href' => route('student.tickets.index'), 'active' => true, 'icon' => 'ticket'],
        ['label' => 'Placement', 'href' => route('student.placements.ticket'), 'icon' => 'briefcase'],
        ['label' => 'Payment', 'href' => route('student.payments.index'), 'icon' => 'K'],
        ['label' => 'Feedback', 'href' => route('student.feedback.index'), 'icon' => 'F'],
    ];
@endphp

<x-layouts.app-shell title="My Ticket" role="Student" :navigation="$navigation">
    <div class="grid gap-4 md:grid-cols-3">
        <x-ui.stat-card label="Total Tickets" :value="$tickets->count()" meta="Assigned to your profile" tone="cyan" />
        <x-ui.stat-card label="Unused Tickets" :value="$tickets->filter(fn ($ticket) => $ticket->displayStatus() === 'Unused')->count()" meta="Available for placement" tone="amber" />
        <x-ui.stat-card label="Used Tickets" :value="$tickets->filter(fn ($ticket) => $ticket->displayStatus() === 'Used')->count()" meta="Already linked to placement" />
    </div>

    <x-ui.card class="mt-6" title="Purchased Tickets" description="Use your assigned ticket serial number and pin to unlock the placement form.">
        <div class="grid gap-4 lg:grid-cols-2">
            @forelse ($tickets as $ticket)
                <article class="rounded-2xl border border-[var(--line)] bg-[var(--surface-muted)] p-5 shadow-[0_16px_38px_rgb(8_15_12_/_0.06)]">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-extrabold uppercase text-[var(--text-soft)]">Serial Number</p>
                            <h2 class="mt-1 break-all text-lg font-black text-[var(--text-strong)]">{{ $ticket->serial_number }}</h2>
                        </div>
                        <span @class([
                            'inline-flex rounded-full px-3 py-1 text-xs font-extrabold uppercase',
                            'bg-emerald-600 text-white' => $ticket->displayStatus() === 'Used',
                            'bg-amber-300 text-graphite-950' => $ticket->displayStatus() === 'Unused',
                        ])>
                            {{ $ticket->displayStatus() }}
                        </span>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-xl border border-[var(--line)] bg-[var(--surface-raised)] p-3">
                            <p class="text-xs font-bold uppercase text-[var(--text-soft)]">Pin</p>
                            <button type="button" data-pin-reveal data-pin="{{ $ticket->pin }}" class="mt-2 inline-flex items-center gap-2 rounded-md border border-[var(--line)] bg-[var(--surface-muted)] px-2 py-1 font-mono text-sm font-bold text-[var(--text-strong)] theme-transition hover:border-brand-400">
                                <span data-pin-value>******</span>
                                <x-ui.icon name="eye" class="size-4 text-[var(--text-soft)]" />
                            </button>
                        </div>
                        <div class="rounded-xl border border-[var(--line)] bg-[var(--surface-raised)] p-3">
                            <p class="text-xs font-bold uppercase text-[var(--text-soft)]">Amount</p>
                            <p class="mt-2 text-sm font-bold text-[var(--text-strong)]">{{ $ticket->currency }} {{ number_format($ticket->amount) }}</p>
                        </div>
                        <div class="rounded-xl border border-[var(--line)] bg-[var(--surface-raised)] p-3">
                            <p class="text-xs font-bold uppercase text-[var(--text-soft)]">Assigned</p>
                            <p class="mt-2 text-sm font-bold text-[var(--text-strong)]">{{ $ticket->assigned_at?->toDayDateTimeString() ?? 'N/A' }}</p>
                        </div>
                        <div class="rounded-xl border border-[var(--line)] bg-[var(--surface-raised)] p-3">
                            <p class="text-xs font-bold uppercase text-[var(--text-soft)]">Expires</p>
                            <p class="mt-2 text-sm font-bold text-[var(--text-strong)]">{{ $ticket->expires_at?->toFormattedDateString() ?? 'Open' }}</p>
                        </div>
                    </div>

                    @if ($ticket->displayStatus() === 'Unused')
                        <x-ui.button :href="route('student.placements.ticket')" class="mt-5 w-full" icon="briefcase">Use for Placement</x-ui.button>
                    @endif
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-[var(--line)] bg-[var(--surface-muted)] p-8 text-center">
                    <x-ui.icon name="ticket" class="mx-auto size-10 text-[var(--text-soft)]" />
                    <h2 class="mt-3 text-base font-bold text-[var(--text-strong)]">No ticket assigned yet</h2>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-[var(--text-soft)]">Tickets assigned after cash upload or online payment will appear here.</p>
                </div>
            @endforelse
        </div>
    </x-ui.card>
</x-layouts.app-shell>
