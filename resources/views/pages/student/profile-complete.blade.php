@php
    $navigation = [
        ['label' => 'Dashboard', 'href' => route('student.dashboard'), 'icon' => 'D'],
        ['label' => 'Profile', 'href' => route('student.profile.show'), 'icon' => 'user-circle'],
        ['label' => 'Payment', 'href' => route('student.payments.index'), 'icon' => 'K'],
        ['label' => 'Feedback', 'href' => route('student.feedback.index'), 'icon' => 'F'],
    ];
@endphp

<x-layouts.app-shell title="Profile Completed" role="Student" :navigation="$navigation">
    <section class="relative isolate overflow-hidden rounded-2xl bg-brand-600 px-5 py-12 text-center text-white shadow-[0_24px_70px_rgb(0_81_54_/_0.28)] sm:px-8 lg:py-16">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_20%_20%,rgba(255,255,255,0.20),transparent_18rem),linear-gradient(128deg,transparent_0%,transparent_54%,rgba(255,255,255,0.09)_54%,rgba(255,255,255,0.09)_61%,transparent_61%,transparent_70%,rgba(255,255,255,0.08)_70%,rgba(255,255,255,0.08)_78%,transparent_78%)]"></div>
        <span class="mx-auto grid size-20 place-items-center rounded-3xl bg-white text-brand-600 shadow-[0_20px_50px_rgb(0_0_0_/_0.16)]">
            <x-ui.icon name="check-check" class="size-10" />
        </span>
        <h2 class="mx-auto mt-6 max-w-3xl text-3xl font-black leading-tight sm:text-4xl">Congratulations, {{ explode(' ', trim($student->user->name))[0] ?: 'Student' }}.</h2>
        <p class="mx-auto mt-3 max-w-2xl text-sm leading-7 text-white/80 sm:text-base">Your profile milestone is complete. You can now continue to your SIWES dashboard and use the student portal without interruption.</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <x-ui.button :href="route('student.dashboard')" class="bg-white text-brand-600 hover:bg-brand-50">Open Dashboard</x-ui.button>
            <x-ui.button :href="route('student.payments.index')" variant="secondary" class="border-white/20 bg-white/10 text-white hover:border-white/40 hover:text-white">Go to Payments</x-ui.button>
        </div>
    </section>
</x-layouts.app-shell>
