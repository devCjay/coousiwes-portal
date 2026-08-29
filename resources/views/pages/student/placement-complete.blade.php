@php
    $placement = $student->placement;
    $navigation = [
        ['label' => 'Dashboard', 'href' => route('student.dashboard'), 'icon' => 'D'],
        ['label' => 'Profile', 'href' => route('student.profile.show'), 'icon' => 'user-circle'],
        ['label' => 'My Ticket', 'href' => route('student.tickets.index'), 'icon' => 'ticket'],
        ['label' => 'Placement', 'href' => route('student.placements.create'), 'active' => true, 'icon' => 'briefcase'],
        ['label' => 'Payment', 'href' => route('student.payments.index'), 'icon' => 'K'],
        ['label' => 'Feedback', 'href' => route('student.feedback.index'), 'icon' => 'F'],
    ];
@endphp

<x-layouts.app-shell title="Placement Complete" role="Student" :navigation="$navigation">
    <section class="mx-auto max-w-5xl overflow-hidden rounded-2xl bg-brand-600 text-white shadow-[0_24px_70px_rgb(0_81_54_/_0.26)]">
        <div class="relative isolate p-6 text-center sm:p-10">
            <div class="absolute inset-0 -z-10 bg-[linear-gradient(128deg,transparent_0%,transparent_52%,rgba(255,255,255,0.09)_52%,rgba(255,255,255,0.09)_60%,transparent_60%,transparent_68%,rgba(255,255,255,0.08)_68%,rgba(255,255,255,0.08)_76%,transparent_76%)]"></div>
            <span class="mx-auto grid size-20 place-items-center rounded-3xl bg-white/15 ring-1 ring-white/20">
                <x-ui.icon name="check-check" class="size-10" />
            </span>
            <h2 class="mt-6 text-3xl font-black">Placement Submitted</h2>
            <p class="mx-auto mt-3 max-w-2xl text-sm leading-6 text-white/78 sm:text-base">Congratulations. Your SIWES placement information has been saved and your ticket has been used for placement access.</p>

            <div class="mt-8 grid gap-4 text-left md:grid-cols-2">
                <div class="rounded-2xl bg-white p-5 text-[var(--text-strong)]">
                    <p class="text-xs font-bold uppercase text-brand-700">SIWES Information</p>
                    <dl class="mt-4 grid gap-3 text-sm">
                        <div><dt class="text-[var(--text-soft)]">Level</dt><dd class="font-bold">{{ $placement->academicLevel?->name ?? 'N/A' }}</dd></div>
                        <div><dt class="text-[var(--text-soft)]">Session</dt><dd class="font-bold">{{ $placement->academicSession?->name ?? 'N/A' }}</dd></div>
                        <div><dt class="text-[var(--text-soft)]">Year</dt><dd class="font-bold">{{ $placement->siwes_year }}</dd></div>
                        <div><dt class="text-[var(--text-soft)]">Attachment Period</dt><dd class="font-bold">{{ $placement->attachment_period }}</dd></div>
                    </dl>
                </div>
                <div class="rounded-2xl bg-white p-5 text-[var(--text-strong)]">
                    <p class="text-xs font-bold uppercase text-brand-700">Company Information</p>
                    <dl class="mt-4 grid gap-3 text-sm">
                        <div><dt class="text-[var(--text-soft)]">Company</dt><dd class="font-bold">{{ $placement->company_name }}</dd></div>
                        <div><dt class="text-[var(--text-soft)]">Location</dt><dd class="font-bold">{{ $placement->company_lga }}, {{ $placement->company_state }}</dd></div>
                        <div><dt class="text-[var(--text-soft)]">Supervisor Phone</dt><dd class="font-bold">{{ $placement->company_supervisor_phone }}</dd></div>
                    </dl>
                </div>
            </div>

            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <x-ui.button :href="route('student.dashboard')" class="border-white/20 bg-white text-brand-700 hover:bg-white">Back to Dashboard</x-ui.button>
                <x-ui.button :href="route('student.placements.create')" variant="secondary" class="border-white/20 bg-white/10 text-white hover:border-white/40 hover:text-white">Update Placement</x-ui.button>
            </div>
        </div>
    </section>
</x-layouts.app-shell>
