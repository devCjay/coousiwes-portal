@props(['label', 'value'])

<div class="min-w-0 rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-3">
    <dt class="text-xs font-semibold uppercase text-[var(--text-soft)]">{{ $label }}</dt>
    <dd class="mt-1 break-words text-sm font-semibold text-[var(--text-strong)]">{{ $value }}</dd>
</div>
