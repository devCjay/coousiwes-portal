<?php

namespace App\Services;

use App\Models\Supervisor;
use App\Models\User;
use App\Notifications\SupervisorLoginDetailsNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SupervisorManager
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Supervisor
    {
        return DB::transaction(function () use ($data): Supervisor {
            $temporaryPassword = Str::password(12);

            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $temporaryPassword,
                'status' => $data['status'] ?? Supervisor::STATUS_ACTIVE,
                'otp_enabled' => false,
                'email_verified_at' => now(),
            ]);
            $user->assignRole('supervisor');

            $supervisor = Supervisor::query()->create($this->payload($data, $user));

            $user->notify(new SupervisorLoginDetailsNotification($temporaryPassword));
            app(WhatsAppNotificationService::class)->send(
                $user->phone,
                'supervisor_login_details',
                "A supervisor account has been created for you on the COOU SIWES portal.\nEmail: {email}\nTemporary password: {temporary_password}\nLogin: {login_url}",
                [
                    'name' => $user->name,
                    'email' => $user->email,
                    'temporary_password' => $temporaryPassword,
                    'login_url' => route('login.supervisor'),
                ],
            );

            return $supervisor;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Supervisor $supervisor, array $data): Supervisor
    {
        return DB::transaction(function () use ($supervisor, $data): Supervisor {
            $user = $supervisor->user;
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'status' => $data['status'],
            ]);
            $supervisor->update($this->payload($data, $user));

            return $supervisor->refresh();
        });
    }

    public function changeStatus(Supervisor $supervisor, string $status): Supervisor
    {
        return DB::transaction(function () use ($supervisor, $status): Supervisor {
            $supervisor->update(['status' => $status]);
            $supervisor->user->update(['status' => $status]);

            return $supervisor->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data, User $user): array
    {
        return [
            'user_id' => $user->id,
            'staff_no' => $data['staff_no'] ?? $this->generateStaffNo(),
            'organization' => $data['organization'] ?? 'COOU SIWES Unit',
            'department' => $data['department'] ?? null,
            'status' => $data['status'] ?? Supervisor::STATUS_ACTIVE,
        ];
    }

    private function generateStaffNo(): string
    {
        do {
            $staffNo = 'SUP-'.now()->format('Y').'-'.Str::upper(Str::random(6));
        } while (Supervisor::query()->where('staff_no', $staffNo)->exists());

        return $staffNo;
    }
}
