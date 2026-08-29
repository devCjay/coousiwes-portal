@php
    $navigation = [
        ['label' => 'Dashboard', 'href' => route('student.dashboard'), 'icon' => 'D'],
        ['label' => 'Profile', 'href' => route('student.profile.show'), 'icon' => 'user-circle'],
        ['label' => 'My Ticket', 'href' => route('student.tickets.index'), 'icon' => 'ticket'],
        ['label' => 'Workshop Fee', 'href' => route('student.workshop.checkout'), 'active' => true, 'icon' => 'credit-card'],
        ['label' => 'Placement', 'href' => route('student.placements.ticket'), 'icon' => 'briefcase'],
        ['label' => 'Payment', 'href' => route('student.payments.index'), 'icon' => 'K'],
        ['label' => 'Feedback', 'href' => route('student.feedback.index'), 'icon' => 'F'],
    ];
@endphp

<x-layouts.app-shell title="Workshop Fee" role="Student" :navigation="$navigation">
    @if (session('status'))
        <x-ui.alert title="Workshop Fee" tone="success">{{ session('status') }}</x-ui.alert>
    @endif

    <section class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
        <div class="overflow-hidden rounded-2xl bg-brand-600 text-white shadow-[0_24px_70px_rgba(0,81,54,0.26)]">
            <div class="relative isolate p-6 sm:p-8">
                <div class="absolute inset-0 -z-10 bg-[linear-gradient(122deg,transparent_0%,transparent_58%,rgba(255,255,255,0.08)_58%,rgba(255,255,255,0.08)_64%,transparent_64%,transparent_70%,rgba(255,255,255,0.08)_70%,rgba(255,255,255,0.08)_77%,transparent_77%)]"></div>
                <span class="grid size-14 place-items-center rounded-2xl bg-white/15 ring-1 ring-white/20">
                    <x-ui.icon name="credit-card" class="size-7" />
                </span>
                <h2 class="mt-6 text-2xl font-black leading-tight sm:text-3xl">Workshop Fee</h2>
                <p class="mt-3 max-w-xl text-sm leading-7 text-white/82">Complete your SIWES workshop payment online before opening the placement workflow.</p>

                <div class="mt-8 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl bg-white/12 p-4 ring-1 ring-white/15">
                        <p class="text-xs font-bold uppercase text-white/70">Amount</p>
                        <p class="mt-2 text-2xl font-black">{{ $currency }} {{ number_format($amount) }}</p>
                    </div>
                    <div class="rounded-2xl bg-white/12 p-4 ring-1 ring-white/15">
                        <p class="text-xs font-bold uppercase text-white/70">Status</p>
                        <p class="mt-2 text-2xl font-black">{{ $hasPaidWorkshop ? 'Paid' : 'Pending' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <x-ui.card title="Korapay Checkout" description="Workshop fee payments are verified from Korapay before placement access is unlocked.">
            <div class="grid gap-4">
                @if ($hasPaidWorkshop)
                    <div class="rounded-2xl border border-emerald-500/25 bg-emerald-500/10 p-5">
                        <div class="flex items-start gap-4">
                            <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-emerald-600 text-white">
                                <x-ui.icon name="check-check" class="size-6" />
                            </span>
                            <div>
                                <h3 class="font-bold text-[var(--text-strong)]">Workshop fee verified</h3>
                                <p class="mt-1 text-sm leading-6 text-[var(--text-soft)]">You can now continue to your placement ticket confirmation page.</p>
                            </div>
                        </div>
                        <x-ui.button :href="route('student.placements.ticket')" class="mt-5">Continue to Placement</x-ui.button>
                    </div>
                @elseif (! $onlinePaymentAvailable)
                    <div class="rounded-2xl border border-amber-400/35 bg-amber-300/10 p-5">
                        <h3 class="font-bold text-[var(--text-strong)]">Online payment unavailable</h3>
                        <p class="mt-2 text-sm leading-6 text-[var(--text-soft)]">Sorry online payment is currently not available. Please check back later or contact the SIWES office.</p>
                    </div>
                @else
                    <div class="rounded-2xl border border-[var(--line)] bg-[var(--surface-muted)] p-5">
                        <h3 class="font-bold text-[var(--text-strong)]">Ready for checkout</h3>
                        <p class="mt-2 text-sm leading-6 text-[var(--text-soft)]">Click below to continue to Korapay. The portal will update automatically after successful payment verification.</p>
                        @if ($pendingPayment)
                            <p class="mt-3 text-xs font-semibold text-[var(--text-soft)]">Latest pending reference: {{ $pendingPayment->reference }}</p>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('student.workshop.initialize') }}" data-ajax-reset="false">
                        @csrf
                        <x-ui.button type="submit" class="w-full" data-loading-text="Opening checkout...">Pay Workshop Fee</x-ui.button>
                    </form>
                @endif
            </div>
        </x-ui.card>
    </section>
</x-layouts.app-shell>
