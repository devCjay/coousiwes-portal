<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\KorapayService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;

class KorapayWebhookController extends Controller
{
    public function __invoke(Request $request, KorapayService $korapayService, PaymentService $paymentService): Response
    {
        if (! $korapayService->webhookSignatureIsValid($request->getContent(), $request->header('x-korapay-signature'))) {
            return response('Invalid signature', 401);
        }

        $payload = $request->all();
        $reference = Arr::get($payload, 'data.reference') ?? Arr::get($payload, 'data.payment_reference');

        if ($reference) {
            $payment = Payment::query()->where('reference', $reference)->first();

            if ($payment) {
                $paymentService->markFromProviderPayload(
                    $payment,
                    $payload,
                    Arr::get($payload, 'event'),
                    Arr::get($payload, 'data.id') ?? Arr::get($payload, 'id'),
                );
            }
        }

        return response('OK');
    }
}
