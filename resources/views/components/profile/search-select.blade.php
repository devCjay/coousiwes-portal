@props([
    'label',
    'name',
    'placeholder' => 'Search...',
    'options' => [],
    'value' => '',
])

@php
    $optionCount = collect($options)->count();
    $selected = collect($options)->firstWhere('value', (string) $value);
    $displayValue = $selected['label'] ?? '';
@endphp

<div {{ $attributes->merge(['class' => 'relative min-w-0'])->except(['data-profile-gender', 'data-profile-nationality', 'data-profile-state', 'data-profile-lga', 'data-profile-faculty', 'data-profile-department', 'data-profile-bank']) }}
    data-profile-combobox
    @foreach ($attributes->whereStartsWith('data-profile-') as $attribute => $attributeValue)
        {{ $attribute }}="{{ $attributeValue }}"
    @endforeach
>
    <label class="text-sm font-medium text-[var(--text-strong)]">{{ $label }}</label>
    <input type="hidden" name="{{ $name }}" value="{{ $value }}" data-profile-combobox-value required>
    <div class="relative mt-2">
        <input
            type="text"
            value="{{ $displayValue }}"
            placeholder="{{ $placeholder }}"
            autocomplete="off"
            data-profile-combobox-input
            @class([
                'block w-full min-w-0 rounded-xl border border-[var(--line)] bg-[var(--surface-raised)] px-3 py-3 text-sm text-[var(--text-strong)] shadow-sm theme-transition placeholder:text-[var(--text-soft)] focus:border-brand-400 focus:ring-4 focus:ring-brand-400/15',
                'pr-10' => $optionCount > 10,
            ])
        >
        @if ($optionCount > 10)
            <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[var(--text-soft)]">
                <x-ui.icon name="search" class="size-4" />
            </span>
        @endif
    </div>
    <div data-profile-combobox-list class="absolute left-0 right-0 z-50 mt-2 hidden max-h-72 min-w-full overflow-y-auto rounded-xl border border-brand-600/20 bg-[var(--surface-raised)] p-1.5 shadow-[0_24px_70px_rgb(8_15_12_/_0.20)] ring-1 ring-white/40 dark:ring-white/5">
        @forelse ($options as $option)
            <button
                type="button"
                data-profile-combobox-option
                data-value="{{ $option['value'] }}"
                data-label="{{ $option['label'] }}"
                @isset($option['sort_code']) data-sort-code="{{ $option['sort_code'] }}" @endisset
                class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-left text-sm text-[var(--text-strong)] theme-transition hover:bg-[var(--surface-muted)]"
            >
                <span class="min-w-0">
                    <span class="block truncate font-medium">{{ $option['label'] }}</span>
                    @isset($option['meta'])
                        <span class="mt-0.5 block truncate text-xs text-[var(--text-soft)]">{{ $option['meta'] }}</span>
                    @endisset
                </span>
                <x-ui.icon name="check" @class(['size-4 shrink-0 text-brand-600', 'hidden' => (string) $option['value'] !== (string) $value]) data-profile-combobox-check />
            </button>
        @empty
            <p class="px-3 py-2 text-sm text-[var(--text-soft)]">No records available.</p>
        @endforelse
    </div>
</div>
