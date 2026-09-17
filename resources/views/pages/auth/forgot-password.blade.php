@php
    $role = in_array($role ?? 'Student', ['Student', 'Supervisor'], true) ? $role : 'Student';
@endphp

<x-layouts.auth title="Reset Password" :role="$role">
    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        @if ($errors->any())
            <x-ui.alert tone="danger" title="Reset failed">
                {{ $errors->first() }}
            </x-ui.alert>
        @endif

        @if (session('status'))
            <x-ui.alert tone="{{ session('toast_tone', 'success') }}" title="{{ session('toast_title', 'Reset request') }}">
                {{ session('status') }}
            </x-ui.alert>
        @endif

        <p class="text-sm leading-6 text-[var(--text-soft)]">
            Enter your registered email address and a secure reset link will be sent to you.
        </p>

        <x-ui.input label="Email address" name="email" type="email" value="{{ old('email') }}" required />

        <x-ui.button type="submit" class="w-full">Send Reset Link</x-ui.button>

        <div class="text-center text-sm">
            <a href="{{ $role === 'Supervisor' ? route('login.supervisor') : route('login.student') }}" class="font-semibold text-brand-700 dark:text-brand-300">Back to login</a>
        </div>
    </form>
</x-layouts.auth>
