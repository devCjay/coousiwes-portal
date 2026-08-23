@php
    $navigation = [
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'D'],
        ['label' => 'Generate List', 'href' => route('admin.generate-list.index'), 'icon' => 'file-text'],
        ['label' => 'Students', 'href' => route('admin.students.index'), 'icon' => 'S'],
        ['label' => 'Tickets', 'href' => route('admin.tickets.index'), 'icon' => 'T'],
        ['label' => 'Supervisors', 'href' => route('admin.supervisors.index'), 'icon' => 'V'],
        ['label' => 'Reports', 'href' => route('admin.reports.index'), 'icon' => 'R'],
        ['label' => 'Rubric', 'href' => route('admin.assessments.rubric.index'), 'active' => true, 'icon' => 'A'],
        ['label' => 'Academics', 'href' => route('admin.academics.index'), 'icon' => 'C'],
        ['label' => 'Settings', 'href' => route('admin.settings.index'), 'icon' => 'G'],
    ];
@endphp

<x-layouts.app-shell title="Assessment Rubric" role="Admin" :navigation="$navigation">
    <div class="grid gap-4 md:grid-cols-3">
        <x-ui.stat-card label="Rubric Items" :value="$rubricItems->count()" meta="Assessment criteria" />
        <x-ui.stat-card label="Active Items" :value="$rubricItems->where('is_active', true)->count()" meta="Visible to supervisors" tone="cyan" />
        <x-ui.stat-card label="Recent Assessments" :value="$recentAssessments->count()" meta="Latest submissions" tone="amber" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[0.85fr_1.15fr]">
        <x-ui.card title="Create Rubric Item" description="Configured items drive supervisor assessment scoring.">
            <form method="POST" action="{{ route('admin.assessments.rubric.store') }}" class="grid gap-4">
                @csrf
                <x-ui.input label="Name" name="name" placeholder="Technical skill" required />
                <label class="block">
                    <span class="text-sm font-medium text-[var(--text-strong)]">Description</span>
                    <textarea name="description" rows="4" class="siwes-form-control mt-2" placeholder="Assessment guidance for supervisors"></textarea>
                </label>
                <div class="grid gap-4 md:grid-cols-3">
                    <x-ui.input label="Max Score" name="max_score" type="number" value="10" min="1" max="100" required />
                    <x-ui.input label="Weight" name="weight" type="number" value="1" min="1" max="20" required />
                    <x-ui.input label="Sort" name="sort_order" type="number" value="{{ $rubricItems->count() + 1 }}" min="0" required />
                </div>
                <label class="flex items-center gap-2 text-sm text-[var(--text-strong)]">
                    <input type="checkbox" name="is_active" value="1" class="size-4 rounded border-[var(--line)]" checked>
                    Active
                </label>
                <x-ui.button type="submit">Create Item</x-ui.button>
            </form>
        </x-ui.card>

        <x-ui.card title="Rubric Library" description="Live-search and update the scoring model without code changes.">
            <x-ui.input class="mb-4" label="Live Search" name="rubric_search" placeholder="Search rubric items..." data-live-search="#rubric-table tbody tr" />
            <x-ui.data-table
                id="rubric-table"
                :headers="['Item', 'Score', 'Weight', 'Status', 'Update']"
                :rows="$rubricItems->map(fn ($item) => [
                    '<span class=&quot;font-semibold&quot;>'.e($item->name).'</span><div class=&quot;text-xs text-[var(--text-soft)]&quot;>'.e($item->description ?? 'No description').'</div>',
                    e((string) $item->max_score),
                    e((string) $item->weight),
                    '<span class=&quot;inline-flex rounded-md px-2 py-1 text-xs font-semibold '.($item->is_active ? 'bg-brand-500/10 text-brand-700 dark:text-brand-200' : 'bg-rose-500/10 text-rose-700 dark:text-rose-200').'&quot;>'.e($item->is_active ? 'Active' : 'Inactive').'</span>',
                    '<form method=&quot;POST&quot; action=&quot;'.route('admin.assessments.rubric.update', $item).'&quot; class=&quot;grid min-w-72 gap-2&quot;><input type=&quot;hidden&quot; name=&quot;_token&quot; value=&quot;'.csrf_token().'&quot;><input type=&quot;hidden&quot; name=&quot;_method&quot; value=&quot;PUT&quot;><input type=&quot;hidden&quot; name=&quot;name&quot; value=&quot;'.e($item->name).'&quot;><input type=&quot;hidden&quot; name=&quot;description&quot; value=&quot;'.e($item->description ?? '').'&quot;><div class=&quot;grid grid-cols-3 gap-2&quot;><input class=&quot;rounded-lg border border-[var(--line)] bg-[var(--surface-raised)] px-2 py-1 text-xs&quot; name=&quot;max_score&quot; type=&quot;number&quot; value=&quot;'.e((string) $item->max_score).'&quot;><input class=&quot;rounded-lg border border-[var(--line)] bg-[var(--surface-raised)] px-2 py-1 text-xs&quot; name=&quot;weight&quot; type=&quot;number&quot; value=&quot;'.e((string) $item->weight).'&quot;><input class=&quot;rounded-lg border border-[var(--line)] bg-[var(--surface-raised)] px-2 py-1 text-xs&quot; name=&quot;sort_order&quot; type=&quot;number&quot; value=&quot;'.e((string) $item->sort_order).'&quot;></div><label class=&quot;flex items-center gap-2 text-xs&quot;><input type=&quot;checkbox&quot; name=&quot;is_active&quot; value=&quot;1&quot; '.($item->is_active ? 'checked' : '').'> Active</label><button class=&quot;rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white&quot; type=&quot;submit&quot;>Save</button></form>',
                ])->all()"
            />
        </x-ui.card>
    </div>
</x-layouts.app-shell>
