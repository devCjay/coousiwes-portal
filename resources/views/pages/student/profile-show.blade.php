@php
    $metadata = $student->metadata ?? [];
    $initials = collect(explode(' ', trim($student->user->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
        ->join('') ?: 'ST';
    $selectedState = $metadata['state'] ?? '';
    $stateRecord = collect($states)->firstWhere('name', $selectedState);
    $selectedBank = $metadata['bank_name'] ?? '';
    $selectedBankRecord = collect($banks)->firstWhere('name', $selectedBank);
    $selectedFaculty = $student->faculty_id ? (string) $student->faculty_id : '';
    $selectedDepartment = $student->department_id ? (string) $student->department_id : '';
    $navigation = [
        ['label' => 'Dashboard', 'href' => route('student.dashboard'), 'icon' => 'D'],
        ['label' => 'Profile', 'href' => route('student.profile.show'), 'active' => true, 'icon' => 'user-circle'],
        ['label' => 'Placement', 'href' => route('student.placements.ticket'), 'icon' => 'briefcase'],
        ['label' => 'Payment', 'href' => route('student.payments.index'), 'icon' => 'K'],
        ['label' => 'Feedback', 'href' => route('student.feedback.index'), 'icon' => 'F'],
    ];
@endphp

<x-layouts.app-shell title="Student Profile" role="Student" :navigation="$navigation">
    <section data-student-profile class="overflow-hidden rounded-2xl border border-brand-600/15 bg-[var(--surface-raised)] shadow-[0_24px_70px_rgb(8_15_12_/_0.10)]">
        <div class="relative isolate min-h-40 bg-brand-600 px-5 py-8 text-white sm:px-8">
            <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_20%_15%,rgba(255,255,255,0.20),transparent_16rem),linear-gradient(128deg,transparent_0%,transparent_54%,rgba(255,255,255,0.09)_54%,rgba(255,255,255,0.09)_61%,transparent_61%,transparent_70%,rgba(255,255,255,0.08)_70%,rgba(255,255,255,0.08)_78%,transparent_78%)]"></div>
            <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex min-w-0 items-end gap-4">
                    <div class="grid size-24 shrink-0 place-items-center rounded-3xl border-4 border-white/35 bg-white text-3xl font-black text-brand-600 shadow-[0_22px_55px_rgb(0_0_0_/_0.18)]">
                        {{ $initials }}
                    </div>
                    <div class="min-w-0 pb-1">
                        <p class="inline-flex items-center gap-2 rounded-full bg-white/12 px-3 py-1 text-xs font-semibold ring-1 ring-white/15">
                            <x-ui.icon name="graduation-cap" class="size-4" />
                            {{ $student->matric_no }}
                        </p>
                        <h2 class="mt-3 truncate text-2xl font-bold sm:text-3xl">{{ $student->user->name }}</h2>
                        <p class="mt-1 text-sm text-white/78">{{ $student->department?->name ?? 'N/A' }} - {{ $student->placement?->academicLevel?->name ?? 'N/A' }}</p>
                    </div>
                </div>
                <div class="rounded-2xl bg-white/12 p-4 ring-1 ring-white/15 sm:min-w-56">
                    <p class="text-xs font-semibold uppercase text-white/70">Profile completion</p>
                    <p class="mt-2 text-3xl font-bold">{{ $student->profileCompletionPercent() }}%</p>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-white/18">
                        <div class="h-full rounded-full bg-white" style="width: {{ $student->profileCompletionPercent() }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="border-b border-[var(--line)] bg-[var(--surface-muted)] p-4 lg:hidden">
            <div class="grid gap-3">
                @foreach ([
                    ['id' => 'overview', 'label' => 'Overview', 'icon' => 'layout-dashboard'],
                    ['id' => 'personal', 'label' => 'Personal', 'icon' => 'user-circle'],
                    ['id' => 'contact', 'label' => 'Contact', 'icon' => 'home'],
                    ['id' => 'academic', 'label' => 'Academic', 'icon' => 'graduation-cap'],
                    ['id' => 'bank', 'label' => 'Bank', 'icon' => 'wallet'],
                ] as $index => $tab)
                    <button
                        type="button"
                        data-modal-target="#mobile-profile-{{ $tab['id'] }}"
                        @class([
                            'flex min-h-16 w-full items-center gap-4 rounded-xl border bg-[var(--surface-raised)] p-3 text-left shadow-[0_10px_26px_rgb(8_15_12_/_0.04)] theme-transition hover:border-brand-600',
                            'border-brand-600' => $index === 0,
                            'border-[var(--line)]' => $index !== 0,
                        ])
                    >
                        <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-brand-600 text-white">
                            <x-ui.icon :name="$tab['icon']" class="size-5" />
                        </span>
                        <span class="text-sm font-semibold text-[var(--text-strong)]">{{ $tab['label'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        <div class="hidden min-w-0 gap-0 lg:grid lg:grid-cols-[18rem_minmax(0,1fr)]">
            <aside class="min-w-0 border-b border-[var(--line)] bg-[var(--surface-muted)] p-3 sm:p-4 lg:border-b-0 lg:border-r">
                <div class="flex max-w-full gap-2 overflow-x-auto pb-1 lg:block lg:space-y-2 lg:overflow-visible lg:pb-0">
                    @foreach ([
                        ['id' => 'overview', 'label' => 'Overview', 'icon' => 'layout-dashboard'],
                        ['id' => 'personal', 'label' => 'Personal', 'icon' => 'user-circle'],
                        ['id' => 'contact', 'label' => 'Contact', 'icon' => 'home'],
                        ['id' => 'academic', 'label' => 'Academic', 'icon' => 'graduation-cap'],
                        ['id' => 'bank', 'label' => 'Bank', 'icon' => 'wallet'],
                    ] as $index => $tab)
                        <button
                            type="button"
                            data-profile-page-tab-target="#profile-tab-{{ $tab['id'] }}"
                            @class([
                                'flex min-w-44 shrink-0 items-center gap-3 rounded-xl border p-3 text-left theme-transition lg:w-full lg:min-w-0',
                                'is-active border-brand-600 bg-[var(--surface-raised)] shadow-[0_14px_34px_rgb(0_81_54_/_0.10)]' => $index === 0,
                                'border-[var(--line)] bg-[var(--surface-raised)]/60' => $index !== 0,
                            ])
                        >
                            <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-brand-600 text-white">
                                <x-ui.icon :name="$tab['icon']" class="size-4" />
                            </span>
                            <span class="text-sm font-semibold text-[var(--text-strong)]">{{ $tab['label'] }}</span>
                        </button>
                    @endforeach
                </div>
            </aside>

            <div class="min-w-0 p-3 sm:p-6">
                <div id="profile-tab-overview" data-profile-page-panel>
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <x-profile.info-card icon="user-circle" label="Full Name" :value="$student->user->name" meta="Locked by admin" locked />
                        <x-profile.info-card icon="graduation-cap" label="Matric Number" :value="$student->matric_no" meta="Locked by admin" locked />
                        <x-profile.info-card icon="credit-card" label="Email" :value="$student->user->email ?: 'N/A'" meta="Student editable" />
                        <x-profile.info-card icon="home" label="Location" :value="($metadata['state'] ?? 'Not set').' / '.($metadata['lga'] ?? 'Not set')" meta="Contact details" />
                        <x-profile.info-card icon="building" label="Faculty" :value="$student->faculty?->name ?? 'N/A'" meta="Student editable" />
                        <x-profile.info-card icon="wallet" label="Bank" :value="$metadata['bank_name'] ?? 'Not set'" meta="Student editable" />
                    </div>
                </div>

                <div id="profile-tab-personal" data-profile-page-panel class="hidden">
                    <x-ui.card title="Personal Information" description="Name and matric number are managed by the SIWES admin team.">
                        <x-profile.locked-note />
                        <dl class="mt-5 grid gap-3 sm:grid-cols-2">
                            <x-profile.detail label="Full Name" :value="$student->user->name" />
                            <x-profile.detail label="Matric Number" :value="$student->matric_no" />
                        </dl>
                        <form id="personal-edit" method="POST" action="{{ route('student.profile.step') }}" data-profile-step-form data-ajax-reset="false" class="mt-5 grid min-w-0 gap-5">
                            @csrf
                            <input type="hidden" name="step" value="basic">
                            <input type="hidden" name="source" value="profile">
                            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                                <x-ui.input label="Email Address" name="email" type="email" value="{{ $student->user->email }}" required />
                                <x-ui.input label="Phone Number" name="phone" value="{{ $student->user->phone }}" required />
                                <x-profile.search-select label="Gender" name="gender" placeholder="Select gender..." :options="[['value' => 'Male', 'label' => 'Male'], ['value' => 'Female', 'label' => 'Female']]" :value="$student->gender" data-profile-gender />
                                <x-ui.input label="Date of Birth" name="date_of_birth" type="date" value="{{ $student->date_of_birth?->toDateString() }}" required />
                                <x-profile.search-select label="Nationality" name="nationality" placeholder="Search nationalities..." :options="collect($nationalities)->map(fn ($nationality) => ['value' => $nationality['label'], 'label' => $nationality['label']])->sortBy('label')->values()->all()" :value="$metadata['nationality'] ?? 'Nigerian'" data-profile-nationality />
                            </div>
                            <x-ui.button type="submit" class="w-full sm:w-auto" data-loading-text="Saving...">Save Personal Details</x-ui.button>
                        </form>
                    </x-ui.card>
                </div>

                <div id="profile-tab-contact" data-profile-page-panel class="hidden">
                    <x-ui.card title="Contact Information" description="Keep your address and residential location current.">
                        <dl class="grid gap-3 sm:grid-cols-3">
                            <x-profile.detail label="Address" :value="$student->address ?: 'Not set'" />
                            <x-profile.detail label="State" :value="$metadata['state'] ?? 'Not set'" />
                            <x-profile.detail label="LGA" :value="$metadata['lga'] ?? 'Not set'" />
                        </dl>
                        <form id="contact-edit" method="POST" action="{{ route('student.profile.step') }}" data-profile-step-form data-ajax-reset="false" class="mt-5 grid min-w-0 gap-5">
                            @csrf
                            <input type="hidden" name="step" value="contact">
                            <input type="hidden" name="source" value="profile">
                            <x-ui.input label="Address" name="address" value="{{ $student->address }}" required />
                            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                                <x-profile.search-select label="State" name="state" placeholder="Search Nigerian states..." :options="collect($states)->map(fn ($state) => ['value' => $state['name'], 'label' => $state['name']])->all()" :value="$selectedState" data-profile-state />
                                <x-profile.search-select label="Local Government Area" name="lga" placeholder="Search local government areas..." :options="collect($stateRecord['lgas'] ?? [])->map(fn ($lga) => ['value' => $lga, 'label' => $lga])->all()" :value="$metadata['lga'] ?? ''" data-profile-lga />
                            </div>
                            <x-ui.button type="submit" class="w-full sm:w-auto" data-loading-text="Saving...">Save Contact</x-ui.button>
                        </form>
                    </x-ui.card>
                </div>

                <div id="profile-tab-academic" data-profile-page-panel class="hidden">
                    <x-ui.card title="Academic Information" description="Update the faculty and department details you submitted during profile setup.">
                        <dl class="grid gap-3 sm:grid-cols-2">
                            <x-profile.detail label="Level" :value="$student->placement?->academicLevel?->name ?? 'N/A'" />
                            <x-profile.detail label="Academic Session" :value="$student->academicSession?->name ?? 'N/A'" />
                            <x-profile.detail label="Activation Status" :value="ucfirst($student->activation_status)" />
                        </dl>
                        <form id="academic-edit" method="POST" action="{{ route('student.profile.step') }}" data-profile-step-form data-ajax-reset="false" class="mt-5 grid min-w-0 gap-5">
                            @csrf
                            <input type="hidden" name="step" value="academic">
                            <input type="hidden" name="source" value="profile">
                            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                                <x-profile.search-select label="Faculty" name="faculty_id" placeholder="Search faculties..." :options="$faculties->map(fn ($faculty) => ['value' => (string) $faculty->id, 'label' => $faculty->name, 'meta' => $faculty->code])->all()" :value="$selectedFaculty" data-profile-faculty />
                                <x-profile.search-select label="Department" name="department_id" placeholder="Search departments..." :options="$departments->where('faculty_id', $student->faculty_id)->map(fn ($department) => ['value' => (string) $department->id, 'label' => $department->name, 'meta' => $department->code])->values()->all()" :value="$selectedDepartment" data-profile-department />
                            </div>
                            <x-ui.button type="submit" class="w-full sm:w-auto" data-loading-text="Saving...">Save Academic</x-ui.button>
                        </form>
                    </x-ui.card>
                </div>

                <div id="profile-tab-bank" data-profile-page-panel class="hidden">
                    <x-ui.card title="Bank Information" description="Bank details are used for verified student payment workflows.">
                        <dl class="grid gap-3 sm:grid-cols-3">
                            <x-profile.detail label="Bank Name" :value="$metadata['bank_name'] ?? 'Not set'" />
                            <x-profile.detail label="Account Number" :value="$metadata['account_number'] ?? 'Not set'" />
                            <x-profile.detail label="Sort Code" :value="$metadata['sort_code'] ?? 'Not set'" />
                        </dl>
                        <form id="bank-edit" method="POST" action="{{ route('student.profile.step') }}" data-profile-step-form data-ajax-reset="false" class="mt-5 grid min-w-0 gap-5">
                            @csrf
                            <input type="hidden" name="step" value="bank">
                            <input type="hidden" name="source" value="profile">
                            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                                <x-profile.search-select label="Bank Name" name="bank_name" placeholder="Search Nigerian banks..." :options="collect($banks)->map(fn ($bank) => ['value' => $bank['name'], 'label' => $bank['name'], 'meta' => 'Sort code '.$bank['sort_code'], 'sort_code' => $bank['sort_code']])->all()" :value="$selectedBank" data-profile-bank />
                                <x-ui.input label="Account Number" name="account_number" value="{{ $metadata['account_number'] ?? '' }}" inputmode="numeric" maxlength="10" required />
                                <x-ui.input label="Sort Code" name="sort_code" value="{{ $metadata['sort_code'] ?? ($selectedBankRecord['sort_code'] ?? '') }}" required readonly data-profile-sort-code />
                            </div>
                            <x-ui.button type="submit" class="w-full sm:w-auto" data-loading-text="Saving...">Save Bank</x-ui.button>
                        </form>
                    </x-ui.card>
                </div>
            </div>
        </div>

        <x-ui.modal id="mobile-profile-overview" title="Profile Overview" class="w-[min(42rem,calc(100vw-1rem))]">
            <div class="grid gap-4">
                <x-profile.info-card icon="user-circle" label="Full Name" :value="$student->user->name" meta="Locked by admin" locked />
                <x-profile.info-card icon="graduation-cap" label="Matric Number" :value="$student->matric_no" meta="Locked by admin" locked />
                <x-profile.info-card icon="credit-card" label="Email" :value="$student->user->email ?: 'N/A'" meta="Student editable" />
                <x-profile.info-card icon="home" label="Location" :value="($metadata['state'] ?? 'Not set').' / '.($metadata['lga'] ?? 'Not set')" meta="Contact details" />
                <x-profile.info-card icon="building" label="Faculty" :value="$student->faculty?->name ?? 'N/A'" meta="Student editable" />
                <x-profile.info-card icon="wallet" label="Bank" :value="$metadata['bank_name'] ?? 'Not set'" meta="Student editable" />
            </div>
        </x-ui.modal>

        <x-ui.modal id="mobile-profile-personal" title="Personal Information" class="w-[min(42rem,calc(100vw-1rem))]">
            <x-profile.locked-note />
            <dl class="mt-5 grid gap-3">
                <x-profile.detail label="Full Name" :value="$student->user->name" />
                <x-profile.detail label="Matric Number" :value="$student->matric_no" />
            </dl>
            <form method="POST" action="{{ route('student.profile.step') }}" data-profile-step-form data-ajax-reset="false" class="mt-5 grid min-w-0 gap-5">
                @csrf
                <input type="hidden" name="step" value="basic">
                <input type="hidden" name="source" value="profile">
                <x-ui.input label="Email Address" name="email" type="email" value="{{ $student->user->email }}" required />
                <x-ui.input label="Phone Number" name="phone" value="{{ $student->user->phone }}" required />
                <x-profile.search-select label="Gender" name="gender" placeholder="Select gender..." :options="[['value' => 'Male', 'label' => 'Male'], ['value' => 'Female', 'label' => 'Female']]" :value="$student->gender" data-profile-gender />
                <x-ui.input label="Date of Birth" name="date_of_birth" type="date" value="{{ $student->date_of_birth?->toDateString() }}" required />
                <x-profile.search-select label="Nationality" name="nationality" placeholder="Search nationalities..." :options="collect($nationalities)->map(fn ($nationality) => ['value' => $nationality['label'], 'label' => $nationality['label']])->sortBy('label')->values()->all()" :value="$metadata['nationality'] ?? 'Nigerian'" data-profile-nationality />
                <x-ui.button type="submit" class="w-full" data-loading-text="Saving...">Save Personal Details</x-ui.button>
            </form>
        </x-ui.modal>

        <x-ui.modal id="mobile-profile-contact" title="Contact Information" class="w-[min(42rem,calc(100vw-1rem))]">
            <form method="POST" action="{{ route('student.profile.step') }}" data-profile-step-form data-ajax-reset="false" class="grid min-w-0 gap-5">
                @csrf
                <input type="hidden" name="step" value="contact">
                <input type="hidden" name="source" value="profile">
                <x-ui.input label="Address" name="address" value="{{ $student->address }}" required />
                <x-profile.search-select label="State" name="state" placeholder="Search Nigerian states..." :options="collect($states)->map(fn ($state) => ['value' => $state['name'], 'label' => $state['name']])->all()" :value="$selectedState" data-profile-state />
                <x-profile.search-select label="Local Government Area" name="lga" placeholder="Search local government areas..." :options="collect($stateRecord['lgas'] ?? [])->map(fn ($lga) => ['value' => $lga, 'label' => $lga])->all()" :value="$metadata['lga'] ?? ''" data-profile-lga />
                <x-ui.button type="submit" class="w-full" data-loading-text="Saving...">Save Contact</x-ui.button>
            </form>
        </x-ui.modal>

        <x-ui.modal id="mobile-profile-academic" title="Academic Information" class="w-[min(42rem,calc(100vw-1rem))]">
            <dl class="grid gap-3">
                <x-profile.detail label="Level" :value="$student->placement?->academicLevel?->name ?? 'N/A'" />
                <x-profile.detail label="Academic Session" :value="$student->academicSession?->name ?? 'N/A'" />
                <x-profile.detail label="Activation Status" :value="ucfirst($student->activation_status)" />
            </dl>
            <form method="POST" action="{{ route('student.profile.step') }}" data-profile-step-form data-ajax-reset="false" class="mt-5 grid min-w-0 gap-5">
                @csrf
                <input type="hidden" name="step" value="academic">
                <input type="hidden" name="source" value="profile">
                <x-profile.search-select label="Faculty" name="faculty_id" placeholder="Search faculties..." :options="$faculties->map(fn ($faculty) => ['value' => (string) $faculty->id, 'label' => $faculty->name, 'meta' => $faculty->code])->all()" :value="$selectedFaculty" data-profile-faculty />
                <x-profile.search-select label="Department" name="department_id" placeholder="Search departments..." :options="$departments->where('faculty_id', $student->faculty_id)->map(fn ($department) => ['value' => (string) $department->id, 'label' => $department->name, 'meta' => $department->code])->values()->all()" :value="$selectedDepartment" data-profile-department />
                <x-ui.button type="submit" class="w-full" data-loading-text="Saving...">Save Academic</x-ui.button>
            </form>
        </x-ui.modal>

        <x-ui.modal id="mobile-profile-bank" title="Bank Information" class="w-[min(42rem,calc(100vw-1rem))]">
            <form method="POST" action="{{ route('student.profile.step') }}" data-profile-step-form data-ajax-reset="false" class="grid min-w-0 gap-5">
                @csrf
                <input type="hidden" name="step" value="bank">
                <input type="hidden" name="source" value="profile">
                <x-profile.search-select label="Bank Name" name="bank_name" placeholder="Search Nigerian banks..." :options="collect($banks)->map(fn ($bank) => ['value' => $bank['name'], 'label' => $bank['name'], 'meta' => 'Sort code '.$bank['sort_code'], 'sort_code' => $bank['sort_code']])->all()" :value="$selectedBank" data-profile-bank />
                <x-ui.input label="Account Number" name="account_number" value="{{ $metadata['account_number'] ?? '' }}" inputmode="numeric" maxlength="10" required />
                <x-ui.input label="Sort Code" name="sort_code" value="{{ $metadata['sort_code'] ?? ($selectedBankRecord['sort_code'] ?? '') }}" required readonly data-profile-sort-code />
                <x-ui.button type="submit" class="w-full" data-loading-text="Saving...">Save Bank</x-ui.button>
            </form>
        </x-ui.modal>
    </section>
</x-layouts.app-shell>
