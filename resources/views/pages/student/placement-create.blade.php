@php
    $navigation = [
        ['label' => 'Dashboard', 'href' => route('student.dashboard'), 'icon' => 'D'],
        ['label' => 'Profile', 'href' => route('student.profile.show'), 'icon' => 'user-circle'],
        ['label' => 'Placement', 'href' => route('student.placements.create'), 'active' => true, 'icon' => 'briefcase'],
        ['label' => 'Payment', 'href' => route('student.payments.index'), 'icon' => 'K'],
        ['label' => 'Feedback', 'href' => route('student.feedback.index'), 'icon' => 'F'],
    ];
    $fields = collect([
        $placement?->academic_level_id,
        $placement?->academic_session_id,
        $placement?->siwes_year,
        $placement?->attachment_period,
        $placement?->company_name,
        $placement?->company_address,
        $placement?->company_state,
        $placement?->company_lga,
        $placement?->company_supervisor_phone,
    ]);
    $completion = (int) round(($fields->filter(fn ($value) => filled($value))->count() / $fields->count()) * 100);
    $currentYear = now()->year;
    $selectedLevel = $placement?->academic_level_id ? (string) $placement->academic_level_id : '';
    $selectedSession = $placement?->academic_session_id ? (string) $placement->academic_session_id : ($student->academic_session_id ? (string) $student->academic_session_id : '');
    $selectedState = $placement?->company_state ?? '';
    $steps = [
        ['title' => 'SIWES Information', 'icon' => 'briefcase', 'hint' => 'Level, session, year, and attachment period'],
        ['title' => 'Company Information', 'icon' => 'building', 'hint' => 'Organization and company supervisor contact'],
        ['title' => 'Milestone', 'icon' => 'check-check', 'hint' => 'Placement completion celebration'],
    ];
@endphp

