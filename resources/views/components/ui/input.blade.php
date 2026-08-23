@props(['label', 'name', 'type' => 'text'])

<label class="block min-w-0">
    <span class="siwes-form-label">{{ $label }}</span>
    <input
        name="{{ $name }}"
        type="{{ $type }}"
        {{ $attributes->merge(['class' => 'siwes-form-control mt-2 theme-transition placeholder:text-[var(--text-soft)]']) }}
    >
</label>
