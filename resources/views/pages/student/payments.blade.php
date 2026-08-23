@php
    $navigation = [
        ['label' => 'Dashboard', 'href' => route('student.dashboard'), 'icon' => 'D'],
        ['label' => 'Placement', 'href' => route('student.placements.ticket'), 'icon' => 'briefcase'],
        ['label' => 'Payments', 'href' => route('student.payments.index'), 'active' => true, 'icon' => 'P'],
        ['label' => 'Feedback', 'href' => route('student.feedback.index'), 'icon' => 'F'],
    ];
@endphp

<x-layouts.app-shell title="Activation Payments" role="Student" :navigation="$navigation">
    @if (session('status'))
        <x-ui.alert title="Verification" tone="success">{{ session('status') }}</x-ui.alert>
    @endif

    <div class="grid gap-4 md:grid-cols-3">
        <x-ui.stat-card label="Activation Status" :value="ucfirst($student->activation_status)" meta="Updated after successful Korapay payment" />
        <x-ui.stat-card label="Tickets" :value="$tickets->count()" meta="Assigned to your profile" tone="cyan" />
        <x-ui.stat-card label="Payments" :value="$payments->count()" meta="Korapay attempts" tone="amber" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
        <x-ui.card title="Available Tickets" description="Use a payable ticket to open Korapay checkout.">
            <div class="space-y-3">
                @if ($availableTicket && ! $tickets->contains(fn ($ticket) => $ticket->isPayable()))
                    <div class="rounded-lg border border-brand-400/35 bg-brand-500/10 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="font-semibold">{{ $availableTicket->currency }} {{ number_format($availableTicket->amount) }}</p>
                                <p class="text-xs text-[var(--text-soft)]">Activation ticket available / expires {{ $availableTicket->expires_at?->toDateString() ?? 'open' }}</p>
                            </div>
                            <form method="POST" action="{{ route('student.payments.initialize') }}">
                                @csrf
                                <input type="hidden" name="ticket_id" value="{{ $availableTicket->id }}">
                                <x-ui.button type="submit">Pay With Korapay</x-ui.button>
                            </form>
                        </div>
                    </div>
                @endif

                @forelse ($tickets as $ticket)
                    <div class="rounded-lg border border-[var(--line)] bg-[var(--surface-raised)] p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="font-semibold">{{ $ticket->currency }} {{ number_format($ticket->amount) }}</p>
                                <p class="text-xs text-[var(--text-soft)]">{{ ucfirst($ticket->status) }} / expires {{ $ticket->expires_at?->toDateString() ?? 'open' }}</p>
                            </div>
                            @if ($ticket->isPayable())
                                <form method="POST" action="{{ route('student.payments.initialize') }}">
                                    @csrf
                                    <input type="hidden" name="ticket_id" value="{{ $ticket->id }}">
                                    <x-ui.button type="submit">Pay With Korapay</x-ui.button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    @unless ($availableTicket)
                        <p class="text-sm text-[var(--text-soft)]">No activation ticket is available yet.</p>
                    @endunless
                @endforelse
            </div>
        </x-ui.card>

        <x-ui.card title="Payment History" description="References are verified server-side after checkout and webhooks.">
            <x-ui.data-table
                id="student-payments-table"
                :headers="['Reference', 'Amount', 'Status', 'Verified']"
                :rows="$payments->map(fn ($payment) => [
                    e($payment->reference),
                    e($payment->currency.' '.number_format($payment->amount)),
                    e(ucfirst($payment->status)),
                    e($payment->verified_at?->toDateTimeString() ?? 'Pending'),
                ])->all()"
            />
        </x-ui.card>
    </div>
</x-layouts.app-shell>
