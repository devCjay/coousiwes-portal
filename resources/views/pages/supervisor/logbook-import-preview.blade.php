@php
    $navigation = [
        ['label' => 'Dashboard', 'href' => route('supervisor.dashboard'), 'icon' => 'D'],
        ['label' => 'Assigned Students', 'href' => route('supervisor.students.index'), 'active' => true, 'icon' => 'S'],
        ['label' => 'Assessment Score Sheet', 'href' => route('supervisor.assessments.index'), 'icon' => 'A'],
    ];
@endphp

<x-layouts.app-shell title="Review Logbook Import" role="Supervisor" :navigation="$navigation">
    @if ($error)
        <x-ui.alert title="Import failed" tone="danger">{{ $error }}</x-ui.alert>
        <div class="mt-5">
            <x-ui.button :href="route('supervisor.students.index')" variant="secondary">Back to Assigned Students</x-ui.button>
        </div>
    @else
        <x-ui.card title="Review Imported Logbook Scores" description="Confirm or correct each uploaded score before saving. Blank scores are skipped.">
            <form method="POST" action="{{ route('supervisor.students.logbook-import.confirm') }}" class="grid gap-5" data-ajax="false">
                @csrf
                <div class="overflow-x-auto rounded-xl border border-[var(--line)]">
                    <table class="min-w-full divide-y divide-[var(--line)] text-sm">
                        <thead class="bg-[var(--surface-muted)] text-left text-xs font-bold uppercase text-[var(--text-soft)]">
                            <tr>
                                <th class="px-4 py-3">Student</th>
                                <th class="px-4 py-3">Reg No</th>
                                <th class="px-4 py-3">Department</th>
                                <th class="px-4 py-3">Matched Row</th>
                                @foreach ($rubricItems as $item)
                                    <th class="px-4 py-3">{{ $item->name }} ({{ $item->max_score }})</th>
                                @endforeach
                                <th class="px-4 py-3">Total ({{ $maxScore }})</th>
                                <th class="px-4 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--line)]">
                            @forelse ($rows as $row)
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-[var(--text-strong)]">{{ $row['student_name'] }}</td>
                                    <td class="px-4 py-3 text-[var(--text-strong)]">{{ $row['matric_no'] }}</td>
                                    <td class="px-4 py-3 text-[var(--text-strong)]">{{ $row['department'] }}</td>
                                    <td class="max-w-xs px-4 py-3 text-xs leading-5 text-[var(--text-soft)]">{{ $row['matched_line'] ?: 'No matching spreadsheet row found.' }}</td>
                                    @foreach ($rubricItems as $item)
                                        <td class="px-4 py-3">
                                            <input
                                                name="scores[{{ $row['assignment_id'] }}][{{ $item->id }}]"
                                                type="number"
                                                min="0"
                                                max="{{ $item->max_score }}"
                                                value="{{ $row['locked'] ? '' : ($row['rubric_scores'][$item->id] ?? '') }}"
                                                @disabled($row['locked'])
                                                data-assessment-score
                                                data-assessment-group="import-{{ $row['assignment_id'] }}"
                                                class="w-24 rounded-lg border border-[var(--line)] bg-[var(--surface-raised)] px-3 py-2 text-sm text-[var(--text-strong)]"
                                            >
                                        </td>
                                    @endforeach
                                    <td class="px-4 py-3 font-bold text-[var(--text-strong)]">
                                        <span data-assessment-total data-assessment-group="import-{{ $row['assignment_id'] }}">{{ $row['detected_score'] }}</span> / {{ $maxScore }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $row['locked'] ? 'bg-amber-300 text-graphite-950' : ($row['status'] === 'Needs review' ? 'bg-rose-100 text-rose-700' : 'bg-brand-400 text-white') }}">
                                            {{ $row['status'] }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ 6 + $rubricItems->count() }}" class="px-4 py-6 text-center text-sm text-[var(--text-soft)]">No active assignments were found for this supervisor.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <details class="rounded-xl border border-[var(--line)] bg-[var(--surface-muted)] p-4">
                    <summary class="cursor-pointer text-sm font-bold text-[var(--text-strong)]">View uploaded spreadsheet rows</summary>
                    <div class="mt-3 max-h-72 overflow-auto">
                        <table class="min-w-full divide-y divide-[var(--line)] text-xs">
                            <thead class="text-left font-bold uppercase text-[var(--text-soft)]">
                                <tr>
                                    <th class="px-3 py-2">Row</th>
                                    <th class="px-3 py-2">Reg No</th>
                                    @foreach ($rubricItems as $item)
                                        <th class="px-3 py-2">{{ $item->name }}</th>
                                    @endforeach
                                    <th class="px-3 py-2">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[var(--line)]">
                                @forelse ($importedRows as $importedRow)
                                    <tr>
                                        <td class="px-3 py-2 text-[var(--text-strong)]">{{ $importedRow['row_number'] }}</td>
                                        <td class="px-3 py-2 text-[var(--text-strong)]">{{ $importedRow['matric_no'] }}</td>
                                        @foreach ($rubricItems as $item)
                                            <td class="px-3 py-2 text-[var(--text-strong)]">{{ $importedRow['rubric_scores'][$item->id]['raw_score'] ?? 'N/A' }}</td>
                                        @endforeach
                                        <td class="px-3 py-2 font-bold text-[var(--text-strong)]">{{ $importedRow['total_score'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ 3 + $rubricItems->count() }}" class="px-3 py-4 text-center text-[var(--text-soft)]">No import rows were found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </details>

                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <x-ui.button :href="route('supervisor.students.index')" variant="ghost">Cancel</x-ui.button>
                    <x-ui.button type="submit" data-loading-text="Saving...">Confirm Reviewed Scores</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    @endif

    <script>
        document.addEventListener('input', (event) => {
            const input = event.target.closest('[data-assessment-score]');
            if (! input) return;

            const group = input.dataset.assessmentGroup;
            const total = document.querySelector(`[data-assessment-total][data-assessment-group="${group}"]`);
            if (! total) return;

            const sum = Array.from(document.querySelectorAll(`[data-assessment-score][data-assessment-group="${group}"]`))
                .reduce((carry, field) => carry + (Number.parseInt(field.value || '0', 10) || 0), 0);

            total.textContent = sum;
        });
    </script>
</x-layouts.app-shell>
