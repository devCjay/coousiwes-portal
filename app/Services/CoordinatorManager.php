<?php

namespace App\Services;

use App\Models\Department;
use App\Models\SiwesCoordinator;
use App\Models\User;
use App\Notifications\CoordinatorLoginDetailsNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CoordinatorManager
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SiwesCoordinator
    {
        return DB::transaction(function () use ($data): SiwesCoordinator {
            $temporaryPassword = Str::upper(Str::random(4)).'-'.Str::lower(Str::random(4)).'-'.random_int(1000, 9999);

            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => Hash::make($temporaryPassword),
                'status' => SiwesCoordinator::STATUS_ACTIVE,
                'otp_enabled' => false,
                'email_verified_at' => now(),
            ]);
            $user->assignRole('coordinator');

            $coordinator = SiwesCoordinator::query()->create($this->payload($data, $user));
            $user->notify(new CoordinatorLoginDetailsNotification($temporaryPassword));

            app(WhatsAppNotificationService::class)->send(
                $user->phone,
                'coordinator_login_details',
                "A SIWES coordinator account has been created for you on the COOU SIWES portal.\nEmail: {email}\nTemporary password: {temporary_password}\nLogin: {login_url}",
                [
                    'name' => $user->name,
                    'email' => $user->email,
                    'temporary_password' => $temporaryPassword,
                    'login_url' => route('login.coordinator'),
                ],
            );

            return $coordinator;
        });
    }

    public function delete(SiwesCoordinator $coordinator): void
    {
        DB::transaction(function () use ($coordinator): void {
            $user = $coordinator->user;
            $coordinator->forceDelete();
            $user?->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data, User $user): array
    {
        $department = Department::query()->with('faculty')->findOrFail($data['department_id']);

        return [
            'user_id' => $user->id,
            'faculty_id' => $department->faculty_id,
            'department_id' => $department->id,
            'staff_no' => $data['staff_no'] ?? $this->generateStaffNo(),
            'rank' => $data['rank'],
            'status' => SiwesCoordinator::STATUS_ACTIVE,
        ];
    }

    private function generateStaffNo(): string
    {
        do {
            $staffNo = 'COR-'.now()->format('Y').'-'.Str::upper(Str::random(6));
        } while (SiwesCoordinator::query()->where('staff_no', $staffNo)->exists());

        return $staffNo;
    }
}
