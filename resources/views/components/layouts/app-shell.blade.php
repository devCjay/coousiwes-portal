@props([
    'title' => 'Dashboard',
    'role' => 'Admin',
    'navigation' => [],
])

@php
    $visibleNavigation = collect($navigation)
        ->filter(fn (array $item): bool => $role !== 'Admin' || \App\Support\AdminNavigation::canSee($item))
        ->values();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title }} | COOU SIWES</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased" data-user-id="{{ auth()->id() }}">
        <div class="min-h-screen lg:grid lg:grid-cols-[18rem_1fr]">
            <button
                type="button"
                data-sidebar-close
                class="pointer-events-none fixed inset-0 z-30 bg-black/45 opacity-0 transition-opacity duration-200 lg:hidden"
                aria-label="Close navigation backdrop"
            ></button>

            <aside data-sidebar class="fixed inset-y-0 left-0 z-40 w-72 -translate-x-full border-r border-white/10 bg-brand-600 text-white shadow-[18px_0_55px_rgb(0_81_54_/_0.22)] transition-transform duration-200 lg:static lg:translate-x-0">
                <div class="flex h-full flex-col">
                    <div class="border-b border-white/10 bg-black/10 p-5">
                        <div class="mb-4 flex items-start justify-between gap-3 lg:hidden">
                            <a href="{{ route('home') }}" class="block">
                                <x-ui.logo class="h-11 max-w-full" />
                                <span class="mt-3 block text-xs font-semibold text-white/62">{{ $role }} Console</span>
                            </a>
                            <button type="button" data-sidebar-close class="grid size-9 shrink-0 place-items-center rounded-lg border border-white/10 text-white/70 theme-transition hover:bg-white/10 hover:text-white lg:hidden" aria-label="Close navigation">
                                <x-ui.icon name="x" class="size-4" />
                            </button>
                        </div>
                        <div class="hidden lg:block">
                            <a href="{{ route('home') }}" class="block">
                                <x-ui.logo class="h-11 max-w-full" />
                                <span class="mt-3 block text-xs font-semibold text-white/62">{{ $role }} Console</span>
                            </a>
                        </div>
                    </div>

                    <nav class="flex-1 space-y-1.5 overflow-y-auto p-4">
                        @foreach ($visibleNavigation as $item)
                            <x-ui.sidebar-link :href="$item['href']" :active="$item['active'] ?? false" :icon="$item['icon'] ?? null">
                                {{ $item['label'] }}
                            </x-ui.sidebar-link>
                        @endforeach
                    </nav>

                    <div class="border-t border-white/10 p-4">
                        <div class="rounded-2xl border border-white/10 bg-white/10 p-3 shadow-[inset_0_1px_0_rgb(255_255_255_/_0.08)]">
                            <p class="text-xs font-semibold text-white/82">{{ $role }}</p>
                            <p class="mt-1 text-xs text-white/50">Active secure session</p>
                        </div>
                    </div>
                </div>
            </aside>

            <div class="min-w-0">
                <header class="sticky top-0 z-30 border-b border-[var(--line)] bg-[color-mix(in_srgb,var(--surface-raised)_88%,transparent)] shadow-[0_10px_30px_rgb(8_15_12_/_0.045)]">
                    <div class="flex h-16 items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
                        <div class="flex min-w-0 items-center gap-3">
                            <button type="button" data-sidebar-toggle class="grid size-10 place-items-center rounded-xl border border-[var(--line)] bg-[var(--surface-raised)] text-[var(--text-soft)] shadow-[0_10px_24px_rgb(8_15_12_/_0.05)] lg:hidden" aria-label="Toggle navigation" aria-expanded="false">
                                <x-ui.icon name="menu" class="size-5" />
                            </button>
                            <div class="min-w-0">
                                <p class="text-xs font-bold uppercase text-brand-600 dark:text-brand-300">{{ $role }}</p>
                                <h1 class="truncate text-lg font-bold md:text-xl">{{ $title }}</h1>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <a href="{{ route('notifications.index') }}" data-notification-link class="relative grid size-10 place-items-center rounded-xl border border-[var(--line)] bg-[var(--surface-raised)] text-[var(--text-soft)] shadow-[0_10px_24px_rgb(8_15_12_/_0.05)] theme-transition hover:border-brand-400 hover:text-brand-600" aria-label="Notifications">
                                <x-ui.icon name="bell" class="size-5" />
                                @php($unreadNotificationCount = auth()->user()?->unreadNotifications()->count() ?? 0)
                                <span data-notification-count @class([
                                    'absolute -right-1 -top-1 grid min-w-5 place-items-center rounded-full bg-cyan-400 px-1 text-[10px] font-bold text-graphite-950 shadow-glow',
                                    'hidden' => $unreadNotificationCount === 0,
                                ])>{{ $unreadNotificationCount }}</span>
                            </a>
                            <button type="button" data-theme-toggle class="grid size-10 place-items-center rounded-xl border border-[var(--line)] bg-[var(--surface-raised)] text-[var(--text-soft)] shadow-[0_10px_24px_rgb(8_15_12_/_0.05)] theme-transition hover:border-brand-400 hover:text-brand-600" aria-label="Toggle theme">
                                <x-ui.icon name="moon" class="size-5 drop-shadow-[0_0_8px_current]" />
                            </button>
                            <details class="group relative">
                                <summary class="grid size-10 cursor-pointer list-none place-items-center rounded-xl border border-[var(--line)] bg-[var(--surface-raised)] text-[var(--text-soft)] shadow-[0_10px_24px_rgb(8_15_12_/_0.05)] theme-transition hover:border-brand-400 hover:text-brand-600 [&::-webkit-details-marker]:hidden" aria-label="Account menu">
                                    <x-ui.icon name="user-circle" class="size-5" />
                                </summary>
                                <div class="absolute right-0 mt-2 w-48 overflow-hidden rounded-2xl border border-[var(--line)] bg-[var(--surface-raised)] p-1.5 shadow-[0_24px_70px_rgb(8_15_12_/_0.18)]">
                                    @php($profileHref = auth()->user()?->student ? (auth()->user()->student->hasCompleteProfile() ? route('student.profile.show') : route('student.profile.edit')) : route('profile.show'))
                                    <a href="{{ $profileHref }}" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-[var(--text-strong)] theme-transition hover:bg-[var(--surface-muted)]">
                                        <x-ui.icon name="user-circle" class="size-4" />
                                        Profile
                                    </a>
                                    <a href="{{ route('account.password.edit') }}" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-[var(--text-strong)] theme-transition hover:bg-[var(--surface-muted)]">
                                        <x-ui.icon name="key-round" class="size-4" />
                                        Change Password
                                    </a>
                                    <form method="POST" action="{{ route('logout') }}" data-ajax="false">
                                        @csrf
                                        <button type="submit" class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm font-medium text-rose-600 theme-transition hover:bg-rose-500/10 dark:text-rose-200">
                                            <x-ui.icon name="log-out" class="size-4" />
                                            Logout
                                        </button>
                                    </form>
                                </div>
                            </details>
                        </div>
                    </div>
                </header>

                <main class="max-w-full overflow-x-clip px-2 py-4 sm:px-6 sm:py-6 lg:px-8 lg:py-8">
                    {{ $slot }}
                </main>
            </div>
        </div>

        <div
            data-toast
            data-flash-title="{{ session('toast_title', 'Notification') }}"
            data-flash-message="{{ session('status') }}"
            data-flash-tone="{{ session('toast_tone', 'success') }}"
            role="status"
            aria-live="polite"
            style="--toast-title: #064e3b; --toast-body: #047857;"
            class="pointer-events-none fixed right-5 top-5 z-50 w-[min(24rem,calc(100vw-2rem))] -translate-y-6 rounded-2xl border border-brand-400/30 bg-emerald-50 p-4 opacity-0 shadow-[0_24px_70px_rgb(0_81_54_/_0.18)] transition duration-200 dark:bg-graphite-900"
        >
            <p data-toast-title class="text-sm font-semibold text-[var(--toast-title)]">Notification</p>
            <p data-toast-message class="mt-1 text-xs font-medium text-[var(--toast-body)]">Action completed.</p>
        </div>
    </body>
</html>
