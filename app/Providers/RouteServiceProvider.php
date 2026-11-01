<?php

namespace App\Providers;

use App\Support\IndonesianPhone;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Every code costs a WhatsApp message, and a flood at one number is
        // harassment as much as cost: cap by number and by caller.
        RateLimiter::for('otp-request', function (Request $request) {
            $phone = IndonesianPhone::normalize((string) $request->input('phone')) ?? 'invalid';

            return [
                Limit::perHour(5)->by('otp-phone:' . $phone),
                Limit::perHour(20)->by('otp-ip:' . $request->ip()),
            ];
        });

        // The per-code attempt limit stops guessing one code; this stops
        // guessing across many numbers from one place.
        RateLimiter::for('otp-verify', function (Request $request) {
            return Limit::perMinute(10)->by('otp-verify:' . $request->ip());
        });
    }
}
