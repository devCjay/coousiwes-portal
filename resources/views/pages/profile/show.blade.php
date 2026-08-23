@php
    $role = auth()->user()?->hasRole('supervisor') ? 'Supervisor' : (auth()->user()?->hasRole('student') ? 'Student' : 'Admin');
    $dashboard = $role === 'Supervisor' ? route('supervisor.dashboard') : ($role === 'Student' ? route('student.dashboard') : route('admin.dashboard'));
    $navigation = [
        ['label' => 'Dashboard', 'href' => $dashboard, 'icon' => 'D'],
        ['label' => 'Profile', 'href' => route('profile.show'), 'active' => true, 'icon' => 'P'],
        ['label' => 'Notifications', 'href' => route('notifications.index'), 'icon' => 'N'],
    ];
@endphp

<x-layouts.app-shell title="Profile" :role="$role" :navigation="$navigation">
    <div class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
        <x-ui.card title="Account Profile" description="Current authenticated portal account details.">
            <div class="flex items-center gap-4">
                <span class="grid size-16 place-items-center rounded-lg bg-brand-600 text-white shadow-glow">
                    <x-ui.icon name="user-circle" class="size-8" />
                </span>
                <div>
                    <p class="text-lg font-semibold text-[var(--text-strong)]">{{ $user->name }}</p>
                    <p class="text-sm text-[var(--text-soft)]">{{ $user->email }}</p>
                </div>
            </div>

            <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                <div class="rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-4">
                    <dt class="text-xs font-semibold uppercase text-[var(--text-soft)]">Phone</dt>
                    <dd class="mt-1 text-sm font-medium">{{ $user->phone ?: 'Not provided' }}</dd>
                </div>
                <div class="rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-4">
                    <dt class="text-xs font-semibold uppercase text-[var(--text-soft)]">Role</dt>
                    <dd class="mt-1 text-sm font-medium">{{ $user->roles->pluck('name')->join(', ') ?: $role }}</dd>
                </div>
                <div class="rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-4">
                    <dt class="text-xs font-semibold uppercase text-[var(--text-soft)]">Last Login</dt>
                    <dd class="mt-1 text-sm font-medium">{{ $user->last_login_at?->diffForHumans() ?: 'Not recorded' }}</dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card title="Portal Identity" description="Role-specific profile information synchronized from the database.">
            @if ($user->student)
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div><dt class="text-xs font-semibold uppercase text-[var(--text-soft)]">Matric Number</dt><dd class="mt-1 text-sm font-medium">{{ $user->student->matric_no }}</dd></div>
                    <div><dt class="text-xs font-semibold uppercase text-[var(--text-soft)]">Status</dt><dd class="mt-1 text-sm font-medium">{{ ucfirst($user->student->activation_status) }}</dd></div>
                </dl>
            @elseif ($user->supervisor)
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div><dt class="text-xs font-semibold uppercase text-[var(--text-soft)]">Staff Number</dt><dd class="mt-1 text-sm font-medium">{{ $user->supervisor->staff_no }}</dd></div>
                    <div><dt class="text-xs font-semibold uppercase text-[var(--text-soft)]">Status</dt><dd class="mt-1 text-sm font-medium">{{ ucfirst($user->supervisor->status) }}</dd></div>
                </dl>
            @else
                <p class="text-sm text-[var(--text-soft)]">Administrative account profile.</p>
            @endif
        </x-ui.card>
    </div>
</x-layouts.app-shell>
