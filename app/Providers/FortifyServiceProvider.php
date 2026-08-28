<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[\Override]
    public function register(): void
    {
        Fortify::ignoreRoutes();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        RateLimiter::for('login', function (Request $request) {
            $identifier = $request->input('matric_no') ?: $request->input(Fortify::username());
            $throttleKey = Str::transliterate(Str::lower((string) $identifier).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute(5)->by($request->session()->get('login.id')));

        RateLimiter::for('otp', fn (Request $request) => Limit::perMinute(5)->by(($request->user()?->id ?: $request->ip()).'|otp'));

        RateLimiter::for('exports', fn (Request $request) => Limit::perMinute(12)->by(($request->user()?->id ?: $request->ip()).'|exports'));

        RateLimiter::for('notifications', fn (Request $request) => Limit::perMinute(60)->by(($request->user()?->id ?: $request->ip()).'|notifications'));

        RateLimiter::for('webhooks', fn (Request $request) => Limit::perMinute(120)->by($request->ip().'|webhooks'));

        RateLimiter::for('cron', fn (Request $request) => Limit::perMinute(12)->by($request->ip().'|cron'));

        RateLimiter::for('passkeys', function (Request $request) {
            $credentialId = $request->input('credential.id');

            return Limit::perMinute(10)->by(
                ($credentialId ?: $request->session()->getId()).'|'.$request->ip()
            );
        });
    }
}
