<?php

use App\Http\Middleware\EnsureOtpIsVerified;
use App\Http\Middleware\EnsureWorkshopFeeIsPaid;
use App\Http\Middleware\EnsureUserHasPermission;
use App\Http\Middleware\EnsureStudentProfileIsComplete;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\RedirectIfOtpVerified;
use App\Http\Middleware\SecurityHeaders;
use App\Support\RoleRedirector;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);

        $middleware->validateCsrfTokens(except: [
            'webhooks/korapay',
        ]);

        $middleware->redirectUsersTo(function (Request $request): string {
            $user = Auth::guard('admin')->user() ?? Auth::guard('web')->user();

            if ($user && $user->otp_enabled && $request->session()->get('otp.verified') !== true) {
                return route('otp.show');
            }

            return $user ? RoleRedirector::dashboardFor($user) : route('home');
        });

        $middleware->alias([
            'role.portal' => EnsureUserHasRole::class,
            'otp.verified' => EnsureOtpIsVerified::class,
            'student.profile.complete' => EnsureStudentProfileIsComplete::class,
            'student.workshop.paid' => EnsureWorkshopFeeIsPaid::class,
            'otp.unverified' => RedirectIfOtpVerified::class,
            'permission' => EnsureUserHasPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson() || $request->ajax(),
        );
    })->create();
