@php
    $metadata = $student->metadata ?? [];
    $completion = $student->profileCompletionPercent();
    $selectedState = $metadata['state'] ?? '';
    $selectedBank = $metadata['bank_name'] ?? '';
    $stateRecord = collect($states)->firstWhere('name', $selectedState);
    $selectedBankRecord = collect($banks)->firstWhere('name', $selectedBank);
    $selectedFaculty = $student->faculty_id ? (string) $student->faculty_id : '';
    $selectedDepartment = $student->department_id ? (string) $student->department_id : '';
    $selectedSession = $student->academic_session_id ? (string) $student->academic_session_id : ($activeSession?->id ? (string) $activeSession->id : '');
    $profilePhotoUrl = $student->user->profilePhotoUrl();
    $navigation = [
        ['label' => 'Dashboard', 'href' => route('student.dashboard'), 'icon' => 'D'],
        ['label' => 'Profile Setup', 'href' => route('student.profile.edit'), 'active' => true, 'icon' => 'user-check'],
        ['label' => 'My Ticket', 'href' => route('student.tickets.index'), 'icon' => 'ticket'],
        ['label' => 'Placement', 'href' => route('student.placements.ticket'), 'icon' => 'briefcase'],
        ['label' => 'Payment', 'href' => route('student.payments.index'), 'icon' => 'K'],
        ['label' => 'Feedback', 'href' => route('student.feedback.index'), 'icon' => 'F'],
    ];
    $steps = [
        ['title' => 'Basic Information', 'icon' => 'user-circle', 'hint' => 'Identity and personal details'],
        ['title' => 'Contact Information', 'icon' => 'home', 'hint' => 'Where the SIWES office can reach you'],
        ['title' => 'Academic Information', 'icon' => 'graduation-cap', 'hint' => 'Faculty and department mapping'],
        ['title' => 'Bank Information', 'icon' => 'wallet', 'hint' => 'Verified payment and refund details'],
        ['title' => 'Milestone', 'icon' => 'check-check', 'hint' => 'Profile completion celebration'],
    ];
@endphp

