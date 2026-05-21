<?php

namespace App\Providers;

use App\Models\Download;
use App\Policies\DownloadPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Download::class, DownloadPolicy::class);
        RateLimiter::for('downloads-store', function (Request $request) {
            return Limit::perMinute(config('malu.rate_limit.store', 10))
                ->by($request->ip());
        });

        RateLimiter::for('downloads-read', function (Request $request) {
            return Limit::perMinute(config('malu.rate_limit.read', 60))
                ->by($request->ip());
        });
    }
}
