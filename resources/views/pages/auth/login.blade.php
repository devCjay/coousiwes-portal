@php
    $role = $role ?? 'Student';
    $routeMap = [
        'Admin' => route('login.store', 'admin', false),
        'Supervisor' => route('login.store', 'supervisor', false),
        'Student' => route('login.store', 'student', false),
    ];
@endphp

<x-layouts.auth :title="$role.' Login'" :role="$role">
    @if ($role !== 'Admin')
    <div class="mb-6 flex rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-1">
        <a href="{{ route('login.supervisor') }}" @class(['flex-1 rounded-md px-3 py-2 text-center text-sm font-semibold theme-transition', 'bg-[var(--surface-raised)] text-brand-700 shadow-sm dark:text-brand-200' => $role === 'Supervisor', 'text-[var(--text-soft)]' => $role !== 'Supervisor'])>Supervisor</a>
        <a href="{{ route('login.student') }}" @class(['flex-1 rounded-md px-3 py-2 text-center text-sm font-semibold theme-transition', 'bg-[var(--surface-raised)] text-brand-700 shadow-sm dark:text-brand-200' => $role === 'Student', 'text-[var(--text-soft)]' => $role !== 'Student'])>Student</a>
    </div>
    @endif

    <form method="POST" action="{{ $routeMap[$role] }}" class="space-y-5">
        @csrf

        @if ($errors->any())
            <x-ui.alert tone="danger" title="Sign in failed">
                {{ $errors->first() }}
            </x-ui.alert>
        @endif

        @if ($role === 'Student')
            <x-ui.input label="Reg No" name="matric_no" type="text" placeholder="2026/DEMO/001" required />
        @else
            <x-ui.input label="Email address" name="email" type="email" placeholder="{{ strtolower($role) }}@coousiwes.edu.ng" required />
        @endif
        <x-ui.input label="Password" name="password" type="password" placeholder="Enter password" required />

        <div class="flex items-center justify-between gap-4">
            <label class="inline-flex items-center gap-2 text-sm text-[var(--text-soft)]">
                <input name="remember" type="checkbox" class="size-4 rounded border-[var(--line)] text-brand-600">
                Remember device
            </label>
            <a href="#" class="text-sm font-semibold text-brand-700 dark:text-brand-300">Reset password</a>
        </div>

        <x-ui.button type="submit" class="w-full">Continue securely</x-ui.button>
    </form>
</x-layouts.auth>
