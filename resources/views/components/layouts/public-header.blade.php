<header data-reveal="fade-down" class="relative z-10 mx-auto flex max-w-7xl items-center justify-between px-4 py-5 sm:px-6 lg:px-8">
    <a href="{{ route('home') }}" class="inline-flex items-center">
        <x-ui.logo class="h-10 sm:h-12" />
    </a>

    <nav class="hidden items-center gap-2 md:flex">
        <x-ui.button variant="ghost" href="{{ route('home') }}">Home</x-ui.button>
        <x-ui.button variant="ghost" href="{{ route('login.student') }}">Student</x-ui.button>
        <x-ui.button variant="ghost" href="{{ route('login.supervisor') }}">Supervisor</x-ui.button>
        <x-ui.button variant="ghost" href="{{ route('login.admin') }}">Admin</x-ui.button>
        <button type="button" data-theme-toggle class="grid size-10 place-items-center rounded-lg border border-[var(--line)] text-[var(--text-soft)] theme-transition" aria-label="Toggle theme">
            <x-ui.icon name="moon" class="size-5 drop-shadow-[0_0_8px_current]" />
        </button>
    </nav>

    <button type="button" data-public-menu-open class="grid size-10 place-items-center rounded-lg border border-[var(--line)] bg-[var(--surface-raised)] text-[var(--text-soft)] theme-transition hover:border-brand-400 hover:text-brand-600 md:hidden" aria-label="Open navigation menu" aria-controls="public-mobile-menu" aria-expanded="false">
        <x-ui.icon name="menu" class="size-5" />
    </button>
</header>

<div data-public-menu class="fixed inset-0 z-[80] hidden md:hidden" id="public-mobile-menu" aria-hidden="true">
    <button type="button" data-public-menu-close class="absolute inset-0 bg-graphite-900/45 opacity-0 transition-opacity duration-300" aria-label="Close navigation menu"></button>
    <aside data-public-menu-panel class="absolute inset-y-0 left-0 flex w-[min(21rem,86vw)] -translate-x-full flex-col border-r border-[var(--line)] bg-[var(--surface-raised)] p-5 shadow-2xl transition-transform duration-300 ease-out">
        <div class="flex items-center justify-between gap-4">
            <x-ui.logo class="h-10" />
            <button type="button" data-public-menu-close class="grid size-9 place-items-center rounded-lg border border-[var(--line)] text-[var(--text-soft)] theme-transition hover:border-brand-400 hover:text-brand-600" aria-label="Close navigation menu">
                <x-ui.icon name="x" class="size-4" />
            </button>
        </div>

        <nav class="mt-8 space-y-2">
            <a href="{{ route('home') }}" class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-semibold text-[var(--text-strong)] theme-transition hover:bg-[var(--surface-muted)]">
                <span class="grid size-9 place-items-center rounded-md bg-[var(--surface-muted)] text-[var(--text-soft)]">
                    <x-ui.icon name="home" class="size-4" />
                </span>
                Home
            </a>
            <a href="{{ route('login.student') }}" class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-semibold text-[var(--text-strong)] theme-transition hover:bg-[var(--surface-muted)]">
                <span class="grid size-9 place-items-center rounded-md bg-brand-500/10 text-brand-700">
                    <x-ui.icon name="graduation-cap" class="size-4" />
                </span>
                Student Portal
            </a>
            <a href="{{ route('login.supervisor') }}" class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-semibold text-[var(--text-strong)] theme-transition hover:bg-[var(--surface-muted)]">
                <span class="grid size-9 place-items-center rounded-md bg-cyan-400/10 text-cyan-700">
                    <x-ui.icon name="user-check" class="size-4" />
                </span>
                Supervisor Portal
            </a>
            <a href="{{ route('login.admin') }}" class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-semibold text-[var(--text-strong)] theme-transition hover:bg-[var(--surface-muted)]">
                <span class="grid size-9 place-items-center rounded-md bg-amber-400/15 text-amber-700">
                    <x-ui.icon name="shield" class="size-4" />
                </span>
                Admin Portal
            </a>
        </nav>

        <div class="mt-auto border-t border-[var(--line)] pt-4">
            <button type="button" data-theme-toggle class="flex w-full items-center gap-3 rounded-lg px-3 py-3 text-left text-sm font-semibold text-[var(--text-strong)] theme-transition hover:bg-[var(--surface-muted)]">
                <span class="grid size-9 place-items-center rounded-md bg-[var(--surface-muted)] text-[var(--text-soft)]">
                    <x-ui.icon name="moon" class="size-4" />
                </span>
                Toggle theme
            </button>
        </div>
    </aside>
</div>
