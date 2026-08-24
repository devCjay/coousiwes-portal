@php
    $navigation = [
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'D'],
        ['label' => 'Generate List', 'href' => route('admin.generate-list.index'), 'icon' => 'file-text'],
        ['label' => 'Control', 'href' => route('admin.control.index'), 'active' => true, 'icon' => 'C'],
        ['label' => 'Audit', 'href' => route('admin.control.audit.index'), 'icon' => 'L'],
        ['label' => 'Reports', 'href' => route('admin.reports.index'), 'icon' => 'R'],
        ['label' => 'Settings', 'href' => route('admin.settings.index'), 'icon' => 'G'],
    ];
    $flatPermissions = $permissions->flatten(1);
@endphp

<x-layouts.app-shell title="Super Admin Control" role="Super Admin" :navigation="$navigation">
    <div class="grid gap-4 md:grid-cols-4">
        <x-ui.stat-card label="Admins" :value="$admins->count()" meta="Privileged users" />
        <x-ui.stat-card label="Roles" :value="$roles->count()" meta="Configurable access groups" tone="cyan" />
        <x-ui.stat-card label="Queued Jobs" :value="$health['queued_jobs']" meta="Pending background work" tone="amber" />
        <x-ui.stat-card label="Failed Jobs" :value="$health['failed_jobs']" meta="Operational exceptions" tone="rose" />
    </div>

    <div class="mt-6 grid min-w-0 gap-6 xl:grid-cols-[minmax(22rem,0.75fr)_minmax(0,1.25fr)]">
        <x-ui.card title="Create Admin" description="Super-admin verified creation with password confirmation.">
            <form method="POST" action="{{ route('admin.control.admins.store') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
                @csrf
                <x-ui.input label="Current Password" name="current_password" type="password" required />
                <x-ui.input label="Full Name" name="name" required />
                <x-ui.input label="Email" name="email" type="email" required />
                <x-ui.input label="Phone" name="phone" />
                <x-ui.input label="Temporary Password" name="password" type="password" required />
                <label class="block">
                    <span class="text-sm font-medium text-[var(--text-strong)]">Status</span>
                    <select name="status" class="siwes-form-control mt-2" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </label>
                <div class="md:col-span-2 xl:col-span-1 2xl:col-span-2">
                    <p class="text-sm font-medium text-[var(--text-strong)]">Roles</p>
                    <div class="mt-2 grid gap-2 sm:grid-cols-2">
                        @foreach ($roles->whereNotIn('name', ['super-admin', 'student', 'supervisor']) as $role)
                            <label class="flex items-center gap-2 rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] px-3 py-2 text-sm">
                                <input type="checkbox" name="roles[]" value="{{ $role->name }}" @checked($role->name === 'admin')>
                                {{ $role->name }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="md:col-span-2 xl:col-span-1 2xl:col-span-2">
                    <p class="text-sm font-medium text-[var(--text-strong)]">Direct Permissions</p>
                    <div class="mt-2 grid max-h-48 gap-2 overflow-y-auto rounded-lg border border-[var(--line)] p-3 sm:grid-cols-2">
                        @foreach ($flatPermissions as $permission)
                            <label class="flex items-center gap-2 text-xs">
                                <input type="checkbox" name="permissions[]" value="{{ $permission->name }}">
                                {{ $permission->name }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <x-ui.button type="submit" class="md:col-span-2 xl:col-span-1 2xl:col-span-2">Create Admin</x-ui.button>
            </form>
        </x-ui.card>

        <x-ui.card title="Admin Accounts" description="Live-search privileged users and update roles instantly." class="min-w-0">
            <x-ui.input class="mb-4" label="Live Search" name="admin_search" placeholder="Search admins..." data-live-search="#admin-control-table article" />
            <div id="admin-control-table" class="overflow-hidden rounded-lg border border-[var(--line)]">
                <div class="hidden grid-cols-[1.2fr_0.8fr_0.55fr_1.6fr] gap-4 border-b border-[var(--line)] bg-[var(--surface-muted)] px-4 py-3 text-xs font-semibold uppercase text-[var(--text-soft)] lg:grid">
                    <span>Admin</span>
                    <span>Roles</span>
                    <span>Status</span>
                    <span>Update</span>
                </div>
                <div class="divide-y divide-[var(--line)] bg-[var(--surface-raised)]">
                    @foreach ($admins as $admin)
                        <article class="grid gap-4 px-4 py-4 theme-transition hover:bg-[var(--surface-muted)] lg:grid-cols-[1.2fr_0.8fr_0.55fr_1.6fr] lg:items-start">
                            <div class="min-w-0">
                                <p class="font-semibold text-[var(--text-strong)]">{{ $admin->name }}</p>
                                <p class="mt-1 break-all text-xs text-[var(--text-soft)]">{{ $admin->email }}</p>
                                <p class="mt-1 text-[11px] font-semibold uppercase text-[var(--text-soft)]">{{ $admin->admin_code }}</p>
                            </div>
                            <div class="min-w-0">
                                <p class="mb-1 text-xs font-semibold uppercase text-[var(--text-soft)] lg:hidden">Roles</p>
                                <div class="flex flex-wrap gap-1.5">
                                    @forelse ($admin->roles as $role)
                                        <span class="rounded-md px-2 py-1 text-xs font-semibold text-graphite-950" style="background-color: var(--color-amber-300);">{{ $role->name }}</span>
                                    @empty
                                        <span class="rounded-md bg-[var(--surface-muted)] px-2 py-1 text-xs font-semibold text-[var(--text-soft)]">No role</span>
                                    @endforelse
                                </div>
                            </div>
                            <div>
                                <p class="mb-1 text-xs font-semibold uppercase text-[var(--text-soft)] lg:hidden">Status</p>
                                <span class="inline-flex rounded-md px-2 py-1 text-xs font-semibold text-white" style="background-color: var(--color-brand-400);">{{ ucfirst((string) $admin->status) }}</span>
                            </div>
                            <div class="min-w-0">
                                <p class="mb-1 text-xs font-semibold uppercase text-[var(--text-soft)] lg:hidden">Update</p>
                                @if (\App\Support\PortalPermission::isRootAdmin($admin))
                                    <span class="inline-flex rounded-md bg-[var(--surface-muted)] px-2 py-1 text-xs font-semibold text-[var(--text-soft)]">Protected root account</span>
                                @else
                                    <form id="admin-status-form-{{ $admin->id }}" method="POST" action="{{ route('admin.control.admins.update', $admin) }}" class="grid gap-3">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="name" value="{{ $admin->name }}">
                                        <input type="hidden" name="email" value="{{ $admin->email }}">
                                        <input type="hidden" name="phone" value="{{ $admin->phone }}">
                                        @foreach ($admin->roles as $role)
                                            <input type="hidden" name="roles[]" value="{{ $role->name }}">
                                        @endforeach
                                        @foreach ($admin->permissions as $permission)
                                            <input type="hidden" name="permissions[]" value="{{ $permission->name }}">
                                        @endforeach
                                        <div class="grid gap-2 sm:grid-cols-[1fr_auto] lg:grid-cols-1 2xl:grid-cols-[1fr_auto]">
                                            <select name="status" class="rounded-lg border border-[var(--line)] bg-[var(--surface-raised)] px-2 py-2 text-xs text-[var(--text-strong)]">
                                                <option value="active" @selected($admin->status === 'active')>Active</option>
                                                <option value="inactive" @selected($admin->status === 'inactive')>Inactive</option>
                                                <option value="suspended" @selected($admin->status === 'suspended')>Suspended</option>
                                            </select>
                                            <x-ui.button type="button" data-modal-target="#admin-status-confirm-{{ $admin->id }}">Confirm Status</x-ui.button>
                                        </div>
                                    </form>
                                    <x-ui.button type="button" variant="secondary" class="mt-2 w-full" data-modal-target="#admin-access-{{ $admin->id }}">Roles & Permissions</x-ui.button>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </x-ui.card>
    </div>

    @foreach ($admins as $admin)
        @continue(\App\Support\PortalPermission::isRootAdmin($admin))
        <x-ui.modal id="admin-status-confirm-{{ $admin->id }}" title="Confirm Status Update" class="w-[min(34rem,calc(100vw-2rem))]">
            <div class="grid gap-4">
                <div class="rounded-xl border border-[var(--line)] bg-[var(--surface-muted)] p-4">
                    <p class="text-sm font-semibold text-[var(--text-strong)]">{{ $admin->name }}</p>
                    <p class="mt-1 break-all text-xs text-[var(--text-soft)]">{{ $admin->email }}</p>
                    <p class="mt-3 text-sm text-[var(--text-soft)]">Enter your super admin password to confirm the selected status update.</p>
                </div>

                <x-ui.input
                    label="Super Admin Password"
                    name="current_password"
                    type="password"
                    placeholder="Confirm your password"
                    required
                    form="admin-status-form-{{ $admin->id }}"
                />

                <div class="flex justify-end gap-2">
                    <x-ui.button type="button" variant="ghost" data-modal-close>Cancel</x-ui.button>
                    <x-ui.button type="submit" form="admin-status-form-{{ $admin->id }}">Confirm Update</x-ui.button>
                </div>
            </div>
        </x-ui.modal>

        <x-ui.modal id="admin-access-{{ $admin->id }}" title="Roles & Permissions - {{ $admin->name }}" class="w-[min(62rem,calc(100vw-2rem))]">
            <form method="POST" action="{{ route('admin.control.admins.update', $admin) }}" class="grid gap-5">
                @csrf
                @method('PUT')
                <input type="hidden" name="name" value="{{ $admin->name }}">
                <input type="hidden" name="email" value="{{ $admin->email }}">
                <input type="hidden" name="phone" value="{{ $admin->phone }}">
                <input type="hidden" name="status" value="{{ $admin->status }}">

                <div class="rounded-xl border border-[var(--line)] bg-[var(--surface-muted)] p-4">
                    <p class="text-sm font-semibold text-[var(--text-strong)]">Admin Account</p>
                    <div class="mt-3 grid gap-3 sm:grid-cols-3">
                        <div>
                            <p class="text-xs font-semibold uppercase text-[var(--text-soft)]">Name</p>
                            <p class="mt-1 text-sm font-semibold text-[var(--text-strong)]">{{ $admin->name }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase text-[var(--text-soft)]">Email</p>
                            <p class="mt-1 break-all text-sm font-semibold text-[var(--text-strong)]">{{ $admin->email }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase text-[var(--text-soft)]">Status</p>
                            <span class="mt-1 inline-flex rounded-md px-2 py-1 text-xs font-semibold text-white" style="background-color: var(--color-brand-400);">{{ ucfirst((string) $admin->status) }}</span>
                        </div>
                    </div>
                </div>

                <div>
                    <p class="text-sm font-semibold text-[var(--text-strong)]">Assign Roles</p>
                    <p class="mt-1 text-xs text-[var(--text-soft)]">Select one or more module roles for this admin.</p>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($roles->whereNotIn('name', ['super-admin', 'student', 'supervisor']) as $role)
                            <label class="flex items-center gap-3 rounded-xl border border-[var(--line)] bg-[var(--surface-muted)] px-3 py-3 text-sm font-semibold text-[var(--text-strong)] theme-transition hover:border-amber-400">
                                <input type="checkbox" name="roles[]" value="{{ $role->name }}" @checked($admin->hasRole($role->name))>
                                <span>{{ $role->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <details class="rounded-xl border border-[var(--line)] bg-[var(--surface-muted)] p-4">
                    <summary class="cursor-pointer text-sm font-semibold text-[var(--text-strong)]">Direct Permissions</summary>
                    <p class="mt-2 text-xs text-[var(--text-soft)]">Use direct permissions only for exceptions not covered by roles.</p>
                    <div class="mt-4 grid max-h-64 gap-3 overflow-y-auto md:grid-cols-2">
                        @foreach ($flatPermissions as $permission)
                            <label class="flex items-center gap-2 rounded-lg bg-[var(--surface-raised)] px-3 py-2 text-xs text-[var(--text-strong)]">
                                <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" @checked($admin->hasDirectPermission($permission->name))>
                                {{ $permission->name }}
                            </label>
                        @endforeach
                    </div>
                </details>

                <x-ui.input label="Super Admin Password" name="current_password" type="password" placeholder="Confirm your password to save access changes" required />

                <div class="flex justify-end gap-2">
                    <x-ui.button type="button" variant="ghost" data-modal-close>Cancel</x-ui.button>
                    <x-ui.button type="submit">Save Access Changes</x-ui.button>
                </div>
            </form>
        </x-ui.modal>
    @endforeach

    <div class="mt-6 grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
        <x-ui.card title="Role Builder" description="Create custom admin roles and assign granular privileges.">
            <form method="POST" action="{{ route('admin.control.roles.store') }}" class="grid gap-4">
                @csrf
                <x-ui.input label="Current Password" name="current_password" type="password" required />
                <x-ui.input label="Role Name" name="name" placeholder="finance-admin" required />
                <div class="grid gap-4 md:grid-cols-2">
                    @foreach ($permissions as $group => $items)
                        <div class="rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-3">
                            <p class="text-sm font-semibold">{{ ucfirst((string) $group) }}</p>
                            <div class="mt-2 grid gap-2">
                                @foreach ($items as $permission)
                                    <label class="flex items-center gap-2 text-xs">
                                        <input type="checkbox" name="permissions[]" value="{{ $permission->name }}">
                                        {{ $permission->name }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
                <x-ui.button type="submit">Create Role</x-ui.button>
            </form>
        </x-ui.card>

        <x-ui.card title="Existing Roles" description="Permission groups available to admin users.">
            <x-ui.input class="mb-4" label="Live Search" name="role_search" placeholder="Search roles..." data-live-search="#roles-control-table tbody tr" />
            <x-ui.data-table
                id="roles-control-table"
                :headers="['Role', 'Permissions']"
                :rows="$roles->map(fn ($role) => [
                    e($role->name),
                    e((string) $role->permissions->count()),
                ])->all()"
            />
        </x-ui.card>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <x-ui.card title="Recent Audit Trail" description="Latest privileged and system events.">
            <div class="mb-4 flex justify-end">
                <x-ui.button :href="route('admin.control.audit.export')" variant="secondary">Export Audit</x-ui.button>
            </div>
            <x-ui.data-table
                id="control-audit-table"
                :headers="['Date', 'Actor', 'Event']"
                :rows="$auditLogs->map(fn ($log) => [
                    e($log->created_at?->toDateTimeString() ?? ''),
                    e($log->user?->email ?? 'system'),
                    e($log->event),
                ])->all()"
            />
        </x-ui.card>

        <x-ui.card title="System Health" description="Operational status widgets for queues, jobs, and Korapay activity.">
            <div class="space-y-3 text-sm">
                <div class="flex items-center justify-between rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-3">
                    <span>Last Payment</span>
                    <span class="font-semibold">{{ $health['last_payment']?->reference ?? 'No payments yet' }}</span>
                </div>
                <div class="flex items-center justify-between rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-3">
                    <span>Recent Webhooks</span>
                    <span class="font-semibold">{{ $health['recent_webhooks']->count() }}</span>
                </div>
                <div class="flex items-center justify-between rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-3">
                    <span>Scheduler</span>
                    <span class="font-semibold text-brand-700 dark:text-brand-200">Configured</span>
                </div>
            </div>
        </x-ui.card>
    </div>
</x-layouts.app-shell>
