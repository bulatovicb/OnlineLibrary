<?php

namespace App\Providers;

use App\Models\Image;
use App\Observers\ImageObserver;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;

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
        Image::observe(ImageObserver::class);

        RateLimiter::for('google-books-api', function ($job){
            return Limit::perMinute(5)->by('google-books-api');
        });
    }
}