<x-layouts.app-shell title="Complete Student Profile" role="Student" :navigation="$navigation">
    <section data-profile-wizard class="w-full max-w-full min-w-0 rounded-2xl border border-brand-600/15 bg-[var(--surface-raised)] shadow-[0_24px_70px_rgb(8_15_12_/_0.10)]">
        <div class="relative isolate overflow-hidden rounded-t-2xl bg-brand-600 px-4 py-6 text-white sm:px-6 lg:px-8">
            <div class="absolute inset-0 -z-10 bg-[linear-gradient(128deg,transparent_0%,transparent_54%,rgba(255,255,255,0.09)_54%,rgba(255,255,255,0.09)_61%,transparent_61%,transparent_68%,rgba(255,255,255,0.08)_68%,rgba(255,255,255,0.08)_76%,transparent_76%)]"></div>
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl">
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/12 px-3 py-1 text-xs font-semibold ring-1 ring-white/15">
                        <x-ui.icon name="graduation-cap" class="size-4" />
                        {{ $student->matric_no }}
                    </span>
                    <h2 class="mt-4 text-2xl font-bold leading-tight sm:text-3xl">Complete your student profile</h2>
                    <p class="mt-2 text-sm leading-6 text-white/78 sm:text-base">Save each step as you go. Your dashboard unlocks automatically when every required milestone is complete.</p>
                </div>
                <div class="rounded-2xl bg-white/12 p-4 ring-1 ring-white/15 lg:min-w-64">
                    <p class="text-xs font-semibold uppercase text-white/70">Overall progress</p>
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
                    <x-ui.card title="Basic Information" description="Start with your identity and direct contact details.">
                        <form id="profile-step-basic" method="POST" action="{{ route('student.profile.step') }}" enctype="multipart/form-data" data-profile-step-form data-step-index="0" data-ajax-reset="false" class="grid min-w-0 gap-5">
                            @csrf
                            <input type="hidden" name="step" value="basic">
                            <div class="flex flex-col gap-4 rounded-2xl border border-brand-600/15 bg-[var(--surface-muted)] p-4 sm:flex-row sm:items-center">
                                <div class="grid size-24 shrink-0 place-items-center overflow-hidden rounded-2xl border-4 border-white bg-brand-600 text-2xl font-black text-white shadow-[0_18px_42px_rgb(0_81_54_/_0.18)]" data-profile-photo-preview>
                                    @if ($profilePhotoUrl)
                                        <img src="{{ $profilePhotoUrl }}" alt="{{ $student->user->name }} profile photo" class="h-full w-full object-cover" data-profile-photo-image>
                                    @else
                                        <x-ui.icon name="user-circle" class="size-10" />
                                    @endif
                                </div>
                                <label class="block min-w-0 flex-1">
                                    <span class="siwes-form-label">Profile Picture</span>
                                    <input
                                        type="file"
                                        name="profile_photo"
                                        accept="image/png,image/jpeg,image/webp"
                                        capture="environment"
                                        data-profile-photo-input
                                    >
                                    <span class="mt-2 block text-xs leading-5 text-[var(--text-soft)]">Upload a clear JPG, PNG, or WEBP image. Maximum size is 2MB.</span>
                                </label>
                            </div>
                            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                                <x-ui.input label="Email Address" name="email" type="email" value="{{ $student->user->email }}" required />
                                <x-ui.input label="Phone Number" name="phone" value="{{ $student->user->phone }}" required />
                                <x-profile.search-select
                                    label="Gender"
                                    name="gender"
                                    placeholder="Select gender..."
                                    :options="[
                                        ['value' => 'Male', 'label' => 'Male'],
                                        ['value' => 'Female', 'label' => 'Female'],
                                    ]"
                                    :value="$student->gender"
                                    data-profile-gender
                                />
                                <x-ui.input label="Date of Birth" name="date_of_birth" type="date" value="{{ $student->date_of_birth?->toDateString() }}" required />
                                <x-profile.search-select
                                    label="Nationality"
                                    name="nationality"
                                    placeholder="Search nationalities..."
                                    :options="collect($nationalities)->map(fn ($nationality) => ['value' => $nationality['label'], 'label' => $nationality['label']])->sortBy('label')->values()->all()"
                                    :value="$metadata['nationality'] ?? 'Nigerian'"
                                    data-profile-nationality
                                />
                            </div>
                            <div class="mt-1 grid gap-3 sm:flex sm:flex-wrap sm:items-center sm:justify-between" data-profile-wizard-actions>
                                <x-ui.button type="button" variant="secondary" class="hidden w-full sm:w-auto" data-profile-prev data-profile-wizard-prev hidden>Back</x-ui.button>
                                <button
                                    type="submit"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-bold text-white shadow-[0_16px_34px_rgb(0_81_54_/_0.22)] theme-transition hover:-translate-y-0.5 hover:bg-brand-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-brand-400 dark:text-graphite-950 dark:hover:bg-brand-300 sm:w-auto"
                                    data-loading-text="Saving..."
                                    data-profile-wizard-submit
                                >
                                    <x-ui.icon name="save" class="size-4 shrink-0" />
                                    <span data-profile-wizard-submit-label>Save and Continue</span>
                                </button>
                            </div>
                        </form>
                    </x-ui.card>
                </div>

                <div data-profile-step-panel="1" class="profile-step-panel hidden min-w-0" hidden>
                    <x-ui.card title="Contact Information" description="Choose your current state and local government area with live filtering.">
                        <form id="profile-step-contact" method="POST" action="{{ route('student.profile.step') }}" data-profile-step-form data-step-index="1" data-ajax-reset="false" class="grid min-w-0 gap-5">
                            @csrf
                            <input type="hidden" name="step" value="contact">
                            <x-ui.input label="Address" name="address" value="{{ $student->address }}" required />
                            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                                <x-profile.search-select
                                    label="State"
                                    name="state"
                                    placeholder="Search Nigerian states..."
                                    :options="collect($states)->map(fn ($state) => ['value' => $state['name'], 'label' => $state['name']])->all()"
                                    :value="$selectedState"
                                    data-profile-state
                                />
                                <x-profile.search-select
                                    label="Local Government Area"
                                    name="lga"
                                    placeholder="Search local government areas..."
                                    :options="collect($stateRecord['lgas'] ?? [])->map(fn ($lga) => ['value' => $lga, 'label' => $lga])->all()"
                                    :value="$metadata['lga'] ?? ''"
                                    data-profile-lga
                                />
                            </div>
                        </form>
                    </x-ui.card>
                </div>

                <div data-profile-step-panel="2" class="profile-step-panel hidden min-w-0" hidden>
                    <x-ui.card title="Academic Information" description="Select the faculty and department linked to your SIWES record.">
                        <form id="profile-step-academic" method="POST" action="{{ route('student.profile.step') }}" data-profile-step-form data-step-index="2" data-ajax-reset="false" class="grid min-w-0 gap-5">
                            @csrf
                            <input type="hidden" name="step" value="academic">
                            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                                <x-profile.search-select
                                    label="Faculty"
                                    name="faculty_id"
                                    placeholder="Search faculties..."
                                    :options="$faculties->map(fn ($faculty) => ['value' => (string) $faculty->id, 'label' => $faculty->name, 'meta' => $faculty->code])->all()"
                                    :value="$selectedFaculty"
                                    data-profile-faculty
                                />
                                <x-profile.search-select
                                    label="Department"
                                    name="department_id"
                                    placeholder="Search departments..."
                                    :options="$departments->where('faculty_id', $student->faculty_id)->map(fn ($department) => ['value' => (string) $department->id, 'label' => $department->name, 'meta' => $department->code])->values()->all()"
                                    :value="$selectedDepartment"
                                    data-profile-department
                                />
                            </div>
                            <x-profile.search-select
                                label="Academic Session"
                                name="academic_session_id"
                                placeholder="Search sessions..."
                                :options="$sessions->map(fn ($session) => ['value' => (string) $session->id, 'label' => $session->name, 'meta' => $session->is_active ? 'Current session' : null])->all()"
                                :value="$selectedSession"
                            />
                        </form>
                    </x-ui.card>
                </div>

                <div data-profile-step-panel="3" class="profile-step-panel hidden min-w-0" hidden>
                    <x-ui.card title="Bank Information" description="Select your bank and confirm your student account details.">
                        <form id="profile-step-bank" method="POST" action="{{ route('student.profile.step') }}" data-profile-step-form data-step-index="3" data-ajax-reset="false" class="grid min-w-0 gap-5">
                            @csrf
                            <input type="hidden" name="step" value="bank">
                            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                                <x-profile.search-select
                                    label="Bank Name"
                                    name="bank_name"
                                    placeholder="Search Nigerian banks..."
                                    :options="collect($banks)->map(fn ($bank) => ['value' => $bank['name'], 'label' => $bank['name'], 'meta' => 'Sort code '.$bank['sort_code'], 'sort_code' => $bank['sort_code']])->all()"
                                    :value="$selectedBank"
                                    data-profile-bank
                                />
                                <x-ui.input label="Account Number" name="account_number" value="{{ $metadata['account_number'] ?? '' }}" inputmode="numeric" maxlength="10" required />
                                <x-ui.input label="Sort Code" name="sort_code" value="{{ $metadata['sort_code'] ?? ($selectedBankRecord['sort_code'] ?? '') }}" required readonly data-profile-sort-code />
                            </div>
                        </form>
                    </x-ui.card>
                </div>

                <div data-profile-step-panel="4" class="profile-step-panel hidden min-w-0" hidden>
                    <x-ui.card title="Milestone Completion" description="Your final celebration unlocks after every required profile field is saved.">
                        <div class="rounded-2xl bg-brand-600 p-6 text-center text-white sm:p-8">
                            <span class="mx-auto grid size-16 place-items-center rounded-2xl bg-white/15 text-white ring-1 ring-white/20">
                                <x-ui.icon name="check-check" class="size-8" />
                            </span>
                            <h3 class="mt-5 text-2xl font-bold">Almost there</h3>
                            <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-white/78">Save your bank information to finish the profile milestone. A congratulatory page will open once your completion reaches 100%.</p>
                        </div>
                    </x-ui.card>
                </div>

            </div>
        </div>
    </section>
</x-layouts.app-shell>
