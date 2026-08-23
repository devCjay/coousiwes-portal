@props(['tone' => 'info', 'title' => null])

@php
    $tones = [
        'info' => [
            'box' => 'border-brand-600/25 bg-[linear-gradient(135deg,#f0fdf4_0%,#ecfeff_100%)] shadow-[0_14px_34px_rgb(0_81_54_/_0.10)] dark:border-brand-400/30 dark:bg-[linear-gradient(135deg,rgb(0_81_54_/_0.32)_0%,rgb(8_47_73_/_0.25)_100%)] dark:shadow-[0_0_32px_rgb(0_81_54_/_0.16)]',
            'icon' => 'info',
            'iconBox' => 'bg-brand-600 text-white shadow-[0_0_18px_rgb(0_81_54_/_0.24)] dark:bg-brand-400 dark:text-graphite-900',
            'title' => '#064e3b',
            'body' => '#166534',
            'darkTitle' => '#d9fbe5',
            'darkBody' => '#b6f5cf',
        ],
        'success' => [
            'box' => 'border-brand-600/25 bg-[linear-gradient(135deg,#effdf4_0%,#ffffff_100%)] shadow-[0_14px_34px_rgb(0_81_54_/_0.10)] dark:border-brand-400/30 dark:bg-[linear-gradient(135deg,rgb(0_81_54_/_0.34)_0%,rgb(13_26_21_/_0.92)_100%)] dark:shadow-[0_0_32px_rgb(0_81_54_/_0.16)]',
            'icon' => 'check',
            'iconBox' => 'bg-brand-600 text-white shadow-[0_0_18px_rgb(0_81_54_/_0.24)] dark:bg-brand-400 dark:text-graphite-900',
            'title' => '#064e3b',
            'body' => '#166534',
            'darkTitle' => '#d9fbe5',
            'darkBody' => '#b6f5cf',
        ],
        'warning' => [
            'box' => 'border-amber-500/35 bg-[linear-gradient(135deg,#fffbeb_0%,#f0fdf4_100%)] shadow-[0_14px_34px_rgb(217_155_0_/_0.10)] dark:border-amber-400/35 dark:bg-[linear-gradient(135deg,rgb(120_53_15_/_0.30)_0%,rgb(6_78_59_/_0.24)_100%)] dark:shadow-[0_0_32px_rgb(246_196_69_/_0.11)]',
            'icon' => 'alert-triangle',
            'iconBox' => 'bg-amber-500 text-white shadow-[0_0_18px_rgb(217_155_0_/_0.24)] dark:bg-amber-300 dark:text-amber-950',
            'title' => '#064e3b',
            'body' => '#166534',
            'darkTitle' => '#fef3c7',
            'darkBody' => '#fde68a',
        ],
        'danger' => [
            'box' => 'border-rose-500/30 bg-[linear-gradient(135deg,#fff1f2_0%,#f0fdf4_100%)] shadow-[0_14px_34px_rgb(225_29_72_/_0.10)] dark:border-rose-400/35 dark:bg-[linear-gradient(135deg,rgb(136_19_55_/_0.28)_0%,rgb(6_78_59_/_0.20)_100%)] dark:shadow-[0_0_32px_rgb(251_113_133_/_0.11)]',
            'icon' => 'alert-triangle',
            'iconBox' => 'bg-rose-600 text-white shadow-[0_0_18px_rgb(225_29_72_/_0.22)] dark:bg-rose-300 dark:text-rose-950',
            'title' => '#064e3b',
            'body' => '#166534',
            'darkTitle' => '#ffe4e6',
            'darkBody' => '#fecdd3',
        ],
    ];
    $toneClasses = $tones[$tone] ?? $tones['info'];
@endphp

<div
    style="--alert-title: {{ $toneClasses['title'] }}; --alert-body: {{ $toneClasses['body'] }}; --alert-dark-title: {{ $toneClasses['darkTitle'] }}; --alert-dark-body: {{ $toneClasses['darkBody'] }};"
    {{ $attributes->merge(['class' => 'siwes-alert relative overflow-hidden rounded-lg border p-4 '.$toneClasses['box']]) }}
>
    <div class="absolute inset-y-3 left-0 w-1 rounded-r-full bg-brand-600 dark:bg-brand-400"></div>

    <div class="relative flex gap-3">
        <span class="mt-0.5 grid size-8 shrink-0 place-items-center rounded-md {{ $toneClasses['iconBox'] }}">
            <x-ui.icon :name="$toneClasses['icon']" class="size-4" />
        </span>

        <div class="min-w-0">
            @if ($title)
                <p class="siwes-alert-title text-sm font-bold">
                    {{ $title }}
                </p>
            @endif
            <div class="siwes-alert-body {{ $title ? 'mt-1 ' : '' }}text-sm font-medium leading-6">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
