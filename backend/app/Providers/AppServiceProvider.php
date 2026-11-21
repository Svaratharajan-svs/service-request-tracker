<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // General API: 60 requests per minute per user/IP.
        RateLimiter::for('api', function (Request $request) {
            $key = $request->user()
                ? 'user:' . $request->user()->id
                : 'ip:' . $request->ip();

            return Limit::perMinute(60)->by($key);
        });

        // Login: 5 attempts per minute per IP and email.
        RateLimiter::for('login', function (Request $request) {
            return [
                Limit::perMinute(5)
                    ->by('login-ip:' . $request->ip()),

                Limit::perMinute(5)
                    ->by('login-email:' . strtolower(
                        $request->input('email', '')
                    )),
            ];
        });

        // Status update: 20 requests per minute per user.
        RateLimiter::for('service-request-update', function (Request $request) {
            $key = $request->user()
                ? 'user:' . $request->user()->id
                : 'ip:' . $request->ip();

            return Limit::perMinute(20)->by($key);
        });

        // Request history: 60 requests per minute per user.
        RateLimiter::for('service-request-history', function (Request $request) {
            $key = $request->user()
                ? 'user:' . $request->user()->id
                : 'ip:' . $request->ip();

            return Limit::perMinute(60)->by($key);
        });
    }
}
