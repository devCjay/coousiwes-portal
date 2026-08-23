@php
    $navigation = [
        ['label' => 'Dashboard', 'href' => $dashboard, 'icon' => 'D'],
        ['label' => 'Profile', 'href' => $user->student ? ($user->student->hasCompleteProfile() ? route('student.profile.show') : route('student.profile.edit')) : route('profile.show'), 'icon' => 'P'],
        ['label' => 'Change Password', 'href' => route('account.password.edit'), 'active' => true, 'icon' => 'key-round'],
        ['label' => 'Notifications', 'href' => route('notifications.index'), 'icon' => 'N'],
    ];
@endphp

<x-layouts.app-shell title="Change Password" :role="$role" :navigation="$navigation">
    <div class="mx-auto grid max-w-5xl gap-6 lg:grid-cols-[0.85fr_1.15fr]">
        <section class="overflow-hidden rounded-2xl border border-brand-600/15 bg-brand-700 text-white shadow-[0_24px_70px_rgb(0_81_54_/_0.18)]">
            <div class="relative p-6 sm:p-8">
                <div class="absolute inset-y-0 right-0 w-1/2 bg-[linear-gradient(120deg,transparent_0%,rgba(255,255,255,.08)_38%,transparent_39%,transparent_56%,rgba(255,255,255,.10)_57%,transparent_78%)]"></div>
                <div class="relative">
                    <span class="grid size-14 place-items-center rounded-2xl bg-white/12 text-white ring-1 ring-white/15">
                        <x-ui.icon name="key-round" class="size-7" />
                    </span>
                    <p class="mt-6 text-xs font-bold uppercase tracking-[0.18em] text-white/60">{{ $role }} Account</p>
                    <h1 class="mt-2 text-2xl font-black tracking-normal sm:text-3xl">Secure your portal access</h1>
                    <p class="mt-3 max-w-md text-sm leading-6 text-white/72">
                        Change your password regularly and avoid reusing your temporary login credentials.
                    </p>

                    <div class="mt-8 grid gap-3">
                        <div class="rounded-2xl border border-white/10 bg-white/10 p-4">
                            <p class="text-xs font-semibold uppercase text-white/55">Signed in as</p>
                            <p class="mt-1 font-bold">{{ $user->name }}</p>
                            <p class="mt-1 text-sm text-white/70">{{ $user->email }}</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/10 p-4">
                            <p class="text-sm font-semibold">Password policy</p>
                            <p class="mt-1 text-xs leading-5 text-white/66">Use at least 8 characters. A stronger password should mix letters, numbers, and symbols.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <x-ui.card title="Change Password" description="Enter your current password, then choose a new password for future logins.">
            <form method="POST" action="{{ route('account.password.update') }}" class="grid gap-5" data-ajax-reset="false">
                @csrf
                @method('PUT')

                <x-ui.input
                    label="Current Password"
                    name="current_password"
                    type="password"
                    autocomplete="current-password"
                    required
                />

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-ui.input
                        label="New Password"
                        name="password"
                        type="password"
                        autocomplete="new-password"
                        required
                    />
                    <x-ui.input
                        label="Confirm New Password"
                        name="password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        required
                    />
                </div>

                <div class="rounded-2xl border border-brand-600/15 bg-brand-600/5 p-4">
                    <div class="flex gap-3">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-600 text-white">
                            <x-ui.icon name="shield" class="size-5" />
                        </span>
                        <div>
                            <p class="text-sm font-bold text-[var(--text-strong)]">After saving</p>
                            <p class="mt-1 text-sm leading-6 text-[var(--text-soft)]">Your new password becomes active immediately. Keep it private and sign out on shared devices.</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <x-ui.button :href="$dashboard" variant="secondary">Cancel</x-ui.button>
                    <x-ui.button type="submit" data-loading-text="Changing...">Change Password</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-layouts.app-shell>
