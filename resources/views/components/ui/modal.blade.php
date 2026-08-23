@props(['id', 'title'])

<dialog id="{{ $id }}" {{ $attributes->merge(['class' => 'm-auto max-h-[calc(100vh-2rem)] w-[min(34rem,calc(100vw-2rem))] overflow-hidden rounded-2xl border border-[var(--line)] bg-[var(--surface-raised)] p-0 text-[var(--text-strong)] shadow-[0_30px_90px_rgb(8_15_12_/_0.28)] backdrop:bg-graphite-950/70']) }}>
    <div class="relative border-b border-[var(--line)] bg-[var(--surface-muted)]/65 p-5">
        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-brand-700 via-brand-500 to-amber-500"></div>
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-base font-bold">{{ $title }}</h2>
            <button type="button" data-modal-close class="grid size-9 place-items-center rounded-xl border border-[var(--line)] bg-[var(--surface-raised)] text-[var(--text-soft)] theme-transition hover:border-brand-400 hover:text-brand-600" aria-label="Close modal">
                <x-ui.icon name="x" class="size-4" />
            </button>
        </div>
    </div>
    <div class="max-h-[calc(100vh-8rem)] overflow-y-auto p-5">
        {{ $slot }}
    </div>
</dialog>
