@props(['href', 'active' => false, 'icon' => null])

@php
    $label = trim(strip_tags((string) $slot));
    $semanticIcon = match ($label) {
        'Dashboard' => 'layout-dashboard',
        'Generate List' => 'file-text',
        'Students', 'Assigned Students' => 'users',
        'Profile' => 'graduation-cap',
        'Bulk Upload' => 'upload',
        'Tickets' => 'ticket',
        'Supervisors' => 'user-check',
        'Payments', 'Payment' => 'credit-card',
        'Reports' => 'chart-column',
        'Assessments', 'Rubric' => 'clipboard-check',
        'Academics' => 'book-open',
        'Settings' => 'settings',
        'Control' => 'shield',
        'Audit' => 'history',
        'Feedback' => 'message-square',
        'Notifications' => 'bell',
        default => null,
    };
    $resolvedIcon = $semanticIcon ?: ($icon ?? 'file-text');
@endphp

<a
    href="{{ $href }}"
    @class([
        'group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold theme-transition',
        'bg-white text-brand-700 shadow-[0_16px_36px_rgb(8_15_12_/_0.18)]' => $active,
        'text-white/72 hover:bg-white/10 hover:text-white' => ! $active,
    ])
>
    <span @class([
        'grid size-8 place-items-center rounded-lg theme-transition',
        'bg-brand-600 text-white' => $active,
        'bg-white/10 text-white/82 group-hover:bg-white/15' => ! $active,
    ])>
        <x-ui.icon :name="$resolvedIcon" class="size-4" />
    </span>
    <span class="truncate">{{ $slot }}</span>
</a>
