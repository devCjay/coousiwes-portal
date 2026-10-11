@php
    $navigation = [
        ['label' => 'Dashboard', 'href' => route('supervisor.dashboard'), 'icon' => 'D'],
        ['label' => 'Assigned Students', 'href' => route('supervisor.students.index'), 'active' => true, 'icon' => 'S'],
        ['label' => 'Grade Log Book', 'href' => route('supervisor.assessments.index'), 'icon' => 'A'],
    ];
@endphp

<x-layouts.app-shell title="Review Logbook OCR Import" role="Supervisor" :navigation="$navigation">
    @if ($error)
        <x-ui.alert title="OCR failed" tone="danger">{{ $error }}</x-ui.alert>
        <div class="mt-5">
            <x-ui.button :href="route('supervisor.students.index')" variant="secondary">Back to Assigned Students</x-ui.button>
        </div>
    @else
        <x-ui.card title="Review Imported Logbook Scores" description="Confirm or correct each detected score before saving. Blank scores are skipped.">
            <form method="POST" action="{{ route('supervisor.students.logbook-import.confirm') }}" class="grid gap-5" data-ajax="false">
                @csrf
                <div class="overflow-x-auto rounded-xl border border-[var(--line)]">
                    <table class="min-w-full divide-y divide-[var(--line)] text-sm">
                        <thead class="bg-[var(--surface-muted)] text-left text-xs font-bold uppercase text-[var(--text-soft)]">
                            <tr>
                                <th class="px-4 py-3">Student</th>
                                <th class="px-4 py-3">Reg No</th>
                                <th class="px-4 py-3">Department</th>
                                <th class="px-4 py-3">Detected Line</th>
                                <th class="px-4 py-3">Score</th>
                                <th class="px-4 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--line)]">
                            @forelse ($rows as $row)
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-[var(--text-strong)]">{{ $row['student_name'] }}</td>
                                    <td class="px-4 py-3 text-[var(--text-strong)]">{{ $row['matric_no'] }}</td>
                                    <td class="px-4 py-3 text-[var(--text-strong)]">{{ $row['department'] }}</td>
                                    <td class="max-w-xs px-4 py-3 text-xs leading-5 text-[var(--text-soft)]">{{ $row['matched_line'] ?: 'No matching OCR line found.' }}</td>
                                    <td class="px-4 py-3">
                                        <input
                                            name="scores[{{ $row['assignment_id'] }}]"
                                            type="number"
                                            min="0"
                                            max="{{ $maxScore }}"
                                            value="{{ $row['locked'] ? '' : $row['detected_score'] }}"
                                            @disabled($row['locked'])
                                            class="w-28 rounded-lg border border-[var(--line)] bg-[var(--surface-raised)] px-3 py-2 text-sm text-[var(--text-strong)]"
                                        >
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $row['locked'] ? 'bg-amber-300 text-graphite-950' : ($row['detected_score'] === null ? 'bg-rose-100 text-rose-700' : 'bg-brand-400 text-white') }}">
                                            {{ $row['status'] }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-sm text-[var(--text-soft)]">No active assignments were found for this supervisor.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <details class="rounded-xl border border-[var(--line)] bg-[var(--surface-muted)] p-4">
                    <summary class="cursor-pointer text-sm font-bold text-[var(--text-strong)]">View raw OCR text</summary>
                    <pre class="mt-3 max-h-72 overflow-auto whitespace-pre-wrap text-xs leading-5 text-[var(--text-soft)]">{{ $ocrText }}</pre>
                </details>

                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <x-ui.button :href="route('supervisor.students.index')" variant="ghost">Cancel</x-ui.button>
                    <x-ui.button type="submit" data-loading-text="Saving...">Confirm Reviewed Scores</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    @endif
</x-layouts.app-shell>
