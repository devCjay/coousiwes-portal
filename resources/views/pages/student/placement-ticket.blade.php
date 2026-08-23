@php
    $navigation = [
        ['label' => 'Dashboard', 'href' => route('student.dashboard'), 'icon' => 'D'],
        ['label' => 'Profile', 'href' => route('student.profile.show'), 'icon' => 'user-circle'],
        ['label' => 'Placement', 'href' => route('student.placements.ticket'), 'active' => true, 'icon' => 'briefcase'],
        ['label' => 'Payment', 'href' => route('student.payments.index'), 'icon' => 'K'],
        ['label' => 'Feedback', 'href' => route('student.feedback.index'), 'icon' => 'F'],
    ];
@endphp

<x-layouts.app-shell title="Placement Ticket" role="Student" :navigation="$navigation">
    <section class="mx-auto grid max-w-5xl gap-6 lg:grid-cols-[1fr_0.8fr]">
        <x-ui.card title="Confirm Placement Ticket" description="Enter your ticket serial number and pin to unlock the SIWES placement form.">
            <form method="POST" action="{{ route('student.placements.ticket.confirm') }}" data-ajax-reset="false" class="grid gap-5">
                @csrf
                <div class="rounded-2xl bg-brand-600 p-5 text-white shadow-[0_24px_70px_rgb(0_81_54_/_0.22)]">
                    <span class="grid size-12 place-items-center rounded-2xl bg-white/14 ring-1 ring-white/15">
                        <x-ui.icon name="ticket" class="size-6" />
                    </span>
                    <h2 class="mt-4 text-xl font-bold">Ticket protected access</h2>
                    <p class="mt-2 text-sm leading-6 text-white/78">Your ticket is validated securely before placement details can be submitted.</p>
                </div>

                <x-ui.input label="Serial Number" name="serial_number" placeholder="SIWES-656484753637" required />
                <x-ui.input label="Pin" name="pin" type="password" placeholder="Enter ticket pin" required />

                <x-ui.button type="submit" class="w-full sm:w-auto" data-loading-text="Validating...">Confirm Ticket</x-ui.button>
            </form>
        </x-ui.card>

        <x-ui.card title="Don't have a Ticket?" description="Pay online when Korapay activation payment is available.">
            <div class="flex h-full flex-col justify-between gap-6">
                <div class="rounded-2xl border border-brand-600/15 bg-brand-600/5 p-5">
                    <span class="grid size-12 place-items-center rounded-2xl bg-brand-600 text-white">
                        <x-ui.icon name="credit-card" class="size-6" />
                    </span>
                    <h3 class="mt-4 text-lg font-bold text-[var(--text-strong)]">Online payment</h3>
                    <p class="mt-2 text-sm leading-6 text-[var(--text-soft)]">The portal will check live payment availability before sending you to the payment workflow.</p>
                </div>

                <form method="POST" action="{{ route('student.placements.pay-online') }}" data-ajax-reset="false">
                    @csrf
                    <x-ui.button type="submit" class="w-full" data-loading-text="Checking...">Pay Online</x-ui.button>
                </form>
            </div>
        </x-ui.card>
    </section>
</x-layouts.app-shell>
