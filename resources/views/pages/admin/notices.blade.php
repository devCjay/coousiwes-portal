@php
    $navigation = [
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'D'],
        ['label' => 'Generate List', 'href' => route('admin.generate-list.index'), 'icon' => 'file-text'],
        ['label' => 'Notices', 'href' => route('admin.notices.index'), 'active' => true, 'icon' => 'N'],
        ['label' => 'Settings', 'href' => route('admin.settings.index'), 'icon' => 'G'],
    ];
@endphp

<x-layouts.app-shell title="Notice Board" role="Admin" :navigation="$navigation">
    <div class="grid gap-6 xl:grid-cols-[0.85fr_1.15fr]">
        <x-ui.card title="Publish Notice" description="Create public notices shown on the landing page notice board.">
            <form method="POST" action="{{ route('admin.notices.store') }}" class="grid gap-4">
                @csrf
                <x-ui.input label="Title" name="title" placeholder="SIWES orientation schedule" required />
                <label class="block">
                    <span class="text-sm font-medium text-[var(--text-strong)]">Notice Body</span>
                    <textarea name="body" rows="5" required class="siwes-form-control mt-2 theme-transition placeholder:text-[var(--text-soft)]" placeholder="Write the notice students and supervisors should see..."></textarea>
                </label>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="text-sm font-medium text-[var(--text-strong)]">Audience</span>
                        <select name="audience" class="siwes-form-control mt-2">
                            <option value="all">All</option>
                            <option value="students">Students</option>
                            <option value="supervisors">Supervisors</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium text-[var(--text-strong)]">Tone</span>
                        <select name="tone" class="siwes-form-control mt-2">
                            <option value="info">Info</option>
                            <option value="success">Success</option>
                            <option value="warning">Warning</option>
                            <option value="danger">Important</option>
                        </select>
                    </label>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.input label="Publish At" name="published_at" type="datetime-local" />
                    <x-ui.input label="Expires At" name="expires_at" type="datetime-local" />
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_pinned" value="1" class="rounded border-[var(--line)]">
                    Pin this notice
                </label>
                <x-ui.button type="submit">Publish Notice</x-ui.button>
            </form>
        </x-ui.card>

        <x-ui.card title="Published Notices" description="Live search and status view for notices displayed on the public portal.">
            <x-ui.input class="mb-4" label="Live Search" name="notice_search" placeholder="Search notices..." data-live-search="#notices-table tbody tr" />
            <x-ui.data-table
                id="notices-table"
                :headers="['Title', 'Audience', 'Tone', 'Pinned', 'Published', 'Expires']"
                :rows="$notices->map(fn ($notice) => [
                    e($notice->title),
                    e(ucfirst($notice->audience)),
                    e(ucfirst($notice->tone)),
                    $notice->is_pinned ? 'Yes' : 'No',
                    e($notice->published_at?->format('M d, Y H:i') ?? 'Draft'),
                    e($notice->expires_at?->format('M d, Y H:i') ?? 'No expiry'),
                ])->all()"
            />
        </x-ui.card>
    </div>
</x-layouts.app-shell>
