@props(['label', 'value', 'meta' => null, 'tone' => 'brand', 'icon' => null])

@php
    $tones = [
        'brand' => ['accent' => 'text-brand-700 dark:text-brand-200', 'icon' => 'bg-brand-600 text-white dark:bg-brand-400 dark:text-graphite-950'],
        'cyan' => ['accent' => 'text-cyan-700 dark:text-cyan-200', 'icon' => 'bg-cyan-500 text-white dark:bg-cyan-300 dark:text-cyan-950'],
        'amber' => ['accent' => 'text-amber-700 dark:text-amber-200', 'icon' => 'bg-amber-500 text-white dark:bg-amber-300 dark:text-amber-950'],
        'rose' => ['accent' => 'text-rose-700 dark:text-rose-200', 'icon' => 'bg-rose-600 text-white dark:bg-rose-300 dark:text-rose-950'],
    ];
    $toneClasses = $tones[$tone] ?? $tones['brand'];

    $icon ??= match (true) {
        str_contains(strtolower($label), 'student') => 'graduation-cap',
        str_contains(strtolower($label), 'supervisor') => 'user-check',
        str_contains(strtolower($label), 'ticket') => 'ticket',
        str_contains(strtolower($label), 'payment') || str_contains(strtolower($label), 'amount') => 'credit-card',
        str_contains(strtolower($label), 'notification') || str_contains(strtolower($label), 'unread') => 'bell',
        str_contains(strtolower($label), 'feedback') => 'message-square',
        str_contains(strtolower($label), 'assessment') || str_contains(strtolower($label), 'rubric') => 'clipboard-check',
        str_contains(strtolower($label), 'role') || str_contains(strtolower($label), 'admin') => 'shield',
        str_contains(strtolower($label), 'faculty') || str_contains(strtolower($label), 'department') || str_contains(strtolower($label), 'session') => 'building',
        str_contains(strtolower($label), 'import') || str_contains(strtolower($label), 'upload') => 'upload',
        str_contains(strtolower($label), 'score') || str_contains(strtolower($label), 'average') => 'chart-column',
        str_contains(strtolower($label), 'failed') || str_contains(strtolower($label), 'open') => 'alert-triangle',
        default => 'trending-up',
    };
@endphp

<article class="siwes-surface siwes-panel-accent rounded-2xl p-5 theme-transition hover:-translate-y-0.5">
    <p class="text-sm font-semibold text-[var(--text-soft)]">{{ $label }}</p>
    <div class="mt-3 flex items-end justify-between gap-4">
        <strong data-countup class="text-3xl font-bold text-[var(--text-strong)]">{{ $value }}</strong>
        <span class="grid size-11 shrink-0 place-items-center rounded-xl {{ $toneClasses['icon'] }} shadow-[0_14px_30px_rgb(8_15_12_/_0.14)]">
            <x-ui.icon :name="$icon" class="size-5" />
        </span>
    </div>
    @if ($meta)
        <p class="mt-3 text-xs font-bold {{ $toneClasses['accent'] }}">{{ $meta }}</p>
    @endif
</article>
