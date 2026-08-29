<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\InitializePaymentRequest;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Ticket;
use App\Services\AuditLogger;
use App\Services\KorapayService;
use App\Services\PaymentService;
use App\Services\TicketService;
use App\Support\AjaxResponse;
use App\Support\PaymentSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $student = $request->user()?->student;
        abort_unless($student instanceof Student, 403);

        return view('pages.student.payments', [
            'student' => $student->load(['tickets', 'payments']),
            'tickets' => $student->tickets()->latest()->get(),
            'availableTicket' => Ticket::query()
                ->whereNull('student_id')
                ->whereIn('status', Ticket::unusedStatuses())
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->oldest()
                ->first(),
            'payments' => $student->payments()->with('ticket')->latest()->get(),
        ]);
    }

    public function workshop(Request $request): View|RedirectResponse
    {
        $student = $request->user()?->student;
        abort_unless($student instanceof Student, 403);

        if (! PaymentSettings::workshopEnabled()) {
            return redirect()->route('student.dashboard')->with('status', 'Workshop fee payment is not currently required.');
        }

        return view('pages.student.workshop-checkout', [
            'student' => $student->load('payments'),
            'amount' => PaymentSettings::workshopAmount(),
            'currency' => PaymentSettings::currency(),
            'onlinePaymentAvailable' => PaymentSettings::onlinePaymentAvailable(),
            'hasPaidWorkshop' => PaymentSettings::studentHasPaidWorkshop($student),
            'pendingPayment' => $student->payments()
                ->where('purpose', Payment::PURPOSE_WORKSHOP_FEE)
                ->where('status', Payment::STATUS_PENDING)
                ->latest()
                ->first(),
        ]);
    }

    public function initialize(InitializePaymentRequest $request, KorapayService $korapayService, AuditLogger $auditLogger, TicketService $ticketService): JsonResponse|RedirectResponse
    {
        $student = $request->user()?->student;
        abort_unless($student instanceof Student, 403);

        $ticket = Ticket::query()
            ->where(function ($query) use ($student): void {
                $query->whereNull('student_id')->orWhere('student_id', $student->id);
            })
            ->findOrFail($request->integer('ticket_id'));
        $ticket = $ticketService->assignToStudent($ticket, $student);
        $payment = $korapayService->initialize($student, $ticket);

        $auditLogger->record('payments.initialized', $request->user(), $request, $payment, [
            'ticket_id' => $ticket->id,
            'reference' => $payment->reference,
        ]);

        return AjaxResponse::success($request, 'Korapay checkout initialized.', $payment->checkout_url);
    }

    public function initializeWorkshop(Request $request, KorapayService $korapayService, AuditLogger $auditLogger): JsonResponse|RedirectResponse
    {
        $student = $request->user()?->student;
        abort_unless($student instanceof Student, 403);

        if (! PaymentSettings::workshopEnabled()) {
            return AjaxResponse::error($request, 'Workshop fee payment is not currently required.', key: 'workshop');
        }

        if (PaymentSettings::studentHasPaidWorkshop($student)) {
            return AjaxResponse::success($request, 'Workshop fee already verified.', route('student.dashboard', absolute: false));
        }

        if (! PaymentSettings::onlinePaymentAvailable()) {
            return AjaxResponse::error($request, 'Sorry online payment is currently not available.', key: 'payment');
        }

        try {
            $payment = $korapayService->initializeWorkshopFee($student);
        } catch (\RuntimeException $exception) {
            return AjaxResponse::error($request, $exception->getMessage(), key: 'workshop');
        }

        $auditLogger->record('payments.workshop_initialized', $request->user(), $request, $payment, [
            'reference' => $payment->reference,
            'amount' => $payment->amount,
        ]);

        return AjaxResponse::success($request, 'Workshop fee checkout initialized.', $payment->checkout_url);
    }

    public function callback(Request $request, KorapayService $korapayService, PaymentService $paymentService): RedirectResponse
    {
        $reference = $request->string('reference')->toString();
        $payment = Payment::query()->where('reference', $reference)->firstOrFail();
        $payload = $korapayService->verify($reference);
        $paymentService->markFromProviderPayload($payment, $payload);

        $route = $payment->isWorkshopFee() ? 'student.workshop.checkout' : 'student.payments.index';

        return redirect()->route($route)->with('status', 'Payment verification completed.');
    }
}
