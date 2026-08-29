@props(['title' => config('app.name', 'COOU SIWES')])

@php
    $welcomeSettings = collect();

    if (\Illuminate\Support\Facades\Schema::hasTable('app_settings')) {
        $welcomeSettings = \App\Models\AppSetting::query()
            ->whereIn('key', [
                'site.welcome.enabled',
                'site.welcome.title',
                'site.welcome.message',
                'site.welcome.duration_seconds',
            ])
            ->pluck('value', 'key');
    }

    $welcomeEnabled = (bool) ($welcomeSettings['site.welcome.enabled'] ?? true);
    $welcomeTitle = (string) ($welcomeSettings['site.welcome.title'] ?? 'Welcome to COOU SIWES');
    $welcomeMessage = (string) ($welcomeSettings['site.welcome.message'] ?? '');
    $welcomeDuration = max(3, (int) ($welcomeSettings['site.welcome.duration_seconds'] ?? 6));
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased">
        <div class="relative flex min-h-screen flex-col overflow-hidden">
            <div class="particle-field"></div>
            <div class="relative z-10 flex-1">
                {{ $slot }}
            </div>

            <footer class="relative z-10 border-t border-[var(--line)] bg-[var(--surface-raised)]/80 px-4 py-6 sm:px-6 lg:px-8">
                <div class="mx-auto flex max-w-7xl flex-col gap-4 text-sm text-[var(--text-soft)] md:flex-row md:items-center md:justify-between">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <x-ui.logo class="h-9" />
                        <p>&copy; {{ date('Y') }} COOU SIWES Management Portal. All rights reserved.</p>
                    </div>
                    <p>Built for students, supervisors, and industrial training coordination.</p>
                </div>
            </footer>
        </div>
        <div
            data-toast
            data-flash-title="{{ session('toast_title', 'Notification') }}"
            data-flash-message="{{ session('status') }}"
            data-flash-tone="{{ session('toast_tone', 'success') }}"
            style="--toast-title: #064e3b; --toast-body: #047857;"
            class="pointer-events-none fixed right-5 top-5 z-50 w-[min(24rem,calc(100vw-2rem))] -translate-y-6 rounded-lg border border-brand-400/30 bg-emerald-50 p-4 opacity-0 shadow-glow transition duration-200 dark:bg-graphite-900"
        >
            <p data-toast-title class="text-sm font-semibold text-[var(--toast-title)]">Notification</p>
            <p data-toast-message class="mt-1 text-xs font-medium text-[var(--toast-body)]">Action completed.</p>
        </div>
        @if (request()->routeIs('home') && $welcomeEnabled && $welcomeMessage !== '')
            <div
                data-welcome-toast
                data-welcome-duration="{{ $welcomeDuration * 2000 }}"
                data-welcome-session-key="siwes-welcome-seen"
                class="fixed left-1/2 top-5 z-[70] w-[min(32rem,calc(100vw-2rem))] -translate-x-1/2 -translate-y-8 rounded-lg border border-brand-400/30 bg-[var(--surface-raised)] p-4 text-[var(--text-strong)] opacity-0 shadow-glow transition duration-500"
                role="dialog"
                aria-live="polite"
                aria-label="{{ $welcomeTitle }}"
            >
                <div class="flex items-start gap-3">
                    <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-brand-500/10 text-brand-700 dark:text-brand-200">
                        <x-ui.icon name="bell" class="size-5" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold">{{ $welcomeTitle }}</p>
                        <p class="mt-1 text-sm leading-6 text-[var(--text-soft)]">{{ $welcomeMessage }}</p>
                    </div>
                    <button type="button" data-welcome-close class="ml-auto grid size-8 shrink-0 place-items-center rounded-md text-[var(--text-soft)] theme-transition hover:bg-[var(--surface-muted)] hover:text-[var(--text-strong)]" aria-label="Dismiss welcome message">
                        <x-ui.icon name="x" class="size-4" />
                    </button>
                </div>
            </div>
        @endif
    </body>
</html>
