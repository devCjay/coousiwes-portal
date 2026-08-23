@props(['title' => null, 'description' => null])

<section {{ $attributes->merge(['class' => 'siwes-surface siwes-panel-accent min-w-0 rounded-2xl p-4 theme-transition sm:p-5']) }}>
    @if ($title || $description)
        <header class="mb-5 min-w-0 pt-1">
            @if ($title)
                <h2 class="text-base font-bold tracking-normal text-[var(--text-strong)]">{{ $title }}</h2>
            @endif
            @if ($description)
                <p class="mt-1 text-sm leading-6 text-[var(--text-soft)]">{{ $description }}</p>
            @endif
        </header>
    @endif

    {{ $slot }}
</section>
