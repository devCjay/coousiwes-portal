@php
    $metadata = $metadata ?? [];
    $selectedBank = $metadata['bank_name'] ?? '';
    $selectedBankRecord = collect($banks)->firstWhere('name', $selectedBank);
    $navigation = [
        ['label' => 'Profile Setup', 'href' => route('supervisor.profile.edit'), 'active' => true, 'icon' => 'wallet'],
    ];
@endphp

<x-layouts.app-shell title="Complete Supervisor Profile" role="Supervisor" :navigation="$navigation">
    <section data-student-profile class="overflow-hidden rounded-2xl border border-brand-600/15 bg-[var(--surface-raised)] shadow-[0_24px_70px_rgb(8_15_12_/_0.10)]">
        <div class="relative bg-brand-700 p-6 text-white sm:p-8">
            <div class="absolute inset-y-0 right-0 hidden w-1/2 bg-[linear-gradient(120deg,transparent_0%,rgba(255,255,255,.08)_36%,transparent_37%,transparent_55%,rgba(255,255,255,.10)_56%,transparent_78%)] md:block"></div>
            <div class="relative max-w-3xl">
                <span class="inline-flex items-center gap-2 rounded-full bg-white/12 px-3 py-1 text-xs font-bold ring-1 ring-white/15">
                    <x-ui.icon name="wallet" class="size-4" />
                    Required profile update
                </span>
                <h1 class="mt-4 text-2xl font-black tracking-normal sm:text-3xl">Update Your bank details</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-white/78">
                    Update Your Bank information before accessing assigned students, assessments, and dashboard tools.
                </p>
            </div>
        </div>

        <div class="p-5 sm:p-7">
            @if (session('status'))
                <x-ui.alert tone="{{ session('toast_tone', 'warning') }}" title="{{ session('toast_title', 'Profile update required') }}">
                    {{ session('status') }}
                </x-ui.alert>
            @endif

            <x-ui.card title="Bank Information" description="Select your bank and confirm your supervisor account details.">
                <form method="POST" action="{{ route('supervisor.profile.update') }}" data-profile-step-form data-ajax-reset="false" class="grid min-w-0 gap-5">
                    @csrf
                    <div class="grid min-w-0 gap-5 md:grid-cols-2">
                        <x-profile.search-select
                            label="Bank Name"
                            name="bank_name"
                            placeholder="Search Nigerian banks..."
                            :options="collect($banks)->map(fn ($bank) => ['value' => $bank['name'], 'label' => $bank['name'], 'meta' => 'Sort code '.$bank['sort_code'], 'sort_code' => $bank['sort_code']])->all()"
                            :value="$selectedBank"
                            data-profile-bank
                        />
                        <x-ui.input label="Account Number" name="account_number" value="{{ $metadata['account_number'] ?? '' }}" inputmode="numeric" maxlength="12" required />
                        <x-ui.input label="Account Name" name="account_name" value="{{ $metadata['account_name'] ?? '' }}" required />
                        <x-ui.input label="Sort Code" name="sort_code" value="{{ $metadata['sort_code'] ?? ($selectedBankRecord['sort_code'] ?? '') }}" required readonly data-profile-sort-code />
                    </div>

                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <x-ui.button type="submit" data-loading-text="Saving...">Save and Continue</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </section>
</x-layouts.app-shell>
