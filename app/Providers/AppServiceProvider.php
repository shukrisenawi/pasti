<?php

namespace App\Providers;

use App\Listeners\SendDatabaseNotificationToFcm;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
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
        Carbon::setToStringFormat(config('app.date_format'));

        Event::listen(NotificationSent::class, SendDatabaseNotificationToFcm::class);
    }
}
