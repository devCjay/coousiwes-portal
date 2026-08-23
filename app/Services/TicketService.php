<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TicketService
{
    public function generate(?User $generatedBy = null): Ticket
    {
        return Ticket::query()->create([
            'generated_by' => $generatedBy?->id,
            'serial_number' => $this->serialNumber(),
            'pin' => $pin = $this->pin(),
            'code_hash' => Hash::make($pin),
            'amount' => (int) config('siwes.payments.ticket_amount'),
            'currency' => (string) config('siwes.payments.currency'),
            'status' => Ticket::STATUS_GENERATED,
            'expires_at' => now()->addDays((int) config('siwes.payments.ticket_valid_days')),
        ]);
    }

    public function generateFor(Student $student, ?User $generatedBy = null): Ticket
    {
        return DB::transaction(function () use ($student, $generatedBy): Ticket {
            $student->tickets()
                ->whereIn('status', Ticket::payableStatuses())
                ->whereNull('paid_at')
                ->update(['status' => Ticket::STATUS_UNUSED]);

            $ticket = new Ticket([
                'generated_by' => $generatedBy?->id,
                'serial_number' => $this->serialNumber(),
                'pin' => $pin = $this->pin(),
                'code_hash' => Hash::make($pin),
                'amount' => (int) config('siwes.payments.ticket_amount'),
                'currency' => (string) config('siwes.payments.currency'),
                'status' => Ticket::STATUS_UNUSED,
                'assigned_at' => now(),
                'expires_at' => now()->addDays((int) config('siwes.payments.ticket_valid_days')),
            ]);
            $student->tickets()->save($ticket);

            return $ticket;
        });
    }

    public function assignToStudent(Ticket $ticket, Student $student): Ticket
    {
        return DB::transaction(function () use ($ticket, $student): Ticket {
            $ticket = Ticket::query()->lockForUpdate()->findOrFail($ticket->id);

            if (in_array($ticket->status, Ticket::usedStatuses(), true) || $ticket->used_at !== null) {
                throw new \RuntimeException('Ticket has already been used.');
            }

            if ($ticket->student_id !== null && (int) $ticket->student_id !== (int) $student->id) {
                throw new \RuntimeException('Ticket has already been assigned to another student.');
            }

            if ($ticket->student_id === null) {
                $ticket->update([
                    'student_id' => $student->id,
                    'status' => Ticket::STATUS_UNUSED,
                    'assigned_at' => now(),
                ]);
            }

            return $ticket->refresh();
        });
    }

    public function generateMany(int $quantity, ?User $generatedBy = null): int
    {
        for ($count = 0; $count < $quantity; $count++) {
            $this->generate($generatedBy);
        }

        return $quantity;
    }

    public function markPaid(Ticket $ticket): Ticket
    {
        $ticket->update([
            'status' => Ticket::STATUS_USED,
            'paid_at' => now(),
            'used_at' => now(),
        ]);

        return $ticket->refresh();
    }

    public function markUsedByStudent(Ticket $ticket, Student $student): Ticket
    {
        return DB::transaction(function () use ($ticket, $student): Ticket {
            $ticket = Ticket::query()->lockForUpdate()->findOrFail($ticket->id);

            if (in_array($ticket->status, Ticket::usedStatuses(), true) || $ticket->used_at !== null) {
                if ((int) $ticket->student_id === (int) $student->id) {
                    return $ticket->refresh();
                }

                throw new \RuntimeException('Ticket has already been used.');
            }

            if ($ticket->student_id !== null && (int) $ticket->student_id !== (int) $student->id) {
                throw new \RuntimeException('Ticket has already been assigned to another student.');
            }

            $ticket->update([
                'student_id' => $student->id,
                'status' => Ticket::STATUS_USED,
                'used_at' => now(),
                'metadata' => array_merge($ticket->metadata ?? [], [
                    'placement_used_at' => now()->toISOString(),
                ]),
            ]);

            return $ticket->refresh();
        });
    }

    private function serialNumber(): string
    {
        do {
            $serial = 'SIWES-'.str_pad((string) random_int(0, 999999999999), 12, '0', STR_PAD_LEFT);
        } while (Ticket::query()->where('serial_number', $serial)->exists());

        return $serial;
    }

    private function pin(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
