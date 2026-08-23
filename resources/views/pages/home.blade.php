<x-layouts.public title="COOU SIWES Management Portal">
    <x-layouts.public-header />

    <main class="relative z-10">
        <section data-reveal="scale" class="siwes-hero siwes-surface relative mx-auto mb-8 max-w-7xl overflow-hidden px-4 py-8 text-graphite-900 sm:px-6 lg:px-8 dark:bg-graphite-50 dark:text-graphite-900">
            <div class="siwes-hero-watermark"></div>
            <div class="relative z-10 grid gap-8 lg:grid-cols-[0.95fr_1.05fr] lg:items-center">
                <div class="max-w-3xl">
                    <div class="border-l-[5px] border-brand-800 pl-4">
                        <h1 class="text-3xl font-black uppercase leading-[1.02] text-brand-900 sm:text-4xl lg:text-5xl">
                            Students
                            <span class="block text-cyber-amber">Industrial Work</span>
                            <span class="block">Experience Scheme</span>
                        </h1>
                    </div>

                    <div class="mt-4 inline-flex max-w-full rounded-r-md bg-brand-900 px-4 py-2 text-sm font-bold text-white shadow-lg sm:text-base">
                        Bridging Knowledge. Building Careers. Strengthening Nigeria.
                    </div>

                    <p class="mt-4 max-w-xl text-base font-medium italic leading-7 text-graphite-800">
                        Empowering students with real-world experience, skills development and industry exposure.
                    </p>

                    <div class="mt-6 flex flex-wrap gap-3">
                        <x-ui.button href="{{ route('login.student') }}">Student Access</x-ui.button>
                        <x-ui.button variant="secondary" href="{{ route('login.supervisor') }}">Supervisor Access</x-ui.button>
                    </div>
                </div>

                <div class="relative min-h-[17rem] lg:min-h-[25rem]">
                    <div class="siwes-hero-image-shell">
                        <img
                            src="{{ asset('images/siwes-landing-hero.png') }}"
                            alt="COOU SIWES students across engineering, health sciences, creative arts, and business training"
                            class="h-full w-full object-cover"
                        >
                    </div>
                </div>
            </div>
            <div class="siwes-hero-badge">
                <span class="whitespace-nowrap text-base font-black leading-tight text-white sm:text-lg">Building Future Workforce</span>
            </div>
        </section>

        <!-- <section class="border-t border-[var(--line)] bg-[var(--surface-raised)]/70 px-4 py-10 sm:px-6 lg:px-8">
            <div class="mx-auto grid max-w-7xl gap-4 md:grid-cols-2">
                <x-ui.card title="For Students" description="Activate accounts, complete profiles, track tickets, and view feedback.">
                    <div class="flex flex-wrap items-center gap-3">
                        <x-ui.badge>Student Portal</x-ui.badge> 
                        <x-ui.button href="{{ route('login.student') }}">Open Student Login</x-ui.button>
                    </div>
                </x-ui.card>
                <x-ui.card title="For Supervisors" description="Monitor assigned students, submit assessments, and manage feedback.">
                    <div class="flex flex-wrap items-center gap-3">
                       <x-ui.badge tone="cyan">Supervisor Portal</x-ui.badge> 
                        <x-ui.button href="{{ route('login.supervisor') }}" variant="secondary">Open Supervisor Login</x-ui.button>
                    </div>
                </x-ui.card>
            </div>
        </section> -->

        <section data-reveal="fade-up" class="px-4 py-10 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <x-ui.badge tone="amber">Latest Update</x-ui.badge>
                        <h2 class="mt-3 text-2xl font-semibold text-[var(--text-strong)]">Notice Board</h2>
                        <p class="mt-1 max-w-2xl text-sm text-[var(--text-soft)]">Important updates published by the SIWES administration for students and supervisors.</p>
                    </div>
                    <span class="inline-flex items-center gap-2 text-xs font-semibold uppercase text-brand-700 dark:text-brand-200">
                        <x-ui.icon name="bell" class="size-4" />
                       <!--  Synced from admin -->
                    </span>
                </div>

                <div class="notice-board-grid grid gap-4 lg:grid-cols-3">
                    @forelse ($notices as $notice)
                        @php
                            $toneClasses = [
                                'info' => 'border-cyan-400/30 bg-cyan-400/10 text-cyan-800 dark:text-cyan-100',
                                'success' => 'border-brand-400/30 bg-brand-400/10 text-brand-800 dark:text-brand-100',
                                'warning' => 'border-amber-400/35 bg-amber-400/12 text-amber-800 dark:text-amber-100',
                                'danger' => 'border-rose-400/30 bg-rose-400/10 text-rose-800 dark:text-rose-100',
                            ];
                        @endphp
                        <article data-reveal="fade-up" style="--reveal-delay: {{ min($loop->index * 80, 240) }}ms" class="notice-card group relative overflow-hidden rounded-lg border border-[var(--line)] bg-[var(--surface-raised)] p-5 shadow-[0_18px_50px_rgb(8_15_12_/_0.08)] theme-transition hover:-translate-y-1 hover:border-brand-400 hover:shadow-glow">
                            <div class="absolute inset-x-0 top-0 h-1 bg-linear-to-r from-brand-500 via-cyan-400 to-cyber-amber"></div>
                            <div class="flex items-start justify-between gap-3">
                                <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-brand-500/10 text-brand-700 dark:text-brand-200">
                                    <x-ui.icon name="bell" class="size-5" />
                                </span>
                                @if ($notice->is_pinned)
                                    <span class="rounded-md bg-cyber-amber/20 px-2 py-1 text-[10px] font-black uppercase text-amber-700">Pinned</span>
                                @endif
                            </div>
                            <h3 class="mt-4 text-base font-semibold text-[var(--text-strong)]">{{ $notice->title }}</h3>
                            <p class="mt-2 line-clamp-4 text-sm leading-6 text-[var(--text-soft)]">{{ $notice->body }}</p>
                            <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
                                <span class="rounded-md border px-2 py-1 text-xs font-semibold {{ $toneClasses[$notice->tone] ?? $toneClasses['info'] }}">{{ ucfirst($notice->audience) }}</span>
                                <time class="text-xs text-[var(--text-soft)]">{{ $notice->published_at?->format('M d, Y') }}</time>
                            </div>
                        </article>
                    @empty
                        <div class="notice-card rounded-lg border border-dashed border-[var(--line)] bg-[var(--surface-raised)] p-6 text-sm text-[var(--text-soft)] lg:col-span-3">
                            No public notices have been published yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </section>
    </main>
</x-layouts.public>
