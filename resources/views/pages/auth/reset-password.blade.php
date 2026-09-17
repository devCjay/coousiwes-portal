<x-layouts.auth title="Create New Password" role="Student">
    <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        @if ($errors->any())
            <x-ui.alert tone="danger" title="Reset failed">
                {{ $errors->first() }}
            </x-ui.alert>
        @endif

        <x-ui.input label="Email address" name="email" type="email" value="{{ old('email', $email) }}" required />
        <x-ui.input label="New Password" name="password" type="password" required />
        <x-ui.input label="Confirm Password" name="password_confirmation" type="password" required />

        <x-ui.button type="submit" class="w-full">Reset Password</x-ui.button>
    </form>
</x-layouts.auth>
