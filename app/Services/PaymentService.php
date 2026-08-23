<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Ticket;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(private readonly TicketService $ticketService) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function markFromProviderPayload(Payment $payment, array $payload, ?string $event = null, ?string $eventId = null): Payment
    {
        return DB::transaction(function () use ($payment, $payload, $event, $eventId): Payment {
            if ($eventId && Payment::query()->where('webhook_event_id', $eventId)->whereKeyNot($payment->id)->exists()) {
                return $payment;
            }

            $providerStatus = strtolower((string) (Arr::get($payload, 'data.status') ?? Arr::get($payload, 'status')));
            $successful = in_array($providerStatus, ['success', 'successful', 'paid'], true);

            $payment->update([
                'status' => $successful ? Payment::STATUS_SUCCESSFUL : Payment::STATUS_FAILED,
                'provider_status' => $providerStatus,
                'webhook_event' => $event,
                'webhook_event_id' => $eventId,
                'payload' => $payload,
                'verified_at' => now(),
                'paid_at' => $successful ? now() : null,
            ]);

            if ($successful && $payment->ticket instanceof Ticket) {
                $this->ticketService->markPaid($payment->ticket);
                $payment->student->update(['activation_status' => 'active']);
            }

            return $payment->refresh();
        });
    }
}
