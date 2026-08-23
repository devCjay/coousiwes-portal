<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Student;
use App\Models\Ticket;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class KorapayService
{
    public function __construct(private readonly HttpFactory $http) {}

    public function initialize(Student $student, Ticket $ticket): Payment
    {
        if (! $ticket->isPayable()) {
            throw new \RuntimeException('Ticket is not payable.');
        }

        $reference = $this->reference();
        $payload = [
            'amount' => $ticket->amount,
            'currency' => $ticket->currency,
            'reference' => $reference,
            'redirect_url' => config('siwes.korapay.redirect_url') ?: route('student.payments.callback'),
            'customer' => [
                'name' => $student->user->name,
                'email' => $student->user->email,
            ],
            'metadata' => [
                'student_id' => $student->id,
                'ticket_id' => $ticket->id,
            ],
        ];

        $response = $this->http->withToken((string) config('siwes.korapay.secret_key'))
            ->acceptJson()
            ->post(rtrim((string) config('siwes.korapay.base_url'), '/').'/charges/initialize', $payload)
            ->throw()
            ->json();

        return Payment::query()->create([
            'student_id' => $student->id,
            'ticket_id' => $ticket->id,
            'provider' => 'korapay',
            'reference' => $reference,
            'amount' => $ticket->amount,
            'currency' => $ticket->currency,
            'status' => Payment::STATUS_PENDING,
            'checkout_url' => Arr::get($response, 'data.checkout_url') ?? Arr::get($response, 'data.payment_url'),
            'payload' => $response,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function verify(string $reference): array
    {
        return $this->http->withToken((string) config('siwes.korapay.secret_key'))
            ->acceptJson()
            ->get(rtrim((string) config('siwes.korapay.base_url'), '/').'/charges/'.$reference)
            ->throw()
            ->json();
    }

    public function webhookSignatureIsValid(string $payload, ?string $signature): bool
    {
        if (! $signature) {
            return false;
        }

        $decoded = json_decode($payload, true);
        $data = is_array($decoded) ? ($decoded['data'] ?? $decoded) : [];
        $computed = hash_hmac('sha256', json_encode($data, JSON_UNESCAPED_SLASHES), (string) config('siwes.korapay.webhook_secret'));

        return hash_equals($computed, $signature);
    }

    private function reference(): string
    {
        do {
            $reference = 'SIWES-'.now()->format('YmdHis').'-'.Str::upper(Str::random(8));
        } while (Payment::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
