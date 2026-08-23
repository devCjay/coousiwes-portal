@props(['title' => 'Sign in', 'role' => null])

<x-layouts.public :title="$title">
    <x-layouts.public-header />

    <main class="relative grid min-h-[calc(100vh-10.5rem)] place-items-center px-4 py-10">
        <section data-reveal="scale" class="grid w-full max-w-5xl overflow-hidden rounded-lg siwes-surface lg:grid-cols-[1fr_0.88fr]">
            <div class="relative min-h-[22rem] overflow-hidden bg-graphite-900 p-8 text-white lg:p-10">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_15%,rgb(34_211_238_/_0.22),transparent_18rem),radial-gradient(circle_at_80%_70%,rgb(0_81_54_/_0.28),transparent_20rem)]"></div>
                @if ($role === 'Student' && $title === 'Student Login')
                    <img
                        src="{{ asset('images/student-auth-overlay.png') }}"
                        alt="COOU SIWES students"
                        class="pointer-events-none absolute bottom-0 left-1/2 z-0 w-[115%] max-w-none -translate-x-1/2 opacity-90 drop-shadow-[0_24px_38px_rgb(0_0_0_/_0.35)]"
                    >
                    <div class="absolute inset-0 z-0 bg-linear-to-t from-graphite-900 via-graphite-900/58 to-graphite-900/8"></div>
                @endif

                <div class="relative z-10 flex h-full flex-col justify-end gap-10">
                    <div class="max-w-md">
                        <span class="mb-4 inline-flex items-center gap-2 rounded-full border border-cyan-300/20 bg-cyan-300/10 px-3 py-1 text-xs font-semibold text-cyan-100">
                            <x-ui.icon name="shield" class="size-3.5" />
                            Secure access
                        </span>
                        <h1 class="text-3xl font-semibold leading-tight md:text-4xl">{{ $title }}</h1>
                        <p class="mt-4 text-sm leading-6 text-white/68">COOU SIWES Portal</p>
                    </div>
                </div>
            </div>

            <div class="bg-[var(--surface-raised)] p-6 md:p-8 lg:p-10">
                {{ $slot }}
            </div>
        </section>
    </main>
</x-layouts.public>
