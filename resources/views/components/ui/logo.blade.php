@props([
    'class' => 'h-12',
    'surface' => true,
])

<span @class([
    'inline-flex items-center rounded-lg border border-[var(--line)] bg-white/95 px-3 py-2 shadow-sm ring-1 ring-black/5 theme-transition dark:border-white/15 dark:bg-white dark:shadow-glow' => $surface,
])>
    <img
        src="{{ asset('images/coou-logo.png') }}"
        alt="Chukwuemeka Odumegwu Ojukwu University"
        {{ $attributes->merge(['class' => $class.' w-auto object-contain']) }}
    >
</span>
