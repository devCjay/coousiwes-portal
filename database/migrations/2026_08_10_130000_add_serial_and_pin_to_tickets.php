<?php

use App\Models\Ticket;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->string('serial_number', 32)->nullable()->unique()->after('generated_by');
            $table->text('pin')->nullable()->after('serial_number');
        });

        Ticket::query()
            ->whereNull('serial_number')
            ->orWhereNull('pin')
            ->each(function (Ticket $ticket): void {
                $ticket->forceFill([
                    'serial_number' => $this->serialNumber(),
                    'pin' => $this->pin(),
                ])->save();
            });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropUnique(['serial_number']);
            $table->dropColumn(['serial_number', 'pin']);
        });
    }

    private function serialNumber(): string
    {
        do {
            $serial = 'SIWES-'.str_pad((string) random_int(0, 999999999999), 12, '0', STR_PAD_LEFT);
        } while (DB::table('tickets')->where('serial_number', $serial)->exists());

        return $serial;
    }

    private function pin(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
};
