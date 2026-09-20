<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\OtpChallenge;
use App\Models\User;
use App\Notifications\OtpLoginNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OtpService
{
    public function createLoginChallenge(User|Admin $user, Request $request): ?OtpChallenge
    {
        $code = app()->environment('testing') ? '123456' : (string) random_int(100000, 999999);
        $ttlMinutes = (int) config('siwes.security.otp_ttl_minutes', 10);
        $expiresAt = now()->addMinutes($ttlMinutes);

        session([
            'otp.guard' => $user instanceof Admin ? 'admin' : 'web',
            'otp.verified' => false,
            'otp.expires_at' => $expiresAt->toISOString(),
        ]);

        if ($user instanceof Admin) {
            session([
                'otp.challenge_id' => null,
                'otp.admin_id' => $user->id,
                'otp.user_id' => null,
                'otp.code_hash' => Hash::make($code),
            ]);

            $this->deliverCode($user, $code, $ttlMinutes);

            return null;
        }

        $challenge = OtpChallenge::create([
            'user_id' => $user->id,
            'code_hash' => Hash::make($code),
            'purpose' => 'login',
            'delivery_channel' => 'email',
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
            'expires_at' => $expiresAt,
        ]);

        session([
            'otp.challenge_id' => $challenge->id,
            'otp.user_id' => $user->id,
        ]);

        $this->deliverCode($user, $code, $ttlMinutes);

        return $challenge;
    }

    public function expiresAt(User|Admin $user, Request $request): ?Carbon
    {
        if ($user instanceof Admin) {
            $expiresAt = $request->session()->get('otp.expires_at');

            return $expiresAt ? Carbon::parse($expiresAt) : null;
        }

        return $this->challenge($user, $request)?->expires_at;
    }

    public function verifyLoginChallenge(User|Admin $user, Request $request, string $code): bool
    {
        if ($user instanceof Admin) {
            $expiresAt = $this->expiresAt($user, $request);
            $hash = $request->session()->get('otp.code_hash');

            if (! $expiresAt || $expiresAt->isPast() || ! $hash || ! Hash::check($code, $hash)) {
                return false;
            }

            session([
                'otp.verified' => true,
                'otp.verified_at' => now()->toISOString(),
            ]);
            session()->forget(['otp.debug_code', 'otp.code_hash']);

            return true;
        }

        $challenge = $this->challenge($user, $request);

        return $challenge !== null && $this->verify($challenge, $code);
    }

    public function challenge(User $user, Request $request): ?OtpChallenge
    {
        $challengeId = $request->session()->get('otp.challenge_id');

        if (! $challengeId) {
            return null;
        }

        return OtpChallenge::query()
            ->whereKey($challengeId)
            ->where('user_id', $user->id)
            ->where('purpose', 'login')
            ->latest()
            ->first();
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

    private function deliverCode(User|Admin $user, string $code, int $ttlMinutes): void
    {
        $user->notify(new OtpLoginNotification($code, $ttlMinutes));

        if (app()->environment(['local', 'testing'])) {
            session(['otp.debug_code' => $code]);
        }
    }
}
