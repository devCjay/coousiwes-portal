<x-layouts.auth title="OTP Verification" :centered="true">
    <div class="mb-7 text-center">
        <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-brand-600 text-white shadow-[0_18px_40px_rgb(0_81_54_/_0.22)]">
            <x-ui.icon name="shield" class="size-6" />
        </span>
        <h1 class="mt-4 text-2xl font-black tracking-normal text-[var(--text-strong)]">Verify your sign in</h1>
        <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-[var(--text-soft)]">
            Enter the six-digit code sent to your email address. The code expires at {{ $expiresAt->format('H:i') }}.
        </p>
    </div>

    <form method="POST" action="{{ route('otp.verify') }}" class="space-y-5" data-otp-form>
        @csrf

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

        <input type="hidden" name="code" data-otp-code required>

        <div>
            <label class="mb-3 block text-center text-sm font-semibold text-[var(--text-strong)]">OTP Code</label>
            <div class="grid grid-cols-6 gap-2 sm:gap-3" data-otp-boxes>
                @for ($index = 0; $index < 6; $index++)
                    <input
                        type="text"
                        inputmode="numeric"
                        autocomplete="{{ $index === 0 ? 'one-time-code' : 'off' }}"
                        maxlength="1"
                        pattern="[0-9]"
                        aria-label="OTP digit {{ $index + 1 }}"
                        class="h-12 rounded-xl border border-[var(--line)] bg-[var(--surface-muted)] text-center text-xl font-black text-brand-700 shadow-sm outline-none theme-transition focus:border-brand-500 focus:bg-[var(--surface-raised)] focus:ring-4 focus:ring-brand-400/15 dark:text-brand-200 sm:h-14"
                        data-otp-digit
                    >
                @endfor
            </div>
        </div>

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

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('[data-otp-form]');
            const hidden = form?.querySelector('[data-otp-code]');
            const digits = Array.from(form?.querySelectorAll('[data-otp-digit]') ?? []);

            if (! form || ! hidden || digits.length === 0) {
                return;
            }

            const syncCode = () => {
                hidden.value = digits.map((input) => input.value).join('');
            };

            digits.forEach((input, index) => {
                input.addEventListener('input', () => {
                    input.value = input.value.replace(/\D/g, '').slice(-1);
                    syncCode();

                    if (input.value && digits[index + 1]) {
                        digits[index + 1].focus();
                    }
                });

                input.addEventListener('keydown', (event) => {
                    if (event.key === 'Backspace' && ! input.value && digits[index - 1]) {
                        digits[index - 1].focus();
                    }
                });

                input.addEventListener('paste', (event) => {
                    event.preventDefault();
                    const pasted = (event.clipboardData?.getData('text') ?? '').replace(/\D/g, '').slice(0, 6);

                    pasted.split('').forEach((digit, pasteIndex) => {
                        if (digits[pasteIndex]) {
                            digits[pasteIndex].value = digit;
                        }
                    });

                    syncCode();
                    digits[Math.min(pasted.length, digits.length) - 1]?.focus();
                });
            });

            form.addEventListener('submit', syncCode);
            digits[0]?.focus();
        });
    </script>
</x-layouts.auth>