<x-layouts.app-shell title="Add Placement" role="Student" :navigation="$navigation">
    <section data-profile-wizard class="w-full max-w-full min-w-0 rounded-2xl border border-brand-600/15 bg-[var(--surface-raised)] shadow-[0_24px_70px_rgb(8_15_12_/_0.10)]">
        <div class="relative isolate overflow-hidden rounded-t-2xl bg-brand-600 px-4 py-6 text-white sm:px-6 lg:px-8">
            <div class="absolute inset-0 -z-10 bg-[linear-gradient(128deg,transparent_0%,transparent_54%,rgba(255,255,255,0.09)_54%,rgba(255,255,255,0.09)_61%,transparent_61%,transparent_68%,rgba(255,255,255,0.08)_68%,rgba(255,255,255,0.08)_76%,transparent_76%)]"></div>
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl">
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/12 px-3 py-1 text-xs font-semibold ring-1 ring-white/15">
                        <x-ui.icon name="graduation-cap" class="size-4" />
                        {{ $student->matric_no }}
                    </span>
                    <h2 class="mt-4 text-2xl font-bold leading-tight sm:text-3xl">Add your SIWES placement</h2>
                    <p class="mt-2 text-sm leading-6 text-white/78 sm:text-base">Save each step as you go. Your company details are submitted after the ticket-protected flow is complete.</p>
                </div>
                <div class="rounded-2xl bg-white/12 p-4 ring-1 ring-white/15 lg:min-w-64">
                    <p class="text-xs font-semibold uppercase text-white/70">Placement progress</p>
                    <p class="mt-2 text-3xl font-bold"><span data-profile-percent>{{ $completion }}</span>%</p>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-white/18">
                        <div data-profile-progress class="h-full rounded-full bg-white transition-all duration-500" style="width: {{ $completion }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid min-w-0 gap-0 lg:grid-cols-[19rem_minmax(0,1fr)]">
            <aside class="min-w-0 overflow-hidden border-b border-[var(--line)] bg-[var(--surface-muted)] p-3 sm:p-4 lg:border-b-0 lg:border-r">
                <div class="flex w-full max-w-full min-w-0 gap-2 overflow-x-auto overscroll-x-contain pb-1 sm:gap-3 lg:block lg:space-y-3 lg:overflow-visible lg:pb-0">
                    @foreach ($steps as $index => $step)
                        <button
                            type="button"
                            data-profile-step-button="{{ $index }}"
                            @class([
                                'flex min-w-[11.75rem] shrink-0 items-center gap-3 rounded-xl border p-3 text-left theme-transition sm:min-w-56 lg:w-full lg:min-w-0',
                                'border-brand-600 bg-[var(--surface-raised)] shadow-[0_14px_34px_rgb(0_81_54_/_0.10)]' => $index === 0,
                                'border-[var(--line)] bg-[var(--surface-raised)]/60' => $index !== 0,
                            ])
                        >
                            <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-brand-600 text-white">
                                <x-ui.icon :name="$step['icon']" class="size-5" />
                            </span>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-[var(--text-strong)]">Step {{ $index + 1 }}</span>
                                <span class="block truncate text-xs font-medium text-[var(--text-soft)]">{{ $step['title'] }}</span>
                            </span>
                        </button>
                    @endforeach
                </div>
            </aside>

            <div class="w-full min-w-0 max-w-full p-2 sm:p-6 lg:p-8">
                <div data-profile-step-panel="0" class="profile-step-panel min-w-0">
                    <x-ui.card title="SIWES Information" description="This is the only place students can update their level for SIWES placement.">
                        <form method="POST" action="{{ route('student.placements.store-step') }}" data-profile-step-form data-step-index="0" data-ajax-reset="false" class="grid min-w-0 gap-5">
                            @csrf
                            <input type="hidden" name="step" value="siwes">
                            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                                <x-profile.search-select label="Student Level" name="academic_level_id" placeholder="Search levels..." :options="$levels->map(fn ($level) => ['value' => (string) $level->id, 'label' => $level->name, 'meta' => $level->level.' Level'])->all()" :value="$selectedLevel" />
                                <x-profile.search-select label="SIWES Year" name="siwes_year" placeholder="Select SIWES year..." :options="[['value' => (string) $currentYear, 'label' => (string) $currentYear], ['value' => (string) ($currentYear - 1), 'label' => (string) ($currentYear - 1)]]" :value="(string) ($placement?->siwes_year ?? $currentYear)" />
                                <x-profile.search-select label="SIWES Session" name="academic_session_id" placeholder="Search sessions..." :options="$sessions->map(fn ($session) => ['value' => (string) $session->id, 'label' => $session->name, 'meta' => $session->is_active ? 'Active session' : null])->all()" :value="$selectedSession" />
                                <x-profile.search-select label="Period of Attachment" name="attachment_period" placeholder="Select period..." :options="[['value' => 'April to October', 'label' => 'April to October'], ['value' => 'August to October', 'label' => 'August to October']]" :value="$placement?->attachment_period ?? ''" />
                            </div>
                            <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                                <x-ui.button type="submit" class="w-full sm:w-auto" data-loading-text="Saving...">Save and Continue</x-ui.button>
                            </div>
                        </form>
                    </x-ui.card>
                </div>

                <div data-profile-step-panel="1" class="profile-step-panel hidden min-w-0">
                    <x-ui.card title="Company Information" description="Add the organization and company-based supervisor contact.">
                        <form method="POST" action="{{ route('student.placements.store-step') }}" data-profile-step-form data-step-index="1" data-ajax-reset="false" class="grid min-w-0 gap-5">
                            @csrf
                            <input type="hidden" name="step" value="company">
                            <x-ui.input label="Company Name" name="company_name" value="{{ $placement?->company_name }}" required />
                            <x-ui.input label="Company Address" name="company_address" value="{{ $placement?->company_address }}" required />
                            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                                <x-profile.search-select label="State" name="company_state" placeholder="Search Nigerian states..." :options="collect($states)->map(fn ($state) => ['value' => $state['name'], 'label' => $state['name']])->all()" :value="$selectedState" data-profile-state />
                                <x-profile.search-select label="Local Government Area" name="company_lga" placeholder="Search local government areas..." :options="collect($stateRecord['lgas'] ?? [])->map(fn ($lga) => ['value' => $lga, 'label' => $lga])->all()" :value="$placement?->company_lga ?? ''" data-profile-lga />
                            </div>
                            <x-ui.input label="Company Based Supervisor Phone Number" name="company_supervisor_phone" value="{{ $placement?->company_supervisor_phone }}" required />
                            <div class="grid gap-3 sm:flex sm:flex-wrap sm:justify-between">
                                <x-ui.button type="button" variant="secondary" class="w-full sm:w-auto" data-profile-prev>Back</x-ui.button>
                                <x-ui.button type="submit" class="w-full sm:w-auto" data-loading-text="Completing...">Complete Placement</x-ui.button>
                            </div>
                        </form>
                    </x-ui.card>
                </div>

                <div data-profile-step-panel="2" class="profile-step-panel hidden min-w-0">
                    <x-ui.card title="Milestone Completion" description="Your final celebration opens automatically after company information is saved.">
                        <div class="rounded-2xl bg-brand-600 p-6 text-center text-white sm:p-8">
                            <span class="mx-auto grid size-16 place-items-center rounded-2xl bg-white/15 text-white ring-1 ring-white/20">
                                <x-ui.icon name="check-check" class="size-8" />
                            </span>
                            <h3 class="mt-5 text-2xl font-bold">Almost done</h3>
                            <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-white/78">Save your company information to finish your SIWES placement milestone.</p>
                        </div>
                    </x-ui.card>
                </div>
            </div>
        </div>
    </section>
</x-layouts.app-shell>
