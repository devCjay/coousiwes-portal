<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class StudentManager
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Student
    {
        return DB::transaction(function () use ($data): Student {
            if (! Role::query()->where('name', 'student')->where('guard_name', 'web')->exists()) {
                throw new \RuntimeException('Student role is missing. Open System Settings and run Import / update database seeders, then clear cache.');
            }

            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'password' => $data['matric_no'],
                'status' => $data['activation_status'] === Student::STATUS_SUSPENDED ? 'suspended' : 'active',
                'otp_enabled' => false,
                'email_verified_at' => now(),
            ]);
            $user->assignRole('student');

            return Student::query()->create($this->studentPayload($data, $user));
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Student $student, array $data): Student
    {
        return DB::transaction(function () use ($student, $data): Student {
            $user = $student->user;
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'status' => $data['activation_status'] === Student::STATUS_SUSPENDED ? 'suspended' : 'active',
            ]);

            $student->update($this->studentPayload($data, $user));

            return $student->refresh();
        });
    }

    public function changeStatus(Student $student, string $status): Student
    {
        return DB::transaction(function () use ($student, $status): Student {
            $student->update(['activation_status' => $status]);
            $student->user->update(['status' => $status === Student::STATUS_SUSPENDED ? 'suspended' : 'active']);

            return $student->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function studentPayload(array $data, User $user): array
    {
        return [
            'user_id' => $user->id,
            'matric_no' => $data['matric_no'],
            'faculty_id' => $data['faculty_id'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'course_id' => $data['course_id'] ?? null,
            'academic_level_id' => $data['academic_level_id'] ?? null,
            'academic_session_id' => $data['academic_session_id'] ?? null,
            'activation_status' => $data['activation_status'] ?? Student::STATUS_INACTIVE,
            'gender' => Arr::get($data, 'gender'),
            'date_of_birth' => Arr::get($data, 'date_of_birth'),
            'address' => Arr::get($data, 'address'),
            'metadata' => Arr::get($data, 'metadata'),
        ];
    }

}
