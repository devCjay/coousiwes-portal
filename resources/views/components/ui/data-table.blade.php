@props(['headers' => [], 'rows' => []])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl border border-[var(--line)] bg-[var(--surface-raised)] shadow-[0_14px_34px_rgb(8_15_12_/_0.055)]']) }}>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-[var(--line)] text-left text-sm">
            <thead class="bg-brand-600/12 text-xs font-extrabold uppercase text-black dark:bg-white/5 dark:text-brand-100">
                <tr>
                    @foreach ($headers as $header)
                        <th class="whitespace-nowrap px-4 py-3.5">{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-[var(--line)] bg-[var(--surface-raised)]">
                @foreach ($rows as $row)
                    <tr class="theme-transition hover:bg-brand-600/5">
                        @foreach ($row as $cell)
                            <td class="whitespace-nowrap px-4 py-3.5 text-[var(--text-strong)]">{!! $cell !!}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
