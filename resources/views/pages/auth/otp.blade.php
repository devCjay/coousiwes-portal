<x-layouts.auth title="OTP Verification">
    <form method="POST" action="{{ route('otp.verify') }}" class="space-y-5">
        @csrf

        <x-ui.alert tone="info" title="One-time password required">
            Enter the six-digit security code sent to your registered channel. The code expires at {{ $expiresAt->format('H:i') }}.
        </x-ui.alert>

        @if ($debugCode)
            <x-ui.alert tone="warning" title="Local development code">
                Use {{ $debugCode }} to complete this local OTP challenge.
            </x-ui.alert>
        @endif

        @if ($errors->any())
            <x-ui.alert tone="danger" title="Verification failed">
                {{ $errors->first() }}
            </x-ui.alert>
        @endif

        @if (session('status'))
            <x-ui.alert tone="success" title="OTP resent">
                {{ session('status') }}
            </x-ui.alert>
        @endif

        <x-ui.input label="OTP Code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="123456" required />

        <x-ui.button type="submit" class="w-full">Verify and continue</x-ui.button>
    </form>

    <form method="POST" action="{{ route('otp.resend') }}" class="mt-4">
        @csrf
        <x-ui.button type="submit" variant="secondary" class="w-full">Generate new OTP</x-ui.button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4">
        @csrf
        <x-ui.button type="submit" variant="ghost" class="w-full">Cancel sign in</x-ui.button>
    </form>
</x-layouts.auth>
