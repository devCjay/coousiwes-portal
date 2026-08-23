<?php

namespace App\Services;

use App\Models\OtpChallenge;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OtpService
{
    public function createLoginChallenge(User $user, Request $request): OtpChallenge
    {
        $code = app()->environment('testing') ? '123456' : (string) random_int(100000, 999999);

        $challenge = OtpChallenge::create([
            'user_id' => $user->id,
            'code_hash' => Hash::make($code),
            'purpose' => 'login',
            'delivery_channel' => 'email',
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
            'expires_at' => now()->addMinutes((int) config('siwes.security.otp_ttl_minutes', 10)),
        ]);

        session([
            'otp.challenge_id' => $challenge->id,
            'otp.user_id' => $user->id,
            'otp.verified' => false,
        ]);

        if (app()->environment(['local', 'testing'])) {
            session(['otp.debug_code' => $code]);
        }

        return $challenge;
    }

    public function verify(OtpChallenge $challenge, string $code): bool
    {
        if ($challenge->isExpired() || $challenge->isVerified()) {
            return false;
        }

        if ($challenge->attempts >= (int) config('siwes.security.otp_max_attempts', 5)) {
            return false;
        }

        $challenge->increment('attempts');

        if (! Hash::check($code, $challenge->code_hash)) {
            return false;
        }

        $challenge->forceFill(['verified_at' => now()])->save();

        session([
            'otp.verified' => true,
            'otp.verified_at' => now()->toISOString(),
        ]);
        session()->forget('otp.debug_code');

        return true;
    }
}
