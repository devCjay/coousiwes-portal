@php
    $navigation = [
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'D'],
        ['label' => 'Generate List', 'href' => route('admin.generate-list.index'), 'icon' => 'file-text'],
        ['label' => 'Students', 'href' => route('admin.students.index'), 'icon' => 'S'],
        ['label' => 'Tickets', 'href' => route('admin.tickets.index'), 'icon' => 'T'],
        ['label' => 'Payments', 'href' => route('admin.payments.index'), 'active' => true, 'icon' => 'P'],
        ['label' => 'Reports', 'href' => route('admin.reports.index'), 'icon' => 'R'],
    ];
    $statuses = [
        '' => 'All',
        \App\Models\Payment::STATUS_PENDING => 'Pending',
        \App\Models\Payment::STATUS_SUCCESSFUL => 'Successful',
        \App\Models\Payment::STATUS_FAILED => 'Failed',
        \App\Models\Payment::STATUS_ABANDONED => 'Abandoned',
    ];
@endphp

<x-layouts.app-shell title="Payment History" role="Admin" :navigation="$navigation">
    <div class="grid gap-4 md:grid-cols-3">
        <x-ui.stat-card label="Payments" :value="number_format($paymentTotal)" meta="Korapay attempts" />
        <x-ui.stat-card label="Verified Payments" :value="number_format($verifiedTotal)" meta="Successful activations" tone="cyan" />
        <x-ui.stat-card label="Ticket Fee" :value="\App\Support\PaymentSettings::currency().' '.number_format(\App\Support\PaymentSettings::ticketAmount())" meta="Configured activation amount" tone="amber" />
    </div>

    <x-ui.card class="mt-6" title="Korapay Payment History" description="Payment references, checkout status, and verification state.">
        <form method="GET" action="{{ route('admin.payments.index') }}" data-ajax="false" class="mb-4 grid gap-3 md:grid-cols-[1fr_12rem_auto] md:items-end">
            <x-ui.input label="Search" name="search" value="{{ request('search') }}" placeholder="Search reference, student, email, or matric number" data-live-search="#payments-table tbody tr" />
            <label class="block">
                <span class="text-sm font-medium text-[var(--text-strong)]">Status</span>
                <select name="status" class="siwes-form-control mt-2">
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <x-ui.button type="submit">Apply Filters</x-ui.button>
        </form>

        <x-ui.data-table
            id="payments-table"
            :headers="['Student', 'Matric', 'Reference', 'Provider', 'Amount', 'Status', 'Verified']"
            :rows="$payments->getCollection()->map(fn ($payment) => [
                e($payment->student->user->name),
                e($payment->student->matric_no),
                e($payment->reference),
                e(ucfirst($payment->provider)),
                e($payment->currency.' '.number_format($payment->amount)),
                e(ucfirst($payment->status)),
                e($payment->verified_at?->diffForHumans() ?? 'Pending'),
            ])->all()"
        />
        <div class="mt-4">{{ $payments->links() }}</div>
    </x-ui.card>
</x-layouts.app-shell>
