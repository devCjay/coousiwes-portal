@php
    $user = auth()->user();
    $role = $user?->hasRole('supervisor') ? 'Supervisor' : ($user?->hasRole('student') ? 'Student' : 'Admin');
    $dashboard = $role === 'Supervisor' ? route('supervisor.dashboard') : ($role === 'Student' ? route('student.dashboard') : route('admin.dashboard'));
    $navigation = [
        ['label' => 'Dashboard', 'href' => $dashboard, 'icon' => 'D'],
        ['label' => 'Notifications', 'href' => route('notifications.index'), 'active' => true, 'icon' => 'N'],
    ];
@endphp

<x-layouts.app-shell title="Notification Center" :role="$role" :navigation="$navigation">
    <div class="grid gap-4 md:grid-cols-3">
        <x-ui.stat-card label="Unread" :value="$unreadCount" meta="Actionable alerts" />
        <x-ui.stat-card label="Total" :value="$notifications->total()" meta="Notification history" tone="cyan" />
        <x-ui.stat-card label="Delivery" value="Realtime" meta="Database + Reverb/Echo ready" tone="amber" />
    </div>

    <x-ui.card class="mt-6" title="Notifications" description="Search, review, and clear your portal notifications.">
        <div class="mb-4 grid gap-3 md:grid-cols-[1fr_auto]">
            <x-ui.input label="Live Search" name="notification_search" placeholder="Search notifications..." data-live-search="#notification-center-list [data-notification-item]" />
            <form method="POST" action="{{ route('notifications.read-all') }}" class="flex items-end">
                @csrf
                <x-ui.button type="submit" variant="secondary">Mark All Read</x-ui.button>
            </form>
        </div>

        <div id="notification-center-list" class="space-y-3">
            @forelse ($notifications as $notification)
                <article data-notification-item @class([
                    'rounded-lg border p-4 theme-transition',
                    'border-cyan-400/30 bg-cyan-400/5 shadow-glow' => $notification->read_at === null,
                    'border-[var(--line)] bg-[var(--surface-raised)]' => $notification->read_at !== null,
                ])>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold">{{ $notification->data['title'] ?? 'Portal notification' }}</p>
                            <p class="mt-1 text-sm text-[var(--text-soft)]">{{ $notification->data['message'] ?? 'Open your dashboard for details.' }}</p>
                            <p class="mt-2 text-xs text-[var(--text-soft)]">{{ $notification->created_at?->diffForHumans() }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @if (! empty($notification->data['action_url']))
                                <x-ui.button :href="$notification->data['action_url']" variant="secondary">Open</x-ui.button>
                            @endif
                            @if ($notification->read_at === null)
                                <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                    @csrf
                                    <x-ui.button type="submit" variant="ghost">Mark Read</x-ui.button>
                                </form>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-6 text-sm text-[var(--text-soft)]">
                    No notifications yet.
                </div>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $notifications->links() }}
        </div>
    </x-ui.card>
</x-layouts.app-shell>
