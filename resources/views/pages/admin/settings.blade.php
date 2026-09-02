@php
    $navigation = [
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'D'],
        ['label' => 'Generate List', 'href' => route('admin.generate-list.index'), 'icon' => 'file-text'],
        ...(\App\Support\PortalPermission::isRootAdmin(auth('admin')->user()) ? [['label' => 'Control', 'href' => route('admin.control.index'), 'icon' => 'C']] : []),
        ['label' => 'Academics', 'href' => route('admin.academics.index'), 'icon' => 'A'],
        ['label' => 'Notices', 'href' => route('admin.notices.index'), 'icon' => 'N'],
        ['label' => 'Settings', 'href' => route('admin.settings.index'), 'active' => true, 'icon' => 'G'],
    ];
@endphp

<x-layouts.app-shell title="System Settings" role="Admin" :navigation="$navigation">
    @php
        $allSettings = $settings->flatten(1)->keyBy('key');
        $welcomeMessage = $allSettings->get('site.welcome.message');
        $welcomeTitle = $allSettings->get('site.welcome.title');
        $welcomeEnabled = $allSettings->get('site.welcome.enabled');
        $welcomeDuration = $allSettings->get('site.welcome.duration_seconds');
        $canUpdateSettings = \App\Support\PortalPermission::userHas(auth('admin')->user(), 'settings.update');
        $canProcessImports = \App\Support\PortalPermission::userHas(auth('admin')->user(), 'students.import');
        $settingValue = fn (string $key, mixed $default = null) => $allSettings->get($key)?->value ?? $default;
        $mailSchemeValue = strtolower((string) $settingValue('mail.scheme', config('mail.mailers.smtp.scheme')));
        $mailSchemeValue = in_array($mailSchemeValue, ['ssl', 'smtps'], true) ? 'smtps' : '';
        $paymentFields = [
            ['group' => 'payment', 'key' => 'payment.provider', 'label' => 'Payment Provider', 'type' => 'string', 'input' => 'text', 'value' => $settingValue('payment.provider', config('siwes.payments.provider', 'korapay')), 'description' => 'Active payment provider. Use korapay.'],
            ['group' => 'payment', 'key' => 'payment.currency', 'label' => 'Currency', 'type' => 'string', 'input' => 'text', 'value' => $settingValue('payment.currency', config('siwes.payments.currency', 'NGN')), 'description' => 'Currency code used for ticket payments.'],
            ['group' => 'payment', 'key' => 'payment.ticket_amount', 'label' => 'Ticket Amount', 'type' => 'integer', 'input' => 'number', 'value' => $settingValue('payment.ticket_amount', config('siwes.payments.ticket_amount', 5000)), 'description' => 'SIWES activation ticket fee.'],
            ['group' => 'payment', 'key' => 'payment.ticket_valid_days', 'label' => 'Ticket Valid Days', 'type' => 'integer', 'input' => 'number', 'value' => $settingValue('payment.ticket_valid_days', config('siwes.payments.ticket_valid_days', 30)), 'description' => 'Number of days before an unused ticket expires.'],
            ['group' => 'payment', 'key' => 'payment.workshop_fee_enabled', 'label' => 'Workshop Fee Module', 'type' => 'boolean', 'input' => 'select', 'options' => ['true' => 'Active', 'false' => 'Inactive'], 'value' => $settingValue('payment.workshop_fee_enabled', false) ? 'true' : 'false', 'description' => 'Show workshop fee card and require payment before placement access.'],
            ['group' => 'payment', 'key' => 'payment.workshop_fee_amount', 'label' => 'Workshop Fee Amount', 'type' => 'integer', 'input' => 'number', 'value' => $settingValue('payment.workshop_fee_amount', config('siwes.payments.workshop_fee_amount', 0)), 'description' => 'Online workshop fee charged before placement access when the module is active.'],
            ['group' => 'korapay', 'key' => 'korapay.base_url', 'label' => 'Korapay Base URL', 'type' => 'string', 'input' => 'url', 'value' => $settingValue('korapay.base_url', config('siwes.korapay.base_url')), 'description' => 'Korapay merchant API base URL.'],
            ['group' => 'korapay', 'key' => 'korapay.public_key', 'label' => 'Korapay Public Key', 'type' => 'string', 'input' => 'text', 'value' => $settingValue('korapay.public_key', config('siwes.korapay.public_key')), 'description' => 'Korapay public API key.'],
            ['group' => 'korapay', 'key' => 'korapay.secret_key', 'label' => 'Korapay Secret Key', 'type' => 'string', 'input' => 'password', 'value' => $settingValue('korapay.secret_key', config('siwes.korapay.secret_key')), 'description' => 'Korapay private API key.'],
            ['group' => 'korapay', 'key' => 'korapay.webhook_secret', 'label' => 'Korapay Webhook Secret', 'type' => 'string', 'input' => 'password', 'value' => $settingValue('korapay.webhook_secret', config('siwes.korapay.webhook_secret')), 'description' => 'Secret used to verify Korapay webhook signatures.'],
            ['group' => 'korapay', 'key' => 'korapay.redirect_url', 'label' => 'Korapay Redirect URL', 'type' => 'string', 'input' => 'url', 'value' => $settingValue('korapay.redirect_url', config('siwes.korapay.redirect_url')), 'description' => 'Callback URL after Korapay checkout.'],
        ];
        $emailFields = [
            ['group' => 'mail', 'key' => 'mail.mailer', 'label' => 'Mailer', 'type' => 'string', 'input' => 'select', 'options' => ['smtp' => 'SMTP', 'sendmail' => 'Sendmail', 'log' => 'Log', 'array' => 'Array'], 'value' => $settingValue('mail.mailer', config('mail.default', 'smtp')), 'description' => 'Default Laravel mailer. Use smtp for live delivery.'],
            ['group' => 'mail', 'key' => 'mail.host', 'label' => 'SMTP Host', 'type' => 'string', 'input' => 'text', 'value' => $settingValue('mail.host', config('mail.mailers.smtp.host')), 'description' => 'SMTP server hostname.'],
            ['group' => 'mail', 'key' => 'mail.port', 'label' => 'SMTP Port', 'type' => 'integer', 'input' => 'number', 'value' => $settingValue('mail.port', config('mail.mailers.smtp.port')), 'description' => 'SMTP server port.'],
            ['group' => 'mail', 'key' => 'mail.scheme', 'label' => 'SMTP Security', 'type' => 'string', 'input' => 'select', 'options' => ['' => 'Auto / STARTTLS (port 587)', 'smtps' => 'SMTPS / SSL (port 465)'], 'value' => $mailSchemeValue, 'description' => 'Use Auto for STARTTLS on port 587. Use SMTPS / SSL for port 465.'],
            ['group' => 'mail', 'key' => 'mail.username', 'label' => 'SMTP Username', 'type' => 'string', 'input' => 'text', 'value' => $settingValue('mail.username', config('mail.mailers.smtp.username')), 'description' => 'SMTP account username.'],
            ['group' => 'mail', 'key' => 'mail.password', 'label' => 'SMTP Password', 'type' => 'string', 'input' => 'password', 'value' => $settingValue('mail.password', config('mail.mailers.smtp.password')), 'description' => 'SMTP account password or app password.'],
            ['group' => 'mail', 'key' => 'mail.from_address', 'label' => 'From Address', 'type' => 'string', 'input' => 'email', 'value' => $settingValue('mail.from_address', config('mail.from.address')), 'description' => 'Default sender email address.'],
            ['group' => 'mail', 'key' => 'mail.from_name', 'label' => 'From Name', 'type' => 'string', 'input' => 'text', 'value' => $settingValue('mail.from_name', config('mail.from.name')), 'description' => 'Default sender display name.'],
        ];
        $emailTemplateFields = [
            ['group' => 'mail_templates', 'key' => 'mail.templates.admin_login_details.subject', 'label' => 'Admin Login Subject', 'type' => 'string', 'input' => 'text', 'value' => $settingValue('mail.templates.admin_login_details.subject', 'COOU SIWES Admin Portal Login Details'), 'description' => 'Subject for new admin login detail email.'],
            ['group' => 'mail_templates', 'key' => 'mail.templates.admin_login_details.body', 'label' => 'Admin Login Body', 'type' => 'string', 'input' => 'textarea', 'value' => $settingValue('mail.templates.admin_login_details.body', "An admin account has been created for you on the COOU SIWES portal.\n\nEmail: {email}\nTemporary password: {temporary_password}\n\nPlease sign in and change your password after your first login."), 'description' => 'Available placeholders: {name}, {email}, {temporary_password}, {login_url}.'],
            ['group' => 'mail_templates', 'key' => 'mail.templates.supervisor_login_details.subject', 'label' => 'Supervisor Login Subject', 'type' => 'string', 'input' => 'text', 'value' => $settingValue('mail.templates.supervisor_login_details.subject', 'COOU SIWES Supervisor Portal Login Details'), 'description' => 'Subject for new supervisor login detail email.'],
            ['group' => 'mail_templates', 'key' => 'mail.templates.supervisor_login_details.body', 'label' => 'Supervisor Login Body', 'type' => 'string', 'input' => 'textarea', 'value' => $settingValue('mail.templates.supervisor_login_details.body', "A supervisor account has been created for you on the COOU SIWES portal.\n\nEmail: {email}\nTemporary password: {temporary_password}\n\nPlease sign in and change your password after your first login."), 'description' => 'Available placeholders: {name}, {email}, {temporary_password}, {login_url}.'],
            ['group' => 'mail_templates', 'key' => 'mail.templates.supervisor_assignment.subject', 'label' => 'Supervisor Assignment Subject', 'type' => 'string', 'input' => 'text', 'value' => $settingValue('mail.templates.supervisor_assignment.subject', 'New COOU SIWES Student Assignment'), 'description' => 'Subject for single student assignment email.'],
            ['group' => 'mail_templates', 'key' => 'mail.templates.supervisor_assignment.body', 'label' => 'Supervisor Assignment Body', 'type' => 'string', 'input' => 'textarea', 'value' => $settingValue('mail.templates.supervisor_assignment.body', "A student has been assigned to you for SIWES supervision.\n\nStudent: {student_name}\nMatric Number: {matric_no}\nDepartment: {department}\nFaculty: {faculty}\nPlacement Location: {lga}, {state}"), 'description' => 'Available placeholders: {supervisor_name}, {student_name}, {matric_no}, {department}, {faculty}, {state}, {lga}, {dashboard_url}.'],
            ['group' => 'mail_templates', 'key' => 'mail.templates.supervisor_bulk_assignment.subject', 'label' => 'Supervisor Bulk Assignment Subject', 'type' => 'string', 'input' => 'text', 'value' => $settingValue('mail.templates.supervisor_bulk_assignment.subject', 'COOU SIWES Bulk Student Assignment Summary'), 'description' => 'Subject for bulk assignment summary email.'],
            ['group' => 'mail_templates', 'key' => 'mail.templates.supervisor_bulk_assignment.body', 'label' => 'Supervisor Bulk Assignment Body', 'type' => 'string', 'input' => 'textarea', 'value' => $settingValue('mail.templates.supervisor_bulk_assignment.body', "Bulk SIWES supervisor assignment has been completed.\n\nTotal Students Assigned: {total_assigned}\nFaculty: {faculty}\nDepartment: {department}\nPlacement State: {state}\nPlacement LGA: {lga}\n\nOpen your supervisor dashboard to view the assigned students."), 'description' => 'Available placeholders: {supervisor_name}, {total_assigned}, {assigned_count}, {reassigned_count}, {skipped_count}, {faculty}, {department}, {level}, {academic_session}, {state}, {lga}, {dashboard_url}.'],
        ];
        $importFields = [
            ['group' => 'imports', 'key' => 'imports.immediate_threshold', 'label' => 'Immediate Import Threshold', 'type' => 'integer', 'input' => 'number', 'value' => $settingValue('imports.immediate_threshold', config('siwes.imports.immediate_threshold', 2000)), 'description' => 'Rows up to this number import immediately. Higher uploads are queued.'],
            ['group' => 'imports', 'key' => 'imports.cron_batch_size', 'label' => 'Cron Batch Size', 'type' => 'integer', 'input' => 'number', 'value' => $settingValue('imports.cron_batch_size', config('siwes.imports.cron_batch_size', 1000)), 'description' => 'Rows processed per cron hit. Keep between 500 and 2000 on cPanel.'],
            ['group' => 'imports', 'key' => 'imports.cron_token', 'label' => 'Cron URL Token', 'type' => 'string', 'input' => 'password', 'value' => $settingValue('imports.cron_token', config('siwes.imports.cron_token')), 'description' => 'Required token for the cPanel cron URL. Use a long random value.'],
        ];
    @endphp

    @if (session('status'))
        <x-ui.alert title="Saved" tone="success">{{ session('status') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert title="Validation required" tone="danger">{{ $errors->first() }}</x-ui.alert>
    @endif

    <x-ui.card title="Settings Workspace" description="Manage welcome messages, typed configuration, and grouped setting records from focused tabs.">
        <div class="overflow-x-auto">
            <div class="inline-flex min-w-full gap-2 rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-1" role="tablist" aria-label="System settings">
                <button type="button" class="settings-tab is-active rounded-md px-3 py-2 text-sm font-semibold theme-transition" data-settings-tab-target="#welcome-settings-panel" aria-selected="true">Welcome Message</button>
                <button type="button" class="settings-tab rounded-md px-3 py-2 text-sm font-semibold theme-transition" data-settings-tab-target="#payment-settings-panel" aria-selected="false">Payment Settings</button>
                <button type="button" class="settings-tab rounded-md px-3 py-2 text-sm font-semibold theme-transition" data-settings-tab-target="#email-settings-panel" aria-selected="false">Email Settings</button>
                <button type="button" class="settings-tab rounded-md px-3 py-2 text-sm font-semibold theme-transition" data-settings-tab-target="#email-templates-panel" aria-selected="false">Email Templates</button>
                <button type="button" class="settings-tab rounded-md px-3 py-2 text-sm font-semibold theme-transition" data-settings-tab-target="#import-settings-panel" aria-selected="false">Import Settings</button>
                <button type="button" class="settings-tab rounded-md px-3 py-2 text-sm font-semibold theme-transition" data-settings-tab-target="#system-settings-panel" aria-selected="false">System Settings</button>
                @if ($canUpdateSettings)
                    <button type="button" class="settings-tab rounded-md px-3 py-2 text-sm font-semibold theme-transition" data-settings-tab-target="#create-settings-panel" aria-selected="false">Create Setting</button>
                @endif
                <button type="button" class="settings-tab rounded-md px-3 py-2 text-sm font-semibold theme-transition" data-settings-tab-target="#settings-records-panel" aria-selected="false">Settings Records</button>
            </div>
        </div>

        <section id="welcome-settings-panel" class="settings-panel mt-5" data-settings-panel>
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-[var(--text-strong)]">Welcome Message</h2>
                    <p class="mt-1 text-sm text-[var(--text-soft)]">Controls the welcome toast shown on public pages.</p>
                </div>
                @if ($canUpdateSettings)
                    <x-ui.button type="button" data-modal-target="#welcome-settings-modal">Configure Welcome Message</x-ui.button>
                @endif
            </div>
            <div class="mt-5 grid gap-4 md:grid-cols-3">
                <div class="rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-4">
                    <p class="text-xs font-semibold uppercase text-[var(--text-soft)]">Title</p>
                    <p class="mt-2 text-sm font-semibold text-[var(--text-strong)]">{{ $welcomeTitle?->value ?? 'Welcome to COOU SIWES' }}</p>
                </div>
                <div class="rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-4">
                    <p class="text-xs font-semibold uppercase text-[var(--text-soft)]">Visibility</p>
                    <p class="mt-2 text-sm font-semibold text-[var(--text-strong)]">{{ ($welcomeEnabled?->value ?? true) === true ? 'Enabled' : 'Disabled' }}</p>
                </div>
                <div class="rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-4">
                    <p class="text-xs font-semibold uppercase text-[var(--text-soft)]">Duration</p>
                    <p class="mt-2 text-sm font-semibold text-[var(--text-strong)]">{{ $welcomeDuration?->value ?? 6 }} seconds</p>
                </div>
            </div>
            <div class="mt-4 rounded-lg border border-[var(--line)] bg-[var(--surface-raised)] p-4">
                <p class="text-xs font-semibold uppercase text-[var(--text-soft)]">Message</p>
                <p class="mt-2 text-sm leading-6 text-[var(--text-strong)]">{{ $welcomeMessage?->value ?: 'No welcome message configured yet.' }}</p>
            </div>
        </section>

        <section id="payment-settings-panel" class="settings-panel mt-5 hidden" data-settings-panel>
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-[var(--text-strong)]">Payment Settings</h2>
                    <p class="mt-1 text-sm text-[var(--text-soft)]">Korapay credentials, ticket pricing, currency, and payment callback configuration.</p>
                </div>
                @if ($canUpdateSettings)
                    <div class="flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('admin.settings.bulk') }}">
                            @csrf
                            <input type="hidden" name="settings[0][group]" value="payment">
                            <input type="hidden" name="settings[0][key]" value="payment.workshop_fee_enabled">
                            <input type="hidden" name="settings[0][type]" value="boolean">
                            <input type="hidden" name="settings[0][description]" value="Show workshop fee card and require payment before placement access.">
                            <input type="hidden" name="settings[0][value]" value="{{ $settingValue('payment.workshop_fee_enabled', false) ? 'false' : 'true' }}">
                            <x-ui.button type="submit" variant="secondary">
                                {{ $settingValue('payment.workshop_fee_enabled', false) ? 'Deactivate Workshop Fee' : 'Activate Workshop Fee' }}
                            </x-ui.button>
                        </form>
                        <x-ui.button type="button" data-modal-target="#payment-settings-modal">Configure Payment</x-ui.button>
                    </div>
                @endif
            </div>
            <div class="mt-5 grid gap-4 md:grid-cols-4">
                <div class="rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-4">
                    <p class="text-xs font-semibold uppercase text-[var(--text-soft)]">Provider</p>
                    <p class="mt-2 text-sm font-semibold text-[var(--text-strong)]">{{ $settingValue('payment.provider', config('siwes.payments.provider', 'korapay')) }}</p>
                </div>
                <div class="rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-4">
                    <p class="text-xs font-semibold uppercase text-[var(--text-soft)]">Ticket Fee</p>
                    <p class="mt-2 text-sm font-semibold text-[var(--text-strong)]">{{ $settingValue('payment.currency', config('siwes.payments.currency', 'NGN')) }} {{ number_format((int) $settingValue('payment.ticket_amount', config('siwes.payments.ticket_amount', 5000))) }}</p>
                </div>
                <div class="rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-4">
                    <p class="text-xs font-semibold uppercase text-[var(--text-soft)]">Workshop Fee</p>
                    <p class="mt-2 text-sm font-semibold text-[var(--text-strong)]">{{ $settingValue('payment.workshop_fee_enabled', false) ? 'Active' : 'Inactive' }}</p>
                    <p class="mt-1 text-xs text-[var(--text-soft)]">{{ $settingValue('payment.currency', config('siwes.payments.currency', 'NGN')) }} {{ number_format((int) $settingValue('payment.workshop_fee_amount', config('siwes.payments.workshop_fee_amount', 0))) }}</p>
                </div>
                <div class="rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-4">
                    <p class="text-xs font-semibold uppercase text-[var(--text-soft)]">Korapay Public Key</p>
                    <p class="mt-2 truncate text-sm font-semibold text-[var(--text-strong)]">{{ $settingValue('korapay.public_key', config('siwes.korapay.public_key')) ?: 'Not configured' }}</p>
                </div>
            </div>
        </section>

        <section id="email-settings-panel" class="settings-panel mt-5 hidden" data-settings-panel>
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-[var(--text-strong)]">Email Settings</h2>
                    <p class="mt-1 text-sm text-[var(--text-soft)]">SMTP host, credentials, sender identity, and live connection test.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if ($canUpdateSettings)
                        <x-ui.button type="button" data-modal-target="#email-settings-modal">Configure Email</x-ui.button>
                        <x-ui.button type="button" variant="secondary" data-modal-target="#email-test-modal">Test Connection</x-ui.button>
                    @endif
                </div>
            </div>
            <div class="mt-5 grid gap-4 md:grid-cols-4">
                <div class="rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-4">
                    <p class="text-xs font-semibold uppercase text-[var(--text-soft)]">Mailer</p>
                    <p class="mt-2 text-sm font-semibold text-[var(--text-strong)]">{{ $settingValue('mail.mailer', config('mail.default', 'smtp')) }}</p>
                </div>
                <div class="rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-4">
                    <p class="text-xs font-semibold uppercase text-[var(--text-soft)]">SMTP Host</p>
                    <p class="mt-2 truncate text-sm font-semibold text-[var(--text-strong)]">{{ $settingValue('mail.host', config('mail.mailers.smtp.host')) ?: 'Not configured' }}</p>
                </div>
                <div class="rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-4">
                    <p class="text-xs font-semibold uppercase text-[var(--text-soft)]">Port</p>
                    <p class="mt-2 text-sm font-semibold text-[var(--text-strong)]">{{ $settingValue('mail.port', config('mail.mailers.smtp.port')) ?: 'Not set' }}</p>
                </div>
                <div class="rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-4">
                    <p class="text-xs font-semibold uppercase text-[var(--text-soft)]">From</p>
                    <p class="mt-2 truncate text-sm font-semibold text-[var(--text-strong)]">{{ $settingValue('mail.from_address', config('mail.from.address')) }}</p>
                </div>
            </div>
        </section>

        <section id="email-templates-panel" class="settings-panel mt-5 hidden" data-settings-panel>
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-[var(--text-strong)]">Email Templates</h2>
                    <p class="mt-1 text-sm text-[var(--text-soft)]">Edit the notification messages sent by the portal. Placeholders are replaced automatically during delivery.</p>
                </div>
                @if ($canUpdateSettings)
                    <x-ui.button type="button" data-modal-target="#email-templates-modal">Configure Templates</x-ui.button>
                @endif
            </div>
            <div class="mt-5 grid gap-4 lg:grid-cols-3">
                <div class="rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-4">
                    <p class="text-xs font-semibold uppercase text-[var(--text-soft)]">Admin Login Details</p>
                    <p class="mt-2 text-sm font-semibold text-[var(--text-strong)]">{{ $settingValue('mail.templates.admin_login_details.subject', 'COOU SIWES Admin Portal Login Details') }}</p>
                </div>
                <div class="rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-4">
                    <p class="text-xs font-semibold uppercase text-[var(--text-soft)]">Supervisor Login Details</p>
                    <p class="mt-2 text-sm font-semibold text-[var(--text-strong)]">{{ $settingValue('mail.templates.supervisor_login_details.subject', 'COOU SIWES Supervisor Portal Login Details') }}</p>
                </div>
                <div class="rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-4">
                    <p class="text-xs font-semibold uppercase text-[var(--text-soft)]">Supervisor Assignment</p>
                    <p class="mt-2 text-sm font-semibold text-[var(--text-strong)]">{{ $settingValue('mail.templates.supervisor_assignment.subject', 'New COOU SIWES Student Assignment') }}</p>
                </div>
            </div>
        </section>

        <section id="import-settings-panel" class="settings-panel mt-5 hidden" data-settings-panel>
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-[var(--text-strong)]">Import Settings</h2>
                    <p class="mt-1 text-sm text-[var(--text-soft)]">Student bulk import thresholds, cPanel cron batch size, and secure cron token.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if ($canProcessImports)
                        <form method="POST" action="{{ route('admin.settings.imports.process') }}">
                            @csrf
                            <x-ui.button type="submit" icon="refresh-cw">Run Queued Imports</x-ui.button>
                        </form>
                    @endif
                    @if ($canUpdateSettings)
                        <x-ui.button type="button" variant="secondary" data-modal-target="#import-settings-modal">Configure Import</x-ui.button>
                    @endif
                </div>
            </div>
            <div class="mt-5 grid gap-4 md:grid-cols-3">
                <div class="rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-4">
                    <p class="text-xs font-semibold uppercase text-[var(--text-soft)]">Immediate Threshold</p>
                    <p class="mt-2 text-sm font-semibold text-[var(--text-strong)]">{{ number_format((int) $settingValue('imports.immediate_threshold', config('siwes.imports.immediate_threshold', 2000))) }} rows</p>
                </div>
                <div class="rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-4">
                    <p class="text-xs font-semibold uppercase text-[var(--text-soft)]">Cron Batch Size</p>
                    <p class="mt-2 text-sm font-semibold text-[var(--text-strong)]">{{ number_format((int) $settingValue('imports.cron_batch_size', config('siwes.imports.cron_batch_size', 1000))) }} rows</p>
                </div>
                <div class="rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-4">
                    <p class="text-xs font-semibold uppercase text-[var(--text-soft)]">Cron Token</p>
                    <p class="mt-2 text-sm font-semibold text-[var(--text-strong)]">{{ filled($settingValue('imports.cron_token', config('siwes.imports.cron_token'))) ? 'Configured' : 'Not configured' }}</p>
                </div>
            </div>
            <div class="mt-4 rounded-lg border border-[var(--line)] bg-[var(--surface-raised)] p-4">
                <p class="text-xs font-semibold uppercase text-[var(--text-soft)]">cPanel Cron URL</p>
                <p class="mt-2 break-all font-mono text-xs text-[var(--text-strong)]">{{ url('/cron/student-imports/process') }}?token=YOUR_TOKEN&limit={{ (int) $settingValue('imports.cron_batch_size', config('siwes.imports.cron_batch_size', 1000)) }}</p>
            </div>
        </section>

        <section id="system-settings-panel" class="settings-panel mt-5 hidden" data-settings-panel>
            <div class="grid gap-4 xl:grid-cols-[1fr_22rem]">
                <div class="rounded-xl border border-[var(--line)] bg-[var(--surface-raised)] p-5 shadow-[0_18px_44px_rgb(8_15_12_/_0.06)]">
                    <div class="flex items-start gap-3">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-600 text-white shadow-[0_12px_28px_rgb(0_81_54_/_0.2)]">
                            <x-ui.icon name="refresh-cw" class="size-5" />
                        </span>
                        <div>
                            <h2 class="text-base font-semibold text-[var(--text-strong)]">System Cache</h2>
                            <p class="mt-1 text-sm leading-6 text-[var(--text-soft)]">Clear Laravel cache directly from the admin panel when cPanel terminal access is unavailable.</p>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        @foreach (['Application cache', 'Configuration cache', 'Route cache', 'Compiled views', 'Permission cache'] as $cacheItem)
                            <div class="flex items-center gap-2 rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] px-3 py-2 text-sm font-semibold text-[var(--text-strong)]">
                                <x-ui.icon name="check" class="size-4 shrink-0 text-brand-600" />
                                {{ $cacheItem }}
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="grid gap-4">
                    <form method="POST" action="{{ route('admin.settings.cache.clear') }}" class="rounded-xl border border-[var(--line)] bg-[var(--surface-muted)] p-5">
                        @csrf
                        <p class="text-sm font-semibold text-[var(--text-strong)]">Clear cached files</p>
                        <p class="mt-2 text-sm leading-6 text-[var(--text-soft)]">Use after deployment, environment changes, route updates, permission fixes, or Blade/Vite view changes.</p>
                        <x-ui.button type="submit" class="mt-5 w-full" icon="refresh-cw">Clear System Cache</x-ui.button>
                    </form>

                    @if ($canUpdateSettings && \App\Support\PortalPermission::isRootAdmin(auth('admin')->user()))
                        <form method="POST" action="{{ route('admin.settings.database.seed') }}" class="rounded-xl border border-amber-400/40 bg-amber-400/10 p-5">
                            @csrf
                            <span class="mb-3 flex size-10 items-center justify-center rounded-xl bg-amber-400 text-graphite-950 shadow-[0_12px_28px_rgb(217_155_0_/_0.25)]">
                                <x-ui.icon name="database" class="size-5" />
                            </span>
                            <p class="text-sm font-semibold text-[var(--text-strong)]">Run migrations and update seeders</p>
                            <p class="mt-2 text-sm leading-6 text-[var(--text-soft)]">Apply pending database repairs, then refresh roles, permissions, academic data, and default portal settings.</p>
                            <x-ui.button type="submit" class="mt-5 w-full" icon="database">Update Database</x-ui.button>
                        </form>
                    @endif
                </div>
            </div>
        </section>

        @if ($canUpdateSettings)
            <section id="create-settings-panel" class="settings-panel mt-5 hidden" data-settings-panel>
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-[var(--text-strong)]">Create Setting</h2>
                        <p class="mt-1 text-sm text-[var(--text-soft)]">Typed configuration for site, academic, OTP, upload, payment, theme, and notifications.</p>
                    </div>
                    <x-ui.button type="button" data-modal-target="#create-setting-modal">Create Setting</x-ui.button>
                </div>
                <div class="mt-5 rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-4">
                    <p class="text-sm text-[var(--text-soft)]">Use grouped keys such as <span class="font-mono text-[var(--text-strong)]">payment.provider</span> or <span class="font-mono text-[var(--text-strong)]">site.welcome.title</span> so configuration stays searchable and auditable.</p>
                </div>
            </section>
        @endif

        <section id="settings-records-panel" class="settings-panel mt-5 hidden" data-settings-panel>
            <div class="mb-4">
                <h2 class="text-base font-semibold text-[var(--text-strong)]">Settings Records</h2>
                <p class="mt-1 text-sm text-[var(--text-soft)]">Configurable values audited on every change.</p>
            </div>
            <div class="grid gap-5">
                @forelse ($settings as $group => $items)
                    <section class="rounded-lg border border-[var(--line)] bg-[var(--surface-raised)] p-4">
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-semibold text-[var(--text-strong)]">{{ ucfirst((string) $group) }}</h3>
                                <p class="mt-1 text-xs text-[var(--text-soft)]">{{ $items->count() }} configurable value(s)</p>
                            </div>
                        </div>
                        <x-ui.input class="mb-4" label="Live Search" name="settings_search_{{ $loop->index }}" placeholder="Search {{ $group }} settings..." data-live-search="#settings-table-{{ $loop->index }} tbody tr" />
                        <x-ui.data-table
                            id="settings-table-{{ $loop->index }}"
                            :headers="['Key', 'Value', 'Type', 'Public']"
                            :rows="$items->map(fn ($setting) => [
                                e($setting->key),
                                e(is_array($setting->value) ? json_encode($setting->value) : (string) $setting->value),
                                e($setting->type),
                                $setting->is_public ? 'Yes' : 'No',
                            ])->all()"
                        />
                    </section>
                @empty
                    <div class="rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-4">
                        <p class="text-sm text-[var(--text-soft)]">Settings added here are permission protected and audited.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </x-ui.card>

    @if ($canUpdateSettings)
        <x-ui.modal id="welcome-settings-modal" title="Configure Welcome Message" class="w-[min(56rem,calc(100vw-2rem))]">
        <div class="grid gap-5">
            <form method="POST" action="{{ $welcomeMessage ? route('admin.settings.update', $welcomeMessage) : route('admin.settings.store') }}" class="grid gap-4">
                @csrf
                @if ($welcomeMessage)
                    @method('PUT')
                @endif
                <input type="hidden" name="group" value="site">
                <input type="hidden" name="key" value="site.welcome.message">
                <input type="hidden" name="type" value="string">
                <input type="hidden" name="is_public" value="1">
                <label class="block">
                    <span class="text-sm font-medium text-[var(--text-strong)]">Message</span>
                    <textarea name="value" rows="4" class="siwes-form-control mt-2 theme-transition placeholder:text-[var(--text-soft)]">{{ old('value', $welcomeMessage?->value ?? '') }}</textarea>
                </label>
                <div class="flex justify-end gap-2">
                    <x-ui.button type="button" variant="ghost" data-modal-close>Cancel</x-ui.button>
                    <x-ui.button type="submit">Save Welcome Message</x-ui.button>
                </div>
            </form>

            <div class="grid gap-4 sm:grid-cols-2">
                <form method="POST" action="{{ $welcomeTitle ? route('admin.settings.update', $welcomeTitle) : route('admin.settings.store') }}" class="grid gap-3 rounded-lg border border-[var(--line)] p-4">
                    @csrf
                    @if ($welcomeTitle)
                        @method('PUT')
                    @endif
                    <input type="hidden" name="group" value="site">
                    <input type="hidden" name="key" value="site.welcome.title">
                    <input type="hidden" name="type" value="string">
                    <input type="hidden" name="is_public" value="1">
                    <x-ui.input label="Title" name="value" :value="$welcomeTitle?->value ?? 'Welcome to COOU SIWES'" />
                    <x-ui.button type="submit" variant="secondary">Save Title</x-ui.button>
                </form>

                <form method="POST" action="{{ $welcomeDuration ? route('admin.settings.update', $welcomeDuration) : route('admin.settings.store') }}" class="grid gap-3 rounded-lg border border-[var(--line)] p-4">
                    @csrf
                    @if ($welcomeDuration)
                        @method('PUT')
                    @endif
                    <input type="hidden" name="group" value="site">
                    <input type="hidden" name="key" value="site.welcome.duration_seconds">
                    <input type="hidden" name="type" value="integer">
                    <input type="hidden" name="is_public" value="1">
                    <x-ui.input label="Duration Seconds" name="value" type="number" min="3" max="30" :value="$welcomeDuration?->value ?? 6" />
                    <x-ui.button type="submit" variant="secondary">Save Duration</x-ui.button>
                </form>
            </div>

            <form method="POST" action="{{ $welcomeEnabled ? route('admin.settings.update', $welcomeEnabled) : route('admin.settings.store') }}" class="flex flex-wrap items-end gap-3 rounded-lg border border-[var(--line)] p-4">
                @csrf
                @if ($welcomeEnabled)
                    @method('PUT')
                @endif
                <input type="hidden" name="group" value="site">
                <input type="hidden" name="key" value="site.welcome.enabled">
                <input type="hidden" name="type" value="boolean">
                <input type="hidden" name="is_public" value="1">
                <label class="block">
                    <span class="text-sm font-medium text-[var(--text-strong)]">Visibility</span>
                    <select name="value" class="siwes-form-control mt-2">
                        <option value="true" @selected(($welcomeEnabled?->value ?? true) === true)>Enabled</option>
                        <option value="false" @selected(($welcomeEnabled?->value ?? true) === false)>Disabled</option>
                    </select>
                </label>
                <x-ui.button type="submit" variant="secondary">Save Visibility</x-ui.button>
            </form>
        </div>
        </x-ui.modal>

        <x-ui.modal id="payment-settings-modal" title="Payment Settings" class="w-[min(58rem,calc(100vw-2rem))]">
        <form method="POST" action="{{ route('admin.settings.bulk') }}" class="grid gap-4">
            @csrf
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($paymentFields as $index => $field)
                    <label class="block">
                        <span class="text-sm font-medium text-[var(--text-strong)]">{{ $field['label'] }}</span>
                        @if (($field['input'] ?? 'text') === 'select')
                            <select name="settings[{{ $index }}][value]" class="siwes-form-control mt-2 theme-transition">
                                @foreach ($field['options'] as $optionValue => $optionLabel)
                                    <option value="{{ $optionValue }}" @selected((string) $field['value'] === (string) $optionValue)>{{ $optionLabel }}</option>
                                @endforeach
                            </select>
                        @else
                            <input
                                type="{{ $field['input'] }}"
                                name="settings[{{ $index }}][value]"
                                value="{{ $field['value'] }}"
                                class="siwes-form-control mt-2 theme-transition placeholder:text-[var(--text-soft)]"
                            >
                        @endif
                        <span class="mt-1 block text-xs text-[var(--text-soft)]">{{ $field['description'] }}</span>
                        <input type="hidden" name="settings[{{ $index }}][group]" value="{{ $field['group'] }}">
                        <input type="hidden" name="settings[{{ $index }}][key]" value="{{ $field['key'] }}">
                        <input type="hidden" name="settings[{{ $index }}][type]" value="{{ $field['type'] }}">
                        <input type="hidden" name="settings[{{ $index }}][description]" value="{{ $field['description'] }}">
                    </label>
                @endforeach
            </div>
            <div class="flex justify-end gap-2">
                <x-ui.button type="button" variant="ghost" data-modal-close>Cancel</x-ui.button>
                <x-ui.button type="submit">Save Payment Settings</x-ui.button>
            </div>
        </form>
        </x-ui.modal>

        <x-ui.modal id="email-settings-modal" title="Email Settings" class="w-[min(58rem,calc(100vw-2rem))]">
        <form method="POST" action="{{ route('admin.settings.bulk') }}" class="grid gap-4">
            @csrf
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($emailFields as $index => $field)
                    <label class="block">
                        <span class="text-sm font-medium text-[var(--text-strong)]">{{ $field['label'] }}</span>
                        @if (($field['input'] ?? 'text') === 'select')
                            <select name="settings[{{ $index }}][value]" class="siwes-form-control mt-2 theme-transition">
                                @foreach ($field['options'] as $optionValue => $optionLabel)
                                    <option value="{{ $optionValue }}" @selected((string) $field['value'] === (string) $optionValue)>{{ $optionLabel }}</option>
                                @endforeach
                            </select>
                        @else
                            <input
                                type="{{ $field['input'] }}"
                                name="settings[{{ $index }}][value]"
                                value="{{ $field['value'] }}"
                                class="siwes-form-control mt-2 theme-transition placeholder:text-[var(--text-soft)]"
                            >
                        @endif
                        <span class="mt-1 block text-xs text-[var(--text-soft)]">{{ $field['description'] }}</span>
                        <input type="hidden" name="settings[{{ $index }}][group]" value="{{ $field['group'] }}">
                        <input type="hidden" name="settings[{{ $index }}][key]" value="{{ $field['key'] }}">
                        <input type="hidden" name="settings[{{ $index }}][type]" value="{{ $field['type'] }}">
                        <input type="hidden" name="settings[{{ $index }}][description]" value="{{ $field['description'] }}">
                    </label>
                @endforeach
            </div>
            <div class="flex justify-end gap-2">
                <x-ui.button type="button" variant="ghost" data-modal-close>Cancel</x-ui.button>
                <x-ui.button type="submit">Save Email Settings</x-ui.button>
            </div>
        </form>
        </x-ui.modal>

        <x-ui.modal id="email-test-modal" title="Send Test Email" class="w-[min(34rem,calc(100vw-2rem))]">
        <form method="POST" action="{{ route('admin.settings.email.test') }}" class="grid gap-4" data-ajax-reset="false">
            @csrf
            <div class="rounded-xl border border-cyan-400/35 bg-cyan-400/10 p-4">
                <p class="text-sm font-semibold text-[var(--text-strong)]">Test actual email delivery</p>
                <p class="mt-1 text-sm leading-6 text-[var(--text-soft)]">Enter an inbox you can access. The portal will send a real test message using the current SMTP settings.</p>
            </div>
            <x-ui.input label="Test Email Address" name="test_email" type="email" placeholder="you@example.com" required />
            <div class="flex justify-end gap-2 border-t border-[var(--line)] pt-4">
                <x-ui.button type="button" variant="ghost" data-modal-close>Cancel</x-ui.button>
                <x-ui.button type="submit" icon="mail" data-loading-text="Sending...">Send Test Email</x-ui.button>
            </div>
        </form>
        </x-ui.modal>

        <x-ui.modal id="email-templates-modal" title="Email Templates" class="w-[min(64rem,calc(100vw-2rem))]">
        <form method="POST" action="{{ route('admin.settings.bulk') }}" class="grid gap-4">
            @csrf
            <div class="max-h-[70vh] space-y-4 overflow-y-auto pr-1">
                @foreach ($emailTemplateFields as $index => $field)
                    <label class="block rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-4">
                        <span class="text-sm font-medium text-[var(--text-strong)]">{{ $field['label'] }}</span>
                        @if (($field['input'] ?? 'text') === 'textarea')
                            <textarea
                                name="settings[{{ $index }}][value]"
                                rows="5"
                                class="siwes-form-control mt-2 theme-transition placeholder:text-[var(--text-soft)]"
                            >{{ $field['value'] }}</textarea>
                        @else
                            <input
                                type="{{ $field['input'] }}"
                                name="settings[{{ $index }}][value]"
                                value="{{ $field['value'] }}"
                                class="siwes-form-control mt-2 theme-transition placeholder:text-[var(--text-soft)]"
                            >
                        @endif
                        <span class="mt-2 block text-xs leading-5 text-[var(--text-soft)]">{{ $field['description'] }}</span>
                        <input type="hidden" name="settings[{{ $index }}][group]" value="{{ $field['group'] }}">
                        <input type="hidden" name="settings[{{ $index }}][key]" value="{{ $field['key'] }}">
                        <input type="hidden" name="settings[{{ $index }}][type]" value="{{ $field['type'] }}">
                        <input type="hidden" name="settings[{{ $index }}][description]" value="{{ $field['description'] }}">
                    </label>
                @endforeach
            </div>
            <div class="flex justify-end gap-2 border-t border-[var(--line)] pt-4">
                <x-ui.button type="button" variant="ghost" data-modal-close>Cancel</x-ui.button>
                <x-ui.button type="submit">Save Email Templates</x-ui.button>
            </div>
        </form>
        </x-ui.modal>

        <x-ui.modal id="import-settings-modal" title="Import Settings" class="w-[min(58rem,calc(100vw-2rem))]">
            <form method="POST" action="{{ route('admin.settings.bulk') }}" class="grid gap-4">
                @csrf
                @foreach ($importFields as $index => $field)
                    <div class="rounded-lg border border-[var(--line)] bg-[var(--surface-muted)] p-4">
                        <input type="hidden" name="settings[{{ $index }}][group]" value="{{ $field['group'] }}">
                        <input type="hidden" name="settings[{{ $index }}][key]" value="{{ $field['key'] }}">
                        <input type="hidden" name="settings[{{ $index }}][type]" value="{{ $field['type'] }}">
                        <label class="block">
                            <span class="text-sm font-medium text-[var(--text-strong)]">{{ $field['label'] }}</span>
                            <input name="settings[{{ $index }}][value]" type="{{ $field['input'] }}" value="{{ $field['value'] }}" class="siwes-form-control mt-2" @if (($field['input'] ?? null) === 'number') min="1" @endif>
                        </label>
                        <p class="mt-2 text-xs text-[var(--text-soft)]">{{ $field['description'] }}</p>
                    </div>
                @endforeach
                <div class="flex justify-end gap-2 border-t border-[var(--line)] pt-4">
                    <x-ui.button type="button" variant="ghost" data-modal-close>Cancel</x-ui.button>
                    <x-ui.button type="submit">Save Import Settings</x-ui.button>
                </div>
            </form>
        </x-ui.modal>

        <x-ui.modal id="create-setting-modal" title="Create Setting" class="w-[min(42rem,calc(100vw-2rem))]">
        <form method="POST" action="{{ route('admin.settings.store') }}" class="grid gap-4">
            @csrf
            <x-ui.input label="Group" name="group" placeholder="payment" />
            <x-ui.input label="Key" name="key" placeholder="payment.provider" />
            <x-ui.input label="Value" name="value" placeholder="korapay" />
            <label class="block">
                <span class="text-sm font-medium text-[var(--text-strong)]">Type</span>
                <select name="type" class="siwes-form-control mt-2">
                    <option value="string">String</option>
                    <option value="integer">Integer</option>
                    <option value="boolean">Boolean</option>
                    <option value="decimal">Decimal</option>
                    <option value="json">JSON</option>
                </select>
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_public" value="1" class="rounded border-[var(--line)]">
                Public setting
            </label>
            <div class="flex justify-end gap-2">
                <x-ui.button type="button" variant="ghost" data-modal-close>Cancel</x-ui.button>
                <x-ui.button type="submit">Create Setting</x-ui.button>
            </div>
        </form>
        </x-ui.modal>
    @endif
</x-layouts.app-shell>

