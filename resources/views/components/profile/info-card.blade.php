@props(['icon', 'label', 'value', 'meta' => null, 'locked' => false])

<article class="min-w-0 rounded-xl border border-[var(--line)] bg-[var(--surface-muted)] p-4 theme-transition hover:border-brand-400">
    <div class="flex items-start gap-3">
        <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-brand-600 text-white">
            <x-ui.icon :name="$icon" class="size-5" />
        </span>
        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase text-[var(--text-soft)]">{{ $label }}</p>
            <p class="mt-1 truncate text-sm font-bold text-[var(--text-strong)]">{{ $value }}</p>
            @if ($meta)
                <p class="mt-1 text-xs text-[var(--text-soft)]">{{ $meta }}</p>
            @endif
        </div>
    </div>
</article>
