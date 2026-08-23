@props(['tone' => 'brand'])

@php
    $tones = [
        'brand' => 'border-brand-500/25 bg-brand-500/10 text-brand-700 dark:text-brand-200',
        'cyan' => 'border-cyan-400/25 bg-cyan-400/10 text-cyan-700 dark:text-cyan-200',
        'amber' => 'border-amber-400/30 bg-amber-400/12 text-amber-700 dark:text-amber-200',
        'rose' => 'border-rose-400/25 bg-rose-400/10 text-rose-700 dark:text-rose-200',
        'muted' => 'border-[var(--line)] bg-[var(--surface-muted)] text-[var(--text-soft)]',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-md border px-2.5 py-1 text-xs font-semibold '.$tones[$tone]]) }}>
    {{ $slot }}
</span>
