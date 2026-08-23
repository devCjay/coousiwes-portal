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

    public function callback(Request $request, KorapayService $korapayService, PaymentService $paymentService): RedirectResponse
    {
        $reference = $request->string('reference')->toString();
        $payment = Payment::query()->where('reference', $reference)->firstOrFail();
        $payload = $korapayService->verify($reference);
        $paymentService->markFromProviderPayload($payment, $payload);

        return redirect()->route('student.payments.index')->with('status', 'Payment verification completed.');
    }
}
