@props([
    'variant' => 'primary',
    'href' => null,
    'type' => 'button',
    'icon' => null,
])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold theme-transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-50';
    $variants = [
        'primary' => 'bg-brand-600 text-white shadow-[0_16px_34px_rgb(0_81_54_/_0.22)] hover:-translate-y-0.5 hover:bg-brand-700 dark:bg-brand-400 dark:text-graphite-950 dark:hover:bg-brand-300',
        'secondary' => 'border border-[var(--line)] bg-[var(--surface-raised)] text-[var(--text-strong)] shadow-[0_10px_24px_rgb(8_15_12_/_0.05)] hover:-translate-y-0.5 hover:border-brand-400 hover:text-brand-700 dark:hover:text-brand-200',
        'danger' => 'bg-rose-600 text-white shadow-[0_16px_34px_rgb(225_29_72_/_0.18)] hover:-translate-y-0.5 hover:bg-rose-700',
        'ghost' => 'text-[var(--text-soft)] hover:bg-[var(--surface-muted)] hover:text-[var(--text-strong)]',
    ];
    $label = trim(strip_tags((string) $slot));
    $autoIcon = match (true) {
        str_contains($label, 'Student') => 'graduation-cap',
        str_contains($label, 'Supervisor') => 'user-check',
        str_contains($label, 'Admin') => 'shield',
        str_contains($label, 'Dashboard') => 'layout-dashboard',
        str_contains($label, 'Started') => 'log-in',
        str_contains($label, 'Assessment') => 'clipboard-check',
        str_contains($label, 'securely') => 'key-round',
        str_contains($label, 'OTP') => 'refresh-cw',
        str_contains($label, 'Search'), str_contains($label, 'Filter') => 'search',
        str_contains($label, 'Export'), str_contains($label, 'Download'), str_contains($label, 'Template') => 'download',
        str_contains($label, 'Upload'), str_contains($label, 'Import'), str_contains($label, 'Preview') => 'upload',
        str_contains($label, 'Create'), str_contains($label, 'Add') => 'plus',
        str_contains($label, 'Update'), str_contains($label, 'Save') => 'save',
        str_contains($label, 'Generate') => 'ticket',
        str_contains($label, 'Pay'), str_contains($label, 'Korapay') => 'credit-card',
        str_contains($label, 'Verify'), str_contains($label, 'Continue') => 'check',
        str_contains($label, 'Cancel') => 'x',
        str_contains($label, 'Suspend') => 'ban',
        str_contains($label, 'Reactivate') => 'rotate-ccw',
        str_contains($label, 'Assign'), str_contains($label, 'Supervisor') => 'user-check',
        str_contains($label, 'Mark') => 'check-check',
        str_contains($label, 'Open') => 'open',
        default => null,
    };
    $resolvedIcon = $icon === false ? null : ($icon ?: $autoIcon);
@endphp

@if ($href)
    <a {{ $attributes->merge(['href' => $href, 'class' => $base.' '.$variants[$variant]]) }}>
        @if ($resolvedIcon)
            <x-ui.icon :name="$resolvedIcon" class="size-4 shrink-0" />
        @endif
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['type' => $type, 'class' => $base.' '.$variants[$variant]]) }}>
        @if ($resolvedIcon)
            <x-ui.icon :name="$resolvedIcon" class="size-4 shrink-0" />
        @endif
        {{ $slot }}
    </button>
@endif
